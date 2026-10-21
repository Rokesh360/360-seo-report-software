@include('admin.partials.header', [
'pageTitle' => 'Overview',
'active' => 'overview',
'periodLabel' => $periodLabel,
'mainId' => 'overview',
])

@php
$icons = [
'
<path d="M4 14.5 8 10l2.5 2.5L16 6" />
<path d="M11.5 6H16v4.5" />',
'
<circle cx="8.5" cy="8.5" r="5.5" />
<path d="m13 13 4 4" />',
'
<path d="M10 3v14M5 8l5-5 5 5M5 12l5 5 5-5" />',
'
<path d="M5 4h10M5 8h10M5 12h6M5 16h6" />
<circle cx="15" cy="14" r="3" />',
];
@endphp

<div class="dashboard-content">
    <section class="page-heading">
        <div>
            <div class="eyebrow">
                <span class="status-dot status-dot-dark"></span> MONTHLY REPORT
                <span class="heading-date">{{ strtoupper($start->format('d M')) }} — {{ strtoupper($end->format('d M Y')) }}</span>
            </div>
            <h1>{{ $greeting }}, {{ trim(\Illuminate\Support\Str::after(auth()->user()->name, ' ')) ?: auth()->user()->name }}.</h1>
            <p class="page-subtitle">Here’s how your campaigns performed this month.</p>
        </div>

        <form method="GET" class="dashboard-filters" style="display:flex;gap:8px;align-items:center;">
            <select name="month" onchange="this.form.submit()" aria-label="Month">
                @foreach($monthOptions as $m)
                <option value="{{ $m->format('Y-m') }}" @selected($m->format('Y-m') === $month->format('Y-m'))>
                    {{ $m->format('F Y') }}
                </option>
                @endforeach
            </select>
            <select name="client_id" onchange="this.form.submit()" aria-label="Client">
                @foreach($clients as $c)
                <option value="{{ $c->id }}" @selected($clientId===$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    </section>

    {{-- ============ METRIC CARDS ============ --}}
    <section class="metric-grid" aria-label="Monthly metrics">
        @foreach($cards as $card)
        <article class="metric-card">
            <div class="metric-top">
                <span>{{ $card['label'] }}</span>
                <span class="metric-icon metric-icon-{{ $card['color'] }}" aria-hidden="true">
                    <svg viewBox="0 0 20 20">{!! $icons[$card['icon']] !!}</svg>
                </span>
            </div>
            <div class="metric-value">{{ $card['value'] }}</div>
            <div class="metric-foot">
                @if($card['change'] === null)
                <span>—</span><span>no previous month data</span>
                @else
                <span class="{{ $card['change'] >= 0 ? 'change-up' : 'change-down' }}">
                    {!! $card['change'] >= 0 ? '&#8593;' : '&#8595;' !!} {{ abs($card['change']) }}%
                </span>
                <span>vs. previous month</span>
                @endif
            </div>
            <div class="metric-spark spark-{{ $card['color'] }}" aria-hidden="true">
                @foreach($card['spark'] as $h)<span style="height:{{ $h }}%"></span>@endforeach
            </div>
        </article>
        @endforeach
    </section>

    <section class="content-grid">
        {{-- ============ LINE CHART ============ --}}
        <article class="panel performance-panel" id="performance">
            <div class="panel-heading">
                <div>
                    <h2>Campaign performance</h2>
                    <p>Clicks and impressions over time</p>
                </div>
                <span class="chart-period">Daily, {{ $month->format('F') }}</span>
            </div>
            <div class="chart-legend">
                <span><i class="legend-dot clicks-dot"></i> Clicks <small>(peak {{ $chart['peakClicks'] }})</small></span>
                <span><i class="legend-dot impressions-dot"></i> Impressions <small>(peak {{ $chart['peakImpressions'] }})</small></span>
                <span class="chart-change"><small>{{ $reportCount }} report {{ \Illuminate\Support\Str::plural('entry', $reportCount) }}</small></span>
            </div>

            @if($chart['hasData'])
            <div class="chart-wrap" role="img" aria-label="Clicks and impressions by day for {{ $periodLabel }}">
                <div class="chart-y-labels">
                    @foreach($chart['yLabels'] as $l)<span>{{ $l }}</span>@endforeach
                </div>
                <svg class="performance-chart" viewBox="0 0 760 210" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="clickFill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="#4b9a70" stop-opacity=".19" />
                            <stop offset="1" stop-color="#4b9a70" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <path class="gridline" d="M0 18H760M0 72H760M0 126H760M0 180H760" />
                    <path class="impressions-line" d="{{ $chart['impressionsPath'] }}" />
                    <path class="click-area" d="{{ $chart['areaPath'] }}" />
                    <path class="clicks-line" d="{{ $chart['clicksPath'] }}" />
                    @if($chart['peak'])
                    <circle class="chart-point" cx="{{ round($chart['peak'][0], 1) }}" cy="{{ round($chart['peak'][1], 1) }}" r="4" />
                    @endif
                </svg>
                <div class="chart-x-labels">
                    @foreach($chart['xLabels'] as $l)<span>{{ $l }}</span>@endforeach
                </div>
            </div>
            <p class="page-subtitle" style="margin-top:6px;font-size:12px;">
                Left axis shows impressions. Clicks are scaled to their own peak so the trend stays visible.
            </p>
            @else
            <p class="page-subtitle" style="padding:48px 0;text-align:center;">No report entries for {{ $periodLabel }}.</p>
            @endif
        </article>

        {{-- ============ ENGAGEMENT DONUT ============ --}}
        <article class="panel channel-panel" id="engagement-mix">
            <div class="panel-heading">
                <div>
                    <h2>Engagement mix</h2>
                    <p>Reactions, comments, shares, saves</p>
                </div>
                <span class="health-check-label">{{ $donut['totalLabel'] }} total</span>
            </div>

            @if(count($donut['slices']))
            <div class="health-score">
                <svg viewBox="0 0 120 120" role="img" aria-label="Engagement mix donut chart">
                    <circle cx="60" cy="60" r="{{ $donut['r'] }}" fill="none" stroke="#eef0f2" stroke-width="14" />
                    <g transform="rotate(-90 60 60)">
                        @foreach($donut['slices'] as $s)
                        <circle cx="60" cy="60" r="{{ $donut['r'] }}" fill="none"
                            stroke="{{ $s['color'] }}" stroke-width="14"
                            stroke-dasharray="{{ $s['dash'] }}"
                            stroke-dashoffset="{{ $s['offset'] }}">
                            <title>{{ $s['label'] }}: {{ number_format($s['value']) }} ({{ $s['pct'] }}%)</title>
                        </circle>
                        @endforeach
                    </g>
                </svg>
                <div class="health-score-label"><strong>{{ $donut['totalLabel'] }}</strong><span>Interactions</span></div>
            </div>
            <div class="health-list">
                @foreach($donut['slices'] as $s)
                <div>
                    <span><i class="health-mini-dot" style="background:{{ $s['color'] }}"></i>{{ $s['label'] }}</span>
                    <strong>{{ number_format($s['value']) }} · {{ $s['pct'] }}%</strong>
                </div>
                @endforeach
            </div>
            @else
            <p class="page-subtitle" style="padding:48px 0;text-align:center;">No engagement data for {{ $periodLabel }}.</p>
            @endif
        </article>
    </section>

    @if($lowBudgetRows->isNotEmpty())
    <style>
        #low-budget-records.is-highlighted {
            animation: lowBudgetPulse 1s ease-in-out 3;
            outline: 3px solid #d27663;
            outline-offset: 4px;
        }


        @keyframes lowBudgetPulse {

            0%,
            100% {
                box-shadow: 0 4px 18px rgba(210, 118, 99, 0.15);
            }

            50% {
                box-shadow: 0 0 0 14px rgba(210, 118, 99, 0);
            }
        }

        #low-budget-records {
            border: 2px solid #d27663;
            background: #fff7f4;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(210, 118, 99, 0.15);
            transition: background .4s, outline-color .4s;
        }

        #low-budget-records .panel-heading {
            border-bottom-color: #f0d0c8;
        }

        #low-budget-records h2 {
            color: #b94f3b;
        }

        #low-budget-records.low-budget-default {
            border: 2px solid #d27663;
            background: #fff7f4;
        }

        .lb-popup-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        .lb-popup-table th {
            padding: 8px;
            border-bottom: 1px solid #e1e8f4;
            color: #6f7c96;
            font-size: 11px;
            white-space: nowrap;
        }

        .lb-popup-table td {
            padding: 8px;
            border-bottom: 1px solid #edf1f8;
            color: #46557a;
        }

        .lb-popup-table td.lb-low {
            color: #d27663;
            font-weight: 700;
            white-space: nowrap;
        }
    </style>

    <section class="panel rankings-panel low-budget-default" id="low-budget-records">
        <div class="panel-heading rankings-heading">
            <div>
                <h2>Low available budget</h2>
                <p>
                    Remaining budget for the selected client, sorted from lowest to highest
                </p>
            </div>

            <span class="table-summary">
                {{ $lowBudgetRows->count() }}
                {{ \Illuminate\Support\Str::plural('record', $lowBudgetRows->count()) }}
            </span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>CLIENT</th>
                        <th>SERVICE</th>
                        <th>DEADLINE</th>
                        <th>REPORT DATE</th>
                        <th>BUDGET</th>
                        <th>SPENT</th>
                        <th>AVAILABLE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowBudgetRows as $row)
