@include('admin.partials.header', [
'pageTitle' => 'Meta Catalog',
'active' => 'meta-catalog',
])

@php
use Illuminate\Support\Str;

$fmt = fn ($v, $d = 2) => number_format((float) $v, $d);

// Default filter = today
$currentRange = $filters['range'] ?? 'today';

$summary = $data['summary']['data'][0] ?? [];
$currency = $summary['account_currency']
?? ($data['campaigns']['data'][0]['account_currency'] ?? '');

$spend = (float) ($summary['spend'] ?? 0);
$impr = (float) ($summary['impressions'] ?? 0);
$clicks = (float) ($summary['clicks'] ?? 0);
$reach = (float) ($summary['reach'] ?? 0);
$ctr = (float) ($summary['ctr'] ?? 0);
$cpc = (float) ($summary['cpc'] ?? 0);

// Daily series
$daily = collect($data['daily']['data'] ?? [])
->filter(fn ($d) => is_array($d) && isset($d['date_start']))
->sortBy('date_start')->values();

$dailyLabels = $daily->map(fn ($d) => \Carbon\Carbon::parse($d['date_start'])->format('d M'))->all();
$dailySpend = $daily->map(fn ($d) => round((float) ($d['spend'] ?? 0), 2))->all();
$dailyImpr = $daily->map(fn ($d) => (float) ($d['impressions'] ?? 0))->all();
$dailyClicks = $daily->map(fn ($d) => (float) ($d['clicks'] ?? 0))->all();
$dailyCtr = $daily->map(fn ($d) => ((float) ($d['impressions'] ?? 0)) > 0
? (float) ($d['clicks'] ?? 0) / (float) $d['impressions'] * 100 : 0)->all();
$hasDailyExtra = $daily->contains(fn ($d) => isset($d['clicks']));

$spark = function (array $vals) {
$vals = array_slice(array_values($vals), -12);
if (! count($vals)) return [];
$max = max($vals) ?: 1;
return array_map(fn ($v) => max(10, (int) round($v / $max * 100)), $vals);
};

// Campaigns
$campaigns = collect($data['campaigns']['data'] ?? [])
->filter(fn ($c) => is_array($c))
->sortByDesc(fn ($c) => (float) ($c['spend'] ?? 0))->values();
$totalSpend = max(0.0001, (float) $campaigns->sum(fn ($c) => (float) ($c['spend'] ?? 0)));
$cname = fn ($c) => Str::limit($c['campaign_name'] ?? $c['campaign_id'] ?? '-', 30);
// ---- Extract "results" from Meta's actions array ----
$resultsMap = [
'lead' => 'Leads',
'offsite_conversion.fb_pixel_lead' => 'Leads',
'onsite_conversion.lead_grouped' => 'Leads',
'offsite_complete_registration_add_meta_leads' => 'Registrations',
'offsite_search_add_meta_leads' => 'Searches',
'offsite_content_view_add_meta_leads' => 'Content views',
'purchase' => 'Purchases',
'offsite_conversion.fb_pixel_purchase' => 'Purchases',
'add_to_cart' => 'Add to cart',
'initiate_checkout' => 'Checkouts',
'onsite_conversion.messaging_conversation_started_7d' => 'Messaging conversations',
'link_click' => 'Link clicks',
'post_engagement' => 'Post engagement',
'video_view' => 'Video views',
];

/** Pull the "primary" result out of an actions array. */
$primaryResult = function (array $c) use ($resultsMap) {
$actions = collect($c['actions'] ?? [])
->filter(fn ($a) => isset($a['action_type'], $a['value']))
->keyBy('action_type');

foreach ($resultsMap as $type => $label) {
if ($actions->has($type)) {
return [
'label' => $label,
'type' => $type,
'value' => (float) $actions[$type]['value'],
];
}
}
return null;
};

