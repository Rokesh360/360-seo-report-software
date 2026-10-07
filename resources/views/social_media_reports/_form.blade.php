@include('admin.partials.header', [
    'pageTitle' => $title,
    'active' => 'social-media-reports',
    'bodyClass' => 'admin-page report-page',
    'includeBootstrap' => true,
])

@php
use App\Models\AssignService;
use App\Models\Service;

// Resolve prefill from URL or existing report
$prefillAssignServiceId = old('assign_service_id',
    request()->integer('assign_service_id') ?: ($prefillId ?? null)
);

// Assign services — value is carried via URL/report; no UI shown
$selectedAssignServiceIds = old('assign_service_ids',
    $report?->assign_service_ids ?? ($prefillAssignServiceId ? [$prefillAssignServiceId] : [])
);
if (!is_array($selectedAssignServiceIds)) {
    $selectedAssignServiceIds = $prefillAssignServiceId ? [$prefillAssignServiceId] : [];
}
$selectedAssignServiceIds = array_map('intval', $selectedAssignServiceIds);

// Resolve every report section required by the selected assignments.
$serviceNameToType = [
    'WhatsApp Meta Ads Monthly Report'       => 'whatsapp_meta_ads',
    'Lead Generation Ads Monthly Report'     => 'lead_generation_ads',
    'Social Media Engagement Monthly Report' => 'social_media_engagement',
];
$serviceIds = AssignService::whereIn('id', $selectedAssignServiceIds)
    ->get()
    ->flatMap(fn ($assignment) => is_array($assignment->service_id)
        ? $assignment->service_id
        : array_filter([(int) $assignment->service_id]))
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

$leadOrWhatsappActive = in_array('whatsapp_meta_ads', $activeTypes, true)
    || in_array('lead_generation_ads', $activeTypes, true);
