<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Google Ads Campaign Report</title>
    <style>
        @page {
            margin: 110px 50px 90px 50px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font: 11px/1.45 'DejaVu Sans', Arial, sans-serif;
            color: #172b4d;
            margin: 0;
            padding: 0;
        }

        /* ===== HEADER (repeats on every page) ===== */
        header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
            height: 70px;
            border-bottom: 2px solid #1f4e9c;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0 0 6px;
            border: none;
        }

        .header-logo {
            width: 90px;
        }

        .header-logo img {
            height: 55px;
            display: block;
        }

        .header-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #1f4e9c;
            padding-right: 90px;
        }

        /* ===== FOOTER ===== */
        .footer {
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            text-align: center;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
        }

        h1 {
            color: #1b4b9b;
            font-size: 17px;
            text-align: center;
            margin: 0 0 2px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 16px;
        }

        h2 {
            font-size: 12px;
            color: #1b4b9b;
            margin: 14px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th {
            background: #174b9b;
            color: #fff;
            text-align: left;
        }

        th,
        td {
            border: 1px solid #d9e2f2;
            padding: 6px 8px;
        }

        .kpis td {
            text-align: center;
            background: #f1f5fb;
        }

        .kpi {
            display: block;
            color: #123d7a;
            font-size: 15px;
            font-weight: bold;
        }

        .label {
            display: block;
            color: #63738c;
            font-size: 9px;
        }

        .image-grid td {
            width: 50%;
            text-align: center;
        }

        .image-grid img {
            max-width: 100%;
            max-height: 290px;
        }
    </style>
</head>

<body>
    @php
    $data = $googleAdsReport->data;
    $budgetBalance = (float) $assignment->budget - (float) ($data['total_cost'] ?? 0);
    $isYoutube = $serviceType === 'google_youtube_ads';
    @endphp

    <header>
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('assets/360degreeinfo.webp') }}" alt="360 Info">
                </td>
                <td class="header-title">360 Degree info Pvt Ltd</td>
            </tr>
        </table>
    </header>

    <div class="footer">
        &copy; {{ now()->format('Y') }} 360 Degree Info Pvt Ltd. All rights reserved.
    </div>

    <div>
        <h1>{{ \App\Support\ReportServiceType::label($serviceType) }} Campaign Report</h1>
        <div class="subtitle">{{ $assignment->client?->name }} · Reporting period: {{ $assignment->from_date?->format('d M Y') }} — {{ $assignment->to_date?->format('d M Y') }}</div>

        <h2>1. Campaign Overview</h2>
        <table class="image-grid">
            @foreach(array_chunk($googleAdsReport->overview_images ?? [], 2) as $imageRow)
            <tr>
                @foreach($imageRow as $image)
                <td>
                    @php $imageFile = \Illuminate\Support\Facades\Storage::disk('public')->path($image); @endphp
                    @if(is_file($imageFile))<img src="{{ $imageFile }}" alt="Campaign overview screenshot">@endif
                </td>
                @endforeach
                @if(count($imageRow) === 1)<td></td>@endif
            </tr>
            @endforeach
        </table>

        <h2>Campaign Performance</h2>
        <table class="kpis">
            <tr>
                @foreach(['clicks' => 'Clicks', 'impressions' => 'Impressions', 'views' => 'Views', 'calls' => 'Calls', 'total_cost' => 'Total Cost', 'balance' => 'Balance Amount'] as $key => $label)
                @if($key === 'balance' || array_key_exists($key, $data))
                <td><span class="kpi">
                        @if(in_array($key, ['total_cost', 'balance'], true)){{ number_format($key === 'balance' ? $budgetBalance : (float) $data[$key], 2) }}
                        @else{{ number_format((int) ($data[$key] ?? 0)) }}@endif
                    </span><span class="label">{{ $label }}</span></td>
                @endif
                @endforeach
            </tr>
        </table>
        @if(isset($data['interactions']))<p><strong>Interactions:</strong> {{ number_format((int) $data['interactions']) }}</p>@endif

        <h2>2. Audience Demographics — Gender</h2>
        <table>
            <thead>
                <tr>
                    <th>Gender</th>
                    <th>Approx. Clicks</th>
                    <th>% of Total Clicks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['demographics'] ?? [] as $row)
                @php
                $totalClicks = (int) ($data['clicks'] ?? 0);
                $clicks = (int) ($row['clicks'] ?? 0);
                $percentage = $totalClicks > 0 ? round($clicks / $totalClicks * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $row['gender'] }}</td>
                    <td>{{ number_format($clicks) }}</td>
                    <td>{{ number_format($percentage, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h2>3. Location Performance</h2>
        <table>
            <thead>
                <tr>
                    <th>Campaign / Location</th>
                    <th>CPC</th>
                    <th>Impressions</th>@unless($isYoutube)<th>Avg. CPC</th>@endunless
                </tr>
            </thead>
            <tbody>
                @foreach($data['locations'] ?? [] as $row)
                <tr>
                    <td>{{ $row['campaign'] }}</td>
                    <td>{{ number_format((float) ($row['cpc'] ?? 0), 2) }}</td>
                    <td>{{ number_format((int) ($row['impressions'] ?? 0)) }}</td>
                    @unless($isYoutube)<td>{{ number_format((float) ($row['avg_cpc'] ?? 0), 2) }}</td>@endunless
                </tr>
                @endforeach
            </tbody>
        </table>

        <h2>4. Key Metrics Analysis</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Insight</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['key_metrics'] ?? [] as $row)
                <tr>
                    <td>{{ $row['metric'] }}</td>
                    <td>{{ $row['value'] }}</td>
                    <td>{{ $row['insight'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>