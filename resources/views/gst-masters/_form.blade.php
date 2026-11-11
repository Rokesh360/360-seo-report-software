@php $v = fn ($key, $default = '') => old($key, $gstMaster?->{$key} ?? $default); @endphp
@include('admin.partials.header', [
'pageTitle' => $title,
'active' => 'gst',
'bodyClass' => 'admin-page report-page',
'includeBootstrap' => true,
])

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><span class="status-dot status-dot-dark"></span> MASTERS</div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">GST rates are used when calculating taxes.</p>
        </div>
        <a href="{{ route('gst-masters.index') }}" class="button button-secondary">Back to GST</a>
    </section>

    <form action="{{ $action }}" method="POST" class="report-form client-form panel" novalidate>
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="client-fields row g-3">
            <div class="col-12">
                <label class="form-label" for="gst-name">
                    Name <span class="required-star" aria-hidden="true">*</span>
                </label>
                <input id="gst-name" name="name" placeholder="GST 18%"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ $v('name') }}" required>
                @error('name') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="gst-percentage">
                    Percentage (%) <span class="required-star" aria-hidden="true">*</span>
                </label>
                <input id="gst-percentage" type="number" step="0.01" min="0" max="100" name="percentage"
                    class="form-control @error('percentage') is-invalid @enderror"
                    value="{{ $v('percentage') }}" required>
                @error('percentage') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="gst-status">
                    Status <span class="required-star" aria-hidden="true">*</span>
                </label>
                <select id="gst-status" name="status"
                    class="form-select @error('status') is-invalid @enderror">
                    <option value="1" @selected((int) $v('status', 1)===1)>Active</option>
                    <option value="0" @selected((int) $v('status', 1)===0)>Inactive</option>
                </select>
                @error('status') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">Save GST</button>
            <a href="{{ route('gst-masters.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

@include('admin.partials.footer')