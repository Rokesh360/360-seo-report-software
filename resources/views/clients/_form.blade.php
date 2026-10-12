@php $v = fn ($key, $default = '') => old($key, $client?->{$key} ?? $default); @endphp
@include('admin.partials.header', [
'pageTitle' => $title,
'active' => 'clients',
'bodyClass' => 'admin-page report-page',
'includeBootstrap' => true,
])

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><span class="status-dot status-dot-dark"></span> CLIENT DIRECTORY</div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">Client details are used to organize monthly SEO reporting.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="button button-secondary">Back to clients</a>
    </section>

    <form action="{{ $action }}" method="POST" class="report-form client-form panel" novalidate>
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="client-fields row g-3">
            <div class="col-12">
                <label class="form-label" for="client-name">
                    Client name <span class="required-star" aria-hidden="true">*</span>
                </label>
                <input id="client-name" name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ $v('name') }}" required>
                @error('name')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="col-12">
                <label class="form-label" for="client-website">
                    Website <span class="required-star" aria-hidden="true">*</span>
                </label>
                <input id="client-website" name="website"
                    class="form-control @error('website') is-invalid @enderror"
                    placeholder="studiomarvel.in"
                    value="{{ $v('website') }}" required>
                @error('website')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="client-email">
                    Email <span class="required-star" aria-hidden="true">*</span>
                </label>
                <input id="client-email" type="email" name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ $v('email') }}" required>
                @error('email')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="client-phone">
                    Phone <span class="required-star" aria-hidden="true">*</span>
                </label>
                <input id="client-phone" name="phone"
                    class="form-control @error('phone') is-invalid @enderror"
                    value="{{ $v('phone') }}" required>
                @error('phone')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="client-status">
                    Status <span class="required-star" aria-hidden="true">*</span>
                </label>
                <select id="client-status" name="status"
                    class="form-select @error('status') is-invalid @enderror">
                    <option value="1" @selected((int) $v('status', 1) === 1)>Active</option>
                    <option value="0" @selected((int) $v('status', 1) === 0)>Inactive</option>
                </select>
                @error('status')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">Save client</button>
            <a href="{{ route('clients.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

@include('admin.partials.footer')