<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Meta Ads Report</title>
    <style>
        /* =====================================================
           PAGE SETUP (same as SEO report template)
           ===================================================== */
        @page {
            margin: 110px 50px 90px 50px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            line-height: 1.5;
        }

        /* =====================================================
           HEADER
           ===================================================== */
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

        /* =====================================================
           FOOTER
           ===================================================== */
        footer {
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            height: 44px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            background: #fff;
            text-align: center;
            font-size: 10px;
            line-height: 1.6;
            color: #444;
        }

        /* =====================================================
           TYPOGRAPHY
           ===================================================== */
        h1 {
            color: #1b4b9b;
            font-size: 17px;
            text-align: center;
            margin: 0 0 3px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 16px;
        }

        h2 {
            color: #0b2f7a;
            font-size: 13px;
            line-height: 1.3;
            margin: 0 0 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #e5e7eb;
            page-break-after: avoid;
        }

        p {
            margin: 0 0 10px;
        }

        /* =====================================================
           SECTIONS / TABLES
           ===================================================== */
        .section {
            margin: 0 0 20px;
        }

        .meta {
            margin: 0 0 20px;
            padding: 12px 16px;
            background: #f8fafc;
            border-left: 4px solid #1f4e9c;
        }

        .meta p {
            margin: 3px 0;
            font-size: 12px;
            font-weight: bold;
            color: #0b2f7a;
        }

        .meta p:first-child {
            font-size: 14px;
            margin-bottom: 6px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 6px;
        }

        table.data th {
            background: #1f4e9c;
            color: #fff;
            text-align: left;
        }

        table.data th,
        table.data td {
            border: 1px solid #d9e2f2;
            padding: 5px 6px;
        }

        table.data tr {
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        td.num,
        th.num {
            text-align: right;
        }

        .kpis {
            width: 100%;
            border-collapse: collapse;
        }

        .kpis td {
            background: #f1f5fb;
            text-align: center;
            border: 1px solid #d9e2f2;
            padding: 8px 6px;
        }

        .kpi {
            display: block;
            color: #123d7a;
            font-size: 13px;
            font-weight: bold;
        }

        .label {
            display: block;
            color: #63738c;
            font-size: 8px;
        }

        .muted {
            color: #94a3b8;
        }
    </style>
</head>

<body>

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

    <footer>
        #34/29, 3rd Street, 200ft Radial Road Chromepet, Chennai-44.<br>
        +919884682652, 8124877711. info@360degreeinfo.com
    </footer>

    @php
        $f      = fn ($v, $d = 2) => number_format((float) $v, $d);
        $spend  = (float) ($summary['spend'] ?? 0);
        $impr   = (float) ($summary['impressions'] ?? 0);
        $clicks = (float) ($summary['clicks'] ?? 0);
        $reach  = (float) ($summary['reach'] ?? 0);
        $ctr    = (float) ($summary['ctr'] ?? ($impr > 0 ? $clicks / $impr * 100 : 0));
        $cpc    = (float) ($summary['cpc'] ?? ($clicks > 0 ? $spend / $clicks : 0));
    @endphp

    {{-- ===================== REPORT DETAILS (first page) ===================== --}}
    <div class="meta">
        <p>Meta Ads Performance Report</p>
        <p>Date range: {{ $rangeLabel }}</p>
        <p>Reporting period: {{ $periodText }}</p>
        <p>Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    {{-- ===================== PERFORMANCE SUMMARY ===================== --}}
    <div class="section">
        <h2>Performance Summary</h2>
        <table class="kpis">
            <tr>
                <td><span class="kpi">{{ $currency }} {{ $f($spend) }}</span><span class="label">Spend</span></td>
                <td><span class="kpi">{{ $f($impr, 0) }}</span><span class="label">Impressions</span></td>
                <td><span class="kpi">{{ $f($clicks, 0) }}</span><span class="label">Clicks</span></td>
                <td><span class="kpi">{{ $f($ctr) }}%</span><span class="label">CTR</span></td>
            </tr>
            <tr>
                <td><span class="kpi">{{ $currency }} {{ $f($cpc) }}</span><span class="label">CPC</span></td>
                <td><span class="kpi">{{ $f($reach, 0) }}</span><span class="label">Reach</span></td>
                <td colspan="2"><span class="kpi">{{ $campaigns->count() }}</span><span class="label">Campaigns</span></td>
            </tr>
        </table>
    </div>

    {{-- ===================== CAMPAIGNS ===================== --}}
    <div class="section">
        <h2>Campaigns</h2>
        @if($campaigns->isEmpty())
            <p class="muted">No campaign spend in this period.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th class="num">Spend</th>
                        <th class="num">Impressions</th>
                        <th class="num">Clicks</th>
                        <th class="num">CTR</th>
                        <th>Result</th>
                        <th class="num">Cost / result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $c)
                        @php
                            $cs  = (float) ($c['spend'] ?? 0);
                            $ci  = (float) ($c['impressions'] ?? 0);
                            $cc  = (float) ($c['clicks'] ?? 0);
                            $res = $c['_result'];
                            $cur = $c['account_currency'] ?? $currency;
                        @endphp
                        <tr>
                            <td>{{ $c['campaign_name'] ?? '-' }}</td>
                            <td class="num">{{ $cur }} {{ $f($cs) }}</td>
                            <td class="num">{{ $f($ci, 0) }}</td>
                            <td class="num">{{ $f($cc, 0) }}</td>
                            <td class="num">{{ $ci > 0 ? $f($cc / $ci * 100) . '%' : '-' }}</td>
                            <td>{{ $res ? $f($res['value'], 0) . ' ' . $res['label'] : 'No results' }}</td>
                            <td class="num">{{ $res && $res['value'] > 0 ? $cur . ' ' . $f($cs / $res['value']) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ===================== DAILY BREAKDOWN ===================== --}}
    <div class="section">
        <h2>Daily Breakdown</h2>
        @if($daily->isEmpty())
            <p class="muted">No daily data in this period.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="num">Spend</th>
                        <th class="num">Impressions</th>
                        <th class="num">Clicks</th>
                        <th class="num">CTR</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daily as $d)
                        @php
                            $di = (float) ($d['impressions'] ?? 0);
                            $dc = (float) ($d['clicks'] ?? 0);
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($d['date_start'])->format('d M Y') }}</td>
                            <td class="num">{{ $currency }} {{ $f($d['spend'] ?? 0) }}</td>
                            <td class="num">{{ $f($di, 0) }}</td>
                            <td class="num">{{ $f($dc, 0) }}</td>
                            <td class="num">{{ $di > 0 ? $f($dc / $di * 100) . '%' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</body>

</html>