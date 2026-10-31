@include('admin.partials.header', [
'pageTitle' => $title,
'active' => 'metrics-reports',
'bodyClass' => 'admin-page report-page',
'includeBootstrap' => true,
])

@php
use App\Models\AssignService;
use App\Models\Service;

/* --------------------------------------------------------------
| Prefill resolution
-------------------------------------------------------------- */
$prefillAssignServiceId = old('assign_service_id',
request()->integer('assign_service_id') ?: ($prefillId ?? null)
);

$selectedAssignServiceId = old(
'assign_service_id',
$report?->assign_service_id ?? $prefillAssignServiceId
);
$selectedAssignServiceId = is_scalar($selectedAssignServiceId) && (int) $selectedAssignServiceId > 0
? (int) $selectedAssignServiceId
: null;

/* --------------------------------------------------------------
| Which service types are attached to those assignments?
-------------------------------------------------------------- */
$serviceNameToType = [
'WhatsApp Meta Ads Monthly Report' => 'whatsapp_meta_ads',
'Lead Generation Ads Monthly Report' => 'lead_generation_ads',
'Social Media Engagement Monthly Report' => 'social_media_engagement',
];
$selectedAssignService = $selectedAssignServiceId
? AssignService::find($selectedAssignServiceId)
: null;
$serviceIds = collect(is_array($selectedAssignService?->service_id)
? $selectedAssignService->service_id
: array_filter([(int) $selectedAssignService?->service_id]))
->filter()
->unique();

$activeTypes = Service::whereIn('id', $serviceIds)
->pluck('name')
->map(fn ($name) => $serviceNameToType[$name] ?? null)
->filter()
->unique()
->values()
->all();

$v = fn ($key, $default = '') => old($key, $report?->{$key} ?? $default);
$err = fn (string $key) => $errors->has($key) ? 'is-invalid' : '';

$showWa = in_array('whatsapp_meta_ads', $activeTypes, true);
$showLg = in_array('lead_generation_ads', $activeTypes, true);
$showEn = in_array('social_media_engagement', $activeTypes, true);
$leadOrWhatsappActive = $showWa || $showLg;
$clientBudget = (float) ($selectedAssignService?->budget ?? 0);
$formAction = $action . (request()->filled('date') && ! $report
? '?date=' . urlencode(request('date'))
: '');
$backUrl = $selectedAssignServiceId
    ? route('metrics-reports.previous-metrics', $selectedAssignServiceId)
    : route('metrics-reports.index');