/** Look up a specific action value by type. */
$actionValue = function (array $c, string $type): ?float {
$a = collect($c['actions'] ?? [])->firstWhere('action_type', $type);
return $a ? (float) $a['value'] : null;
};

/** Build a compact "Results" summary string for a campaign. */
$resultsSummary = function (array $c) use ($actionValue) {
$parts = [];
foreach ([
'Leads' => ['lead', 'offsite_conversion.fb_pixel_lead', 'onsite_conversion.lead_grouped'],
'Purchases' => ['purchase', 'offsite_conversion.fb_pixel_purchase'],
'Add to cart' => ['add_to_cart'],
'Messaging conversations' => ['onsite_conversion.messaging_conversation_started_7d'],
] as $label => $types) {
$v = null;
foreach ($types as $type) {
$v = $actionValue($c, $type);
if ($v !== null) {
break;
}
}
if ($v !== null && $v > 0) {
$parts[] = $label . ': ' . number_format($v, 0);
}
}
return $parts ? implode(' · ', $parts) : '—';
};

$resultOptions = $campaigns
->map(fn ($c) => ($primaryResult($c)['label'] ?? 'No results'))
->countBy()
->sortDesc();

$palette = ['#f7921e', '#1b4b9b', '#ffc72c', '#0b2350', '#6f9be0', '#c9d3e6'];

$topFive = $campaigns->take(5);
$others = $campaigns->slice(5);
$donutLabels = $topFive->map($cname)->all();
$donutValues = $topFive->map(fn ($c) => round((float) ($c['spend'] ?? 0), 2))->all();
if ($others->isNotEmpty()) {
$donutLabels[] = 'Others';
$donutValues[] = round((float) $others->sum(fn ($c) => (float) ($c['spend'] ?? 0)), 2);
}

$barTop = $campaigns->take(8);
$barLabels = $barTop->map($cname)->all();
$barValues = $barTop->map(fn ($c) => round((float) ($c['spend'] ?? 0), 2))->all();

$ctrRows = $campaigns
->filter(fn ($c) => (float) ($c['impressions'] ?? 0) > 0)
->map(fn ($c) => [
'name' => $cname($c),
'ctr' => round((float) ($c['clicks'] ?? 0) / (float) $c['impressions'] * 100, 2),
])->sortByDesc('ctr')->take(8)->values();

// Catalogs & permissions
$catalogs = collect($data['owned']['data'] ?? [])->map(fn ($c) => $c + ['type' => 'Owned'])
->merge(collect($data['client']['data'] ?? [])->map(fn ($c) => $c + ['type' => 'Shared']))
->filter(fn ($c) => is_array($c) && isset($c['id']))->values();

$granted = collect($data['permissions']['data'] ?? [])
->where('status', 'granted')->pluck('permission')->all();
$required = ['ads_read', 'business_management', 'catalog_management'];

