<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meta Ads AI Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font: 10px/1.5 DejaVu Sans, Arial, sans-serif; color: #172b4d; margin: 0; }
        .page { padding: 24px 28px; }
        .brand { color: #1b4b9b; font-size: 19px; font-weight: bold; text-align: center; border-bottom: 2px solid #1b4b9b; padding-bottom: 8px; }
        h1 { color: #1b4b9b; font-size: 17px; text-align: center; margin: 12px 0 3px; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 16px; }
        h2 { font-size: 12px; color: #1b4b9b; margin: 16px 0 6px; }
        p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 7px 0 12px; }
        th { background: #174b9b; color: #fff; text-align: left; }
        th, td { border: 1px solid #d9e2f2; padding: 5px 6px; }
        .kpis td { background: #f1f5fb; text-align: center; }
        .kpi { display: block; color: #123d7a; font-size: 13px; font-weight: bold; }
        .label { display: block; color: #63738c; font-size: 8px; }
        .report ul { margin: 4px 0 8px; padding-left: 18px; }
        .report li { margin: 2px 0; }
        .footer { position: fixed; bottom: -8px; width: 100%; text-align: center; color: #94a3b8; font-size: 8px; }
    </style>
</head>
<body>
@php
    $totals = $payload['totals'];
    $currency = $payload['currency'];
    $reportLines = preg_split('/\r\n|\r|\n/', trim($report));
    $inList = false;
@endphp
<div class="page">
    <div class="brand">360 Degree Info Pvt Ltd</div>
    <h1>Meta Ads AI Performance Report</h1>
    <div class="subtitle">
        Reporting period: {{ \Carbon\Carbon::parse($payload['period']['from'])->format('d M Y') }}
        — {{ \Carbon\Carbon::parse($payload['period']['to'])->format('d M Y') }}
        · Data saved for {{ $payload['days_with_data'] }} of {{ $payload['period']['days'] }} day(s)
    </div>

    <h2>Performance summary</h2>
    <table class="kpis">
        <tr>
            <td><span class="kpi">{{ $currency }} {{ number_format($totals['spend'], 2) }}</span><span class="label">Spend</span></td>
            <td><span class="kpi">{{ number_format($totals['impressions']) }}</span><span class="label">Impressions</span></td>
            <td><span class="kpi">{{ number_format($totals['clicks']) }}</span><span class="label">Clicks</span></td>
            <td><span class="kpi">{{ $totals['ctr_percent'] === null ? '—' : number_format($totals['ctr_percent'], 2) . '%' }}</span><span class="label">CTR</span></td>
        </tr>
        <tr>
            <td><span class="kpi">{{ $totals['cpc'] === null ? '—' : $currency . ' ' . number_format($totals['cpc'], 2) }}</span><span class="label">CPC</span></td>
            <td><span class="kpi">{{ $totals['leads'] }}</span><span class="label">Leads</span></td>
            <td><span class="kpi">{{ $totals['messaging_conversations'] }}</span><span class="label">Messaging conversations</span></td>
            <td><span class="kpi">{{ $totals['cost_per_lead'] === null ? '—' : $currency . ' ' . number_format($totals['cost_per_lead'], 2) }}</span><span class="label">Cost per lead</span></td>
        </tr>
    </table>

    <h2>Campaign performance</h2>
    <table>
        <thead>
            <tr>
                <th>Campaign</th>
                <th>Goal</th>
                <th>Spend</th>
                <th>Impressions</th>
                <th>Clicks</th>
                <th>Leads</th>
                <th>Conversations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['campaigns'] as $campaign)
                <tr>
                    <td>{{ $campaign['name'] ?: 'Unnamed campaign' }}</td>
                    <td>{{ $campaign['type'] }}</td>
                    <td>{{ $currency }} {{ number_format($campaign['spend'], 2) }}</td>
                    <td>{{ number_format($campaign['impressions']) }}</td>
                    <td>{{ number_format($campaign['clicks']) }}</td>
                    <td>{{ $campaign['leads'] }}</td>
                    <td>{{ $campaign['messaging_conversations'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>AI analysis and recommendations</h2>
    <div class="report">
        @foreach($reportLines as $line)
            @php
                $line = trim($line);
                $line = preg_replace('/\*\*(.*?)\*\*/', '$1', $line);
            @endphp
            @if(preg_match('/^#{1,4}\s+(.+)$/', $line, $heading))
                @if($inList)</ul>@php($inList = false)@endif
                <h2>{{ $heading[1] }}</h2>
            @elseif(preg_match('/^[-*•]\s+(.+)$/', $line, $bullet))
                @if(! $inList)<ul>@php($inList = true)@endif
                <li>{{ $bullet[1] }}</li>
            @elseif($line !== '')
                @if($inList)</ul>@php($inList = false)@endif
                <p>{{ $line }}</p>
            @elseif($inList)
                </ul>
                @php($inList = false)
            @endif
        @endforeach
        @if($inList)</ul>@endif
    </div>
</div>
<div class="footer">Generated {{ now()->format('d M Y H:i') }} · {{ config('app.name') }}</div>
</body>
</html>