@endphp

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">Fill in the fields below and save the report.</p>
        </div>
        <a href="{{ $backUrl }}" class="button button-secondary">Back to reports</a>
    </section>

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data"
        class="report-form" novalidate id="socialReportForm">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <input type="hidden" name="assign_service_id" value="{{ $selectedAssignServiceId }}">

        @error('assign_service_id')<span class="field-error">{{ $message }}</span>@enderror

        {{-- ============ WHATSAPP ============ --}}
        @if($showWa)
        <div class="card mb-3">
            <div class="card-header">WhatsApp Campaign Performance</div>
            <div class="card-body row g-3">
                @foreach([
                'total_conversations' => ['Total Conversations', '1'],
                'whatsapp_clicks' => ['WhatsApp Clicks', '1'],
                'whatsapp_ctr' => ['CTR (%)', '0.01'],
                'reach' => ['Reach', '1'],
                'impressions' => ['Impressions', '1'],
                'frequency' => ['Frequency', '0.01'],
                ] as $field => [$label, $step])
                <div class="col-md-4">
                    <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                    <input type="number" min="0" step="{{ $step }}" name="{{ $field }}"
                        @if($field === 'whatsapp_ctr') max="100" @endif
                        class="form-control {{ $err($field) }}"
                        required value="{{ $v($field) }}">
                    @error($field)<span class="field-error">{{ $message }}</span>@enderror
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ============ LEAD GEN ============ --}}
        @if($showLg)
        <div class="card mb-3">
            <div class="card-header">Lead Generation Performance</div>
            <div class="card-body row g-3">
                @foreach([
                'total_leads' => ['Total Leads', '1'],
                'link_clicks' => ['Link Clicks', '1'],
                'lead_ctr' => ['CTR (%)', '0.01'],
                'lead_reach' => ['Reach', '1'],
                'lead_impressions' => ['Impressions', '1'],
                'lead_frequency' => ['Frequency', '0.01'],
                ] as $field => [$label, $step])
                <div class="col-md-4">
                    <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                    <input type="number" min="0" step="{{ $step }}" name="{{ $field }}"
                        @if($field === 'lead_ctr') max="100" @endif
                        class="form-control {{ $err($field) }}"
                        required value="{{ $v($field) }}">
                    @error($field)<span class="field-error">{{ $message }}</span>@enderror
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ============ ENGAGEMENT ============ --}}
        @if($showEn)
        <div class="card mb-3">
            <div class="card-header">Engagement Performance</div>
            <div class="card-body row g-3">
                @foreach([
                'total_engagement' => 'Total Engagement',
                'reactions' => 'Reactions',
                'comments' => 'Comments',
                'shares' => 'Shares',
                'saves' => 'Saves',
                'profile_visits' => 'Profile Visits',
                'new_followers' => 'New Followers',
                ] as $field => $label)
                <div class="col-md-4">
                    <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                    <input type="number" name="{{ $field }}"
                        class="form-control {{ $err($field) }}"
                        required value="{{ $v($field) }}">
                    @error($field)<span class="field-error">{{ $message }}</span>@enderror
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ============ CAMPAIGN / AD SET RESULTS ============ --}}
        @if($leadOrWhatsappActive)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Campaign / Ad Set Results</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addCampaignRow()">+ Add Row</button>
            </div>
            <div class="card-body" id="campaign-rows">
                @php
                $campaignResults = old('campaign_results', $report?->campaign_results ?? []);
                if (empty($campaignResults)) {
                $campaignResults = [['name'=>'','result'=>'','spend'=>'','note'=>'']];
                }
                @endphp

                @foreach($campaignResults as $i => $row)
                <div class="row g-2 mb-2 align-items-center campaign-row">
                    <div class="col-md-3">
                        <input name="campaign_results[{{ $i }}][name]"
                            class="form-control {{ $err("campaign_results.$i.name") }}"
                            required placeholder="Campaign / Ad Set Name"
                            value="{{ $row['name'] ?? '' }}">
                        @error("campaign_results.$i.name")<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-2">
                        <input name="campaign_results[{{ $i }}][result]"
                            class="form-control {{ $err("campaign_results.$i.result") }}"
                            required placeholder="Result"
                            value="{{ $row['result'] ?? '' }}">
                        @error("campaign_results.$i.result")<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-2">
                        <input type="text" inputmode="numeric" pattern="[0-9]*"
                            name="campaign_results[{{ $i }}][spend]"
                            class="form-control js-spend {{ $err("campaign_results.$i.spend") }}"
                            required placeholder="Spend"
                            value="{{ preg_replace('/\D/', '', (string) ($row['spend'] ?? '')) }}">
                        <div id="budgetWarning" class="text-danger small mt-2" style="display:none;">
                            Spend exceeds the client budget.
                        </div>
                        @error("campaign_results.$i.spend")<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-4">
                        <input name="campaign_results[{{ $i }}][note]"
                            class="form-control {{ $err("campaign_results.$i.note") }}"
                            required placeholder="Note"
                            value="{{ $row['note'] ?? '' }}">
                        @error("campaign_results.$i.note")<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100"
                            onclick="this.closest('.campaign-row').remove()">×</button>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div id="budgetBox" class="d-flex gap-4 flex-wrap p-3 rounded border"
                        data-budget="{{ $clientBudget }}">
                        <div>Client budget: <strong>₹<span id="budgetValue">{{ number_format($clientBudget) }}</span></strong></div>
                        <div>Spend entered: <strong>₹<span id="spentValue">0</span></strong></div>
                        <div>Available spend: <strong>₹<span id="availableValue">0</span></strong></div>
                    </div>
                    <div id="budgetWarning" class="text-danger small mt-2" style="display:none;"></div>
                    @error('campaign_results')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
        @endif

        {{-- ============ TOP POSTS ============ --}}
        @if($showEn)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Top Performing Posts</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addTopPostRow()">+ Add</button>
            </div>
            <div class="card-body" id="top-post-rows">
                @php
                $topPosts = old('top_posts', $report?->top_posts ?? []);
                if (empty($topPosts)) {
                $topPosts = [['title'=>'','engagements'=>'','type'=>'']];
                }
                @endphp

                @foreach($topPosts as $i => $row)
                <div class="row g-2 mb-2 align-items-center top-post-row">
                    <div class="col-md-5">
                        <input name="top_posts[{{ $i }}][title]"
                            class="form-control {{ $err("top_posts.$i.title") }}"
                            required placeholder="Post Title / Description"
                            value="{{ $row['title'] ?? '' }}">
                        @error("top_posts.$i.title")<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3">
                        <input name="top_posts[{{ $i }}][engagements]"
                            class="form-control {{ $err("top_posts.$i.engagements") }}"
                            required placeholder="Engagements"
                            value="{{ $row['engagements'] ?? '' }}">
                        @error("top_posts.$i.engagements")<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3">
                        <input name="top_posts[{{ $i }}][type]"
                            class="form-control {{ $err("top_posts.$i.type") }}"
                            required placeholder="Type (Reel/Image/Carousel)"
                            value="{{ $row['type'] ?? '' }}">
                        @error("top_posts.$i.type")<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100"
                            onclick="this.closest('.top-post-row').remove()">×</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ========== Actions ========== --}}
        <div class="report-form-actions">
            <button type="submit" class="button button-primary">Save report</button>
            <a href="{{ $backUrl }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