$sections = [
'Permissions' => 'permissions', 'Business' => 'business',
'Owned catalogs' => 'owned', 'Shared catalogs' => 'client',
'Ad spend summary' => 'summary', 'Campaigns' => 'campaigns', 'Daily spend' => 'daily',
];
@endphp

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><span class="status-dot status-dot-dark"></span>META GRAPH API</div>
            <h1>Meta ads &amp; catalog</h1>
            <p class="page-subtitle">
                Ad spend, campaigns and catalogs
                @if(isset($summary['date_start']))
                &middot; {{ $summary['date_start'] }} to {{ $summary['date_stop'] ?? '' }}
                @endif
            </p>
        </div>
    </section>

    <!-- <section class="panel" role="status" style="margin-bottom:14px;">
        <strong>Automatic daily save: {{ config('services.meta.daily_sync_time', '23:55') }}
            ({{ config('app.timezone', 'Asia/Kolkata') }})</strong>
        <p style="margin:6px 0 0;">
            At this time, today's campaign data is saved: campaign name and ID, spend, impressions,
            clicks, reach, CTR, CPC, CPM, leads, messaging conversations, currency, and action details.
            The saved data for today is replaced with the latest results.
        </p>
    </section> -->

    @if(isset($error) && $error)
    <div class="report-validation" role="alert">{{ $error }}</div>
    @endif
    @if($errors->any())
    <div class="report-validation" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Filters --}}
    <section class="panel">
        <div class="mx-toolbar" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">

            {{-- Filter form: auto-submits, no Refresh button --}}
            <form method="GET" id="filterForm" action="{{ route('meta-catalog.index') }}">
                <label>Date range
                    <select name="range" id="range">
                        @foreach($ranges as $key => $label)
                        <option value="{{ $key }}" @selected($currentRange===$key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="custom-range">From
                    <input type="date" name="since" id="since" value="{{ $filters['since'] ?? '' }}" max="{{ now()->toDateString() }}">
                </label>
                <label class="custom-range">To
                    <input type="date" name="until" id="until" value="{{ $filters['until'] ?? '' }}" max="{{ now()->toDateString() }}">
                </label>
                <noscript><button type="submit" class="button button-primary">Apply</button></noscript>
            </form>

            <form method="POST" id="aiReportForm" action="{{ route('meta-catalog.ai-report') }}">
                @csrf
                <input type="hidden" name="range" id="aiReportRange">
                <input type="hidden" name="since" id="aiReportSince">
                <input type="hidden" name="until" id="aiReportUntil">
                <button type="submit" class="button button-primary">Generate AI report PDF</button>
            </form>

            <strong>Automatic daily save at {{ \Carbon\Carbon::parse(config('services.meta.daily_sync_time', '23:55'))->format('h:i A') }}</strong>
            <!-- <p style="margin:6px 0 0;">
                At this time, today's campaign data is saved: campaign name and ID, spend, impressions,
                clicks, reach, CTR, CPC, CPM, leads, messaging conversations, currency, and action details.
                The saved data for today is replaced with the latest results.
            </p> -->

        </div>
    </section>

    @if($data)

    {{-- KPI cards --}}
    <section class="metric-grid" style="margin-top:14px;">
        <article class="metric-card">
            <div class="metric-top">
                <span>Total spend</span>
                <span class="metric-icon metric-icon-green">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </span>
            </div>
            <div class="metric-value">{{ $currency }} {{ $fmt($spend) }}</div>
            <div class="metric-foot"><span>CPC {{ $currency }} {{ $fmt($cpc) }}</span></div>
            <div class="metric-spark spark-green">
                @foreach($spark($dailySpend) as $h)<span style="height:{{ $h }}%"></span>@endforeach
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-top">
                <span>Impressions</span>
                <span class="metric-icon metric-icon-blue">
                    <svg viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>
            <div class="metric-value">{{ $fmt($impr, 0) }}</div>
            <div class="metric-foot"><span>Reach {{ $fmt($reach, 0) }}</span></div>
            <div class="metric-spark spark-blue">
                @foreach($spark($dailyImpr) as $h)<span style="height:{{ $h }}%"></span>@endforeach
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-top">
                <span>Clicks</span>
                <span class="metric-icon metric-icon-amber">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 9l11 4-5 2-2 5z" />
                        <path d="M5 3v3M3 5h3M14 3l-1 2" />
                    </svg>
                </span>
            </div>
            <div class="metric-value">{{ $fmt($clicks, 0) }}</div>
            <div class="metric-foot"><span>{{ $campaigns->count() }} campaign(s)</span></div>
            <div class="metric-spark spark-amber">
                @foreach($spark($dailyClicks) as $h)<span style="height:{{ $h }}%"></span>@endforeach
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-top">
                <span>Click-through rate</span>
                <span class="metric-icon metric-icon-coral">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 3v18h18" />
                        <path d="M7 15l4-4 3 3 5-6" />
                    </svg>
                </span>
            </div>
            <div class="metric-value">{{ $fmt($ctr) }}%</div>
            <div class="metric-foot"><span>Clicks / impressions</span></div>
            <div class="metric-spark spark-coral">
                @foreach($spark($dailyCtr) as $h)<span style="height:{{ $h }}%"></span>@endforeach
            </div>
        </article>
    </section>

    @if(! $data['summary']['ok'])
    <div class="report-validation" role="alert" style="margin-top:14px;">{{ $data['summary']['error'] }}</div>
    @endif

    {{-- Row A: daily trend + spend share --}}
    <div class="content-grid">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Daily spend trend</h2>
                    <p>Spend per day{{ $hasDailyExtra ? ' with clicks' : '' }}</p>
                </div>
                <div class="chart-legend" style="margin-top:0;">
                    <span><i class="legend-dot clicks-dot"></i>Spend</span>
                    @if($hasDailyExtra)<span><i class="legend-dot impressions-dot"></i>Clicks</span>@endif
                </div>
            </div>

            @if(! $data['daily']['ok'])
            <div class="report-validation" role="alert" style="margin-top:14px;">{{ $data['daily']['error'] }}</div>
            @elseif($daily->isEmpty())
            <div class="mx-empty">No daily data in this period.</div>
            @else
            <div class="mx-canvas"><canvas id="chartDaily"></canvas></div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Spend share</h2>
                    <p>Top campaigns by share of spend</p>
                </div>
            </div>

            @if(! $data['campaigns']['ok'])
            <div class="report-validation" role="alert" style="margin-top:14px;">{{ $data['campaigns']['error'] }}</div>
            @elseif($campaigns->isEmpty())
            <div class="mx-empty">No campaign spend in this period.</div>
            @else
            <div class="mx-donut-wrap">
                <div class="mx-donut"><canvas id="chartShare"></canvas></div>
                <div class="mx-legend">
                    @foreach($donutLabels as $i => $label)
                    <div>
                        <span><i style="background:{{ $palette[$i % count($palette)] }}"></i>{{ $label }}</span>
                        <b>{{ $fmt(($donutValues[$i] / $totalSpend) * 100, 1) }}%</b>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </section>
    </div>

    {{-- Row B: top campaigns + CTR --}}
    <div class="mx-grid-2">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Top campaigns by spend</h2>
                    <p>Highest spending first</p>
                </div>
            </div>
            @if($barTop->isEmpty())
            <div class="mx-empty">No campaign data.</div>
            @else
            <div class="mx-canvas" style="height:{{ max(180, $barTop->count() * 42 + 30) }}px;">
                <canvas id="chartTop"></canvas>
            </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Click-through rate by campaign</h2>
                    <p>Clicks divided by impressions</p>
                </div>
            </div>
            @if($ctrRows->isEmpty())
            <div class="mx-empty">No campaign data.</div>
            @else
            <div class="mx-canvas" style="height:{{ max(180, $ctrRows->count() * 42 + 30) }}px;">
                <canvas id="chartCtr"></canvas>
            </div>
            @endif
        </section>
    </div>

    {{-- Row C: catalogs + request health --}}
    <div class="mx-grid-2">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Catalogs</h2>
                    <p>Owned and shared product catalogs</p>
                </div>
            </div>

            @foreach(['owned', 'client'] as $k)
            @if(! $data[$k]['ok'])
            <div class="report-validation" role="alert" style="margin-top:14px;">{{ $data[$k]['error'] }}</div>
            @endif
            @endforeach

            @if($catalogs->isEmpty())
            <div class="mx-empty">No catalogs found. Assign the catalog to your user in Business Settings.</div>
            @else
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Catalog</th>
                            <th>ID</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($catalogs as $catalog)
                        <tr>
                            <td>
                                <span class="keyword-name">{{ $catalog['name'] ?? '-' }}</span>
                            </td>
                            <td><span class="keyword-url">{{ $catalog['id'] }}</span></td>
                            <td>{{ $catalog['type'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Connection health</h2>
                    <p>Status of each Meta request</p>
                </div>
            </div>
            <div class="health-list" style="border-top:0; padding-top:0;">
                @foreach($sections as $label => $key)
                <div>
                    <span>
                        <i class="health-mini-dot {{ $data[$key]['ok'] ? 'dot-green' : 'dot-red' }}"></i>
                        {{ $label }}
                    </span>
                    <strong>{{ $data[$key]['ok'] ? 'OK' : 'HTTP ' . ($data[$key]['raw']['status'] ?? 'error') }}</strong>
                </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Campaign table --}}
    <section class="panel rankings-panel">
        <div class="panel-heading rankings-heading">
            <div>
                <h2>All campaigns</h2>
                <p id="cmpSummary">{{ $campaigns->count() }} campaign(s) in this period</p>
            </div>

            @if($campaigns->isNotEmpty())
            <label class="mx-filter">
                <span>Result type</span>
                <select id="resultFilter">
                    <option value="">All results ({{ $campaigns->count() }})</option>
                    @foreach($resultOptions as $label => $count)
                    <option value="{{ $label }}">{{ $label }} ({{ $count }})</option>
                    @endforeach
                </select>
            </label>
            @endif
        </div>

        @if($campaigns->isEmpty())
        <div class="mx-empty" style="margin-bottom:14px;">No campaign spend in this period.</div>
        @else
        <div class="table-scroll">
            <table id="campaignTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Spend</th>
                        <th>Impressions</th>
                        <th>Clicks</th>
                        <th>CTR</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $c)
                    @php
                    $cs = (float) ($c['spend'] ?? 0);
                    $ci = (float) ($c['impressions'] ?? 0);
                    $cc = (float) ($c['clicks'] ?? 0);
                    $primary = $primaryResult($c);
                    $resLabel = $primary['label'] ?? 'No results';
                    $resValue = $primary['value'] ?? 0;
                    $costPerResult = $resValue > 0 ? $cs / $resValue : null;
                    $cur = $c['account_currency'] ?? '';
                    @endphp
                    <tr>
                        <td data-order="{{ $c['campaign_name'] ?? '' }}">
                            <span class="keyword-name">{{ $c['campaign_name'] ?? '-' }}</span>
                            <span class="keyword-url">{{ $c['campaign_id'] ?? '' }}</span>
                        </td>
                        <td data-order="{{ $cs }}">{{ $cur }} {{ $fmt($cs) }}</td>
                        <td data-order="{{ $ci }}">{{ $fmt($ci, 0) }}</td>
                        <td data-order="{{ $cc }}">{{ $fmt($cc, 0) }}</td>
                        <td data-order="{{ $ci > 0 ? $cc / $ci * 100 : 0 }}">{{ $ci > 0 ? $fmt($cc / $ci * 100) . '%' : '-' }}</td>
                        <td data-order="{{ $resValue }}"
                            data-label="{{ $resLabel }}"
                            data-spend="{{ $cs }}"
                            data-results="{{ $resValue }}"
                            data-search="{{ $resLabel }}">
                            @if($primary)
                            <span class="keyword-name">{{ $fmt($resValue, 0) }} {{ $resLabel }}</span>
                            <span class="keyword-url">
                                {{ $costPerResult !== null ? $cur . ' ' . $fmt($costPerResult) . ' per result' : '' }}
                            </span>
                            @else
                            <span class="keyword-url">No results</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Raw API responses --}}
        <!-- <section class="panel" style="margin-top:14px;">
            <div class="panel-heading">
                <div>
                    <h2>API responses</h2>
                    <p>Full Meta Graph API output. Failed requests open automatically.</p>
                </div>
            </div>

            @foreach($sections as $label => $key)
            <details class="mx-detail" @if(! $data[$key]['ok']) open @endif>
                <summary>
                    {{ $label }}
                    @if($data[$key]['raw'])
                    <small>GET {{ $data[$key]['raw']['endpoint'] }} (HTTP {{ $data[$key]['raw']['status'] }})</small>
                    @endif
                </summary>
                <pre class="mx-json">{{ json_encode(
                    $data[$key]['raw']['body'] ?? ['error' => $data[$key]['error']],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
                ) }}</pre>
            </details>
            @endforeach
        </section> -->
        @endif
    </section>

    @endif
