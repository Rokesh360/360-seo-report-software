@include('admin.partials.header', [
    'pageTitle' => 'Google Ads Reports',
    'active' => 'google-ads',
])

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><span class="status-dot status-dot-dark"></span> REPORT LIBRARY</div>
            <h1>Google Ads reports</h1>
            <p class="page-subtitle">Select a client to view their Google Ads assignments and reports.</p>
        </div>
    </section>

    @if(session('success'))
        <div class="report-flash" role="status">{{ session('success') }}</div>
    @endif

    <section class="client-card-grid">
        @forelse($clients as $client)
            <a href="{{ route('google-ads.client-services', $client) }}" class="client-card">
                <div class="client-card__avatar">{{ strtoupper(substr($client->name, 0, 1)) }}</div>
                <div class="client-card__body">
                    <h3>{{ $client->name }}</h3>
                    @php $serviceCount = $assignments->where('client_id', $client->id)->count(); @endphp
                    <p>{{ $serviceCount }} {{ \Illuminate\Support\Str::plural('Google Ads service', $serviceCount) }}</p>
                </div>
                <span class="client-card__arrow">→</span>
            </a>
        @empty
            <p class="empty-state">No active clients have Google Ads services assigned yet.</p>
        @endforelse
    </section>
</div>

@include('admin.partials.footer')
