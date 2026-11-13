<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        /* =====================================================
           PAGE SETUP
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
            font-size: 12px;
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
        h2 {
            color: #0b2f7a;
            font-size: 15px;
            line-height: 1.3;
            margin: 0 0 10px;
            padding-bottom: 4px;
            border-bottom: 2px solid #e5e7eb;
            page-break-after: avoid;
        }

        p {
            margin: 0 0 10px;
            font-size: 13px;
        }

        ul {
            margin: 0 0 10px;
            padding: 0 0 0 22px;
        }

        li {
            margin: 0 0 5px;
            padding: 0;
            font-size: 13px;
        }

        /* =====================================================
           SECTIONS
           ===================================================== */
        .section {
            margin: 0 0 24px;
        }

        .meta {
            margin: 0 0 24px;
            padding: 14px 18px;
            background: #f8fafc;
            border-left: 4px solid #1f4e9c;
        }

        .meta p {
            margin: 3px 0;
            font-size: 13px;
            font-weight: bold;
            color: #0b2f7a;
        }

        .meta p:first-child {
            font-size: 15px;
            margin-bottom: 8px;
        }

        /* =====================================================
           BANNER IMAGE — FULL WIDTH
           ===================================================== */
        .banner-img {
            display: block;
            margin: 0 auto 24px;
            width: 100%;
            /* full printable width */
            height: auto;
            /* preserve aspect ratio */
        }

        /* =====================================================
           SCREENSHOT BLOCK — FULL COVER WIDTH
           ===================================================== */
        .shot {
            page-break-inside: avoid;
            text-align: center;
            margin: 0 0 24px;
        }

        .shot h2 {
            text-align: left;
        }

        .shot img {
            display: block;
            margin: 0 auto;
            width: 100%;
            height: auto;
        }

        .other-image-block {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
            margin: 0 0 24px;
        }

        .other-image-title {
            padding: 0 0 8px;
        }

        .other-image-title h2 {
            margin-bottom: 0;
        }

        .other-image-cell {
            text-align: center;
            padding: 0;
            font-size: 0;
            line-height: 0;
        }

        .other-image-cell img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
        }

        .ai-report-item {
            text-align: center;
            margin: 0 0 16px;
            page-break-inside: avoid;
        }

        .ai-report-item p {
            margin: 0 0 8px;
        }

        .ai-report-item img {
            display: block;
            margin: 0 auto;
            max-width: 480px;
            max-height: 420px;
            width: auto;
            height: auto;
        }

        /* =====================================================
           STATS ROW
           ===================================================== */
        .stats {
            width: 100%;
            border-collapse: collapse;
        }

        .stats td {
            color: #fff;
            padding: 12px 10px;
            font-size: 11px;
            text-align: center;
            border-right: 2px solid #fff;
        }

        .stats td:last-child {
            border-right: none;
        }

        .stats strong {
            font-size: 18px;
            display: block;
            margin-top: 4px;
        }

        /* =====================================================
           KEYWORD DETAIL PAGES
           ===================================================== */
        .kw-block {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .kw-title {
            text-align: left;
            padding: 0 0 8px;
            color: #0b2f7a;
            font-size: 15px;
            font-weight: bold;
            line-height: 20px;
        }

        .kw-title sup {
            font-size: 9px;
            line-height: 0;
        }

        /* no line box around the image, so no hidden baseline gap */
        .kw-shot {
            text-align: center;
            padding: 0;
            font-size: 0;
            line-height: 0;
        }

        .kw-shot img {
            margin: 0 auto;
        }

        /* the same fixed gap after every item */
        .kw-gap {
            height: 18px;
            font-size: 0;
            line-height: 0;
        }

        .keyword-heading {
            text-align: center;
            color: #0b2f7a;
            font-size: 15px;
            font-weight: bold;
            line-height: 20px;
            /* fixed, so sup can't change the height */
            margin: 0 0 8px;
            padding: 0;
            page-break-after: avoid;
        }

        .new-page {
            page-break-before: always;
        }

        /* =====================================================
           CONCLUSION — CENTERED
           ===================================================== */
        .conclusion-table {
            width: 100%;
            height: 740px;
        }

        .conclusion-table td {
            vertical-align: middle;
            padding: 230px 40px;
        }

        .conclusion-title {
            display: inline-block;
            margin: 0 0 18px;
            padding: 0 0 6px;
            font-size: 22px;
            color: #0b2f7a;
            border-bottom: 3px solid #1f4e9c;
        }

        .conclusion-text {
            font-size: 15px;
            line-height: 1.8;
            margin: 0;
        }

        img {
            max-width: 100%;
        }

        body.report-layout-compact {
            font-size: 11px;
        }

        body.report-layout-compact .meta,
        body.report-layout-compact .section {
            margin-bottom: 13px;
        }

        body.report-layout-compact .shot {
            margin-bottom: 13px;
        }

        body.report-layout-detailed {
            font-size: 13px;
        }

        body.report-layout-detailed .meta {
            padding: 18px 22px;
            border-left-width: 6px;
        }

        body.report-layout-detailed .section,
        body.report-layout-detailed .shot {
            margin-bottom: 30px;
        }

        body.report-layout-detailed .keyword-item {
            margin-bottom: 38px;
        }
    </style>
</head>

<body class="report-layout-{{ $layout ?? 'standard' }}">

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
        No. 34/29, 3rd Street, 200 Feet Radial Rd, Padmavathi Nagar, Chromepet, Tambaram, Tamil Nadu 600044
    </footer>

    @php
    $sectionOrder = [
    'details',
    'on_page',
    'seo_score',
    'keywords',
    'performance',
    'keyword_details',
    'backlinks',
    'ai_reports',
    'images',
    'conclusion',
    ];

    $ordinal = function ($n) {
    $n = (int) $n;
    if ($n <= 0) return '' ;
        $suffixes=['th','st','nd','rd','th','th','th','th','th','th'];
        if (($n % 100)>= 11 && ($n % 100) <= 13) {
            return $n . 'th' ;
            }
            return $n . $suffixes[$n % 10];
            };

            $pendingBreak=false;

            /* -----------------------------------------------------
            IMAGE FITTER — FULL WIDTH
            ----------------------------------------------------- */
            $fitImage=function ($relPath, $maxW=660, $maxH=null) {
            if (empty($relPath)) return null;
            $full=storage_path('app/public/' . $relPath);
            if (!is_file($full)) return null;

            $size=@getimagesize($full);
            if (!$size || empty($size[0]) || empty($size[1])) {
            return ['path'=> $full, 'w' => $maxW, 'h' => $maxH ?? 360];
            }

            if ($maxH === null) {
            $maxH = 900;
            }

            $ratio = min($maxW / $size[0], $maxH / $size[1]);
            return [
            'path' => $full,
            'w' => (int) round($size[0] * $ratio),
            'h' => (int) round($size[1] * $ratio),
            ];
            };
            @endphp

            @foreach($sectionOrder as $section)
            @switch($section)

            {{-- ===================== REPORT DETAILS ===================== --}}
            @case('details')
            <div class="meta">
                <p>Month SEO Report</p>
                <p>Client: {{ $report->client->name }}</p>
                <p>Website: {{ $report->client->website }}</p>
                <p>Reporting Period:
                    @php
                    try {
                    echo \Carbon\Carbon::parse($report->reporting_period . '-01')->format('F Y');
                    } catch (\Throwable $e) {
                    echo e($report->reporting_period);
                    }
                    @endphp
                </p>
                <p>Prepared by: [{{ $report->prepared_by }}]</p>
            </div>

            <div class="section">
                <h2>Overview</h2>
                <p>{!! nl2br(e($report->overview)) !!}</p>
            </div>

            @php $bannerPath = public_path('assets/seo-monthly-report.png'); @endphp
            @if(file_exists($bannerPath))
            {{-- Full-width banner image --}}
            <img src="{{ $bannerPath }}" alt="SEO Monthly Report" class="banner-img">
            @endif
            @break

            {{-- ===================== ON-PAGE SEO (starts on new page) ===================== --}}
            @case('on_page')
            @if(!empty($report->on_page_items))
            {{-- Force this section to start on a fresh page --}}
            <div class="section new-page">
                <h2>ON-PAGE SEO</h2>
                <ul>
                    @foreach($report->on_page_items as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @break

            {{-- ===================== SEO SCORE ===================== --}}
            @case('seo_score')
            @php $scoreFit = $fitImage($report->seo_score_image ?? null); @endphp
            @if($scoreFit)
            <div class="shot">
                <h2>SEO Score</h2>
                <img src="{{ $scoreFit['path'] }}"
                    style="width: {{ $scoreFit['w'] }}px; height: {{ $scoreFit['h'] }}px;"
                    alt="SEO Score">
            </div>
            @endif
            @break

            {{-- ===================== TARGETING KEYWORDS LIST ===================== --}}
            @case('keywords')
            @if(!empty($report->keywords))
            <div class="section">
                <h2>Targeting Keywords</h2>
                <ul>
                    @foreach($report->keywords as $kw)
                    @php $keywordText = is_array($kw) ? ($kw['keyword'] ?? '') : $kw; @endphp
                    @if($keywordText)
                    <li>{{ $keywordText }}</li>
                    @endif
                    @endforeach
                </ul>
            </div>
            @endif
            @break

            {{-- ===================== TARGET PERFORMANCE OF GOOGLE ===================== --}}
            @case('performance')
            @if(!empty($report->performance_images))
            @foreach($report->performance_images as $img)
            @php $fit = $fitImage($img['path'] ?? null); @endphp
            @if($fit)
            <div class="shot">
                @if(!empty($img['title']))
                <h2>{{ $img['title'] }}</h2>
                @endif
                <img src="{{ $fit['path'] }}"
                    style="width: {{ $fit['w'] }}px; height: {{ $fit['h'] }}px;"
                    alt="">
            </div>
            @endif
            @endforeach
            @elseif(($report->total_clicks ?? 0) > 0 || ($report->total_impressions ?? 0) > 0)
            <div class="section">
                <h2>Target Performance of Google</h2>
                <table class="stats" cellspacing="0">
                    <tr>
                        <td style="background:#3b82f6">Total clicks<strong>{{ number_format($report->total_clicks) }}</strong></td>
                        <td style="background:#7c3aed">Total impressions<strong>{{ number_format($report->total_impressions) }}</strong></td>
                        <td style="background:#0d9488">Average CTR<strong>{{ $report->average_ctr }}%</strong></td>
                        <td style="background:#f97316">Average position<strong>{{ $report->average_position }}</strong></td>
                    </tr>
                </table>
            </div>
            @endif
            @break

            {{-- ===================== KEYWORD TARGET PAGE, POSITION & SCREENSHOT ===================== --}}
            @case('keyword_details')
            @php
            $keywordItems = collect($report->keywords ?? [])
            ->map(function ($kw) use ($ordinal, $fitImage) {
            $text = is_array($kw) ? ($kw['keyword'] ?? '') : $kw;
            $page = is_array($kw) ? ($kw['page'] ?? null) : null;
            $position = is_array($kw) ? ($kw['position'] ?? null) : null;
            $imagePath = is_array($kw) ? ($kw['image_existing'] ?? null) : null;

            return [
            'text' => $text,
            'page' => $page ? $ordinal($page) : '',
            'position' => $position ? $ordinal($position) : '',
            // fit inside 600 x 240 box, any upload size
            'fit' => $fitImage($imagePath, 600, 240),
            ];
            })
            ->filter(fn ($k) => $k['text'] || $k['fit'])
            ->values();
            @endphp

            @foreach($keywordItems as $item)
            <table class="kw-block" cellspacing="0" cellpadding="0">
                @if($item['text'])
                <tr>
                    <td class="kw-title">
                        {{ $item['text'] }}
                        @if($item['page'])
                        <sup>{{ $item['page'] }}</sup> page
                        @endif
                        @if($item['position'])
                        <sup>{{ $item['position'] }}</sup> position
                        @endif
                    </td>
                </tr>
                @endif

                @if($item['fit'])
                <tr>
                    <td class="kw-shot">
                        <img src="{{ $item['fit']['path'] }}"
                            width="{{ $item['fit']['w'] }}"
                            height="{{ $item['fit']['h'] }}"
                            alt="{{ $item['text'] }}">
                    </td>
                </tr>
                @endif
            </table>
            <div class="kw-gap"></div>
            @endforeach
            @break

            {{-- ===================== BACKLINKS SUBMISSION ===================== --}}
            @case('backlinks')
            @if(!empty($report->backlinks_items))
            <div class="section {{ $pendingBreak ? 'new-page' : '' }}">
                @php $pendingBreak = false; @endphp
                <h2>BACKLINKS SUBMISSION</h2>
                <ul>
                    @foreach($report->backlinks_items as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @break

            {{-- ===================== AI REPORT ===================== --}}
            @case('ai_reports')
            @if(!empty($report->ai_reports))
            @php
            $aiReportItems = collect($report->ai_reports)
                ->map(fn ($item) => [
                    'item' => $item,
                    'fit' => $fitImage($item['path'] ?? null, 480, 420),
                ])
                ->filter(fn ($entry) => $entry['fit'])
                ->values();
            @endphp
            @if($aiReportItems->isNotEmpty())
            <div class="section ai-report-section">
                <h2>AI Report</h2>
                @foreach($aiReportItems as $entry)
                @php $item = $entry['item']; $fit = $entry['fit']; @endphp
                <div class="ai-report-item">
                    @if(!empty($item['keyword']))
                    <p><strong>Keyword:</strong> {{ $item['keyword'] }}</p>
                    @endif
                    <img src="{{ $fit['path'] }}"
                        style="width: {{ $fit['w'] }}px; height: {{ $fit['h'] }}px;"
                        alt="">
                </div>
                @endforeach
            </div>
            @endif
            @endif
            @break

            {{-- ===================== OTHER SCREENSHOTS / IMAGES ===================== --}}
            @case('images')
            @if(!empty($report->images))
            @foreach($report->images as $img)
            @php $fit = $fitImage($img['path'] ?? null, 520, 560); @endphp
            @if($fit)
            <table class="other-image-block" cellspacing="0" cellpadding="0">
                @if(!empty($img['title']))
                <tr>
                    <td class="other-image-title">
                        <h2>{{ $img['title'] }}</h2>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="other-image-cell">
                        <img src="{{ $fit['path'] }}"
                            width="{{ $fit['w'] }}"
                            height="{{ $fit['h'] }}"
                            alt="">
                    </td>
                </tr>
            </table>
            @endif
            @endforeach
            @endif
            @break

            {{-- ===================== CONCLUSION (own page, centered) ===================== --}}
            @case('conclusion')
            @if(!empty($report->conclusion))
            <div class="new-page">
                <table class="conclusion-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                            <h2 class="conclusion-title">Conclusion &amp; Thanks</h2>
                            <p class="conclusion-text">{!! nl2br(e($report->conclusion)) !!}</p>
                        </td>
                    </tr>
                </table>
            </div>
            @endif
            @break

            @endswitch
            @endforeach

</body>

</html>