</div>

@if($data)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        const table = document.getElementById('campaignTable');
        if (!table) return;

        const load = (src) => new Promise((res, rej) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = res;
            s.onerror = rej;
            document.head.appendChild(s);
        });

        if (!window.jQuery) {
            await load('https://code.jquery.com/jquery-3.7.1.min.js');
        }
        if (!window.jQuery.fn.DataTable) {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css';
            document.head.appendChild(css);
            await load('https://cdn.datatables.net/2.1.8/js/dataTables.min.js');
        }

        const $ = window.jQuery;
        const RESULT_COL = 5;
        const SPEND_COL = 1;

        const dt = $('#campaignTable').DataTable({
            order: [
                [1, 'desc']
            ],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            scrollX: true,
            columnDefs: [{
                targets: RESULT_COL,
                type: 'num'
            }],
            language: {
                emptyTable: 'No campaigns found',
                zeroRecords: 'No campaigns match this filter'
            },
        });

        const selected = () => $('#resultFilter').val();

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'campaignTable') return true;
            const v = selected();
            if (!v) return true;
            const cell = dt.cell(dataIndex, RESULT_COL).node();
            return cell && cell.dataset.label === v;
        });

        $('#resultFilter').on('change', () => dt.draw());

        const money = (n) => Number(n).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const whole = (n) => Number(n).toLocaleString(undefined, {
            maximumFractionDigits: 0
        });
        const cur = @json($currency);

        const refreshSummary = () => {
            const rows = dt.rows({
                search: 'applied'
            }).nodes().toArray();
            let spend = 0,
                results = 0;
            rows.forEach(tr => {
                spend += parseFloat(tr.cells[SPEND_COL].dataset.order) || 0;
                results += parseFloat(tr.cells[RESULT_COL].dataset.results) || 0;
            });
            const label = selected();
            let text = rows.length + ' campaign(s) · ' + cur + ' ' + money(spend) + ' spend';
            if (results > 0) {
                text += ' · ' + whole(results) + ' ' + (label || 'results') +
                    ' · ' + cur + ' ' + money(spend / results) + ' per result';
            }
            document.getElementById('cmpSummary').textContent = text;
        };

        dt.on('draw', refreshSummary);
        refreshSummary();
    });
    (function() {
        if (typeof Chart === 'undefined') return;

        const ORANGE = '#f7921e',
            BLUE = '#1b4b9b',
            NAVY = '#0b2350';
        const currency = @json($currency);
        const palette = @json($palette);
        const nf = (v, d = 2) => Number(v).toLocaleString(undefined, {
            maximumFractionDigits: d
        });

        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = '#6f7c96';
        Chart.defaults.plugins.legend.display = false;
        Chart.defaults.plugins.tooltip.backgroundColor = NAVY;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;

        const grid = {
            color: '#edf0f6',
            drawBorder: false
        };

        // Daily trend
        const daily = document.getElementById('chartDaily');
        if (daily) {
            const labels = @json($dailyLabels);
            const spend = @json($dailySpend);
            const clicks = @json($dailyClicks);
            const hasExtra = @json($hasDailyExtra);

            const datasets = [{
                label: 'Spend',
                data: spend,
                borderColor: ORANGE,
                borderWidth: 2.5,
                tension: .35,
                fill: true,
                pointRadius: spend.length > 40 ? 0 : 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: ORANGE,
                backgroundColor: (c) => {
                    const {
                        ctx,
                        chartArea
                    } = c.chart;
                    if (!chartArea) return null;
                    const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    g.addColorStop(0, 'rgba(247,146,30,.30)');
                    g.addColorStop(1, 'rgba(247,146,30,0)');
                    return g;
                },
                yAxisID: 'y',
            }];

            const scales = {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 10
                    }
                },
                y: {
                    beginAtZero: true,
                    grid,
                    ticks: {
                        callback: v => nf(v, 0)
                    }
                },
            };

            if (hasExtra) {
                datasets.push({
                    label: 'Clicks',
                    data: clicks,
                    borderColor: BLUE,
                    borderWidth: 2,
                    borderDash: [5, 4],
                    tension: .35,
                    pointRadius: 0,
                    fill: false,
                    yAxisID: 'y1',
                });
                scales.y1 = {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        callback: v => nf(v, 0)
                    },
                };
            }

            new Chart(daily, {
                type: 'line',
                data: {
                    labels,
                    datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (c) => c.dataset.label + ': ' +
                                    (c.dataset.yAxisID === 'y' ? currency + ' ' : '') + nf(c.parsed.y),
                            }
                        }
                    },
                },
            });
        }

        // Spend share doughnut
        const share = document.getElementById('chartShare');
        if (share) {
            new Chart(share, {
                type: 'doughnut',
                data: {
                    labels: @json($donutLabels),
                    datasets: [{
                        data: @json($donutValues),
                        backgroundColor: palette,
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (c) => c.label + ': ' + currency + ' ' + nf(c.parsed),
                            }
                        }
                    },
                },
            });
        }

        // Horizontal bar helper
        const hbar = (id, labels, values, color, fmt) => {
            const el = document.getElementById(id);
            if (!el) return;
            new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: color,
                        borderRadius: 8,
                        barThickness: 22,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid,
                            ticks: {
                                callback: v => nf(v, 0)
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (c) => fmt(c.parsed.x)
                            }
                        }
                    },
                },
            });
        };

        hbar('chartTop', @json($barLabels), @json($barValues), ORANGE,
            v => currency + ' ' + nf(v));
        hbar('chartCtr', @json($ctrRows -> pluck('name') -> all()), @json($ctrRows -> pluck('ctr') -> all()), BLUE,
            v => nf(v) + '%');
    })();

    (function() {
        const form = document.getElementById('filterForm');
        const select = document.getElementById('range');
        const since = document.getElementById('since');
        const until = document.getElementById('until');
        if (!form || !select) return;

        const toggle = () => {
            const isCustom = select.value === 'custom';
            document.querySelectorAll('.custom-range').forEach(
                el => el.style.display = isCustom ? '' : 'none'
            );
            since.disabled = !isCustom;
            until.disabled = !isCustom;
        };

        const submitIfReady = () => {
            if (select.value === 'custom') {
                if (since.value && until.value) form.submit();
            } else {
                form.submit();
            }
        };

        select.addEventListener('change', () => {
            toggle();
            submitIfReady();
        });
        since.addEventListener('change', submitIfReady);
        until.addEventListener('change', submitIfReady);

        toggle();
    })();

    // Show connection errors returned from the Meta OAuth callback.
    (function() {
        const errorMsg = @json(session('error'));

        if (errorMsg) {
            Swal.fire({
                title: 'Meta connection error',
                text: errorMsg,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#f7921e',
            });
        }
    })();

</script>
@endif

<script>
    const aiReportForm = document.getElementById('aiReportForm');
    if (aiReportForm) {
        aiReportForm.addEventListener('submit', function() {
            const range = document.getElementById('range').value;
            this.querySelector('#aiReportRange').value = range;
            this.querySelector('#aiReportSince').value = range === 'custom'
                ? document.getElementById('since').value
                : '';
            this.querySelector('#aiReportUntil').value = range === 'custom'
                ? document.getElementById('until').value
                : '';
        });
    }
</script>

@include('admin.partials.footer')