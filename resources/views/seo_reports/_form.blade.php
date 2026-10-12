@php
    $v = fn ($key, $default = '') => old($key, $report?->{$key} ?? $default);

    // -------- Client resolution (locked field) --------
    $formClientId = old('client_id', $selectedClientId ?? $report?->client_id ?? null);
    $formClient   = $client
        ?? $report?->client
        ?? ($formClientId ? \App\Models\Client::find($formClientId) : null);

    // -------- Reporting period: split stored value into month + year --------
    $storedPeriod = old('reporting_period', $report?->reporting_period ?? '');
    $periodMonth  = '';
    $periodYear   = '';

    if ($storedPeriod) {
        if (preg_match('/^(\d{4})-(\d{2})$/', $storedPeriod, $m)) {
            $periodYear  = (int) $m[1];
            $periodMonth = (int) $m[2];
        } elseif (preg_match('/^([A-Za-z]+)\s+(\d{4})$/', $storedPeriod, $m)) {
            $periodYear  = (int) $m[2];
            $periodMonth = (int) date('n', strtotime($m[1] . ' 1 ' . $m[2]));
        }
    }

    $monthOptions = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $currentYear = (int) date('Y');
    $startYear   = 2000;
    $yearOptions = range($startYear, $currentYear);

    // -------- On-Page options --------
    $defaultOnPageOptions = [
        'Targeting Keyword', 'Competitor Keyword Analysing', 'Google Search Console Analysing',
        'Google Analytics', 'Google index', 'Meta tags update all pages.',
        'Internal and external link', 'URL Structure',
    ];
    $customOnPage    = old('custom_on_page_items', $report?->custom_on_page_items ?? []);
    $onPageOptions   = array_unique(array_merge($defaultOnPageOptions, $customOnPage));
    $selectedOnPage  = old('on_page_items', $report?->on_page_items ?? $defaultOnPageOptions);

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
    $customBacklinks     = old('custom_backlinks_items', $report?->custom_backlinks_items ?? []);
    $backlinksOptions    = array_unique(array_merge($defaultBacklinksOptions, $customBacklinks));
    $selectedBacklinks   = old('backlinks_items', $report?->backlinks_items ?? $defaultBacklinksOptions);

    // -------- Keywords --------
    $keywords = old('keywords', $report
        ? collect($report->keywords)->map(fn ($k) => [
            'keyword'        => is_array($k) ? ($k['keyword'] ?? '') : $k,
            'page'           => is_array($k) ? ($k['page'] ?? null) : null,
            'position'       => is_array($k) ? ($k['position'] ?? null) : null,
            'image_existing' => is_array($k) ? ($k['image_existing'] ?? null) : null,
        ])->all()
        : [['keyword' => '', 'page' => null, 'position' => null, 'image_existing' => null]]);

    // -------- Images --------
    $images = old('images', $report
        ? collect($report->images)->map(fn ($i) => ['title' => $i['title'], 'existing' => $i['path']])->all()
        : []);

    // -------- Performance images --------
    $performanceImages = old('performance_images', $report
        ? collect($report->performance_images ?? [])->map(fn ($i) => ['title' => $i['title'], 'existing' => $i['path']])->all()
        : []);
    if (empty($performanceImages)) {
        $performanceImages = [['title' => '', 'existing' => null]];
    }

    $pageOptions     = range(1, 10);
    $positionOptions = range(1, 10);
@endphp