<tr>
    <td>{{ $row['client'] }}</td>
    <td>{{ $row['service'] }}</td>

    <td style="white-space:nowrap;">
        {{ $row['to_date'] ? \Carbon\Carbon::parse($row['to_date'])->format('d M Y') : '—' }}
    </td>

    <td>{{ $row['date']->format('d M Y') }}</td>
    <td>₹{{ number_format($row['budget']) }}</td>
    <td>₹{{ number_format($row['spent']) }}</td>
    <td>
        <strong style="color:#d27663;">
            ₹{{ number_format($row['available']) }}
        </strong>
    </td>
</tr>
@endforeach
                </tbody>
            </table>
        </div>
    </section>

    @php
    $lowBudgetPopupRows = $lowBudgetRows->map(function ($r) {
    return [
        'client'    => $r['client'],
        'service'   => $r['service'],
'deadline' => $r['to_date']
    ? \Carbon\Carbon::parse($r['to_date'])->format('d M Y')
    : '—',
        'date'      => $r['date']->format('d M Y'),
        'available' => '₹' . number_format($r['available']),
    ];
})->values()->all();
    @endphp

    <script>
        (function() {
            const rows = @json($lowBudgetPopupRows);

            const count = rows.length;

            const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));

            const body = rows.map((r) =>
    '<tr>' +
    '<td>' + esc(r.client) + '</td>' +
    '<td>' + esc(r.service) + '</td>' +
    '<td>' + esc(r.deadline) + '</td>' +
    '<td>' + esc(r.date) + '</td>' +
    '<td class="lb-low">' + esc(r.available) + '</td>' +
    '</tr>'
).join('');

            const html =
                '<p style="margin:0 0 12px;color:#6f7c96;font-size:14px;">' +
                count + (count === 1 ? ' record has' : ' records have') +
                ' a positive remaining budget, sorted from lowest to highest.' +
                '</p>' +
                '<div style="max-height:300px;overflow:auto;">' +
                '<table class="lb-popup-table">' +
                '<thead><tr>' +
