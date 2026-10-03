@php
$v = fn ($key, $default = '') => old($key, $report?->{$key} ?? $default);

// -------- Reporting period: split stored value into month + year --------
$storedPeriod = old('reporting_period', $report?->reporting_period ?? '');

$periodMonth = '';
$periodYear = '';

if ($storedPeriod) {
if (preg_match('/^(\d{4})-(\d{2})$/', $storedPeriod, $m)) {
$periodYear = (int) $m[1];
$periodMonth = (int) $m[2];
} elseif (preg_match('/^([A-Za-z]+)\s+(\d{4})$/', $storedPeriod, $m)) {
$periodYear = (int) $m[2];
$periodMonth = (int) date('n', strtotime($m[1] . ' 1 ' . $m[2]));
}
}

$monthOptions = [
1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
];

$currentYear = (int) date('Y');
$yearOptions = range($currentYear - 5, $currentYear + 5);

// -------- On-Page options --------
$defaultOnPageOptions = [
'Targeting Keyword', 'Competitor Keyword Analysing', 'Google Search Console Analysing',
'Google Analytics', 'Google index', 'Meta tags update all pages.',
'Internal and external link', 'URL Structure',
];
$customOnPage = old('custom_on_page_items', $report?->custom_on_page_items ?? []);
$onPageOptions = array_unique(array_merge($defaultOnPageOptions, $customOnPage));
$selectedOnPage = old('on_page_items', $report?->on_page_items ?? $defaultOnPageOptions);

// -------- Backlinks Submission options --------
$defaultBacklinksOptions = [
'do-follow backlinks',
'No-Follow Backlinks',
'Guest Post Backlinks',
'Directory Backlinks',
'Social Profile Backlinks',
'Image Backlinks',
'Profile Backlinks',
];
$customBacklinks = old('custom_backlinks_items', $report?->custom_backlinks_items ?? []);
$backlinksOptions = array_unique(array_merge($defaultBacklinksOptions, $customBacklinks));
$selectedBacklinks = old('backlinks_items', $report?->backlinks_items ?? $defaultBacklinksOptions);

// -------- Keywords --------
$keywords = old('keywords', $report
? collect($report->keywords)->map(fn ($k) => [
'keyword' => is_array($k) ? ($k['keyword'] ?? '') : $k,
'page' => is_array($k) ? ($k['page'] ?? null) : null,
'position' => is_array($k) ? ($k['position'] ?? null) : null,
'image_existing' => is_array($k) ? ($k['image_existing'] ?? null) : null,
])->all()
: [['keyword' => '', 'page' => null, 'position' => null, 'image_existing' => null]]);

// -------- Images --------
$images = old('images', $report
? collect($report->images)->map(fn ($i) => ['title' => $i['title'], 'existing' => $i['path']])->all()
: []);