@endphp

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">Complete the steps below to create a report.</p>
        </div>
        <a href="{{ route('social-media-reports.index') }}" class="button button-secondary">Back to reports</a>
    </section>

    @if($errors->any())
        <div class="report-validation" role="alert">
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ========== STEP INDICATOR ========== --}}
    <ul class="wizard-steps" id="wizardSteps">
        <li class="wizard-step is-active" data-step="1"><span>1</span> Details</li>
        <li class="wizard-step is-locked" data-step="2"><span>2</span> Metrics</li>
        <li class="wizard-step is-locked" data-step="3"><span>3</span> Summary</li>
    </ul>

    <form action="{{ $action }}" method="POST" enctype="multipart/form-data"
          class="report-form" novalidate id="socialReportForm">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        {{-- ============================================================ --}}
        {{-- HIDDEN assign_service_ids[] — carried from URL / edit mode   --}}
        {{-- ============================================================ --}}
        @foreach($selectedAssignServiceIds as $sid)
            <input type="hidden" name="assign_service_ids[]" value="{{ $sid }}">
        @endforeach

        {{-- ============================================================ --}}
        {{-- STEP 1 — Report Details                                      --}}
        {{-- ============================================================ --}}
        <div class="wizard-panel" data-step="1">

            <div class="card mb-3">
                <div class="card-body">
                    Reports can be submitted only when today falls within the assigned service date range.
                </div>
            </div>

            {{-- Report Details --}}
            <div class="card mb-3">
                <div class="card-header">Report Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Platforms <span class="text-danger">*</span></label>
                        <input name="platforms" class="form-control" required
                               value="{{ $v('platforms', '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Campaign Objective <span class="text-danger">*</span></label>
                        <input name="campaign_objective" class="form-control" required
                               value="{{ $v('campaign_objective') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Campaign Overview <span class="text-danger">*</span></label>
                        <textarea name="campaign_overview" rows="2" class="form-control" required>{{ $v('campaign_overview') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Target Audience <span class="text-danger">*</span></label>
                        <textarea name="target_audience" rows="2" class="form-control" required>{{ $v('target_audience') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Main Campaign Focus <span class="text-danger">*</span></label>
                        <textarea name="campaign_focus" rows="2" class="form-control" required>{{ $v('campaign_focus') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Key Highlights <span class="text-danger">*</span></label>
                        <textarea name="key_highlights" rows="2" class="form-control" required>{{ $v('key_highlights') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- STEP 2 — Metrics                                              --}}
        {{-- ============================================================ --}}
        <div class="wizard-panel" data-step="2" style="display:none;">

            {{-- WhatsApp --}}
            <div class="card mb-3" data-block="whatsapp"
                 data-required-types="whatsapp_meta_ads"
                 style="{{ in_array('whatsapp_meta_ads', $activeTypes, true) ? '' : 'display:none;' }}">
                <div class="card-header">WhatsApp Campaign Performance</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Total Conversations <span class="text-danger">*</span></label>
                        <input type="number" name="total_conversations" class="form-control" required
                               value="{{ $v('total_conversations') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Enquiries <span class="text-danger">*</span></label>
                        <input type="number" name="total_enquiries" class="form-control" required
                               value="{{ $v('total_enquiries') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">WhatsApp Clicks <span class="text-danger">*</span></label>
                        <input type="number" name="whatsapp_clicks" class="form-control" required
                               value="{{ $v('whatsapp_clicks') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reach <span class="text-danger">*</span></label>
                        <input type="number" name="reach" class="form-control" required
                               value="{{ $v('reach') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Impressions <span class="text-danger">*</span></label>
                        <input type="number" name="impressions" class="form-control" required
                               value="{{ $v('impressions') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Frequency <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="frequency" class="form-control" required
                               value="{{ $v('frequency') }}">
                    </div>
                </div>
            </div>

            {{-- Lead Gen --}}
            <div class="card mb-3" data-block="leadgen"
                 data-required-types="lead_generation_ads"
                 style="{{ in_array('lead_generation_ads', $activeTypes, true) ? '' : 'display:none;' }}">
                <div class="card-header">Lead Generation Performance</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Total Leads <span class="text-danger">*</span></label>
                        <input type="number" name="total_leads" class="form-control" required
                               value="{{ $v('total_leads') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Form Submissions <span class="text-danger">*</span></label>
                        <input type="number" name="form_submissions" class="form-control" required
                               value="{{ $v('form_submissions') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Link Clicks <span class="text-danger">*</span></label>
                        <input type="number" name="link_clicks" class="form-control" required
                               value="{{ $v('link_clicks') }}">
                    </div>
                </div>
            </div>

            {{-- Engagement --}}
            <div class="card mb-3" data-block="engagement"
                 data-required-types="social_media_engagement"
                 style="{{ in_array('social_media_engagement', $activeTypes, true) ? '' : 'display:none;' }}">
                <div class="card-header">Engagement Performance</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Total Engagement <span class="text-danger">*</span></label>
                        <input type="number" name="total_engagement" class="form-control" required
                               value="{{ $v('total_engagement') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reactions <span class="text-danger">*</span></label>
                        <input type="number" name="reactions" class="form-control" required
                               value="{{ $v('reactions') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Comments <span class="text-danger">*</span></label>
                        <input type="number" name="comments" class="form-control" required
                               value="{{ $v('comments') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shares <span class="text-danger">*</span></label>
                        <input type="number" name="shares" class="form-control" required
                               value="{{ $v('shares') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Saves <span class="text-danger">*</span></label>
                        <input type="number" name="saves" class="form-control" required
                               value="{{ $v('saves') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Profile Visits <span class="text-danger">*</span></label>
                        <input type="number" name="profile_visits" class="form-control" required
                               value="{{ $v('profile_visits') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">New Followers <span class="text-danger">*</span></label>
                        <input type="number" name="new_followers" class="form-control" required
                               value="{{ $v('new_followers') }}">
                    </div>
                </div>
            </div>

            {{-- Campaign / Ad Set Results --}}
            <div class="card mb-3" data-block="campaign"
                 data-required-types="whatsapp_meta_ads,lead_generation_ads"
                 style="{{ $leadOrWhatsappActive ? '' : 'display:none;' }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Campaign / Ad Set Results</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addCampaignRow()">+ Add Row</button>
                </div>
                <div class="card-body" id="campaign-rows">
                    @php
                        $campaignResults = old('campaign_results', $report?->campaign_results ?? []);
                        if (empty($campaignResults)) $campaignResults = [['name'=>'','result'=>'','spend'=>'','note'=>'']];
                    @endphp
                    @foreach($campaignResults as $i => $row)
                        <div class="row g-2 mb-2 align-items-center campaign-row">
                            <div class="col-md-3">
                                <input name="campaign_results[{{ $i }}][name]" class="form-control" required
                                       placeholder="Campaign / Ad Set Name" value="{{ $row['name'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <input name="campaign_results[{{ $i }}][result]" class="form-control" required
                                       placeholder="Result" value="{{ $row['result'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <input name="campaign_results[{{ $i }}][spend]" class="form-control" required
                                       placeholder="Spend" value="{{ $row['spend'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <input name="campaign_results[{{ $i }}][note]" class="form-control" required
                                       placeholder="Note" value="{{ $row['note'] ?? '' }}">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100"
                                        onclick="this.closest('.campaign-row').remove()">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top Posts --}}
            <div class="card mb-3" data-block="topposts"
                 data-required-types="social_media_engagement"
                 style="{{ in_array('social_media_engagement', $activeTypes, true) ? '' : 'display:none;' }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Top Performing Posts</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTopPostRow()">+ Add</button>
                </div>
                <div class="card-body" id="top-post-rows">
                    @php
                        $topPosts = old('top_posts', $report?->top_posts ?? []);
                        if (empty($topPosts)) $topPosts = [['title'=>'','engagements'=>'','type'=>'']];
                    @endphp
                    @foreach($topPosts as $i => $row)
                        <div class="row g-2 mb-2 align-items-center top-post-row">
                            <div class="col-md-5">
                                <input name="top_posts[{{ $i }}][title]" class="form-control" required
                                       placeholder="Post Title / Description" value="{{ $row['title'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <input name="top_posts[{{ $i }}][engagements]" class="form-control" required
                                       placeholder="Engagements" value="{{ $row['engagements'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <input name="top_posts[{{ $i }}][type]" class="form-control" required
                                       placeholder="Type (Reel/Image/Carousel)" value="{{ $row['type'] ?? '' }}">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100"
                                        onclick="this.closest('.top-post-row').remove()">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- STEP 3 — Summary                                              --}}
        {{-- ============================================================ --}}
        <div class="wizard-panel" data-step="3" style="display:none;">

            <div class="card mb-3">
                <div class="card-header">Key Observations</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Creative attention <span class="text-danger">*</span></label>
                        <textarea name="key_observations[creative_attention]" rows="2" class="form-control" required>{{ old('key_observations.creative_attention', $report?->key_observations['creative_attention'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Audience response <span class="text-danger">*</span></label>
                        <textarea name="key_observations[audience_response]" rows="2" class="form-control" required>{{ old('key_observations.audience_response', $report?->key_observations['audience_response'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Placement interaction <span class="text-danger">*</span></label>
                        <textarea name="key_observations[placement_interaction]" rows="2" class="form-control" required>{{ old('key_observations.placement_interaction', $report?->key_observations['placement_interaction'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Performance Highlights <span class="text-danger">*</span></label>
                        <textarea name="performance_highlights[notes]" rows="2" class="form-control" required>{{ old('performance_highlights.notes', $report?->performance_highlights['notes'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Monthly Summary</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Overall <span class="text-danger">*</span></label>
                        <textarea name="monthly_summary[overall]" rows="2" class="form-control" required>{{ old('monthly_summary.overall', $report?->monthly_summary['overall'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Enquiry / Lead Gen <span class="text-danger">*</span></label>
                        <textarea name="monthly_summary[enquiry]" rows="2" class="form-control" required>{{ old('monthly_summary.enquiry', $report?->monthly_summary['enquiry'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Audience Engagement <span class="text-danger">*</span></label>
                        <textarea name="monthly_summary[engagement]" rows="2" class="form-control" required>{{ old('monthly_summary.engagement', $report?->monthly_summary['engagement'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand Visibility <span class="text-danger">*</span></label>
                        <textarea name="monthly_summary[brand_visibility]" rows="2" class="form-control" required>{{ old('monthly_summary.brand_visibility', $report?->monthly_summary['brand_visibility'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Next Month Plan</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Test New Creatives <span class="text-danger">*</span></label>
                        <textarea name="next_month_plan[test_creatives]" rows="2" class="form-control" required>{{ old('next_month_plan.test_creatives', $report?->next_month_plan['test_creatives'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Improve Audience Targeting <span class="text-danger">*</span></label>
                        <textarea name="next_month_plan[audience_targeting]" rows="2" class="form-control" required>{{ old('next_month_plan.audience_targeting', $report?->next_month_plan['audience_targeting'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Retarget Interested Users <span class="text-danger">*</span></label>
                        <textarea name="next_month_plan[retargeting]" rows="2" class="form-control" required>{{ old('next_month_plan.retargeting', $report?->next_month_plan['retargeting'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Test Different Messaging <span class="text-danger">*</span></label>
                        <textarea name="next_month_plan[messaging]" rows="2" class="form-control" required>{{ old('next_month_plan.messaging', $report?->next_month_plan['messaging'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Focus on High-Performers <span class="text-danger">*</span></label>
                        <textarea name="next_month_plan[high_performers]" rows="2" class="form-control" required>{{ old('next_month_plan.high_performers', $report?->next_month_plan['high_performers'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Increase Quality Leads <span class="text-danger">*</span></label>
                        <textarea name="next_month_plan[quality_leads]" rows="2" class="form-control" required>{{ old('next_month_plan.quality_leads', $report?->next_month_plan['quality_leads'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== Wizard nav ========== --}}
        <div class="wizard-actions">
            <button type="button" class="button button-secondary" id="prevBtn" disabled>← Previous</button>
            <div class="wizard-actions-right">
                <a href="{{ route('social-media-reports.index') }}" class="button button-secondary">Cancel</a>
                <button type="button" class="button button-primary" id="nextBtn" disabled>Next →</button>
                <button type="submit" class="button button-primary" id="submitBtn" style="display:none;">Save report</button>
            </div>
        </div>
    </form>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
<script>
/* ============================================================
 |  WIZARD — Step validation + gated Next / tab clicks
 ============================================================ */
(function () {
    const form      = document.getElementById('socialReportForm');
    const steps     = document.querySelectorAll('.wizard-step');
    const panels    = document.querySelectorAll('.wizard-panel');
    const prevBtn   = document.getElementById('prevBtn');
    const nextBtn   = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    let current = 1;
    const total = steps.length;

    // Hidden assign_service_ids[] inputs are always present
    const hasAssignServices = document.querySelectorAll(
        'input[name="assign_service_ids[]"]'
    ).length > 0;
    const activeReportTypes = @json($activeTypes);

    document.querySelectorAll('[data-required-types]').forEach(block => {
        const required = block.dataset.requiredTypes
            .split(',')
            .some(type => activeReportTypes.includes(type));
        block.querySelectorAll('input, select, textarea').forEach(field => {
            field.required = required;
        });
    });

    function isVisible(el) {
        if (!el) return false;
        if (el.disabled) return false;
        if (el.offsetParent === null) return false;

        let node = el.parentElement;
        while (node && node !== document.body) {
            const cs = window.getComputedStyle(node);
            if (cs.display === 'none' || cs.visibility === 'hidden') return false;
            node = node.parentElement;
        }
        return true;
    }

    function isStepValid(step) {
        const panel = document.querySelector(`.wizard-panel[data-step="${step}"]`);
        if (!panel) return true;

        let valid = true;

        panel.querySelectorAll('input, select, textarea').forEach(f => {
            if (!isVisible(f)) return;
            if (f.required && !f.checkValidity()) valid = false;
        });

        if (step === 1) {
            if (!hasAssignServices) valid = false;
        }

        return valid;
    }

    function reportStepIssues(step) {
        const panel = document.querySelector(`.wizard-panel[data-step="${step}"]`);
        if (!panel) return;

        for (const f of panel.querySelectorAll('input, select, textarea')) {
            if (!isVisible(f)) continue;
            if (f.required && !f.checkValidity()) {
                f.reportValidity();
                f.focus();
                return;
            }
        }

        if (step === 1) {
            if (!hasAssignServices) {
                alert('No assigned service was selected. Open this page from an assigned service.');
                return;
            }
        }
    }

    function decorateFields(step) {
        const panel = document.querySelector(`.wizard-panel[data-step="${step}"]`);
        if (!panel) return;

        panel.querySelectorAll('input, select, textarea').forEach(f => {
            if (!isVisible(f)) return;
            if (!f.required) return;

            const ok = f.checkValidity();
            f.classList.toggle('is-valid',   ok && f.value !== '');
            f.classList.toggle('is-invalid', !ok && f.value !== '');
        });
    }

    function render() {
        panels.forEach(p => {
            p.style.display = (+p.dataset.step === current) ? '' : 'none';
        });

        steps.forEach(s => {
            const n = +s.dataset.step;
            s.classList.toggle('is-active', n === current);
            s.classList.toggle('is-done',   n <  current);
            s.classList.toggle('is-locked', n >  current);
        });

        prevBtn.disabled = current === 1;

        const currentValid = isStepValid(current);
        nextBtn.disabled    = !currentValid;
        nextBtn.style.display   = current < total ? '' : 'none';
        submitBtn.style.display = current === total ? '' : 'none';

        decorateFields(current);
    }

    prevBtn.addEventListener('click', () => {
        if (current > 1) { current--; render(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    });

    nextBtn.addEventListener('click', () => {
        if (!isStepValid(current)) { reportStepIssues(current); render(); return; }
        if (current < total) { current++; render(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    });

    steps.forEach(s => {
        s.addEventListener('click', () => {
            const n = +s.dataset.step;
            if (n < current) { current = n; render(); return; }
            if (n === current + 1 && isStepValid(current)) { current = n; render(); return; }
        });
    });

    form.addEventListener('input',  render);
    form.addEventListener('change', render);

    form.addEventListener('submit', (e) => {
        for (let i = 1; i <= total; i++) {
            if (!isStepValid(i)) {
                e.preventDefault();
                current = i;
                render();
                reportStepIssues(i);
                return;
            }
        }
    });

    render();
})();

/* ============================================================
 |  DYNAMIC ROWS — Campaign / Ad Set
 ============================================================ */
let cIndex = document.getElementById('campaign-rows').children.length;
const campaignFieldsRequired = @json($leadOrWhatsappActive);

function addCampaignRow() {
    document.getElementById('campaign-rows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 align-items-center campaign-row">
            <div class="col-md-3"><input name="campaign_results[${cIndex}][name]" class="form-control" ${campaignFieldsRequired ? 'required' : ''} placeholder="Campaign / Ad Set Name"></div>
            <div class="col-md-2"><input name="campaign_results[${cIndex}][result]" class="form-control" ${campaignFieldsRequired ? 'required' : ''} placeholder="Result"></div>
            <div class="col-md-2"><input name="campaign_results[${cIndex}][spend]" class="form-control" ${campaignFieldsRequired ? 'required' : ''} placeholder="Spend"></div>
            <div class="col-md-4"><input name="campaign_results[${cIndex}][note]" class="form-control" ${campaignFieldsRequired ? 'required' : ''} placeholder="Note"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.campaign-row').remove()">×</button></div>
        </div>`);
    cIndex++;
    document.getElementById('socialReportForm')?.dispatchEvent(new Event('change', { bubbles: true }));
}

/* ============================================================
 |  DYNAMIC ROWS — Top Posts
 ============================================================ */
let tpIndex = document.getElementById('top-post-rows').children.length;
const topPostFieldsRequired = @json(in_array('social_media_engagement', $activeTypes, true));

function addTopPostRow() {
    document.getElementById('top-post-rows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 align-items-center top-post-row">
            <div class="col-md-5"><input name="top_posts[${tpIndex}][title]" class="form-control" ${topPostFieldsRequired ? 'required' : ''} placeholder="Post Title / Description"></div>
            <div class="col-md-3"><input name="top_posts[${tpIndex}][engagements]" class="form-control" ${topPostFieldsRequired ? 'required' : ''} placeholder="Engagements"></div>
            <div class="col-md-3"><input name="top_posts[${tpIndex}][type]" class="form-control" ${topPostFieldsRequired ? 'required' : ''} placeholder="Type (Reel/Image/Carousel)"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.top-post-row').remove()">×</button></div>
        </div>`);
    tpIndex++;
    document.getElementById('socialReportForm')?.dispatchEvent(new Event('change', { bubbles: true }));
}
</script>

@include('admin.partials.footer')