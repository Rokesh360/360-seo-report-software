<?php

namespace Tests\Feature;

use App\Models\SocialMediaReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SocialMediaReportAssignmentScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('assign_services', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        Schema::create('social_media_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assign_service_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('social_media_reports');
        Schema::dropIfExists('assign_services');

        parent::tearDown();
    }

    public function test_it_matches_reports_by_the_exact_assignment_foreign_key(): void
    {
        DB::table('assign_services')->insert([
            ['id' => 2, 'deleted_at' => null],
            ['id' => 12, 'deleted_at' => null],
        ]);
        $matchedReport = SocialMediaReport::create(['assign_service_id' => 2]);
        SocialMediaReport::create(['assign_service_id' => 12]);

        $matchedIds = SocialMediaReport::forAssignService(2)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [$matchedReport->id],
            $matchedIds
        );

        $this->assertSame(2, $matchedReport->assignService->id);
    }
}
