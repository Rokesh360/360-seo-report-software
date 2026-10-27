<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use App\Models\MetaAdsDailyStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class MetaCatalogService
{

    private const TOKEN_FILE = 'meta_token.enc';

    /** Token saved by the Connect flow wins over the .env token. */
    public function token(): string
    {
        if (Storage::disk('local')->exists(self::TOKEN_FILE)) {
            try {
                return trim(Crypt::decryptString(Storage::disk('local')->get(self::TOKEN_FILE)));
            } catch (\Throwable) {
                // fall through to .env
            }
        }

        $token = config('services.meta.access_token');

        $version = config('services.meta.graph_api_version');
        $accessToken = config('services.meta.access_token');

        if (! is_string($version) || ! preg_match('/^v\d+(?:\.\d+)?$/D', $version)) {
            throw new InvalidArgumentException('Invalid Meta Graph API version. Configure META_GRAPH_API_VERSION.');
        }

        if (! str_contains($version, '.')) {
            $version .= '.0';
        }

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw new InvalidArgumentException('Configure META_ACCESS_TOKEN in your .env file.');
        }

        return trim($token);
    }

    private function graphVersion(): string
    {
        $version = config('services.meta.graph_api_version');

        if (! is_string($version) || ! preg_match('/^v\d+(?:\.\d+)?$/D', $version)) {
            throw new InvalidArgumentException('Invalid Meta Graph API version.');
        }

        return str_contains($version, '.') ? $version : $version . '.0';
    }

    /** The login URL. "scope" is where the permissions are requested. */
    public function loginUrl(string $state): string
    {
        return 'https://www.facebook.com/' . $this->graphVersion() . '/dialog/oauth?' . http_build_query([
            'client_id'     => config('services.meta.app_id'),
            'redirect_uri'  => config('services.meta.redirect_uri'),
            'scope'         => implode(',', config('services.meta.scopes')),
            'response_type' => 'code',
            'state'         => $state,
            'auth_type'     => 'rerequest', // re-prompt if a permission was previously declined
        ]);
    }

    /** Exchange the ?code for a token, upgrade it to long-lived (~60 days), save it encrypted. */
    public function exchangeCode(string $code): void
    {
        $base = 'https://graph.facebook.com/' . $this->graphVersion() . '/oauth/access_token';

        $short = Http::acceptJson()->timeout(15)->get($base, [
            'client_id'     => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri'  => config('services.meta.redirect_uri'),
            'code'          => $code,
        ]);

        if (! $short->successful() || ! $short->json('access_token')) {
            throw new RuntimeException(
                $short->json('error.message') ?? 'Could not exchange the login code.'
            );
        }

        $long = Http::acceptJson()->timeout(15)->get($base, [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.meta.app_id'),
            'client_secret'     => config('services.meta.app_secret'),
            'fb_exchange_token' => $short->json('access_token'),
        ]);

        $token = $long->successful() && $long->json('access_token')
            ? $long->json('access_token')
            : $short->json('access_token');

        Storage::disk('local')->put(self::TOKEN_FILE, Crypt::encryptString($token));
    }

    public const RANGES = [
        'today'      => 'Today',
        'yesterday'  => 'Yesterday',
        'last_7d'    => 'Last 7 days',
        'last_14d'   => 'Last 14 days',
        'last_30d'   => 'Last 30 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'custom'     => 'Custom range',
    ];

    /* ------------------------------------------------------------
     |  Config helpers
     ------------------------------------------------------------ */

    public function businessId(): string
    {
        $id = config('services.meta.business_id');

        if (! is_string($id) || ! ctype_digit(trim($id))) {
            throw new InvalidArgumentException(
                'Configure a valid numeric META_BUSINESS_ID in your .env file.'
            );
        }

        return trim($id);
    }

    public function adAccountId(): string
    {
        $id = config('services.meta.ad_account_id');

        if (! is_string($id) || ! ctype_digit(trim($id))) {
            throw new InvalidArgumentException(
                'Configure a valid numeric META_AD_ACCOUNT_ID in your .env file.'
            );
        }

        return 'act_' . trim($id);
    }

    /* ------------------------------------------------------------
     |  Low-level HTTP
     ------------------------------------------------------------ */

    /**
     * Call any Graph API path and return the full raw response.
     * Does not throw on 4xx/5xx, so Meta's error JSON is preserved.
     *
     * @return array{endpoint: string, status: int, body: mixed}
     *
     * @throws ConnectionException
     */
    public function rawGet(string $path, array $query = []): array
    {
        $version = config('services.meta.graph_api_version');
        $accessToken = config('services.meta.access_token');

        if (! is_string($version) || ! preg_match('/^v\d+(?:\.\d+)?$/D', $version)) {
            throw new InvalidArgumentException(
                'Invalid Meta Graph API version. Configure META_GRAPH_API_VERSION.'
            );
        }

        if (! str_contains($version, '.')) {
            $version .= '.0';
        }

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw new InvalidArgumentException(
                'Configure META_ACCESS_TOKEN in your .env file.'
            );
        }

        $version = $this->graphVersion();
        $accessToken = $this->token();
        $path = ltrim($path, '/');

        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get("https://graph.facebook.com/{$version}/{$path}", $query);

        // Decode, then redact the token in case Meta echoes it anywhere.
        $body = $response->json() ?? $response->body();
        $body = json_decode(
            str_replace($accessToken, '[redacted]', json_encode($body)),
            true
        );

        return [
            'endpoint' => "/{$version}/{$path}",
            'status'   => $response->status(),
            'body'     => $body,
        ];
    }

    /**
     * Like rawGet, but follows cursor pagination (up to $maxPages pages).
     *
     * @throws ConnectionException
     */
    public function rawGetAll(string $path, array $query = [], int $maxPages = 10): array
    {
        $merged = [];
        $last = null;

        for ($i = 0; $i < $maxPages; $i++) {
            $last = $this->rawGet($path, $query);

            if ($last['status'] >= 400 || ! is_array($last['body'])) {
                return $last;
            }

            $merged = array_merge($merged, $last['body']['data'] ?? []);

            $after = $last['body']['paging']['cursors']['after'] ?? null;

            if (! $after || empty($last['body']['paging']['next'])) {
                break;
            }

            $query['after'] = $after;
        }

        $last['body'] = ['data' => $merged];

        return $last;
    }

    /* ------------------------------------------------------------
     |  Business / catalog calls
     ------------------------------------------------------------ */

    public function permissions(): array
    {
        return $this->rawGet('me/permissions');
    }

    public function businessInfo(): array
    {
        return $this->rawGet(
            $this->businessId(),
            ['fields' => 'id,name,verification_status']
        );
    }

    public function ownedCatalogs(): array
    {
        return $this->rawGet(
            $this->businessId() . '/owned_product_catalogs',
            ['fields' => 'id,name']
        );
    }

    public function clientCatalogs(): array
    {
        return $this->rawGet(
            $this->businessId() . '/client_product_catalogs',
            ['fields' => 'id,name']
        );
    }

    /* ------------------------------------------------------------
     |  Dashboard
     ------------------------------------------------------------ */

    /** Build the date part of an insights request. */
    public function timeParams(array $filters): array
    {
        if (($filters['range'] ?? '') === 'custom'
            && ! empty($filters['since'])
            && ! empty($filters['until'])
        ) {
            return ['time_range' => json_encode([
                'since' => $filters['since'],
                'until' => $filters['until'],
            ])];
        }

        $range = $filters['range'] ?? 'last_30d';

        return ['date_preset' => ($range !== 'custom' && array_key_exists($range, self::RANGES))
            ? $range
            : 'last_30d'];
    }

    /** Everything the page needs, each section isolated from the others. */
    public function dashboard(array $filters): array
    {
        $time = $this->timeParams($filters);
        $account = fn() => $this->adAccountId();

        return [
            'permissions' => $this->section(fn() => $this->permissions()),
            'business'    => $this->section(fn() => $this->businessInfo()),
            'owned'       => $this->section(fn() => $this->ownedCatalogs()),
            'client'      => $this->section(fn() => $this->clientCatalogs()),

            'summary' => $this->section(fn() => $this->rawGet(
                $account() . '/insights',
                ['fields' => 'spend,impressions,clicks,reach,ctr,cpc,account_currency'] + $time
            )),

            // -----------------------------------------------------------
            // Campaigns — full results, one row per campaign
            // -----------------------------------------------------------
            'campaigns' => $this->section(fn() => $this->rawGetAll(
                $account() . '/insights',
                [
                    'fields' => implode(',', [
                        'campaign_id',
                        'campaign_name',
                        'spend',
                        'impressions',
                        'clicks',
                        'reach',
                        'ctr',
                        'cpc',
                        'cpm',
                        'actions',           // conversions (purchase, lead, etc.)
                        'account_currency',
                        'date_start',
                        'date_stop',
                    ]),
                    'level'  => 'campaign',
                    'limit'  => 200,
                ] + $time
            )),

            'daily' => $this->section(fn() => $this->rawGetAll(
                $account() . '/insights',
                [
                    'fields'         => 'spend,impressions,clicks,account_currency',
                    'time_increment' => 1,
                    'limit'          => 100,
                ] + $time
            )),
        ];
    }

    public function storeToday(): int
    {
        $today = now()->toDateString();
        $data = $this->dashboard([
            'range' => 'custom',
            'since' => $today,
            'until' => $today,
        ]);

        if (! ($data['campaigns']['ok'] ?? false)) {
            throw new RuntimeException(
                $data['campaigns']['error'] ?? 'Could not fetch campaign data from Meta.'
            );
        }

        $campaignRows = $data['campaigns']['data']['data'] ?? $data['campaigns']['data'] ?? [];
        $campaigns = collect($campaignRows);

        if ($campaigns->isEmpty()) {
            throw new RuntimeException("No campaign data found for {$today}.");
        }

        $hasInvalidCampaign = $campaigns->contains(
            fn($campaign) => ! is_array($campaign)
                || empty($campaign['campaign_id'])
                || ! isset($campaign['spend'])
                || ! is_numeric($campaign['spend'])
        );

        if ($hasInvalidCampaign) {
            throw new RuntimeException(
                'Meta returned incomplete campaign data. No data was saved; please try again.'
            );
        }

        if ($campaigns->pluck('campaign_id')->unique()->count() !== $campaigns->count()) {
            throw new RuntimeException(
                'Meta returned duplicate campaign rows. No data was saved; please try again.'
            );
        }

        $action = function (array $campaign, array $types): int {
            $actions = collect($campaign['actions'] ?? [])->keyBy('action_type');
            foreach ($types as $type) {
                if ($actions->has($type)) {
                    return (int) $actions[$type]['value'];
                }
            }

            return 0;
        };

        DB::transaction(function () use ($campaigns, $today, $action): void {
            MetaAdsDailyStat::whereDate('report_date', $today)->delete();

            foreach ($campaigns as $campaign) {
                MetaAdsDailyStat::create([
                    'report_date'             => $today,
                    'campaign_id'             => $campaign['campaign_id'],
                    'campaign_name'           => $campaign['campaign_name'] ?? null,
                    'spend'                   => $campaign['spend'],
                    'impressions'             => (int) ($campaign['impressions'] ?? 0),
                    'clicks'                  => (int) ($campaign['clicks'] ?? 0),
                    'reach'                   => (int) ($campaign['reach'] ?? 0),
                    'ctr'                     => $campaign['ctr'] ?? 0,
                    'cpc'                     => $campaign['cpc'] ?? 0,
                    'cpm'                     => $campaign['cpm'] ?? 0,
                    'leads'                   => $action($campaign, ['lead', 'onsite_conversion.lead_grouped']),
                    'messaging_conversations' => $action($campaign, ['onsite_conversion.messaging_conversation_started_7d']),
                    'currency'                => $campaign['account_currency'] ?? null,
                    'actions'                 => $campaign['actions'] ?? [],
                ]);
            }
        });

        return $campaigns->count();
    }

    /** Run one request and normalise success/error so the view stays simple. */
    private function section(callable $fn): array
    {
        try {
            $raw = $fn();
        } catch (ConnectionException) {
            return [
                'ok'    => false,
                'error' => 'Could not connect to the Meta Graph API.',
                'data'  => [],
                'raw'   => null,
            ];
        } catch (InvalidArgumentException | RuntimeException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
                'data'  => [],
                'raw'   => null,
            ];
        }

        $ok = $raw['status'] >= 200 && $raw['status'] < 300;
        $body = is_array($raw['body']) ? $raw['body'] : [];

        $error = null;

        if (! $ok) {
            $error = ($body['error']['message'] ?? 'Request failed')
                . " (HTTP {$raw['status']}"
                . (isset($body['error']['code']) ? ", Meta error {$body['error']['code']}" : '')
                . ')';
        }

        return [
            'ok'    => $ok,
            'error' => $error,
            // Failed requests always have empty data.
            'data'  => $ok ? ($body['data'] ?? $body) : [],
            'raw'   => $raw,
        ];
    }
}
