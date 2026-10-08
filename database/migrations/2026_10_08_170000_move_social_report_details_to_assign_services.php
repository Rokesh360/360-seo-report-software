<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $detailColumns = [
        'platforms',
        'campaign_objective',
        'campaign_overview',
        'target_audience',
        'campaign_focus',
        'key_highlights',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('assign_services')) {
            return;
        }

        $newColumns = array_values(array_filter(
            $this->detailColumns,
            fn (string $column) => ! Schema::hasColumn('assign_services', $column)
        ));

        if ($newColumns !== []) {
            Schema::table('assign_services', function (Blueprint $table) use ($newColumns) {
                foreach ($newColumns as $column) {
                    if ($column === 'platforms' || $column === 'campaign_objective') {
                        $table->string($column)->nullable();
                    } else {
                        $table->text($column)->nullable();
                    }
                }
            });
        }

        $this->copyReportDetailsToAssignments();

        if (Schema::hasTable('social_media_reports')) {
            $reportColumns = array_values(array_filter(
                $this->detailColumns,
                fn (string $column) => Schema::hasColumn('social_media_reports', $column)
            ));

            if ($reportColumns !== []) {
                Schema::table('social_media_reports', function (Blueprint $table) use ($reportColumns) {
                    $table->dropColumn($reportColumns);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('social_media_reports')) {
            return;
        }

        $missingColumns = array_values(array_filter(
            $this->detailColumns,
            fn (string $column) => ! Schema::hasColumn('social_media_reports', $column)
        ));

        if ($missingColumns !== []) {
            Schema::table('social_media_reports', function (Blueprint $table) use ($missingColumns) {
                foreach ($missingColumns as $column) {
                    if ($column === 'platforms' || $column === 'campaign_objective') {
                        $table->string($column)->nullable();
                    } else {
                        $table->text($column)->nullable();
                    }
                }
            });
        }

        if (Schema::hasTable('assign_services') && Schema::hasColumn('social_media_reports', 'assign_service_ids')) {
            $assignmentColumns = array_values(array_filter(
                $this->detailColumns,
                fn (string $column) => Schema::hasColumn('assign_services', $column)
            ));

            if ($assignmentColumns !== []) {
                $assignments = DB::table('assign_services')
                    ->select(array_merge(['id'], $assignmentColumns))
                    ->get()
                    ->keyBy('id');

                foreach (DB::table('social_media_reports')->select(['id', 'assign_service_ids'])->get() as $report) {
                    $ids = $this->decodeAssignmentIds($report->assign_service_ids);
                    $assignment = $assignments->get((int) ($ids[0] ?? 0));

                    if (! $assignment) {
                        continue;
                    }

                    $values = [];
                    foreach ($assignmentColumns as $column) {
                        $values[$column] = $assignment->{$column};
                    }

                    DB::table('social_media_reports')
                        ->where('id', $report->id)
                        ->update($values);
                }
            }
        }

        if (Schema::hasTable('assign_services')) {
            $assignmentColumns = array_values(array_filter(
                $this->detailColumns,
                fn (string $column) => Schema::hasColumn('assign_services', $column)
            ));

            if ($assignmentColumns !== []) {
                Schema::table('assign_services', function (Blueprint $table) use ($assignmentColumns) {
                    $table->dropColumn($assignmentColumns);
                });
            }
        }
    }

    private function copyReportDetailsToAssignments(): void
    {
        if (! Schema::hasTable('social_media_reports')) {
            return;
        }

        $reportColumns = Schema::getColumnListing('social_media_reports');
        $assignmentColumn = in_array('assign_service_ids', $reportColumns, true)
            ? 'assign_service_ids'
            : (in_array('assign_service_id', $reportColumns, true) ? 'assign_service_id' : null);
        $detailColumns = array_values(array_filter(
            $this->detailColumns,
            fn (string $column) => in_array($column, $reportColumns, true)
        ));

        if ($assignmentColumn === null || $detailColumns === []) {
            return;
        }

        $selectColumns = array_merge(['id', $assignmentColumn], $detailColumns);
        if (in_array('created_at', $reportColumns, true)) {
            $selectColumns[] = 'created_at';
        }
        if (in_array('deleted_at', $reportColumns, true)) {
            $selectColumns[] = 'deleted_at';
        }

        $reports = DB::table('social_media_reports')
            ->select(array_unique($selectColumns))
            ->when(
                in_array('deleted_at', $reportColumns, true),
                fn ($query) => $query->whereNull('deleted_at')
            )
            ->orderByDesc(in_array('created_at', $reportColumns, true) ? 'created_at' : 'id')
            ->orderByDesc('id')
            ->get();

        foreach ($reports as $report) {
            $ids = $this->decodeAssignmentIds($report->{$assignmentColumn});
            $values = [];

            foreach ($detailColumns as $column) {
                $value = $report->{$column};
                if ($value !== null && trim((string) $value) !== '') {
                    $values[$column] = $value;
                }
            }

            if ($ids === [] || $values === []) {
                continue;
            }

            foreach ($ids as $id) {
                foreach ($values as $column => $value) {
                    DB::table('assign_services')
                        ->where('id', $id)
                        ->where(function ($query) use ($column) {
                            $query->whereNull($column)->orWhere($column, '');
                        })
                        ->update([$column => $value]);
                }
            }
        }
    }

    private function decodeAssignmentIds(mixed $rawIds): array
    {
        if (is_string($rawIds)) {
            $decoded = json_decode($rawIds, true);
            $rawIds = json_last_error() === JSON_ERROR_NONE ? $decoded : $rawIds;
        }

        $ids = is_array($rawIds) ? $rawIds : [$rawIds];

        return collect($ids)
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
};
