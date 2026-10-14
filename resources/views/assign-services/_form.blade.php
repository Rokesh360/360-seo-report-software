@include('admin.partials.header', [
'pageTitle' => $title,
'active' => 'assign-services',
'bodyClass' => 'admin-page report-page',
'includeBootstrap' => true,
])

@php
$v = fn ($key, $default = '') => old($key, $assignService?->{$key} ?? $default);

// Multi-select: existing service IDs
$selectedServiceIds = old('service_id', $assignService?->service_id ?? []);
if (!is_array($selectedServiceIds)) {
$selectedServiceIds = $selectedServiceIds ? [(int) $selectedServiceIds] : [];
}
$selectedServiceIds = array_map('intval', $selectedServiceIds);

$reportTypeByServiceName = [
'WhatsApp Meta Ads Monthly Report' => 'whatsapp_meta_ads',
'Lead Generation Ads Monthly Report' => 'lead_generation_ads',
'Social Media Engagement Monthly Report' => 'social_media_engagement',
];
$reportTypesByServiceId = $services->mapWithKeys(fn ($service) => [
$service->id => $reportTypeByServiceName[$service->name] ?? '',
]);
$selectedReportTypes = collect($selectedServiceIds)
->map(fn ($serviceId) => $reportTypesByServiceId[$serviceId] ?? '')
->filter()
->unique();
$showReportNarrativeFields = $selectedReportTypes->isNotEmpty();
@endphp

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">
                @if($client)
                Assigning services to <strong>{{ $client->name }}</strong> —
                select service(s) and set the validity period.
                @else
                Select service(s) and set the validity period.
                @endif
            </p>
        </div>
        <a href="{{ route('assign-services.index', $client ? ['client' => $client->id] : []) }}"
            class="button button-secondary">
            Back to assign services
        </a>
    </section>

    <form action="{{ $action }}" method="POST" class="report-form" novalidate>
        @csrf
        @if($method !== 'POST') @method($method) @endif

        {{-- Client is injected server-side (from route / record), not editable here --}}
        <input type="hidden" name="client_id" value="{{ $selectedClientId ?? $assignService?->client_id }}">

        {{-- ==================== Assignment Details ==================== --}}
        <div class="card mb-3">
            <div class="card-header">Assign Service Details</div>
            <div class="card-body row g-3">

                {{-- Service multi-select --}}
                <div class="col-4">
                    <label class="form-label">
                        Service <span class="required-star" aria-hidden="true">*</span>
                    </label>

                    <div class="multi-select" id="serviceMultiSelect">
                        <button type="button" class="multi-select-trigger" id="serviceTrigger">
                            <span id="serviceTriggerLabel">-- Select Service(s) --</span>
                            <svg viewBox="0 0 20 20" width="14" height="14" aria-hidden="true">
                                <path d="M6 8l4 4 4-4" fill="none" stroke="currentColor"
                                    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <div class="multi-select-panel" id="servicePanel">
                            @forelse($services as $service)
                            <label class="multi-select-option">
                                <input type="checkbox"
                                    name="service_id[]"
                                    value="{{ $service->id }}"
                                    data-report-type="{{ $reportTypesByServiceId[$service->id] ?? '' }}"
                                    @checked(in_array((int) $service->id, $selectedServiceIds, true))>
                                <span>{{ $service->name }}</span>
                            </label>
                            @empty
                            <div class="multi-select-empty">No services available.</div>
                            @endforelse
                        </div>
                    </div>

                    @error('service_id')<span class="field-error">{{ $message }}</span>@enderror
                    @error('service_id.*')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Title --}}
                <div class="col-4">
                    <label class="form-label">
                        Title <span class="required-star" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ $v('title') }}"
                        placeholder="e.g. SEO Monthly Retainer – Q1 2026" required>
                    @error('title')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-4">
                    <label class="form-label">
                        Budget <span class="required-star" aria-hidden="true">*</span>
                    </label>
                    <input type="number" name="budget" step="1" min="0"
                        class="form-control @error('budget') is-invalid @enderror"
                        value="{{ old('budget', $assignService?->budget ?? '') }}"
                        placeholder="e.g. 50000" required>
                    @error('budget')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- From / To --}}
                <div class="col-md-6">
                    <label class="form-label">
                        From Date <span class="required-star" aria-hidden="true">*</span>
                    </label>
                    <input type="date" name="from_date" id="from_date"
                        class="form-control @error('from_date') is-invalid @enderror"
                        value="{{ old('from_date', $assignService?->from_date?->format('Y-m-d') ?? '') }}"
                        required>
                    @error('from_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        To Date <span class="required-star" aria-hidden="true">*</span>
                    </label>
                    <input type="date" name="to_date" id="to_date"
                        class="form-control @error('to_date') is-invalid @enderror"
                        value="{{ old('to_date', $assignService?->to_date?->format('Y-m-d') ?? '') }}"
                        required>
                    @error('to_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Platforms --}}
                <div class="col-md-6">
                    <label class="form-label">Platforms</label>
                    <input name="platforms" class="form-control @error('platforms') is-invalid @enderror"
                        value="{{ $v('platforms') }}"
                        placeholder="e.g. Instagram, Facebook, Google">
                    @error('platforms')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Campaign Objective --}}
                <div class="col-md-6">
                    <label class="form-label">Campaign Objective</label>
                    <input name="campaign_objective" class="form-control @error('campaign_objective') is-invalid @enderror"
                        value="{{ $v('campaign_objective') }}">
                    @error('campaign_objective')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Campaign Overview --}}
                <div class="col-12">
                    <label class="form-label">Campaign Overview</label>
                    <textarea name="campaign_overview" rows="2"
                        class="form-control @error('campaign_overview') is-invalid @enderror">{{ $v('campaign_overview') }}</textarea>
                    @error('campaign_overview')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Target Audience --}}
                <div class="col-md-6">
                    <label class="form-label">Target Audience</label>
                    <textarea name="target_audience" rows="2"
                        class="form-control @error('target_audience') is-invalid @enderror">{{ $v('target_audience') }}</textarea>
                    @error('target_audience')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Main Campaign Focus --}}
                <div class="col-md-6">
                    <label class="form-label">Main Campaign Focus</label>
                    <textarea name="campaign_focus" rows="2"
                        class="form-control @error('campaign_focus') is-invalid @enderror">{{ $v('campaign_focus') }}</textarea>
                    @error('campaign_focus')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Key Highlights --}}
                <div class="col-12">
                    <label class="form-label">Key Highlights</label>
                    <textarea name="key_highlights" rows="2"
                        class="form-control @error('key_highlights') is-invalid @enderror">{{ $v('key_highlights') }}</textarea>
                    @error('key_highlights')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Optional notes about this assignment...">{{ $v('description') }}</textarea>
                    @error('description')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Status --}}
                <div class="col-md-6">
                    <label class="form-label">
                        Status <span class="required-star" aria-hidden="true">*</span>
                    </label>
                    <select name="status"
                        class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" @selected(old('status', $assignService?->status ?? 1) == 1)>Active</option>
                        <option value="0" @selected(old('status', $assignService?->status ?? 1) == 0)>Inactive</option>
                    </select>
                    @error('status')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        @php
        $narrativeValue = fn ($group, $field) => old(
        $group . '.' . $field,
        data_get($assignService?->{$group}, $field, '')
        );
        @endphp

        <div class="assignment-report-narrative" data-report-types="whatsapp_meta_ads lead_generation_ads social_media_engagement" @if(! $showReportNarrativeFields) hidden @endif>
            <div class="card mb-3">
                <div class="card-header">Key Observations</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="creative-attention">Creative attention</label>
                        <textarea id="creative-attention" name="key_observations[creative_attention]" rows="2" class="form-control @error('key_observations.creative_attention') is-invalid @enderror">{{ $narrativeValue('key_observations', 'creative_attention') }}</textarea>
                        @error('key_observations.creative_attention')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="audience-response">Audience response</label>
                        <textarea id="audience-response" name="key_observations[audience_response]" rows="2" class="form-control @error('key_observations.audience_response') is-invalid @enderror">{{ $narrativeValue('key_observations', 'audience_response') }}</textarea>
                        @error('key_observations.audience_response')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="placement-interaction">Placement interaction</label>
                        <textarea id="placement-interaction" name="key_observations[placement_interaction]" rows="2" class="form-control @error('key_observations.placement_interaction') is-invalid @enderror">{{ $narrativeValue('key_observations', 'placement_interaction') }}</textarea>
                        @error('key_observations.placement_interaction')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="performance-highlights">Performance highlights</label>
                        <textarea id="performance-highlights" name="performance_highlights[notes]" rows="2" class="form-control @error('performance_highlights.notes') is-invalid @enderror">{{ $narrativeValue('performance_highlights', 'notes') }}</textarea>
                        @error('performance_highlights.notes')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Monthly Summary</div>
                <div class="card-body row g-3">
                    @foreach([
                    'overall' => 'Overall performance',
                    'enquiry' => 'Enquiry / lead generation',
                    'engagement' => 'Audience engagement',
                    'brand_visibility' => 'Brand visibility',
                    ] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label" for="summary-{{ $field }}">{{ $label }}</label>
                        <textarea id="summary-{{ $field }}" name="monthly_summary[{{ $field }}]" rows="2" class="form-control @error('monthly_summary.' . $field) is-invalid @enderror">{{ $narrativeValue('monthly_summary', $field) }}</textarea>
                        @error('monthly_summary.' . $field)<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Next Month Plan</div>
                <div class="card-body row g-3">
                    @foreach([
                    'test_creatives' => 'Test new creatives',
                    'audience_targeting' => 'Improve audience targeting',
                    'retargeting' => 'Retarget interested users',
                    'messaging' => 'Test different messaging',
                    'high_performers' => 'Focus on high-performing audiences',
                    'quality_leads' => 'Increase quality leads / enquiries',
                    ] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label" for="plan-{{ $field }}">{{ $label }}</label>
                        <textarea id="plan-{{ $field }}" name="next_month_plan[{{ $field }}]" rows="2" class="form-control @error('next_month_plan.' . $field) is-invalid @enderror">{{ $narrativeValue('next_month_plan', $field) }}</textarea>
                        @error('next_month_plan.' . $field)<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">
                {{ $method === 'POST' ? 'Assign service' : 'Update service' }}
            </button>
            <a href="{{ route('assign-services.index', $client ? ['client' => $client->id] : []) }}"
                class="button button-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
<script>
    /* ---------- Service multi-select ---------- */
    (function() {
        const trigger = document.getElementById('serviceTrigger');
        const panel = document.getElementById('servicePanel');
        const label = document.getElementById('serviceTriggerLabel');
        const badge = document.getElementById('serviceCountBadge');

        if (!trigger || !panel) return;

        function refresh() {
            const checked = Array.from(panel.querySelectorAll('input[type="checkbox"]:checked'));
            const count = checked.length;
            const labels = checked.map(input => input.nextElementSibling.textContent.trim());

            label.textContent = count ?
                labels.join(', ') :
                '-- Select Service(s) --';

            if (badge) badge.textContent = count + ' selected';
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            panel.classList.toggle('is-open');
        });

        panel.addEventListener('change', refresh);

        function updateNarrativeFields() {
            const selectedTypes = new Set(Array.from(panel.querySelectorAll('input[type="checkbox"]:checked'))
                .map(input => input.dataset.reportType)
                .filter(Boolean));

            document.querySelectorAll('[data-report-types]').forEach(section => {
                const types = section.dataset.reportTypes.split(/\s+/);
                section.hidden = !types.some(type => selectedTypes.has(type));
            });
        }

        panel.addEventListener('change', updateNarrativeFields);

        document.addEventListener('click', (e) => {
            if (!panel.contains(e.target) && e.target !== trigger) {
                panel.classList.remove('is-open');
            }
        });

        refresh();
        updateNarrativeFields();
    })();

    /* ---------- from_date / to_date sync ---------- */
    (function() {
        const fromDate = document.getElementById('from_date');
        const toDate = document.getElementById('to_date');

        if (!fromDate || !toDate) return;

        function syncToDateMin() {
            const fromValue = fromDate.value;
            if (!fromValue) {
                toDate.removeAttribute('min');
                return;
            }
            toDate.min = fromValue;
            if (toDate.value && toDate.value < fromValue) {
                toDate.value = '';
            }
        }

        fromDate.addEventListener('change', syncToDateMin);
        fromDate.addEventListener('input', syncToDateMin);
        syncToDateMin();
    })();
</script>

@include('admin.partials.footer')