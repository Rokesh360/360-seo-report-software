@php $v = fn ($key, $default = '') => old($key, $service?->{$key} ?? $default); @endphp
@include('admin.partials.header', [
'pageTitle' => $title,
'active' => 'services',
'bodyClass' => 'admin-page report-page',
'includeBootstrap' => true,
])
<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><span class="status-dot status-dot-dark"></span> SERVICE DIRECTORY</div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">Services are used to categorize and assign work to clients.</p>
        </div>
        <a href="{{ route('services.index') }}" class="button button-secondary">Back to services</a>
    </section>

    @if($errors->any())
    <div class="report-validation" role="alert">
        <strong>Please review the service details.</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ $action }}" method="POST" class="report-form client-form panel">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="client-fields row g-3">
            <div class="col-md-4">
                <label class="form-label" for="service-name">Service name</label>
                <input id="service-name" name="name" class="form-control" value="{{ $v('name') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="service-status">Status</label>
                <select id="service-status" name="status" class="form-select">
                    <option value="1" @selected($v('status', 1)==1)>Active</option>
                    <option value="0" @selected($v('status', 1)==0)>Inactive</option>
                </select>
            </div>

            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input
                        type="checkbox"
                        id="service-is-campaign-available"
                        name="is_campaign_available"
                        value="1"
                        class="form-check-input"
                        @checked((int) $v('is_campaign_available', 0)===1)>

                    <label
                        class="form-check-label"
                        for="service-is-campaign-available">
                        Is campaign available?
                    </label>
                </div>
            </div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">Save service</button>
            <a href="{{ route('services.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
    @include('admin.partials.footer')