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

        p {
            margin: 0 0 10px;
            font-size: 15px;
        }

        ul {
            margin: 0 0 10px 18px;
            padding: 0;
        }

        li {
            margin-bottom: 5px;
            margin-left: 20px;
            font-size: 15px;
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
            height: auto;
            margin: 10px auto 0;
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

        /* ---------- Keyword detail (2 per page) ---------- */
        /*
           Printable area (A4): ~660px wide x ~856px high.
           Each item = heading (~60px) + image (324px) + spacing  ≈ 400px
           → 2 items fit on one page.
        */
        .keyword-item {
            text-align: center;          /* centers heading + image */
            height: 400px;               /* equal slot for every item */
            overflow: hidden;
        }

        .keyword-heading {
            color: #0b2f7a;
            font-size: 22px;             /* increased (medium) */
            font-weight: bold;
            text-align: center;
            margin: 0 0 12px;
            line-height: 1.4;
        }

        .keyword-heading sup {
            font-size: 14px;
            font-weight: bold;
        }

        /* Same width & height for every keyword screenshot */
        .keyword-image {
            width: 540px;
            height: 324px;
            margin: 0 auto;
            display: block;
        }

        /* ---------- Screenshot block: title + image always stay together ---------- */
        .shot {
            page-break-inside: avoid;
            text-align: center;
            margin: 0 0 18px;
        }

        .shot h2 {
            text-align: left;
            margin: 0 0 10px;
        }

        .shot img {
            display: block;
            margin: 0 auto;
        }

        /* ---------- Page break ---------- */
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
        <h1 style="text-align: center;">360 Degree info Pvt Ltd</h1>
    </header>

    <footer>
        #34/29, 3rd Street, 200ft Radial Road Chromepet, Chennai-44.<br>
        +919884682652, 8124877711. info@360degreeinfo.com
    </footer>

    @php
        // Same order as the form. Move items to change the PDF order.
        $sectionOrder = [
            'details',
            'on_page',
            'seo_score',
            'keywords',
            'performance',
            'keyword_details',
            'backlinks',
            'images',
            'conclusion',
        ];

        $ordinal = function ($n) {
            $n = (int) $n;
            if ($n <= 0) return '';
            $suffixes = ['th','st','nd','rd','th','th','th','th','th','th'];
            if (($n % 100) >= 11 && ($n % 100) <= 13) {
                return $n . 'th';
            }
            return $n . $suffixes[$n % 10];
        };

        // After the keyword pages, the next section that has content starts on a new page.
        // (Avoids a blank trailing page when nothing follows.)
        $pendingBreak = false;

        // Scales an image to fit inside a max box (keeps aspect ratio, no distortion).
        // dompdf ignores object-fit / max-height, so we compute exact pixel sizes here.
        // Printable area is ~660 x 856px; 660 x 360 lets two screenshots share a page.
        $fitImage = function ($relPath, $maxW = 660, $maxH = 360) {
            if (empty($relPath)) return null;
            $full = storage_path('app/public/' . $relPath);
            if (!is_file($full)) return null;

            $size = @getimagesize($full);
            if (!$size || empty($size[0]) || empty($size[1])) {
                return ['path' => $full, 'w' => $maxW, 'h' => $maxH];
            }

            $ratio = min($maxW / $size[0], $maxH / $size[1]);
            return [
                'path' => $full,
                'w'    => (int) round($size[0] * $ratio),
                'h'    => (int) round($size[1] * $ratio),
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
                                echo $report->reporting_period;
                            }
                        @endphp
                    </p>
                    <p>Prepared by: [{{ $report->prepared_by }}]</p>
                </div>

                <div class="section">
                    <h2 style="margin-top:0;">Overview</h2>
                    <p>{!! nl2br(e($report->overview)) !!}</p>
                </div>

                @php $bannerPath = public_path('assets/seo-monthly-report.png'); @endphp
                @if(file_exists($bannerPath))
                    <img src="{{ $bannerPath }}" alt="SEO Monthly Report" class="banner-img">
                @endif
                @break

            {{-- ===================== ON-PAGE SEO ===================== --}}
            @case('on_page')
                <div class="section" style="margin-top: 35px;">
                    <h2>ON-PAGE SEO</h2>
                    <ul>
                        @foreach($report->on_page_items ?? [] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                @break

            {{-- ===================== SEO SCORE ===================== --}}
            @case('seo_score')
                @if($report->seo_score_image)
                    <div class="section">
                        <h2>SEO Score</h2>
                        <img src="{{ storage_path('app/public/' . $report->seo_score_image) }}" class="score-img" alt="SEO Score">
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
                    <div style="margin-top: 25px;">
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
                    </div>
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

            {{-- ===================== KEYWORD DETAIL PAGES (2 items per page) ===================== --}}
            @case('keyword_details')
                @php
                    // Keep only keywords that have a title or an image
                    $keywordItems = collect($report->keywords ?? [])
                        ->map(function ($kw) use ($ordinal) {
                            $text      = is_array($kw) ? ($kw['keyword'] ?? '') : $kw;
                            $page      = is_array($kw) ? ($kw['page'] ?? null) : null;
                            $position  = is_array($kw) ? ($kw['position'] ?? null) : null;
                            $imagePath = is_array($kw) ? ($kw['image_existing'] ?? null) : null;

                            return [
                                'text'     => $text,
                                'page'     => $page ? $ordinal($page) : '',
                                'position' => $position ? $ordinal($position) : '',
                                'image'    => $imagePath,
                            ];
                        })
                        ->filter(fn ($k) => $k['text'] || $k['image'])
                        ->values();
                @endphp

                @foreach($keywordItems->chunk(2) as $pair)
                    {{-- every pair starts on a new page --}}
                    <div class="page-break"></div>

                    @foreach($pair as $item)
                        <div class="keyword-item">
                            @if($item['text'])
                                <div class="keyword-heading">
                                    {{ $item['text'] }}
                                    @if($item['page'])
                                        <sup>{{ $item['page'] }}</sup> page
                                    @endif
                                    @if($item['position'])
                                        <sup>{{ $item['position'] }}</sup> position
                                    @endif
                                </div>
                            @endif

                            @if($item['image'])
                                <img class="keyword-image"
                                     src="{{ storage_path('app/public/' . $item['image']) }}"
                                     alt="{{ $item['text'] }}">
                            @endif
                        </div>
                    @endforeach
                @endforeach

                @php $pendingBreak = $keywordItems->isNotEmpty(); @endphp
                @break

            {{-- ===================== BACKLINKS SUBMISSION ===================== --}}
            @case('backlinks')
                @if(!empty($report->backlinks_items))
                    @if($pendingBreak)
                        <div class="page-break"></div>
                        @php $pendingBreak = false; @endphp
                    @endif
                    <div class="section">
                        <h2 style="margin-top:0;">BACKLINKS SUBMISSION</h2>
                        <ul>
                            @foreach($report->backlinks_items as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @break

            {{-- ===================== OTHER SCREENSHOTS / IMAGES ===================== --}}
            @case('images')
                @if(!empty($report->images))
                    @if($pendingBreak)
                        <div class="page-break"></div>
                        @php $pendingBreak = false; @endphp
                    @endif
                    @foreach($report->images as $img)
                        @php $fit = $fitImage($img['path'] ?? null); @endphp
                        @if($fit)
                            <div class="shot" style="margin-top: 25px;">
                                @if(!empty($img['title']))
                                    <h2>{{ $img['title'] }}</h2>
                                @endif
                                <img src="{{ $fit['path'] }}"
                                     style="width: {{ $fit['w'] }}px; height: {{ $fit['h'] }}px;"
                                     alt="">
                            </div>
                        @endif
                    @endforeach
                @endif
                @break

            {{-- ===================== CONCLUSION ===================== --}}
            @case('conclusion')
                @if(!empty($report->conclusion))
                    @if($pendingBreak)
                        <div class="page-break"></div>
                        @php $pendingBreak = false; @endphp
                    @endif
                    <div class="section">
                        <h2>Conclusion &amp; Thanks</h2>
                        <p>{!! nl2br(e($report->conclusion)) !!}</p>
                    </div>
                @endif
                @break

        @endswitch
    @endforeach

</body>

</html>