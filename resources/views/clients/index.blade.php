<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clients | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="admin-page report-page">
<div class="admin-shell">
    @include('admin.partials.sidebar', ['active' => 'clients'])
    <main class="admin-main">
        @include('admin.partials.topbar', ['pageTitle' => 'Clients'])
        <div class="dashboard-content report-content">
            <section class="page-heading report-page-heading">
                <div>
                    <div class="eyebrow"><span class="status-dot status-dot-dark"></span> CLIENT DIRECTORY</div>
                    <h1>Clients</h1>
                    <p class="page-subtitle">Manage client details and their monthly SEO reports.</p>
                </div>
                <a href="{{ route('clients.create') }}" class="button button-primary"><span aria-hidden="true">+</span> New client</a>
            </section>

            @if(session('success')) <div class="report-flash" role="status">{{ session('success') }}</div> @endif
            @if(session('error')) <div class="report-validation" role="alert">{{ session('error') }}</div> @endif

            <section class="panel report-table-panel" aria-label="Client directory">
                <div class="report-table-meta">
                    <div><strong>{{ $clients->total() }}</strong> {{ \Illuminate\Support\Str::plural('client', $clients->total()) }}</div>
                    <span>Client accounts and report activity</span>
                </div>
                <div class="report-table-scroll">
                    <table class="report-table client-table">
                        <thead><tr><th>S.NO</th><th>CLIENT</th><th>WEBSITE</th><th>CONTACT</th><th>REPORTS</th><th>STATUS</th><th class="report-actions-heading" style="text-align: center;">ACTIONS</th></tr></thead>
                        <tbody>
                        @forelse($clients as $client)
                            <tr>
                                <td>{{ ($clients->firstItem() ?? 0) + $loop->index }}</td>
                                <td><strong class="report-client">{{ $client->name }}</strong></td>
                                <td><span class="report-website">{{ $client->website }}</span></td>
                                <td><span class="client-contact">{{ $client->email ?: 'No email' }}</span><span class="report-preparer">{{ $client->phone ?: 'No phone' }}</span></td>
                                <td><span class="client-report-count">{{ $client->seo_reports_count }}</span></td>
                                <td><span @class(['client-status', 'client-status-active' => $client->status, 'client-status-inactive' => ! $client->status])>{{ $client->status ? 'Active' : 'Inactive' }}</span></td>
                                <td class="report-actions-cell">
                                    <div class="report-row-actions">
                                        <a href="{{ route('clients.edit', $client->id) }}" class="report-edit-link">Edit</a>
                                        <form action="{{ route('clients.destroy', $client->id) }}" method="POST" onsubmit="return confirm('Delete this client? Clients with reports cannot be deleted.')">
                                            @csrf @method('DELETE')
                                            <button class="report-delete-button" type="submit" aria-label="Delete {{ $client->name }}" title="Delete client">
                                                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 6h12M8 6V4h4v2m3 0-.7 10H5.7L5 6m3 3v4m4-4v4"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="report-empty">No clients yet. Add a client to start organizing SEO reports.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($clients->hasPages())
                    <div class="report-pagination">{{ $clients->links() }}</div>
                @endif
            </section>
            <footer class="dashboard-footer"><span>Client directory</span><span>{{ $clients->total() }} total {{ \Illuminate\Support\Str::plural('client', $clients->total()) }}</span></footer>
        </div>
    </main>
</div>
</body>
</html>