$pageOptions = range(1, 10);
$positionOptions = range(1, 10);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="admin-page report-page">
    <div class="admin-shell">
        @include('admin.partials.sidebar', ['active' => 'reports'])
        <main class="admin-main">
            @include('admin.partials.topbar', ['pageTitle' => $title])
            <div class="dashboard-content report-content">
                <section class="page-heading report-page-heading">
                    <div>
                        <h1>{{ $title }}</h1>
                        <p class="page-subtitle">Add the monthly performance details, search data, and supporting screenshots.</p>
                    </div>
                    <a href="{{ route('seo-reports.index') }}" class="button button-secondary">Back to reports</a>
                </section>

                <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="report-form" novalidate>
                    @csrf
                    @if($method !== 'POST') @method($method) @endif

                    {{-- Basic details --}}
                    <div class="card mb-3">
                        <div class="card-header">Report Details</div>
                        <div class="card-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Client</label>
                                <select name="client_id" id="client_id" class="form-select @error('client_id') is-invalid @enderror">
                                    <option value="">-- Select Client --</option>
                                    @foreach($clients as $c)
                                    <option value="{{ $c->id }}" data-website="{{ $c->website }}"
                                        @selected($v('client_id')==$c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input name="website" id="client_website" class="form-control @error('website') is-invalid @enderror" value="{{ $v('website') }}" readonly>
                                @error('website')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reporting Period</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <select name="reporting_period_month" id="reporting_period_month"
                                            class="form-select @error('reporting_period') is-invalid @enderror">
                                            <option value="">-- Month --</option>
                                            @foreach($monthOptions as $num => $name)
                                            <option value="{{ $num }}" @selected((int) $periodMonth===$num)>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <select name="reporting_period_year" id="reporting_period_year"
                                            class="form-select @error('reporting_period') is-invalid @enderror">
                                            <option value="">-- Year --</option>
                                            @foreach($yearOptions as $year)
                                            <option value="{{ $year }}" @selected((int) $periodYear===(int) $year)>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <input type="hidden" name="reporting_period" id="reporting_period"
                                    value="{{ $storedPeriod }}">

                                @error('reporting_period')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prepared by</label>
                                <input name="prepared_by" class="form-control @error('prepared_by') is-invalid @enderror" readonly value="{{ $v('prepared_by', '360DegreeInfo SEO Team') }}">
                                @error('prepared_by')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Overview</label>
                                <textarea name="overview" rows="4" class="form-control @error('overview') is-invalid @enderror">{{ $v('overview') }}</textarea>
                                @error('overview')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- On-page SEO --}}
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>On-Page SEO</span>
                        </div>
                        <div class="card-body row" id="on-page-items-container">
                            @foreach($onPageOptions as $opt)
                            <div class="col-md-4 on-page-item">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input @error('on_page_items') is-invalid @enderror" name="on_page_items[]" value="{{ $opt }}"
                                        @checked(in_array($opt, $selectedOnPage))>
                                    <span class="form-check-label">{{ $opt }}</span>
                                </label>
                            </div>
                            @endforeach
                            @error('on_page_items')
                            <div class="col-12"><span class="field-error">{{ $message }}</span></div>
                            @enderror
                        </div>
                        <div class="card-footer">
                            <div class="input-group" style="max-width: 400px;">
                                <input type="text" id="custom-on-page-input" class="form-control" placeholder="Add custom on-page item...">
                                <button type="button" class="btn btn-outline-primary" onclick="addOnPageCustomFromInput()">Add</button>
                            </div>
                        </div>
                    </div>

                    {{-- SEO Score --}}
                    <div class="card mb-3">
                        <div class="card-header">SEO Score (upload image)</div>
                        <div class="card-body">
                            <input type="file" name="seo_score_image" accept="image/*" class="form-control @error('seo_score_image') is-invalid @enderror" onchange="previewScore(this)">
                            @error('seo_score_image')
                            <span class="field-error">{{ $message }}</span>
                            @enderror
                            <img id="score-preview" class="mt-2" style="max-height:160px; {{ $report?->seo_score_image ? '' : 'display:none' }}"
                                src="{{ $report?->seo_score_image ? asset('storage/'.$report->seo_score_image) : '' }}" alt="">
                            @if($report?->seo_score_image)
                            <div class="form-text">Leave empty to keep the current image.</div>
                            @endif
                        </div>
                    </div>

                    {{-- Targeting Keywords --}}
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Targeting Keywords</span>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addKeyword()">+ Add Keyword</button>
                        </div>

                        <div class="card-body" id="keyword-rows">
                            @foreach($keywords as $i => $row)
                            <div class="keyword-row border rounded p-3 mb-3 bg-light" data-index="{{ $i }}">

                                {{-- Row header --}}
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-secondary">Keyword #<span class="kw-number">{{ $i + 1 }}</span></span>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="removeKeywordRow(this)"
                                        title="Remove keyword">
                                        &times; Remove
                                    </button>
                                </div>

                                {{-- Row body --}}
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Keyword</label>
                                        <input name="keywords[{{ $i }}][keyword]"
                                            class="form-control keyword-input @error(" keywords.{$i}.keyword") is-invalid @enderror"
                                            placeholder="Enter keyword"
                                            value="{{ $row['keyword'] ?? '' }}">
                                        @error("keywords.{$i}.keyword")
                                        <span class="field-error d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label mb-1">Target Page</label>
                                        <select name="keywords[{{ $i }}][page]"
                                            class="form-select @error(" keywords.{$i}.page") is-invalid @enderror">
                                            <option value="">-- Page --</option>
                                            @foreach($pageOptions as $p)
                                            <option value="{{ $p }}" @selected((int) ($row['page'] ?? 0)===$p)>
                                                {{ $p }}{{ $p == 1 ? 'st' : ($p == 2 ? 'nd' : ($p == 3 ? 'rd' : 'th')) }} Page
                                            </option>
                                            @endforeach
                                        </select>
                                        @error("keywords.{$i}.page")
                                        <span class="field-error d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label mb-1">Target Position</label>
                                        <select name="keywords[{{ $i }}][position]"
                                            class="form-select @error(" keywords.{$i}.position") is-invalid @enderror">
                                            <option value="">-- Position --</option>
                                            @foreach($positionOptions as $pos)
                                            <option value="{{ $pos }}" @selected((int) ($row['position'] ?? 0)===$pos)>
                                                {{ $pos }}{{ $pos == 1 ? 'st' : ($pos == 2 ? 'nd' : ($pos == 3 ? 'rd' : 'th')) }} Position
                                            </option>
                                            @endforeach
                                        </select>
                                        @error("keywords.{$i}.position")
                                        <span class="field-error d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Screenshot file only --}}
                                <div class="row g-2 mt-2 align-items-center keyword-image-row">
                                    <div class="col-md-10">
                                        <label class="form-label mb-1 small text-muted">Screenshot file</label>
                                        <input type="file"
                                            name="keywords[{{ $i }}][image_file]"
                                            accept="image/*"
                                            class="form-control">
                                        @if(!empty($row['image_existing']))
                                        <input type="hidden" name="keywords[{{ $i }}][image_existing]" value="{{ $row['image_existing'] }}">
                                        @endif
                                    </div>
                                    <div class="col-md-2 text-center">
                                        @if(!empty($row['image_existing']))
                                        <img src="{{ asset('storage/'.$row['image_existing']) }}"
                                            style="height:45px; border-radius:4px;"
                                            alt="screenshot">
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            @error('keywords')
                            <span class="field-error d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Google performance --}}
                    <div class="card mb-3">
                        <div class="card-header">Target Performance of Google</div>
                        <div class="card-body row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Total clicks</label>
                                <input type="number" name="total_clicks" class="form-control @error('total_clicks') is-invalid @enderror" value="{{ $v('total_clicks', 0) }}">
                                @error('total_clicks')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Total impressions</label>
                                <input type="number" name="total_impressions" class="form-control @error('total_impressions') is-invalid @enderror" value="{{ $v('total_impressions', 0) }}">
                                @error('total_impressions')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Average CTR (%)</label>
                                <input type="number" step="0.01" name="average_ctr" class="form-control @error('average_ctr') is-invalid @enderror" value="{{ $v('average_ctr', 0) }}">
                                @error('average_ctr')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Average position</label>
                                <input type="number" step="0.01" name="average_position" class="form-control @error('average_position') is-invalid @enderror" value="{{ $v('average_position', 0) }}">
                                @error('average_position')
                                <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Backlinks Submission --}}
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Backlinks Submission</span>
                        </div>
                        <div class="card-body row" id="backlinks-items-container">
                            @foreach($backlinksOptions as $opt)
                            <div class="col-md-4 backlinks-item">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input @error('backlinks_items') is-invalid @enderror"
                                        name="backlinks_items[]" value="{{ $opt }}"
                                        @checked(in_array($opt, $selectedBacklinks))>
                                    <span class="form-check-label">{{ $opt }}</span>
                                </label>
                            </div>
                            @endforeach
                            @error('backlinks_items')
                            <div class="col-12"><span class="field-error">{{ $message }}</span></div>
                            @enderror
                        </div>
                        <div class="card-footer">
                            <div class="input-group" style="max-width: 400px;">
                                <input type="text" id="custom-backlinks-input" class="form-control" placeholder="Add custom backlink item...">
                                <button type="button" class="btn btn-outline-primary" onclick="addBacklinksCustomFromInput()">Add</button>
                            </div>
                        </div>
                    </div>

                    {{-- General Screenshots / Images --}}
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between">
                            <span>Other Screenshots / Images</span>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addImage()">+ Add Image</button>
                        </div>
                        <div class="card-body" id="image-rows">
                            @foreach($images as $i => $row)
                            <div class="row g-2 mb-2 align-items-center image-row">
                                <div class="col-md-4">
                                    <input name="images[{{ $i }}][title]" class="form-control @error(" images.{$i}.title") is-invalid @enderror" placeholder="Title" value="{{ $row['title'] ?? '' }}">
                                    @error("images.{$i}.title")
                                    <span class="field-error d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-5">
                                    <input type="file" name="images[{{ $i }}][file]" accept="image/*" class="form-control @error(" images.{$i}.file") is-invalid @enderror">
                                    @if(!empty($row['existing']))
                                    <input type="hidden" name="images[{{ $i }}][existing]" value="{{ $row['existing'] }}">
                                    @endif
                                    @error("images.{$i}.file")
                                    <span class="field-error d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    @if(!empty($row['existing']))
                                    <img src="{{ asset('storage/'.$row['existing']) }}" style="height:45px" alt="">
                                    @endif
                                </div>
                                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.image-row').remove()">×</button></div>
                            </div>
                            @endforeach
                            @error('images')
                            <span class="field-error d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Conclusion --}}
                    <div class="card mb-3">
                        <div class="card-header">Conclusion &amp; Thanks</div>
                        <div class="card-body">
                            <textarea name="conclusion" rows="4" class="form-control @error('conclusion') is-invalid @enderror">{{ $v('conclusion', "Thank you for reviewing the SEO report. With consistent improvements in visibility, traffic, and rankings, we're on the right track. We appreciate your trust and look forward to delivering even better results in the coming months.\n\nIf you have any questions or need further insights, we're always here to help.") }}</textarea>
                            @error('conclusion')
                            <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="report-form-actions">
                        <button class="button button-primary" type="submit">Save report</button>
                        <a href="{{ route('seo-reports.index') }}" class="button button-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        let kIndex = Number(@json(count($keywords)));
        let iIndex = Number(@json($images ? max(array_keys($images)) + 1 : 0));

        const pageOptions = @json($pageOptions);
        const positionOptions = @json($positionOptions);

        /* ---------- Keyword rows ---------- */
        function buildKeywordRow(index, data = {}) {
            const ordinal = (n) => {
                n = parseInt(n, 10);
                const s = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
                return n + ((n % 100 >= 11 && n % 100 <= 13) ? 'th' : s[n % 10]);
            };

            const pageOpts = pageOptions.map(p => `
        <option value="${p}" ${ (parseInt(data.page) === p) ? 'selected' : '' }>
            ${ordinal(p)} Page
        </option>`).join('');

            const posOpts = positionOptions.map(pos => `
        <option value="${pos}" ${ (parseInt(data.position) === pos) ? 'selected' : '' }>
            ${ordinal(pos)} Position
        </option>`).join('');

            const existingImage = data.image_existing ?
                `<input type="hidden" name="keywords[${index}][image_existing]" value="${data.image_existing}">
           <img src="/storage/${data.image_existing}" style="height:45px; border-radius:4px;" alt="screenshot">` :
                '';

            return `
    <div class="keyword-row border rounded p-3 mb-3 bg-light" data-index="${index}">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-secondary">Keyword #<span class="kw-number"></span></span>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeKeywordRow(this)" title="Remove keyword">
                &times; Remove
            </button>
        </div>

        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label mb-1">Keyword</label>
                <input name="keywords[${index}][keyword]"
                       class="form-control keyword-input"
                       placeholder="Enter keyword"
                       value="${data.keyword || ''}"
                       oninput="onKeywordInput(this)">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Target Page</label>
                <select name="keywords[${index}][page]" class="form-select">
                    <option value="">-- Page --</option>
                    ${pageOpts}
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Target Position</label>
                <select name="keywords[${index}][position]" class="form-select">
                    <option value="">-- Position --</option>
                    ${posOpts}
                </select>
            </div>
        </div>

        <div class="row g-2 mt-2 align-items-center keyword-image-row">
            <div class="col-md-10">
                <label class="form-label mb-1 small text-muted">Screenshot file</label>
                <input type="file" name="keywords[${index}][image_file]" accept="image/*" class="form-control">
                ${existingImage}
            </div>
            <div class="col-md-2 text-center"></div>
        </div>
    </div>`;
        }

        function addKeyword() {
            document.getElementById('keyword-rows')
                .insertAdjacentHTML('beforeend', buildKeywordRow(kIndex, {}));
            kIndex++;
        }

        function removeKeywordRow(btn) {
            const rows = document.querySelectorAll('#keyword-rows .keyword-row');
            if (rows.length > 1) {
                btn.closest('.keyword-row').remove();
            } else {
                const row = btn.closest('.keyword-row');
                row.querySelectorAll('input[type="text"], input[type="file"]').forEach(el => el.value = '');
                row.querySelectorAll('select').forEach(el => el.value = '');
            }
        }

        function onKeywordInput(input) {
            const row = input.closest('.keyword-row');
            const allRows = document.querySelectorAll('#keyword-rows .keyword-row');
            const isLast = row === allRows[allRows.length - 1];

            if (isLast && input.value.trim() !== '') {
                addKeyword();
            }
        }

        /* ---------- General images ---------- */
        function addImage() {
            document.getElementById('image-rows').insertAdjacentHTML('beforeend', `
            <div class="row g-2 mb-2 align-items-center image-row">
                <div class="col-md-4"><input name="images[${iIndex}][title]" class="form-control" placeholder="Title"></div>
                <div class="col-md-5"><input type="file" name="images[${iIndex}][file]" accept="image/*" class="form-control"></div>
                <div class="col-md-2"></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.image-row').remove()">×</button></div>
            </div>`);
            iIndex++;
        }

        /* ---------- On-Page custom items ---------- */
        function addOnPageCustomFromInput() {
            const input = document.getElementById('custom-on-page-input');
            const text = input.value.trim();
            if (text) {
                addOnPageItem(text);
                input.value = '';
            }
        }

        function addOnPageItem(text) {
            const container = document.getElementById('on-page-items-container');
            const div = document.createElement('div');
            div.className = 'col-md-4 on-page-item';
            div.innerHTML = `
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" name="on_page_items[]" value="${text.replace(/"/g, '&quot;')}" checked>
                    <span class="form-check-label">${text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                </label>
                <input type="hidden" name="custom_on_page_items[]" value="${text.replace(/"/g, '&quot;')}">
            `;
            container.appendChild(div);
        }

        /* ---------- Backlinks custom items ---------- */
        function addBacklinksCustomFromInput() {
            const input = document.getElementById('custom-backlinks-input');
            const text = input.value.trim();
            if (text) {
                addBacklinksItem(text);
                input.value = '';
            }
        }

        function addBacklinksItem(text) {
            const container = document.getElementById('backlinks-items-container');
            const div = document.createElement('div');
            div.className = 'col-md-4 backlinks-item';
            div.innerHTML = `
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" name="backlinks_items[]" value="${text.replace(/"/g, '&quot;')}" checked>
                    <span class="form-check-label">${text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                </label>
                <input type="hidden" name="custom_backlinks_items[]" value="${text.replace(/"/g, '&quot;')}">
            `;
            container.appendChild(div);
        }

        /* ---------- Client select ---------- */
        const clientSelect = document.getElementById('client_id');
        const websiteInput = document.getElementById('client_website');

        function syncWebsite() {
            const opt = clientSelect.options[clientSelect.selectedIndex];
            websiteInput.value = (opt && opt.value) ? (opt.dataset.website || '') : '';
        }
        clientSelect.addEventListener('change', syncWebsite);
        syncWebsite();

        function previewScore(input) {
            const img = document.getElementById('score-preview');
            if (input.files && input.files[0]) {
                img.src = URL.createObjectURL(input.files[0]);
                img.style.display = 'block';
            }
        }

        document.getElementById('custom-on-page-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addOnPageCustomFromInput();
            }
        });

        document.getElementById('custom-backlinks-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addBacklinksCustomFromInput();
            }
        });

        /* ---------- Combine month + year into reporting_period ---------- */
        (function() {
            const monthSel = document.getElementById('reporting_period_month');
            const yearSel = document.getElementById('reporting_period_year');
            const hidden = document.getElementById('reporting_period');

            function syncPeriod() {
                const m = monthSel.value;
                const y = yearSel.value;

                if (m && y) {
                    hidden.value = y + '-' + String(m).padStart(2, '0');
                } else {
                    hidden.value = '';
                }
            }

            monthSel.addEventListener('change', syncPeriod);
            yearSel.addEventListener('change', syncPeriod);
            syncPeriod();
        })();
    </script>
</body>

</html>