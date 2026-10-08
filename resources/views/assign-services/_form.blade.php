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
@endphp

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">
                Select a client and service(s), then set the validity period.
            </p>
        </div>
        <a href="{{ route('assign-services.index') }}" class="button button-secondary">
            Back to assignments
        </a>
    </section>

    <form action="{{ $action }}" method="POST" class="report-form" novalidate>
        @csrf
        @if($method !== 'POST') @method($method) @endif

        {{-- ==================== Assignment Details ==================== --}}
        <div class="card mb-3">
            <div class="card-header">Assignment Details</div>
            <div class="card-body row g-3">

                {{-- Client --}}
                <div class="col-md-6">
                    <label class="form-label">Client</label>
                    <select name="client_id"
                        class="form-select @error('client_id') is-invalid @enderror" required>
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}"
                            @selected(old('client_id', $assignService?->client_id ?? '') == $client->id)>
                            {{ $client->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('client_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- Service multi-select --}}
                <div class="col-md-6">
                    <label class="form-label">
                        Service
                    </label>

                    <div class="multi-select" id="serviceMultiSelect">
                        <button type="button" class="multi-select-trigger" id="serviceTrigger">
                            <span id="serviceTriggerLabel">-- Select Service(s) --</span>
                            <svg viewBox="0 0 20 20" width="14" height="14" aria-hidden="true">
                                <path d="M6 8l4 4 4-4" fill="none" stroke="currentColor"
                                      stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <div class="multi-select-panel" id="servicePanel">
                            @forelse($services as $service)
                                <label class="multi-select-option">
                                    <input type="checkbox"
                                           name="service_id[]"
                                           value="{{ $service->id }}"
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
                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input type="text" name="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ $v('title') }}"
                        placeholder="e.g. SEO Monthly Retainer – Q1 2026" required>
                    @error('title')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                {{-- From / To --}}
                <div class="col-md-6">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" id="from_date"
                        class="form-control @error('from_date') is-invalid @enderror"
                        value="{{ old('from_date', $assignService?->from_date?->format('Y-m-d') ?? '') }}"
                        required>
                    @error('from_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" id="to_date"
                        class="form-control @error('to_date') is-invalid @enderror"
                        value="{{ old('to_date', $assignService?->to_date?->format('Y-m-d') ?? '') }}"
                        required>
                    @error('to_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Platforms</label>
                    <input name="platforms" class="form-control @error('platforms') is-invalid @enderror"
                           value="{{ $v('platforms') }}">
                    @error('platforms')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Campaign Objective</label>
                    <input name="campaign_objective" class="form-control @error('campaign_objective') is-invalid @enderror"
                           value="{{ $v('campaign_objective') }}">
                    @error('campaign_objective')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Campaign Overview</label>
                    <textarea name="campaign_overview" rows="2"
                              class="form-control @error('campaign_overview') is-invalid @enderror">{{ $v('campaign_overview') }}</textarea>
                    @error('campaign_overview')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Target Audience</label>
                    <textarea name="target_audience" rows="2"
                              class="form-control @error('target_audience') is-invalid @enderror">{{ $v('target_audience') }}</textarea>
                    @error('target_audience')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Main Campaign Focus</label>
                    <textarea name="campaign_focus" rows="2"
                              class="form-control @error('campaign_focus') is-invalid @enderror">{{ $v('campaign_focus') }}</textarea>
                    @error('campaign_focus')<span class="field-error">{{ $message }}</span>@enderror
                </div>

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
                    <label class="form-label">Status</label>
                    <select name="status"
                        class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" @selected(old('status', $assignService?->status ?? 1) == 1)>Active</option>
                        <option value="0" @selected(old('status', $assignService?->status ?? 1) == 0)>Inactive</option>
                    </select>
                    @error('status')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">
                {{ $method === 'POST' ? 'Assign service' : 'Update assignment' }}
            </button>
            <a href="{{ route('assign-services.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
<script>
/* ---------- Service multi-select ---------- */
(function () {
    const trigger = document.getElementById('serviceTrigger');
    const panel   = document.getElementById('servicePanel');
    const label   = document.getElementById('serviceTriggerLabel');
    const badge   = document.getElementById('serviceCountBadge');

    if (!trigger || !panel) return;

    function refresh() {
        const checked = Array.from(panel.querySelectorAll('input[type="checkbox"]:checked'));
        const count   = checked.length;
        const labels  = checked.map(input => input.nextElementSibling.textContent.trim());

        label.textContent = count
            ? labels.join(', ')
            : '-- Select Service(s) --';

        if (badge) badge.textContent = count + ' selected';
    }

    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.toggle('is-open');
    });

    panel.addEventListener('change', refresh);

    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && e.target !== trigger) {
            panel.classList.remove('is-open');
        }
    });

    refresh();
})();

/* ---------- from_date / to_date sync ---------- */
(function () {
    const fromDate = document.getElementById('from_date');
    const toDate   = document.getElementById('to_date');

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