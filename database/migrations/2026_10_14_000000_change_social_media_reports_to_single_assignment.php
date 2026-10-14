<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('social_media_reports', 'assign_service_id')) {
            Schema::table('social_media_reports', function (Blueprint $table) {
                $table->foreignId('assign_service_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('assign_services')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('social_media_reports', 'assign_service_ids')) {
            return;
        }

        DB::table('social_media_reports')
            ->select(['id', 'assign_service_ids'])
            ->orderBy('id')
            ->chunkById(500, function ($reports) {
                foreach ($reports as $report) {
                    foreach ($this->decodeAssignmentIds($report->assign_service_ids) as $assignmentId) {
                        if (! DB::table('assign_services')->where('id', $assignmentId)->exists()) {
                            continue;
                        }

                        DB::table('social_media_reports')
                            ->where('id', $report->id)
                            ->update(['assign_service_id' => $assignmentId]);

                        break;
                    }
                }
            });

        Schema::table('social_media_reports', function (Blueprint $table) {
            $table->dropColumn('assign_service_ids');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('social_media_reports', 'assign_service_id')) {
            return;
        }

        Schema::table('social_media_reports', function (Blueprint $table) {
            $table->dropForeign(['assign_service_id']);
            $table->json('assign_service_ids')->nullable();
        });

        DB::table('social_media_reports')
            ->select(['id', 'assign_service_id'])
            ->orderBy('id')
            ->chunkById(500, function ($reports) {
                foreach ($reports as $report) {
                    if ($report->assign_service_id === null) {
                        continue;
                    }

                    DB::table('social_media_reports')
                        ->where('id', $report->id)
                        ->update(['assign_service_ids' => json_encode([(int) $report->assign_service_id])]);
                }
            });

        Schema::table('social_media_reports', function (Blueprint $table) {
            $table->dropColumn('assign_service_id');
        });
    }

    private function decodeAssignmentIds(mixed $rawIds): array
    {
        if (is_string($rawIds)) {
            $decoded = json_decode($rawIds, true);
            $rawIds = json_last_error() === JSON_ERROR_NONE ? $decoded : $rawIds;
        }

        $ids = is_array($rawIds) ? $rawIds : [$rawIds];

        return collect($ids)
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
};
