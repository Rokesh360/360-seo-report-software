@include('admin.partials.header', [
'pageTitle' => 'Social Media Reports',
'active' => 'metrics-reports',
])

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <span class="status-dot status-dot-dark"></span>
                REPORT LIBRARY
            </div>
            <h1>Select a client</h1>
            <p class="page-subtitle">
                Pick a client to view their assigned services and daily metrics.
            </p>
        </div>
    </section>

    @if(session('success'))
    <div class="report-flash" role="status">{{ session('success') }}</div>
    @endif

    <section class="client-card-grid">
        @forelse($clients->where('status', true) as $client)
        <a href="{{ route('metrics-reports.client-services', $client->id) }}"
            class="client-card">
            <div class="client-card__avatar">
                {{ strtoupper(substr($client->name, 0, 1)) }}
            </div>
            <div class="client-card__body">
                <h3>{{ $client->name }}</h3>
                <p>{{ $client->assignServices_count }}
                    {{ \Illuminate\Support\Str::plural('assigned service', $client->assignServices_count) }}
                </p>
            </div>
            <span class="client-card__arrow">→</span>
        </a>
        @empty
        <p class="empty-state">No active clients found. <a href="{{ route('clients.create') }}">Add one</a>.</p>
        @endforelse
    </section>
</div>

@include('admin.partials.footer')