<script>
    /* Dynamic rows — Campaign / Ad Set */
    let cIndex = document.getElementById('campaign-rows')?.children.length ?? 0;

    function addCampaignRow() {
        document.getElementById('campaign-rows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 align-items-center campaign-row">
            <div class="col-md-3"><input name="campaign_results[${cIndex}][name]" class="form-control" required placeholder="Campaign / Ad Set Name"></div>
            <div class="col-md-2"><input name="campaign_results[${cIndex}][result]" class="form-control" required placeholder="Result"></div>
            <div class="col-md-2"><input type="text" inputmode="numeric" pattern="[0-9]*" name="campaign_results[${cIndex}][spend]" class="form-control js-spend" required placeholder="Spend"></div>
            <div class="col-md-4"><input name="campaign_results[${cIndex}][note]" class="form-control" required placeholder="Note"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.campaign-row').remove()">×</button></div>
        </div>`);
        cIndex++;
    }

    /* Dynamic rows — Top Posts */
    let tpIndex = document.getElementById('top-post-rows')?.children.length ?? 0;

    function addTopPostRow() {
        document.getElementById('top-post-rows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 align-items-center top-post-row">
            <div class="col-md-5"><input name="top_posts[${tpIndex}][title]" class="form-control" required placeholder="Post Title / Description"></div>
            <div class="col-md-3"><input name="top_posts[${tpIndex}][engagements]" class="form-control" required placeholder="Engagements"></div>
            <div class="col-md-3"><input name="top_posts[${tpIndex}][type]" class="form-control" required placeholder="Type (Reel/Image/Carousel)"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.top-post-row').remove()">×</button></div>
        </div>`);
        tpIndex++;
    }

    /* Live budget tracker: integers only, capped at available spend */
    const fmt = n => Number(n).toLocaleString('en-IN');

    function otherSpend(except) {
        let sum = 0;
        document.querySelectorAll('.js-spend').forEach(i => {
            if (i !== except) sum += parseInt(i.value, 10) || 0;
        });
        return sum;
    }

    function enforceSpend(input) {
        const box = document.getElementById('budgetBox');
        const budget = parseInt(box?.dataset.budget, 10) || 0;
        const warn = document.getElementById('budgetWarning');

        // digits only (also covers paste), drop leading zeros
        let digits = input.value.replace(/\D/g, '');
        if (digits !== '') digits = String(parseInt(digits, 10));

        let clamped = false;
        if (budget > 0 && digits !== '') {
            const max = Math.max(budget - otherSpend(input), 0);
            if (parseInt(digits, 10) > max) {
                digits = String(max);
                clamped = true;
                warn.textContent = `Spend cannot exceed the available amount (₹${fmt(max)}).`;
                warn.style.display = 'block';
            }
        }
        input.value = digits;
        if (!clamped) warn.style.display = 'none';
    }

    function updateBudget() {
        const box = document.getElementById('budgetBox');
        if (!box) return;

        const budget = parseInt(box.dataset.budget, 10) || 0;
        const spent = otherSpend(null);
        const available = budget - spent;
        const ratio = budget > 0 ? spent / budget : 0;

        document.getElementById('spentValue').textContent = fmt(spent);
        document.getElementById('availableValue').textContent = fmt(available);

        // green → amber (80%+) → red (fully used)
        const color = (budget > 0 && available <= 0) ? '#d33' : (ratio >= 0.8 ? '#d97706' : '#16a34a');
        box.style.borderColor = color;
        box.style.backgroundColor = color + '14';
        document.getElementById('availableValue').style.color = color;
    }

    document.addEventListener('input', e => {
        if (e.target.classList.contains('js-spend')) {
            enforceSpend(e.target);
            updateBudget();
        }
    });
    document.addEventListener('click', e => {
        if (e.target.closest('.campaign-row button')) setTimeout(updateBudget, 0);
    });
    document.addEventListener('DOMContentLoaded', updateBudget);
</script>

@include('admin.partials.footer')