@include('admin.partials.header', [
    'pageTitle' => $title,
    'active' => 'reports',
    'bodyClass' => 'admin-page report-page',
    'includeBootstrap' => true,
])

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">
                @if($formClient)
                    Adding a report for <strong>{{ $formClient->name }}</strong> —
                    fill in the monthly performance details, search data, and supporting screenshots.
                @else
                    Add the monthly performance details, search data, and supporting screenshots.
                @endif
            </p>
        </div>
        <a href="{{ route('seo-reports.index', $formClientId ? ['client' => $formClientId] : []) }}"
           class="button button-secondary">
            Back to reports
        </a>
    </section>

    <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="report-form" novalidate>
        @csrf
        @if($method !== 'POST') @method($method) @endif

        {{-- Client + Website are locked and injected server-side --}}
        <input type="hidden" name="client_id" value="{{ $formClientId }}">
        <input type="hidden" name="website" value="{{ $formClient?->website }}">

        {{-- ==================== Report Details ==================== --}}
        <div class="card mb-3">
            <div class="card-header">Report Details</div>
            <div class="card-body row g-3">                

                {{-- Reporting Period --}}
                <div class="col-md-6">
                    <label class="form-label">
                        Reporting Period <span class="required-star" aria-hidden="true">*</span>
                    </label>
                    <div class="row g-2">
                        <div class="col-7">
                            <select name="reporting_period_month" id="reporting_period_month"
                                class="form-select @error('reporting_period') is-invalid @enderror">
                                <option value="">-- Month --</option>
                                @foreach($monthOptions as $num => $name)
                                    <option value="{{ $num }}" @selected((int) $periodMonth === $num)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <select name="reporting_period_year" id="reporting_period_year"
                                class="form-select @error('reporting_period') is-invalid @enderror">
                                <option value="">-- Year --</option>
                                @foreach($yearOptions as $year)
                                    <option value="{{ $year }}" @selected((int) $periodYear === (int) $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="reporting_period" id="reporting_period" value="{{ $storedPeriod }}">

                    @error('reporting_period')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Prepared by --}}
                <div class="col-md-6">
                    <label class="form-label">Prepared by</label>
                    <input name="prepared_by"
                           class="form-control @error('prepared_by') is-invalid @enderror"
                           readonly
                           value="{{ $v('prepared_by', '360DegreeInfo SEO Team') }}">
                    @error('prepared_by')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Overview --}}
                <div class="col-12">
                    <label class="form-label">Overview</label>
                    <textarea name="overview" rows="4"
                              class="form-control @error('overview') is-invalid @enderror">{{ $v('overview') }}</textarea>
                    @error('overview')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        {{-- ==================== On-Page SEO ==================== --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>On-Page SEO</span>
            </div>
            <div class="card-body row" id="on-page-items-container">
                @foreach($onPageOptions as $opt)
                    <div class="col-md-4 on-page-item">
                        <label class="form-check">
                            <input type="checkbox"
                                   class="form-check-input @error('on_page_items') is-invalid @enderror"
                                   name="on_page_items[]" value="{{ $opt }}"
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

        {{-- ==================== SEO Score ==================== --}}
        <div class="card mb-3">
            <div class="card-header">SEO Score (upload image)</div>
            <div class="card-body">
                <input type="file" name="seo_score_image" accept="image/*"
                       class="form-control @error('seo_score_image') is-invalid @enderror"
                       onchange="previewScore(this)">
                @error('seo_score_image')<span class="field-error">{{ $message }}</span>@enderror
                <img id="score-preview" class="mt-2"
                     style="max-height:160px; {{ $report?->seo_score_image ? '' : 'display:none' }}"
                     src="{{ $report?->seo_score_image ? asset('storage/'.$report->seo_score_image) : '' }}"
                     alt="">
                @if($report?->seo_score_image)
                    <div class="form-text">Leave empty to keep the current image.</div>
                @endif
            </div>
        </div>

        {{-- ==================== Targeting Keywords ==================== --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Targeting Keywords</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addKeyword()">+ Add Keyword</button>
            </div>

            <div class="card-body" id="keyword-rows">
                @foreach($keywords as $i => $row)
                    <div class="keyword-row border rounded p-3 mb-3 bg-light" data-index="{{ $i }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary">Keyword #<span class="kw-number">{{ $i + 1 }}</span></span>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="removeKeywordRow(this)" title="Remove keyword">
                                &times; Remove
                            </button>
                        </div>

                        <label class="form-label mb-1">Keyword</label>
                        <input name="keywords[{{ $i }}][keyword]"
                               class="form-control keyword-input @error("keywords.{$i}.keyword") is-invalid @enderror"
                               placeholder="Enter keyword"
                               value="{{ $row['keyword'] ?? '' }}"
                               oninput="onKeywordInput(this)">
                        @error("keywords.{$i}.keyword")
                            <span class="field-error d-block">{{ $message }}</span>
                        @enderror
                    </div>
                @endforeach

                @error('keywords')
                    <span class="field-error d-block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- ==================== Google Performance ==================== --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Google Performance (upload images)</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addPerformanceImage()">+ Add Image</button>
            </div>
            <div class="card-body" id="performance-rows">
                @foreach($performanceImages as $i => $row)
                    <div class="row g-2 mb-2 align-items-center performance-row">
                        <div class="col-md-4">
                            <input name="performance_images[{{ $i }}][title]"
                                   class="form-control @error("performance_images.{$i}.title") is-invalid @enderror"
                                   placeholder="Title (e.g. Clicks & Impressions)"
                                   value="{{ $row['title'] ?? '' }}">
                            @error("performance_images.{$i}.title")
                                <span class="field-error d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <input type="file" name="performance_images[{{ $i }}][file]" accept="image/*"
                                   class="form-control @error("performance_images.{$i}.file") is-invalid @enderror">
                            @if(!empty($row['existing']))
                                <input type="hidden" name="performance_images[{{ $i }}][existing]" value="{{ $row['existing'] }}">
                            @endif
                            @error("performance_images.{$i}.file")
                                <span class="field-error d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-2">
                            @if(!empty($row['existing']))
                                <img src="{{ asset('storage/'.$row['existing']) }}" style="height:45px" alt="">
                            @endif
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100"
                                    onclick="this.closest('.performance-row').remove()">×</button>
                        </div>
                    </div>
                @endforeach
                @error('performance_images')
                    <span class="field-error d-block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- ==================== Keyword Detail (page/position/screenshot) ==================== --}}
        <div class="card mb-3">
            <div class="card-header">Keyword Target Page, Position &amp; Screenshot</div>
            <div class="card-body" id="keyword-detail-rows">
                <p class="text-muted mb-0" id="keyword-detail-empty">
                    Enter a keyword above to add its page, position and screenshot.
                </p>

                @foreach($keywords as $i => $row)
                    @php $filled = trim($row['keyword'] ?? '') !== ''; @endphp
                    <div class="keyword-detail-row border rounded p-3 mb-3 bg-light"
                         data-index="{{ $i }}"
                         style="{{ $filled ? '' : 'display:none' }}">
                        <div class="mb-2">
                            <span class="badge bg-secondary">Keyword #<span class="kw-number">{{ $i + 1 }}</span></span>
                            <strong class="ms-2 kw-label">{{ $row['keyword'] ?? '' }}</strong>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label mb-1">Target Page</label>
                                <select name="keywords[{{ $i }}][page]"
                                        class="form-select @error("keywords.{$i}.page") is-invalid @enderror">
                                    <option value="">-- Page --</option>
                                    @foreach($pageOptions as $p)
                                        <option value="{{ $p }}" @selected((int) ($row['page'] ?? 0) === $p)>
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
                                        class="form-select @error("keywords.{$i}.position") is-invalid @enderror">
                                    <option value="">-- Position --</option>
                                    @foreach($positionOptions as $pos)
                                        <option value="{{ $pos }}" @selected((int) ($row['position'] ?? 0) === $pos)>
                                            {{ $pos }}{{ $pos == 1 ? 'st' : ($pos == 2 ? 'nd' : ($pos == 3 ? 'rd' : 'th')) }} Position
                                        </option>
                                    @endforeach
                                </select>
                                @error("keywords.{$i}.position")
                                    <span class="field-error d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label mb-1">Screenshot file</label>
                                <input type="file" name="keywords[{{ $i }}][image_file]" accept="image/*" class="form-control">
                                @if(!empty($row['image_existing']))
                                    <input type="hidden" name="keywords[{{ $i }}][image_existing]" value="{{ $row['image_existing'] }}">
                                @endif
                            </div>

                            <div class="col-md-2 text-center align-self-end">
                                @if(!empty($row['image_existing']))
                                    <img src="{{ asset('storage/'.$row['image_existing']) }}"
                                         style="height:45px; border-radius:4px;" alt="screenshot">
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ==================== Backlinks Submission ==================== --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Backlinks Submission</span>
            </div>
            <div class="card-body row" id="backlinks-items-container">
                @foreach($backlinksOptions as $opt)
                    <div class="col-md-4 backlinks-item">
                        <label class="form-check">
                            <input type="checkbox"
                                   class="form-check-input @error('backlinks_items') is-invalid @enderror"
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

        {{-- ==================== Other Screenshots / Images ==================== --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>Other Screenshots / Images</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addImage()">+ Add Image</button>
            </div>
            <div class="card-body" id="image-rows">
                @foreach($images as $i => $row)
                    <div class="row g-2 mb-2 align-items-center image-row">
                        <div class="col-md-4">
                            <input name="images[{{ $i }}][title]"
                                   class="form-control @error("images.{$i}.title") is-invalid @enderror"
                                   placeholder="Title" value="{{ $row['title'] ?? '' }}">
                            @error("images.{$i}.title")
                                <span class="field-error d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <input type="file" name="images[{{ $i }}][file]" accept="image/*"
                                   class="form-control @error("images.{$i}.file") is-invalid @enderror">
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
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100"
                                    onclick="this.closest('.image-row').remove()">×</button>
                        </div>
                    </div>
                @endforeach
                @error('images')
                    <span class="field-error d-block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- ==================== Conclusion ==================== --}}
        <div class="card mb-3">
            <div class="card-header">Conclusion &amp; Thanks</div>
            <div class="card-body">
                <textarea name="conclusion" rows="4"
                          class="form-control @error('conclusion') is-invalid @enderror">{{ $v('conclusion', "Thank you for reviewing the SEO report. With consistent improvements in visibility, traffic, and rankings, we're on the right track. We appreciate your trust and look forward to delivering even better results in the coming months.\n\nIf you have any questions or need further insights, we're always here to help.") }}</textarea>
                @error('conclusion')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">
                {{ $method === 'POST' ? 'Save report' : 'Update report' }}
            </button>
            <a href="{{ route('seo-reports.index', $formClientId ? ['client' => $formClientId] : []) }}"
               class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    let kIndex = Number(@json(count($keywords)));
    let iIndex = Number(@json($images ? max(array_keys($images)) + 1 : 0));
    let pIndex = Number(@json(count($performanceImages)));

    const pageOptions     = @json($pageOptions);
    const positionOptions = @json($positionOptions);

    /* ---------- Keyword rows ---------- */
    function ordinal(n) {
        n = parseInt(n, 10);
        const s = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        return n + ((n % 100 >= 11 && n % 100 <= 13) ? 'th' : s[n % 10]);
    }

    function buildKeywordRow(index) {
        return `
        <div class="keyword-row border rounded p-3 mb-3 bg-light" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-secondary">Keyword #<span class="kw-number"></span></span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeKeywordRow(this)" title="Remove keyword">
                    &times; Remove
                </button>
            </div>
            <label class="form-label mb-1">Keyword</label>
            <input name="keywords[${index}][keyword]" class="form-control keyword-input"
                   placeholder="Enter keyword" oninput="onKeywordInput(this)">
        </div>`;
    }

    function buildDetailRow(index) {
        const pageOpts = pageOptions.map(p => `<option value="${p}">${ordinal(p)} Page</option>`).join('');
        const posOpts  = positionOptions.map(p => `<option value="${p}">${ordinal(p)} Position</option>`).join('');

        return `
        <div class="keyword-detail-row border rounded p-3 mb-3 bg-light" data-index="${index}" style="display:none">
            <div class="mb-2">
                <span class="badge bg-secondary">Keyword #<span class="kw-number"></span></span>
                <strong class="ms-2 kw-label"></strong>
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label mb-1">Target Page</label>
                    <select name="keywords[${index}][page]" class="form-select">
                        <option value="">-- Page --</option>${pageOpts}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Target Position</label>
                    <select name="keywords[${index}][position]" class="form-select">
                        <option value="">-- Position --</option>${posOpts}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">Screenshot file</label>
                    <input type="file" name="keywords[${index}][image_file]" accept="image/*" class="form-control">
                </div>
                <div class="col-md-2"></div>
            </div>
        </div>`;
    }

    function addKeyword() {
        document.getElementById('keyword-rows').insertAdjacentHTML('beforeend', buildKeywordRow(kIndex));
        document.getElementById('keyword-detail-rows').insertAdjacentHTML('beforeend', buildDetailRow(kIndex));
        kIndex++;
        refreshKeywordUI();
    }

    function removeKeywordRow(btn) {
        const row    = btn.closest('.keyword-row');
        const detail = document.querySelector(`.keyword-detail-row[data-index="${row.dataset.index}"]`);
        const rows   = document.querySelectorAll('#keyword-rows .keyword-row');

        if (rows.length > 1) {
            row.remove();
            detail?.remove();
        } else {
            row.querySelectorAll('input[type="text"]').forEach(el => el.value = '');
            if (detail) {
                detail.querySelectorAll('input[type="file"]').forEach(el => el.value = '');
                detail.querySelectorAll('select').forEach(el => el.value = '');
                detail.style.display = 'none';
            }
        }
        refreshKeywordUI();
    }

    function onKeywordInput(input) {
        const row    = input.closest('.keyword-row');
        const detail = document.querySelector(`.keyword-detail-row[data-index="${row.dataset.index}"]`);
        const text   = input.value.trim();

        if (detail) {
            detail.style.display = text !== '' ? '' : 'none';
            detail.querySelector('.kw-label').textContent = input.value;
        }

        refreshKeywordUI();
    }

    function refreshKeywordUI() {
        document.querySelectorAll('#keyword-rows .keyword-row .kw-number')
            .forEach((el, i) => el.textContent = i + 1);
        document.querySelectorAll('#keyword-detail-rows .keyword-detail-row .kw-number')
            .forEach((el, i) => el.textContent = i + 1);

        const anyVisible = [...document.querySelectorAll('.keyword-detail-row')]
            .some(r => r.style.display !== 'none');
        document.getElementById('keyword-detail-empty').style.display = anyVisible ? 'none' : '';
    }
    refreshKeywordUI();

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

    /* ---------- Performance images ---------- */
    function addPerformanceImage() {
        document.getElementById('performance-rows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 align-items-center performance-row">
            <div class="col-md-4"><input name="performance_images[${pIndex}][title]" class="form-control" placeholder="Title (e.g. Clicks & Impressions)"></div>
            <div class="col-md-5"><input type="file" name="performance_images[${pIndex}][file]" accept="image/*" class="form-control"></div>
            <div class="col-md-2"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.performance-row').remove()">×</button></div>
        </div>`);
        pIndex++;
    }

    /* ---------- On-Page custom items ---------- */
    function addOnPageCustomFromInput() {
        const input = document.getElementById('custom-on-page-input');
        const text  = input.value.trim();
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
        const text  = input.value.trim();
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

    /* ---------- Score preview ---------- */
    function previewScore(input) {
        const img = document.getElementById('score-preview');
        if (input.files && input.files[0]) {
            img.src = URL.createObjectURL(input.files[0]);
            img.style.display = 'block';
        }
    }

    document.getElementById('custom-on-page-input')?.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addOnPageCustomFromInput();
        }
    });

    document.getElementById('custom-backlinks-input')?.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addBacklinksCustomFromInput();
        }
    });

    /* ---------- Combine month + year into reporting_period ---------- */
    (function () {
        const monthSel = document.getElementById('reporting_period_month');
        const yearSel  = document.getElementById('reporting_period_year');
        const hidden   = document.getElementById('reporting_period');

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

@include('admin.partials.footer')