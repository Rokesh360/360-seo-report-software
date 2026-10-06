@include('admin.partials.header', [
    'pageTitle' => $title,
    'active' => 'assign-services',
    'bodyClass' => 'admin-page report-page',
    'includeBootstrap' => true,
])

@php
$v = fn ($key, $default = '') => old($key, $assignService?->{$key} ?? $default);
@endphp

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">
                Select a client and service, then set the validity period.
            </p>
        </div>
        <a href="{{ route('assign-services.index') }}" class="button button-secondary">
            Back to assignments
        </a>
    </section>

    @if($errors->any())
    <div class="report-validation" role="alert">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ $action }}" method="POST" class="report-form" novalidate>
        @csrf
        @if($method !== 'POST') @method($method) @endif

        {{-- ==================== Assignment Details ==================== --}}
        <div class="card mb-3">
            <div class="card-header">Assignment Details</div>
            <div class="card-body row g-3">
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

                <div class="col-md-6">
                    <label class="form-label">Service</label>
                    <select name="service_id"
                        class="form-select @error('service_id') is-invalid @enderror" required>
                        <option value="">-- Select Service --</option>
                        @foreach($services as $service)
                        <option value="{{ $service->id }}"
                            @selected(old('service_id', $assignService?->service_id ?? '') == $service->id)>
                            {{ $service->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('service_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input type="text" name="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ $v('title') }}"
                        placeholder="e.g. SEO Monthly Retainer – Q1 2026" required>
                    @error('title')<span class="field-error">{{ $message }}</span>@enderror
                </div>

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

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Optional notes about this assignment...">{{ $v('description') }}</textarea>
                    @error('description')<span class="field-error">{{ $message }}</span>@enderror
                </div>

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

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">
                {{ $method === 'POST' ? 'Assign service' : 'Update assignment' }}
            </button>
            <a href="{{ route('assign-services.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const fromDate = document.getElementById('from_date');
        const toDate   = document.getElementById('to_date');

        if (!fromDate || !toDate) return;

        /**
         * When the user picks a "from date", the "to date" field:
         *  1. Gets a minimum value set to the same day (blocks earlier dates).
         *  2. If the current to_date is earlier than the new from_date, it is cleared.
         *  3. The native date picker also disables earlier days automatically
         *     because of the `min` attribute.
         */
        function syncToDateMin() {
            const fromValue = fromDate.value;

            if (!fromValue) {
                toDate.removeAttribute('min');
                return;
            }

            // Block all dates before the selected from_date
            toDate.min = fromValue;

            // If a to_date is already set but is now invalid, clear it
            if (toDate.value && toDate.value < fromValue) {
                toDate.value = '';
            }
        }

        // Run on change of from_date
        fromDate.addEventListener('change', syncToDateMin);
        fromDate.addEventListener('input', syncToDateMin);

        // Run once on page load (handles old() values / edit mode)
        syncToDateMin();
    })();
</script>

@include('admin.partials.footer')