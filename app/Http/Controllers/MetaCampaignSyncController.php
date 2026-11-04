<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCampaignSyncController extends Controller
{
    public function syncCampaigns()
    {
        $accessToken = config('services.meta.access_token');
        $accountId = config('services.meta.ad_account_id');
        $version = config('services.meta.api_version');

        $customerUrl = config('services.customer_campaign.url');
        $customerApiKey = config('services.customer_campaign.api_key');

        if (!$accessToken || !$accountId || !$customerUrl || !$customerApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Required API configuration is missing.',
            ], 500);
        }

        // Support both raw account IDs and act_ prefixed IDs.
        $accountId = 'act_' . preg_replace('/^act_/', '', $accountId);

        $metaUrl = "https://graph.facebook.com/{$version}/{$accountId}/campaigns";

        $params = [
            'fields' => 'id,name,status,effective_status,objective',
            'limit' => 100,
        ];

        $synced = [];
        $failed = [];

        try {
            do {
                // 1. Fetch campaigns from Meta
                $metaResponse = Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(60)
                    ->get($metaUrl, $params);

                if (!$metaResponse->successful()) {
                    Log::error('Meta Campaign API Error', [
                        'status' => $metaResponse->status(),
                        'response' => $metaResponse->json(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to fetch campaigns from Meta.',
                        'meta_response' => $metaResponse->json(),
                    ], 502);
                }

                $campaigns = $metaResponse->json('data', []);

                // 2. Send each campaign to customer API
                foreach ($campaigns as $campaign) {

                    $payload = [
                        'campaign_id' => (string) $campaign['id'],
                        'campaign_name' => $campaign['name'],
                        'campaign_status' => $campaign['status'],
                        'platform' => 'META',
                    ];

                    $customerResponse = Http::withHeaders([
                        'X-API-KEY' => $customerApiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                        ->timeout(30)
                        ->post($customerUrl, $payload);

                    if ($customerResponse->successful()) {

                        $synced[] = [
                            'campaign_id' => $campaign['id'],
                            'campaign_name' => $campaign['name'],
                            'customer_status' => $customerResponse->status(),
                            'customer_response' => $customerResponse->json(),
                        ];
                    } else {

                        $failed[] = [
                            'campaign_id' => $campaign['id'],
                            'campaign_name' => $campaign['name'],
                            'customer_status' => $customerResponse->status(),
                            'customer_response' => $customerResponse->json(),
                        ];

                        Log::error('Customer Campaign API Error', [
                            'campaign_id' => $campaign['id'],
                            'status' => $customerResponse->status(),
                            'response' => $customerResponse->body(),
                        ]);
                    }
                }

                // 3. Handle Meta pagination
                $after = $metaResponse->json('paging.cursors.after');

                $hasNextPage = !empty($metaResponse->json('paging.next'));

                $params['after'] = $after;
            } while ($hasNextPage && !empty($after));

            // 4. Return final response
            return response()->json([
                'success' => count($failed) === 0,
                'message' => 'Meta campaign synchronization completed.',
                'total_campaigns' => count($synced) + count($failed),
                'successful' => count($synced),
                'failed' => count($failed),
                'synced_campaigns' => $synced,
                'failed_campaigns' => $failed,
            ], count($failed) > 0 ? 207 : 200);
        } catch (\Throwable $e) {

            Log::error('Meta Campaign Sync Exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Campaign synchronization failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function sendSelectedTo360Leads(Request $request)
    {
        // 1. Validate selected campaign IDs
        $validated = $request->validate([
            'campaign_ids' => ['required', 'array', 'min:1', 'max:100'],
            'campaign_ids.*' => [
                'required',
                'string',
                'regex:/^\d+$/',
                'distinct',
            ],
        ]);

        // 2. Get Meta API configuration
        $accessToken = config('services.meta.access_token');
        $version = config('services.meta.api_version');

        // 3. Get all customer API configurations
        $customerApis = config('services.customer_campaign', []);

        // Keep only APIs with valid URL and API key
        $customerApis = array_values(array_filter(
            $customerApis,
            function ($api) {
                return is_array($api)
                    && !empty($api['url'])
                    && !empty($api['api_key']);
            }
        ));

        // 4. Validate API configurations
        if (empty($accessToken) || empty($version) || empty($customerApis)) {

            Log::error('Missing API configuration for Meta or Customer Campaign API', [
                'meta_access_token' => !empty($accessToken) ? 'present' : 'missing',
                'meta_version' => !empty($version) ? 'present' : 'missing',
                'customer_apis_configured' => count($customerApis),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Required API configuration is missing. Check Meta and Customer Campaign API settings.',
            ], 500);
        }

        $sent = [];
        $failed = [];

        try {

            // 5. Process each selected campaign
            foreach ($validated['campaign_ids'] as $campaignId) {

                // Fetch latest campaign details from Meta
                $metaResponse = Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(30)
                    ->get(
                        "https://graph.facebook.com/{$version}/{$campaignId}",
                        [
                            'fields' => 'id,name,status',
                        ]
                    );

                // Check Meta API response
                if (!$metaResponse->successful()) {

                    Log::error('Meta Campaign Fetch Error', [
                        'campaign_id' => $campaignId,
                        'http_status' => $metaResponse->status(),
                        'response' => $metaResponse->body(),
                    ]);

                    $failed[] = [
                        'campaign_id' => $campaignId,
                        'message' => 'Unable to retrieve campaign from Meta.',
                        'meta_response' => $metaResponse->json() ?? $metaResponse->body(),
                    ];

                    continue;
                }

                $campaign = $metaResponse->json();

                // Validate campaign response
                if (
                    empty($campaign['id']) ||
                    !isset($campaign['name']) ||
                    !isset($campaign['status'])
                ) {
                    $failed[] = [
                        'campaign_id' => $campaignId,
                        'message' => 'Invalid campaign data received from Meta.',
                    ];

                    continue;
                }

                // 6. Prepare campaign payload
                $payload = [
                    'campaign_id' => (string) $campaign['id'],
                    'campaign_name' => $campaign['name'],
                    'campaign_status' => $campaign['status'],
                    'platform' => 'META',
                ];

                $apiResults = [];

                // 7. Send campaign to every configured customer API
                foreach ($customerApis as $api) {

                    $apiName = $api['name'] ?? 'Customer API';

                    try {

                        $customerResponse = Http::withHeaders([
                            'X-API-KEY' => $api['api_key'],
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])
                            ->timeout(30)
                            ->post($api['url'], $payload);

                        $responseData = $customerResponse->json();

                        $apiResults[] = [
                            'api_name' => $apiName,
                            'success' => $customerResponse->successful(),
                            'http_status' => $customerResponse->status(),
                            'response' => $responseData ?? $customerResponse->body(),
                        ];

                        if (!$customerResponse->successful()) {

                            Log::error('Customer Campaign API Error', [
                                'api_name' => $apiName,
                                'campaign_id' => $campaign['id'],
                                'http_status' => $customerResponse->status(),
                                'response' => $customerResponse->body(),
                            ]);
                        }
                    } catch (\Throwable $e) {

                        Log::error('Customer Campaign API Exception', [
                            'api_name' => $apiName,
                            'campaign_id' => $campaign['id'],
                            'message' => $e->getMessage(),
                        ]);

                        $apiResults[] = [
                            'api_name' => $apiName,
                            'success' => false,
                            'message' => 'Unable to connect to customer API.',
                        ];
                    }
                }

                // 8. Check whether all APIs accepted the campaign
                $allApisSuccessful = count($apiResults) === count($customerApis)
                    && count(array_filter(
                        $apiResults,
                        fn($result) => $result['success'] === true
                    )) === count($customerApis);

                $campaignResult = [
                    'campaign_id' => $campaign['id'],
                    'campaign_name' => $campaign['name'],
                    'campaign_status' => $campaign['status'],
                    'api_results' => $apiResults,
                ];

                if ($allApisSuccessful) {
                    $sent[] = $campaignResult;
                } else {
                    $failed[] = $campaignResult;
                }
            }

            // 9. Return final response
            $totalCampaigns = count($validated['campaign_ids']);
            $successfulCount = count($sent);
            $failedCount = count($failed);

            return response()->json([
                'success' => $failedCount === 0,
                'message' => $failedCount === 0
                    ? 'Selected campaigns sent to all customer APIs successfully.'
                    : 'Campaign sending completed with some failures.',
                'total_campaigns' => $totalCampaigns,
                'successful' => $successfulCount,
                'failed' => $failedCount,
                'configured_apis' => array_map(
                    fn($api) => $api['name'] ?? 'Customer API',
                    $customerApis
                ),
                'sent_campaigns' => $sent,
                'failed_campaigns' => $failed,
            ], $failedCount === 0 ? 200 : 207);
        } catch (\Throwable $e) {

            Log::error('Customer Campaign Sync Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send campaigns to customer APIs.',
            ], 500);
        }
    }
}
