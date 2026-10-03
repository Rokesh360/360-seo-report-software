<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 110px 50px 90px 50px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            line-height: 1.5;
        }

        /* ---------- Header / Footer (unchanged) ---------- */
        header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
            height: 80px;
        }

        header img {
            height: 55px;
            float: left;
        }

        header h1 {
            margin: 12px 0 0 90px;
            font-size: 22px;
            color: #1f4e9c;
        }

        footer {
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
        }

        /* ---------- Typography ---------- */
        h2 {
            color: #0b2f7a;
            font-size: 15px;
            margin: 18px 0 10px;
            padding-bottom: 4px;
            border-bottom: 2px solid #e5e7eb;
        }

        h2:first-child {
            margin-top: 0;
        }

        p {
            margin: 0 0 10px;
        }

        ul {
            margin: 0 0 10px 18px;
            padding: 0;
        }

        li {
            margin-bottom: 5px;
        }

        /* ---------- Meta block ---------- */
        .meta {
            margin-bottom: 25px;
            padding: 14px 18px;
            background: #f8fafc;
            border-left: 4px solid #1f4e9c;
            border-radius: 4px;
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

        /* ---------- Sections ---------- */
        .section {
            margin-bottom: 20px;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        /* ---------- Banner image ---------- */
        .banner-img {
            display: block;
            margin: 15px auto;
            max-width: 100%;
            height: auto;
        }

        /* ---------- Score image ---------- */
        .score-img {
            display: block;
            width: 100%;
            margin: 10px auto 0;
            max-height: 400px;
            object-fit: contain;
        }

        /* ---------- Stats row ---------- */
        .stats {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 0;
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

        /* ---------- Keyword heading ---------- */
        .keyword-heading {
            color: #0b2f7a;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0 15px;
            line-height: 1.4;
        }

        .keyword-heading sup {
            font-size: 12px;
            font-weight: bold;
            vertical-align: super;
        }

        .keyword-image {
            display: block;
            margin: 0 auto 20px;
            max-width: 100%;
            max-height: 500px;
        }

        /* ---------- Page break (used ONLY for keyword detail pages) ---------- */
        .page-break {
            page-break-before: always;
        }

        /* ---------- Generic images ---------- */
        img {
            max-width: 100%;
        }
    </style>
</head>

<body>

    <header>
        <img src="{{ public_path('assets/360degreeinfo.webp') }}" alt="360 Info">
        <h1>360 Degree info Pvt Ltd</h1>
    </header>

    <footer>
        #34/29, 3rd Street, 200ft Radial Road Chromepet, Chennai-44.<br>
        +919884682652, 8124877711. info@360degreeinfo.com
    </footer>

    {{-- ===================== PAGE 1: META + OVERVIEW + BANNER ===================== --}}
    <div class="meta">
        <p>Month SEO Report</p>
        <p>Client: {{ $report->client->name }}</p>
        <p>Website: {{ $report->client->website }}</p>
        <p>Reporting Period:
            @php
                try {
                    echo \Carbon\Carbon::parse($report->reporting_period . '-01')->format('F Y');
                } catch (\Throwable $e) {
                    echo $report->reporting_period;
                }
            @endphp
        </p>
        <p>Prepared by: [{{ $report->prepared_by }}]</p>
    </div>

    <div class="section">
        <h2>Overview</h2>
        <p>{!! nl2br(e($report->overview)) !!}</p>
    </div>

    @php
        $bannerPath = public_path('assets/seo-monthly-report.png');
    @endphp
    @if(file_exists($bannerPath))
        <img src="{{ $bannerPath }}" alt="SEO Monthly Report" class="banner-img">
    @endif

    {{-- ===================== ON-PAGE SEO + SEO SCORE (continuous) ===================== --}}
    <div class="section" style="margin-top: 25px;">
        <h2>ON-PAGE SEO</h2>
        <ul>
            @foreach($report->on_page_items ?? [] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>

    @if($report->seo_score_image)
        <div class="section">
            <h2>SEO Score</h2>
            <img src="{{ storage_path('app/public/' . $report->seo_score_image) }}" class="score-img" alt="SEO Score">
        </div>
    @endif

    {{-- ===================== TARGETING KEYWORDS LIST (continuous) ===================== --}}
    @if(!empty($report->keywords))
        <div class="section">
            <h2>Targeting Keywords</h2>
            <ul>
                @foreach($report->keywords as $kw)
                    @php
                        $keywordText = is_array($kw) ? ($kw['keyword'] ?? '') : $kw;
                    @endphp
                    @if($keywordText)
                        <li>{{ $keywordText }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== KEYWORD DETAIL PAGES (each on its own page) ===================== --}}
    @php
        $ordinal = function ($n) {
            $n = (int) $n;
            if ($n <= 0) return '';
            $suffixes = ['th','st','nd','rd','th','th','th','th','th','th'];
            if (($n % 100) >= 11 && ($n % 100) <= 13) {
                return $n . 'th';
            }
            return $n . $suffixes[$n % 10];
        };
    @endphp

    @forelse($report->keywords ?? [] as $kw)
        @php
            $keywordText = is_array($kw) ? ($kw['keyword'] ?? '') : $kw;
            $page        = is_array($kw) ? ($kw['page'] ?? null) : null;
            $position    = is_array($kw) ? ($kw['position'] ?? null) : null;
            $imagePath   = is_array($kw) ? ($kw['image_existing'] ?? null) : null;

            $pageText     = $page     ? $ordinal($page)     : '';
            $positionText = $position ? $ordinal($position) : '';
        @endphp

        <div class="page-break"></div>

        @if($keywordText)
            <div class="keyword-heading">
                {{ $keywordText }}
                @if($pageText)
                    <sup>{{ $pageText }}</sup> page
                @endif
                @if($positionText)
                    <sup>{{ $positionText }}</sup> position
                @endif
            </div>
        @endif

        @if($imagePath)
            <img class="keyword-image"
                 src="{{ storage_path('app/public/' . $imagePath) }}"
                 alt="{{ $keywordText }}">
        @endif
    @empty
        <div class="page-break"></div>
        <p>No targeting keywords added.</p>
    @endforelse

    {{-- ===================== GOOGLE PERFORMANCE + BACKLINKS + CONCLUSION (all continuous) ===================== --}}
    <div class="page-break"></div>

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

    @if(!empty($report->backlinks_items))
        <div class="section">
            <h2>BACKLINKS SUBMISSION</h2>
            <ul>
                @foreach($report->backlinks_items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- General uploaded images (each on its own page, no break before heading) --}}
    @foreach($report->images ?? [] as $img)
        <div class="section" style="margin-top: 25px;">
            @if(!empty($img['title']))
                <h2>{{ $img['title'] }}</h2>
            @endif
            <img src="{{ storage_path('app/public/' . $img['path']) }}"
                 style="display:block; margin: 10px auto 0; width:100%; max-height:600px; object-fit:contain;"
                 alt="">
        </div>
    @endforeach

    @if(!empty($report->conclusion))
        <div class="section">
            <h2>Conclusion &amp; Thanks</h2>
            <p>{!! nl2br(e($report->conclusion)) !!}</p>
        </div>
    @endif

</body>

</html>