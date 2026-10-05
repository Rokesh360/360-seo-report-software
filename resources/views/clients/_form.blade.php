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

            @if($errors->any())
                <div class="report-validation" role="alert">
                    <strong>Please review the client details.</strong>
                    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

    <form action="{{ $action }}" method="POST" class="report-form client-form panel">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="client-fields row g-3">
            <div class="col-12"><label class="form-label" for="client-name">Client name</label>
                <input id="client-name" name="name" class="form-control" value="{{ $v('name') }}" required></div>
            <div class="col-12"><label class="form-label" for="client-website">Website</label>
                <input id="client-website" name="website" class="form-control" placeholder="studiomarvel.in" value="{{ $v('website') }}" required></div>
            <div class="col-md-6"><label class="form-label" for="client-email">Email</label>
                <input id="client-email" type="email" name="email" class="form-control" value="{{ $v('email') }}"></div>
            <div class="col-md-6"><label class="form-label" for="client-phone">Phone</label>
                <input id="client-phone" name="phone" class="form-control" value="{{ $v('phone') }}"></div>
            <div class="col-md-6"><label class="form-label" for="client-status">Status</label>
                <select id="client-status" name="status" class="form-select">
                    <option value="1" @selected($v('status', 1) == 1)>Active</option>
                    <option value="0" @selected($v('status', 1) == 0)>Inactive</option>
                </select></div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">Save client</button>
            <a href="{{ route('clients.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
@include('admin.partials.footer')
