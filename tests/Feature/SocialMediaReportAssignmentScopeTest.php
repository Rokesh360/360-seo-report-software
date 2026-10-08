<?php

namespace Tests\Feature;

use App\Models\SocialMediaReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SocialMediaReportAssignmentScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('social_media_reports', function (Blueprint $table) {
            $table->id();
            $table->json('assign_service_ids')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('social_media_reports');

        parent::tearDown();
    }

    public function test_it_matches_numeric_and_string_assignment_ids_without_partial_matches(): void
    {
        $numericIdReport = SocialMediaReport::create([
            'assign_service_ids' => [2],
        ]);
        $stringIdReport = SocialMediaReport::create([
            'assign_service_ids' => ['2'],
        ]);
        SocialMediaReport::create([
            'assign_service_ids' => [12],
        ]);

        $matchedIds = SocialMediaReport::forAssignService(2)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [$numericIdReport->id, $stringIdReport->id],
            $matchedIds
        );
    }
}
