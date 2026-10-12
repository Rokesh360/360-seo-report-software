<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = [
        'key_observations',
        'performance_highlights',
        'monthly_summary',
        'next_month_plan',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('assign_services')) {
            return;
        }

        $missing = array_values(array_filter(
            $this->fields,
            fn (string $field) => ! Schema::hasColumn('assign_services', $field)
        ));

        if ($missing !== []) {
            Schema::table('assign_services', function (Blueprint $table) use ($missing) {
                foreach ($missing as $field) {
                    $table->json($field)->nullable();
                }
            });
        }

        $reportFields = array_values(array_filter(
            $this->fields,
            fn (string $field) => Schema::hasTable('social_media_reports')
                && Schema::hasColumn('social_media_reports', $field)
        ));

        if ($reportFields === []) {
            return;
        }

        $columns = Schema::getColumnListing('social_media_reports');
        $assignmentColumn = in_array('assign_service_ids', $columns, true)
            ? 'assign_service_ids'
            : (in_array('assign_service_id', $columns, true) ? 'assign_service_id' : null);

        if ($assignmentColumn !== null) {
            $reports = DB::table('social_media_reports')
                ->select(array_merge(['id', $assignmentColumn], $reportFields))
                ->orderByDesc('id')
                ->get();

            foreach ($reports as $report) {
                $assignmentIds = $this->decodeIds($report->{$assignmentColumn});
                $values = [];

                foreach ($reportFields as $field) {
                    if ($this->hasValue($report->{$field})) {
                        $values[$field] = $report->{$field};
                    }
                }

                if ($assignmentIds === [] || $values === []) {
                    continue;
                }

                foreach ($assignmentIds as $assignmentId) {
                    $assignment = DB::table('assign_services')
                        ->where('id', $assignmentId)
                        ->first($this->fields);

                    if (! $assignment) {
                        continue;
                    }

                    $updates = [];
                    foreach ($values as $field => $value) {
                        if (! $this->hasValue($assignment->{$field})) {
                            $updates[$field] = $value;
                        }
                    }

                    if ($updates !== []) {
                        DB::table('assign_services')->where('id', $assignmentId)->update($updates);
                    }
                }
            }
        }

        Schema::table('social_media_reports', function (Blueprint $table) use ($reportFields) {
            $table->dropColumn($reportFields);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('social_media_reports') || ! Schema::hasTable('assign_services')) {
            return;
        }

        $missing = array_values(array_filter(
            $this->fields,
            fn (string $field) => ! Schema::hasColumn('social_media_reports', $field)
        ));

        if ($missing !== []) {
            Schema::table('social_media_reports', function (Blueprint $table) use ($missing) {
                foreach ($missing as $field) {
                    $table->json($field)->nullable();
                }
            });
        }

        $columns = Schema::getColumnListing('social_media_reports');
        $assignmentColumn = in_array('assign_service_ids', $columns, true)
            ? 'assign_service_ids'
            : (in_array('assign_service_id', $columns, true) ? 'assign_service_id' : null);

        if ($assignmentColumn !== null) {
            foreach (DB::table('social_media_reports')->select(['id', $assignmentColumn])->get() as $report) {
                $assignmentId = $this->decodeIds($report->{$assignmentColumn})[0] ?? null;
                if (! $assignmentId) {
                    continue;
                }

                $existingFields = array_values(array_filter(
                    $this->fields,
                    fn (string $field) => Schema::hasColumn('assign_services', $field)
                ));
                $assignment = DB::table('assign_services')->where('id', $assignmentId)->first($existingFields);
                if (! $assignment) {
                    continue;
                }

                $updates = [];
                foreach ($existingFields as $field) {
                    if ($this->hasValue($assignment->{$field})) {
                        $updates[$field] = $assignment->{$field};
                    }
                }

                if ($updates !== []) {
                    DB::table('social_media_reports')->where('id', $report->id)->update($updates);
                }
            }
        }

        $existingFields = array_values(array_filter(
            $this->fields,
            fn (string $field) => Schema::hasColumn('assign_services', $field)
        ));

        if ($existingFields !== []) {
            Schema::table('assign_services', function (Blueprint $table) use ($existingFields) {
                $table->dropColumn($existingFields);
            });
        }
    }

    private function decodeIds(mixed $rawIds): array
    {
        if (is_string($rawIds)) {
            $decoded = json_decode($rawIds, true);
            $rawIds = json_last_error() === JSON_ERROR_NONE ? $decoded : $rawIds;
        }

        return collect(is_array($rawIds) ? $rawIds : [$rawIds])
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return ! empty($decoded);
            }
        }

        return ! empty($value);
    }
};