'<th>CLIENT</th>' +
'<th>SERVICE</th>' +
'<th>DEADLINE</th>' +
'<th>REPORT DATE</th>' +
'<th>AVAILABLE</th>' +
'</tr></thead>' +
                '<tbody>' + body + '</tbody>' +
                '</table>' +
                '</div>';

            const highlight = () => {
                const el = document.getElementById('low-budget-records');
                if (!el) return;
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                el.classList.remove('is-highlighted');
                void el.offsetWidth; // restart the animation if it was already applied
                el.classList.add('is-highlighted');
                setTimeout(() => el.classList.remove('is-highlighted'), 3500);
            };

            const show = () => Swal.fire({
                icon: 'warning',
                title: 'Low available budget',
                html: html,
                width: 720,
                confirmButtonText: 'View records',
            }).then((result) => {
                if (result.isConfirmed) highlight();
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', show);
            } else {
                show();
            }
        })();
    </script>
    @endif

    {{-- ============ CLIENT TABLE ============ --}}
    <section class="panel rankings-panel" id="rankings">
        <div class="panel-heading rankings-heading">
            <div>
                <h2>Client performance</h2>
                <p>Reach and activity per client this month</p>
            </div>
            <span class="table-summary">{{ $clientRows->count() }} {{ \Illuminate\Support\Str::plural('client', $clientRows->count()) }}</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>CLIENT</th>
                        <th>SERVICES</th>
                        <th>REPORTS</th>
                        <th>CLICKS</th>
                        <th>SHARE OF REACH</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientRows as $row)
                    <tr>
                        <td>
                            <span class="keyword-name">{{ $row['name'] }}</span>
                            <span class="keyword-url">Last entry: {{ $row['lastLabel'] }}</span>
                        </td>
                        <td>{{ $row['services'] }}</td>
                        <td><span class="rank-pill {{ $row['reports'] ? 'rank-top' : '' }}">{{ $row['reports'] }}</span></td>
                        <td>{{ number_format($row['clicks']) }}</td>
                        <td>
                            <span class="visibility-value">{{ $row['reachLabel'] }}</span>
                            <span class="visibility-track"><i style="width:{{ $row['share'] }}%"></i></span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:24px;">No clients or reports for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @include('admin.partials.footer')