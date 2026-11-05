<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $single ? 'Social Media Report' : 'All Social Media Reports' }}</title>
    <style>
        /* ============================================================
           PAGE SETUP
           ============================================================ */
        @page { margin: 110px 48px 85px 48px; }

        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5px;
            color: #1f2937;
            margin: 0;
            line-height: 1.55;
        }
        .page { padding: 0; }

        /* ============================================================
           SITE HEADER (unchanged — repeats on every page)
           ============================================================ */
        header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
            height: 70px;
            border-bottom: 2px solid #1f4e9c;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; padding: 0 0 6px; border: none; }
        .header-logo { width: 90px; }
        .header-logo img { height: 55px; display: block; }
        .header-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #1f4e9c;
            padding-right: 90px;
        }

        /* ============================================================
           FOOTER
           ============================================================ */
        .pdf-footer {
            position: fixed;
            bottom: -62px;
            left: 0;
            right: 0;
            border-top: 1px solid #d0d5dd;
            padding-top: 6px;
            font-size: 8px;
            color: #667085;
        }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { border: none; padding: 0; vertical-align: top; }
        .footer-addr { text-align: left; line-height: 1.5; }
        .footer-name { font-weight: bold; color: #14284b; }
        .footer-page { text-align: right; width: 80px; color: #14284b; }
        .pagenum:before { content: counter(page); }

        /* ============================================================
           REPORT TITLE
           ============================================================ */
        .report-title { margin-bottom: 14px; text-align: center; }
        .report-kicker { font-size: 9px; color: #667085; margin-bottom: 3px; }
        .report-title h1 {
            font-size: 21px;
            margin: 0 0 8px;
            color: #14284b;
            font-weight: bold;
            line-height: 1.2;
        }
        .report-sub { font-size: 10px; color: #475467; margin: -4px 0 8px; }
        .title-rule { border-top: 3px solid #14284b; border-bottom: 1px solid #14284b; height: 4px; }

        /* Document information table */
        .doc-info { width: 100%; border-collapse: collapse; margin: 12px 0 18px; }
        .doc-info td {
            padding: 6px 10px;
            border-bottom: 1px solid #e4e7ec;
            vertical-align: top;
            width: 25%;
        }
        .doc-info .di-label { font-size: 8px; color: #667085; display: block; }
        .doc-info .di-value { font-size: 10.5px; font-weight: bold; color: #14284b; display: block; margin-top: 1px; }

        /* ============================================================
           SECTIONS
           ============================================================ */
        .section { margin-bottom: 18px; }
        .keep { page-break-inside: avoid; }

        .section-title {
            font-size: 12.5px;
            font-weight: bold;
            color: #14284b;
            border-bottom: 1.5px solid #14284b;
            padding: 0 0 4px;
            margin: 0 0 10px;
            page-break-after: avoid;
        }
        .sec-no { color: #1f4e9c; margin-right: 4px; }
        .sub-title {
            font-size: 10px;
            font-weight: bold;
            color: #1f4e9c;
            margin: 12px 0 5px;
            page-break-after: avoid;
        }

        /* ============================================================
           LABEL / VALUE TABLES
           ============================================================ */
        .meta-grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta-grid td {
            padding: 6px 9px;
            border-top: 1px solid #e4e7ec;
            border-bottom: 1px solid #e4e7ec;
            vertical-align: top;
        }
        .meta-grid .label {
            background: #f5f7fa;
            font-weight: bold;
            width: 24%;
            color: #344054;
        }

        /* ============================================================
           KPI TABLES
           ============================================================ */
        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
            border-top: 2px solid #14284b;
            border-bottom: 1px solid #d0d5dd;
            margin-bottom: 6px;
        }
        .kpi-grid td {
            text-align: left;
            padding: 9px 12px;
            border-right: 1px solid #e4e7ec;
            border-bottom: 1px solid #e4e7ec;
            vertical-align: top;
        }
        .kpi-grid tr td:last-child { border-right: none; }
        .kpi-grid tr:last-child td { border-bottom: none; }
        .kpi-grid .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #14284b;
            display: block;
        }
        .kpi-grid .kpi-label {
            font-size: 8.5px;
            color: #667085;
            margin-top: 2px;
            display: block;
        }
        /* Headline strip (executive summary) */
        .kpi-grid.headline { background: #f5f7fa; }
        .kpi-grid.headline .kpi-value { font-size: 19px; color: #1f4e9c; }

        /* ============================================================
           CHARTS
           ============================================================ */
        .chart-section { page-break-inside: avoid; }
        .metric-chart { margin: 0 0 12px; }
        .metric-chart h3 {
            font-size: 9.5px;
            color: #344054;
            margin: 0 0 6px;
        }
        .chart-frame { border: 1px solid #e4e7ec; padding: 10px 12px; }
        .chart-img { display: block; width: 100%; }
        .pie-img { width: 160px; height: 160px; vertical-align: middle; }
        .chart-legend { display: inline-block; margin-left: 18px; vertical-align: middle; }
        .chart-legend-item { margin: 5px 0; }
        .legend-swatch { display: inline-block; width: 9px; height: 9px; margin-right: 6px; }

        /* ============================================================
           DATA TABLES
           ============================================================ */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th {
            background: #14284b;
            color: #ffffff;
            font-size: 9px;
            padding: 6px 8px;
            text-align: left;
            border: none;
        }
        table.data td {
            padding: 6px 8px;
            border-bottom: 1px solid #e4e7ec;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td { background: #f9fafb; }

        /* ============================================================
           TEXT BLOCKS
           ============================================================ */
        .text-block { margin-bottom: 8px; }
        .text-block .tb-label {
            font-weight: bold;
            color: #344054;
            display: block;
            margin-bottom: 1px;
        }
        .text-block .tb-value { color: #1f2937; white-space: pre-wrap; }

        /* ============================================================
           CLOSING NOTE
           ============================================================ */
        .closing {
            margin-top: 20px;
            border-top: 1px solid #d0d5dd;
            padding-top: 8px;
            font-size: 8.5px;
            color: #667085;
            page-break-inside: avoid;
        }
        .closing strong { color: #14284b; }

        .page-break { page-break-after: always; }
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

<div class="pdf-footer">
    <table class="footer-table">
        <tr>
            <td class="footer-addr">
                <span class="footer-name">360 Degree info Pvt Ltd</span><br>
                No. 34/29, 3rd Street, 200 Feet Radial Rd, Padmavathi Nagar, Chromepet, Tambaram, Tamil Nadu 600044.
            </td>
            <td class="footer-page">Page <span class="pagenum"></span></td>
        </tr>
    </table>
</div>

{{-- ============================================================
     TREND LINE CHART — combined export only (PNG via GD)
     ============================================================ --}}
@php
    $trendSrc = null;
    if (!$single && $reports->count() > 1) {
        $trendLabels = $reports->map(fn ($r) => $r->created_at?->format('d M'))->values()->all();
        $trendValues = $reports->map(fn ($r) =>
            (int) $r->total_conversations + (int) $r->total_leads + (int) $r->total_engagement
        )->values()->all();

        $trendSrc = \App\Support\Helpers::lineChart($trendLabels, $trendValues);
    }
@endphp

@if($trendSrc)
    <div class="page page-break">
        <div class="report-title">
            <div class="report-kicker">Combined export</div>
            <h1>Performance Trend</h1>
            <div class="report-sub">Conversations + Leads + Engagement per report entry</div>
            <div class="title-rule"></div>
        </div>
        <div class="chart-frame">
            <img src="{{ $trendSrc }}" style="width:100%;">
        </div>
    </div>
@endif

{{-- ============================================================
     LOOP OVER EVERY REPORT
     ============================================================ --}}
@foreach($reports as $index => $report)

    @php
        /* ---------- Report types ---------- */
        $selectedTypes = ($reportTypes ?? [])[$report->id]
            ?? (array) ($report->selected_report_types ?? []);
        $legacyAll     = empty($selectedTypes);

        $isAll           = count($selectedTypes) > 1;
        $isWhatsApp      = !$isAll && in_array('whatsapp_meta_ads', $selectedTypes, true);
        $isLeadGen       = !$isAll && in_array('lead_generation_ads', $selectedTypes, true);
        $isEngagement    = !$isAll && in_array('social_media_engagement', $selectedTypes, true);

        $showWhatsApp    = $legacyAll
            ? !is_null($report->total_conversations) || !is_null($report->whatsapp_clicks)
            : in_array('whatsapp_meta_ads', $selectedTypes, true);
        $showLeadGen     = $legacyAll
            ? !is_null($report->total_leads) || !is_null($report->link_clicks)
            : in_array('lead_generation_ads', $selectedTypes, true);
        $showEngagement  = $legacyAll
            ? !is_null($report->total_engagement) || !is_null($report->reactions) || !is_null($report->comments)
            : in_array('social_media_engagement', $selectedTypes, true);

        $showCreativeAudience = $showWhatsApp || $showLeadGen || $isAll;
        $showTopPosts         = $showEngagement;

        $reportAssignService = ($assignServices ?? [])[(int) $report->assign_service_id] ?? null;
        $reportAssignServices = $reportAssignService ? [$reportAssignService] : [];
        $clientName = $reportAssignService?->client?->name ?: ($report->client_names ?: '—');

        // Every section is numbered
        $sectionNo = 0;

        $creative = $report->creative_performance  ?? [];
        $audience = $report->audience_performance ?? [];
        $campaignRows = $report->campaign_results ?? [];
        $topPosts     = $report->top_posts        ?? [];
        $content      = $report->content_analysis ?? [];

        /* ---------- Headline figures for the executive summary ---------- */
        $headline = [];
        if ($showWhatsApp) {
            $headline['Conversations'] = number_format((int) $report->total_conversations);
            $headline['WhatsApp Reach'] = number_format((int) $report->reach);
        }
        if ($showLeadGen) {
            $headline['Total Leads'] = number_format((int) $report->total_leads);
            $headline['Lead Reach'] = number_format((int) $report->lead_reach);
        }
        if ($showEngagement) {
            $headline['Total Engagement'] = number_format((int) $report->total_engagement);
            $headline['New Followers'] = number_format((int) $report->new_followers);
        }
        $headline = array_slice($headline, 0, 4, true);
        $headlineWidth = count($headline) ? floor(100 / count($headline)) : 100;

        /* ---------- Chart data ---------- */
        $engagementSlices = [
            'Reactions' => (int) $report->reactions,
            'Comments'  => (int) $report->comments,
            'Shares'    => (int) $report->shares,
            'Saves'     => (int) $report->saves,
        ];
        $engagementSlices = array_filter($engagementSlices, static fn ($value) => $value > 0);
        $engagementTotal  = array_sum($engagementSlices);
        $engagementColors = \App\Support\Helpers::PALETTE;

        $graphMetrics = [];
        if ($showWhatsApp) {
            $graphMetrics += [
                'Conversations' => (int) $report->total_conversations,
                'WA Clicks'     => (int) $report->whatsapp_clicks,
                'WA Reach'      => (int) $report->reach,
                'WA Impr.'      => (int) $report->impressions,
            ];
        }
        if ($showLeadGen) {
            $graphMetrics += [
                'Leads'       => (int) $report->total_leads,
                'Link Clicks' => (int) $report->link_clicks,
                'Lead Reach'  => (int) $report->lead_reach,
                'Lead Impr.'  => (int) $report->lead_impressions,
            ];
        }
        if ($showEngagement) {
            $graphMetrics += [
                'Reactions' => (int) $report->reactions,
                'Comments'  => (int) $report->comments,
                'Shares'    => (int) $report->shares,
                'Saves'     => (int) $report->saves,
            ];
        }

        $barSrc = \App\Support\Helpers::barGraph($graphMetrics);
        $pieSrc = $showEngagement ? \App\Support\Helpers::pie($engagementSlices) : null;

        $assignService = $reportAssignService;
        $obs       = $assignService?->key_observations ?? [];
        $highlight = $assignService?->performance_highlights ?? [];
        $summary   = $assignService?->monthly_summary ?? [];
        $plan      = $assignService?->next_month_plan ?? [];

        $typeLabel = collect($selectedTypes)->map(fn ($type) => match ($type) {
            'whatsapp_meta_ads' => 'WhatsApp Meta Ads',
            'lead_generation_ads' => 'Lead Generation',
            'social_media_engagement' => 'Engagement',
            default => $type,
        })->implode('  +  ');
    @endphp

    <div class="page {{ !$loop->last ? 'page-break' : '' }}">

        {{-- ---------- Report title ---------- --}}
        <div class="report-title">
            <div class="report-kicker">Monthly Performance Report</div>
            <h1>
                @if($isWhatsApp)        WhatsApp Meta Ads
                @elseif($isLeadGen)     Lead Generation Ads
                @elseif($isEngagement)  Social Media Engagement
                @elseif($isAll)         Combined Social Media Report
                @else                   Social Media Report
                @endif
            </h1>
            @if($isAll)
                <div class="report-sub">{{ $typeLabel }}</div>
            @endif
            <div class="title-rule"></div>
        </div>

        {{-- ---------- Document information ---------- --}}
        <table class="doc-info">
            <tr>
                <td><span class="di-label">Client</span><span class="di-value">{{ $clientName }}</span></td>
                <td><span class="di-label">Report date</span><span class="di-value">{{ $report->created_at?->format('d M Y') }}</span></td>
                <td>
                    <span class="di-label">Report type</span>
                    <span class="di-value">{{ (!$single && !empty($report->report_type_label)) ? $report->report_type_label : ($typeLabel ?: 'Social Media') }}</span>
                </td>
                <td><span class="di-label">Prepared by</span><span class="di-value">360 Degree info</span></td>
            </tr>
        </table>

        {{-- ============================================================
             OVERVIEW (first section)
             ============================================================ --}}
        @if($assignService)
            @php $sectionNo++; @endphp
            <div class="section">
                <h2 class="section-title">
                    <span class="sec-no">{{ $sectionNo }}.</span> Overview — {{ $assignService->title ?: 'Service #' . $assignService->id }}
                </h2>
                <table class="meta-grid">
                    <tr>
                        <td class="label">Platforms</td>
                        <td>{{ $assignService->platforms ?: '—' }}</td>
                        <td class="label">Campaign Objective</td>
                        <td>{{ $assignService->campaign_objective ?: '—' }}</td>
                    </tr>
                </table>
                @if($assignService->campaign_overview)
                    <div class="text-block"><span class="tb-label">Campaign Overview</span><span class="tb-value">{{ $assignService->campaign_overview }}</span></div>
                @endif
                @if($assignService->target_audience)
                    <div class="text-block"><span class="tb-label">Target Audience</span><span class="tb-value">{{ $assignService->target_audience }}</span></div>
                @endif
                @if($assignService->campaign_focus)
                    <div class="text-block"><span class="tb-label">Main Campaign Focus</span><span class="tb-value">{{ $assignService->campaign_focus }}</span></div>
                @endif
                @if($assignService->key_highlights)
                    <div class="text-block"><span class="tb-label">Key Highlights</span><span class="tb-value">{{ $assignService->key_highlights }}</span></div>
                @endif
            </div>
        @endif

        {{-- ============================================================
             EXECUTIVE SUMMARY
             ============================================================ --}}
        @if(count($headline) || !empty(array_filter($summary)))
            @php $sectionNo++; @endphp
            <div class="section">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Executive Summary</h2>

                @if(count($headline))
                    <table class="kpi-grid headline">
                        <tr>
                            @foreach($headline as $label => $value)
                                <td style="width:{{ $headlineWidth }}%;">
                                    <span class="kpi-value">{{ $value }}</span>
                                    <span class="kpi-label">{{ $label }}</span>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                @endif

                @if(!empty(array_filter($summary)))
                    <table class="meta-grid" style="margin-top:8px;">
                        @foreach([
                            'overall' => 'Overall Performance',
                            'enquiry' => 'Enquiry / Lead Generation',
                            'engagement' => 'Audience Engagement',
                            'brand_visibility' => 'Brand Visibility',
                        ] as $key => $label)
                            @if(!empty($summary[$key]))
                                <tr><td class="label">{{ $label }}</td><td>{{ $summary[$key] }}</td></tr>
                            @endif
                        @endforeach
                    </table>
                @endif
            </div>
        @endif

        {{-- ============================================================
             WHATSAPP META ADS
             ============================================================ --}}
        @if($showWhatsApp)
            @php $sectionNo++; @endphp
            <div class="section keep">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> WhatsApp Meta Ads Performance</h2>
                <table class="kpi-grid">
                    <tr>
                        <td><span class="kpi-value">{{ number_format((int) $report->total_conversations) }}</span><span class="kpi-label">Conversations</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->whatsapp_clicks) }}</span><span class="kpi-label">WhatsApp Clicks</span></td>
                        <td><span class="kpi-value">{{ number_format((float) $report->whatsapp_ctr, 2) }}%</span><span class="kpi-label">CTR</span></td>
                    </tr>
                    <tr>
                        <td><span class="kpi-value">{{ number_format((int) $report->reach) }}</span><span class="kpi-label">Reach</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->impressions) }}</span><span class="kpi-label">Impressions</span></td>
                        <td><span class="kpi-value">{{ number_format((float) $report->frequency, 2) }}</span><span class="kpi-label">Frequency</span></td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- ============================================================
             LEAD GENERATION
             ============================================================ --}}
        @if($showLeadGen)
            @php $sectionNo++; @endphp
            <div class="section keep">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Lead Generation Performance</h2>
                <table class="kpi-grid">
                    <tr>
                        <td><span class="kpi-value">{{ number_format((int) $report->total_leads) }}</span><span class="kpi-label">Total Leads</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->link_clicks) }}</span><span class="kpi-label">Link Clicks</span></td>
                        <td><span class="kpi-value">{{ number_format((float) $report->lead_ctr, 2) }}%</span><span class="kpi-label">CTR</span></td>
                    </tr>
                    <tr>
                        <td><span class="kpi-value">{{ number_format((int) $report->lead_reach) }}</span><span class="kpi-label">Reach</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->lead_impressions) }}</span><span class="kpi-label">Impressions</span></td>
                        <td><span class="kpi-value">{{ number_format((float) $report->lead_frequency, 2) }}</span><span class="kpi-label">Frequency</span></td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- ============================================================
             ENGAGEMENT
             ============================================================ --}}
        @if($showEngagement)
            @php $sectionNo++; @endphp
            <div class="section keep">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Social Media Engagement</h2>
                <table class="kpi-grid">
                    <tr>
                        <td><span class="kpi-value">{{ number_format((int) $report->total_engagement) }}</span><span class="kpi-label">Total Engagement</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->reactions) }}</span><span class="kpi-label">Reactions</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->comments) }}</span><span class="kpi-label">Comments</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->shares) }}</span><span class="kpi-label">Shares</span></td>
                    </tr>
                    <tr>
                        <td><span class="kpi-value">{{ number_format((int) $report->saves) }}</span><span class="kpi-label">Saves</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->profile_visits) }}</span><span class="kpi-label">Profile Visits</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->new_followers) }}</span><span class="kpi-label">New Followers</span></td>
                        <td><span class="kpi-value">{{ number_format((int) $report->impressions) }}</span><span class="kpi-label">Impressions</span></td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- ============================================================
             PERFORMANCE CHARTS
             ============================================================ --}}
        @if($barSrc || $pieSrc)
            @php $sectionNo++; @endphp
            <div class="section chart-section">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Performance Charts</h2>

                @if($barSrc)
                    <div class="metric-chart">
                        <h3>Metrics overview</h3>
                        <div class="chart-frame">
                            <img class="chart-img" src="{{ $barSrc }}" alt="Metrics overview bar chart">
                        </div>
                    </div>
                @endif

                @if($pieSrc)
                    <div class="metric-chart">
                        <h3>Engagement interaction mix</h3>
                        <div class="chart-frame">
                            <img class="pie-img" src="{{ $pieSrc }}" alt="Engagement interaction mix pie chart">
                            <div class="chart-legend">
                                @foreach($engagementSlices as $label => $value)
                                    <div class="chart-legend-item">
                                        <span class="legend-swatch"
                                              style="background:{{ $engagementColors[$loop->index % count($engagementColors)] }};"></span>
                                        {{ $label }}: {{ number_format($value) }}
                                        ({{ number_format($value / $engagementTotal * 100, 1) }}%)
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ---------- Campaign / Ad Set Results ---------- --}}
        @if(!empty($campaignRows))
            @php $sectionNo++; @endphp
            <div class="section">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Campaign / Ad Set Results</h2>
                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:35%;">Campaign / Ad Set</th>
                            <th style="width:15%;">Result</th>
                            <th style="width:15%;">Spend</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaignRows as $row)
                            <tr>
                                <td>{{ $row['name']   ?? '—' }}</td>
                                <td>{{ $row['result'] ?? '—' }}</td>
                                <td>{{ $row['spend']  ?? '—' }}</td>
                                <td>{{ $row['note']   ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ---------- Top Posts ---------- --}}
        @if($showTopPosts && !empty($topPosts))
            @php $sectionNo++; @endphp
            <div class="section">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Top Performing Posts</h2>
                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:55%;">Post</th>
                            <th style="width:20%;">Engagements</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topPosts as $post)
                            <tr>
                                <td>{{ $post['title']       ?? '—' }}</td>
                                <td>{{ $post['engagements'] ?? '—' }}</td>
                                <td>{{ $post['type']        ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ---------- Content Analysis ---------- --}}
        @if($showTopPosts && !empty(array_filter($content)))
            @php $sectionNo++; @endphp
            <div class="section keep">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Content Analysis</h2>
                <table class="meta-grid">
                    <tr>
                        <td class="label">Best Content Type</td>
                        <td>{{ $content['best_type'] ?? '—' }}</td>
                        <td class="label">Most Engaging Topic</td>
                        <td>{{ $content['engaging_topic'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Best Format</td>
                        <td>{{ $content['best_format'] ?? '—' }}</td>
                        <td class="label">Facebook vs Instagram</td>
                        <td>{{ $content['fb_vs_ig'] ?? '—' }}</td>
                    </tr>
                    @if(!empty($content['audience_response']))
                        <tr>
                            <td class="label">Audience Response</td>
                            <td colspan="3">{{ $content['audience_response'] }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        @endif

        {{-- ---------- Creative & Audience Performance ---------- --}}
        @if($showCreativeAudience && (!empty(array_filter($creative)) || !empty(array_filter($audience))))
            @php $sectionNo++; @endphp
            <div class="section">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Creative &amp; Audience Performance</h2>

                @if(!empty(array_filter($creative)))
                    <div class="sub-title">Creative</div>
                    <table class="meta-grid">
                        @if(!empty($creative['best_creatives'])) <tr><td class="label">Best Creatives</td><td>{{ $creative['best_creatives'] }}</td></tr> @endif
                        @if(!empty($creative['video_image']))    <tr><td class="label">Video / Image</td><td>{{ $creative['video_image'] }}</td></tr>    @endif
                        @if(!empty($creative['ad_copy']))        <tr><td class="label">Ad Copy</td><td>{{ $creative['ad_copy'] }}</td></tr>                  @endif
                        @if(!empty($creative['cta']))            <tr><td class="label">CTA</td><td>{{ $creative['cta'] }}</td></tr>                          @endif
                    </table>
                @endif

                @if(!empty(array_filter($audience)))
                    <div class="sub-title">Audience</div>
                    <table class="meta-grid">
                        @if(!empty($audience['audience']))   <tr><td class="label">Audience</td><td>{{ $audience['audience'] }}</td></tr>    @endif
                        @if(!empty($audience['age_group']))  <tr><td class="label">Age Group</td><td>{{ $audience['age_group'] }}</td></tr>  @endif
                        @if(!empty($audience['location']))   <tr><td class="label">Location</td><td>{{ $audience['location'] }}</td></tr>    @endif
                        @if(!empty($audience['placement']))  <tr><td class="label">Placement</td><td>{{ $audience['placement'] }}</td></tr>  @endif
                    </table>
                @endif
            </div>
        @endif

        {{-- ============================================================
             KEY OBSERVATIONS
             ============================================================ --}}
        @if(!empty(array_filter($obs)) || !empty(array_filter($highlight)))
            @php $sectionNo++; @endphp
            <div class="section">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Key Observations</h2>
                <table class="meta-grid">
                    @if(!empty($obs['creative_attention']))
                        <tr><td class="label">Creative Attention</td><td>{{ $obs['creative_attention'] }}</td></tr>
                    @endif
                    @if(!empty($obs['audience_response']))
                        <tr><td class="label">Audience Response</td><td>{{ $obs['audience_response'] }}</td></tr>
                    @endif
                    @if(!empty($obs['placement_interaction']))
                        <tr><td class="label">Placement Interaction</td><td>{{ $obs['placement_interaction'] }}</td></tr>
                    @endif
                    @if(!empty($highlight['notes']))
                        <tr><td class="label">Performance Highlights</td><td>{{ $highlight['notes'] }}</td></tr>
                    @endif
                </table>
            </div>
        @endif

        {{-- ============================================================
             NEXT MONTH PLAN
             ============================================================ --}}
        @if(!empty(array_filter($plan)))
            @php $sectionNo++; @endphp
            <div class="section">
                <h2 class="section-title"><span class="sec-no">{{ $sectionNo }}.</span> Recommendations &amp; Next Month Plan</h2>
                <table class="meta-grid">
                    @foreach([
                        'test_creatives' => 'Test New Creatives',
                        'audience_targeting' => 'Improve Audience Targeting',
                        'retargeting' => 'Retarget Interested Users',
                        'messaging' => 'Test Different Messaging',
                        'high_performers' => 'Focus on High-Performing Audiences',
                        'quality_leads' => 'Increase Quality Leads / Enquiries',
                    ] as $key => $label)
                        @if(!empty($plan[$key]))
                            <tr><td class="label">{{ $label }}</td><td>{{ $plan[$key] }}</td></tr>
                        @endif
                    @endforeach
                </table>
            </div>
        @endif

        {{-- ---------- Closing note ---------- --}}
        <div class="closing">
            Prepared by <strong>360 Degree info Pvt Ltd</strong> for {{ $clientName }}.
            Figures are taken from the platform dashboards at the time of reporting and may change slightly as platforms finalise their data.
            This document is confidential and intended for the named client only.
        </div>

    </div>
@endforeach

</body>
</html>