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
        $validated = $request->validate([
            'campaign_ids' => ['required', 'array', 'min:1', 'max:100'],
            'campaign_ids.*' => [
                'required',
                'string',
                'regex:/^\d+$/',
                'distinct'
            ],
        ]);

        $accessToken = config('services.meta.access_token');
        $version = config('services.meta.api_version');

        $customerUrl = config('services.customer_campaign.url');
        $customerApiKey = config('services.customer_campaign.api_key');

        if (!$accessToken || !$version || !$customerUrl || !$customerApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Required API configuration is missing.',
            ], 500);
        }

        $sent = [];
        $failed = [];

        try {
            foreach ($validated['campaign_ids'] as $campaignId) {

                // 1. Get latest campaign details from Meta
                $metaResponse = Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(30)
                    ->get(
                        "https://graph.facebook.com/{$version}/{$campaignId}",
                        [
                            'fields' => 'id,name,status',
                        ]
                    );

                if (!$metaResponse->successful()) {

                    $failed[] = [
                        'campaign_id' => $campaignId,
                        'message' => 'Unable to retrieve campaign from Meta.',
                        'meta_response' => $metaResponse->json(),
                    ];

                    continue;
                }

                $campaign = $metaResponse->json();

                // 2. Prepare only required campaign fields
                $payload = [
                    'campaign_id' => (string) $campaign['id'],
                    'campaign_name' => $campaign['name'],
                    'campaign_status' => $campaign['status'],
                    'platform' => 'META',
                ];

                // 3. Send campaign to 360 Leads API
                $customerResponse = Http::withHeaders([
                    'X-API-KEY' => $customerApiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(30)
                    ->post($customerUrl, $payload);

                if ($customerResponse->successful()) {

                    $sent[] = [
                        'campaign_id' => $campaign['id'],
                        'campaign_name' => $campaign['name'],
                        'campaign_status' => $campaign['status'],
                        'http_status' => $customerResponse->status(),
                        'customer_response' => $customerResponse->json()
                            ?? $customerResponse->body(),
                    ];
                } else {

                    $failed[] = [
                        'campaign_id' => $campaign['id'],
                        'campaign_name' => $campaign['name'],
                        'campaign_status' => $campaign['status'],
                        'http_status' => $customerResponse->status(),
                        'customer_response' => $customerResponse->json()
                            ?? $customerResponse->body(),
                    ];

                    Log::error('360 Leads Campaign API Error', [
                        'campaign_id' => $campaign['id'],
                        'status' => $customerResponse->status(),
                        'response' => $customerResponse->body(),
                    ]);
                }
            }

            return response()->json([
                'success' => empty($failed),
                'message' => empty($failed)
                    ? 'Selected campaigns sent to 360 Leads successfully.'
                    : 'Campaign sending completed with some failures.',
                'total_campaigns' => count($validated['campaign_ids']),
                'successful' => count($sent),
                'failed' => count($failed),
                'sent_campaigns' => $sent,
                'failed_campaigns' => $failed,
            ], empty($failed) ? 200 : 207);
        } catch (\Throwable $e) {

            Log::error('360 Leads Campaign Sync Exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send campaigns to 360 Leads.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
