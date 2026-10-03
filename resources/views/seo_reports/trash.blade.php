<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Trashed SEO Reports | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="admin-page report-page">
<div class="admin-shell">
    @include('admin.partials.sidebar', ['active' => 'reports'])
    <main class="admin-main">
        @include('admin.partials.topbar', ['pageTitle' => 'Report trash'])
        <div class="dashboard-content report-content">
            <section class="page-heading report-page-heading">
                <div>
                    <div class="eyebrow"><span class="status-dot status-dot-dark"></span> REPORT LIBRARY</div>
                    <h1>Trashed reports</h1>
                    <p class="page-subtitle">Restore removed reports with their uploaded files intact.</p>
                </div>
                <a href="{{ route('seo-reports.index') }}" class="button button-secondary">Back to reports</a>
            </section>

            @if(session('success'))
                <div class="report-flash" role="status">{{ session('success') }}</div>
            @endif

            <section class="panel report-table-panel">
                <div class="report-table-meta"><div><strong>{{ $reports->total() }}</strong> trashed reports</div></div>
                <div class="report-table-scroll">
                    <table class="report-table trash-table">
                        <thead><tr><th>CLIENT</th><th>WEBSITE</th><th>PERIOD</th><th>DELETED</th><th>ACTION</th></tr></thead>
                        <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td><strong class="report-client">{{ $report->client->name }}</strong></td>
                                <td><span class="report-website">{{ $report->client->website }}</span></td>
                                <td><span class="report-period-tag">{{ $report->reporting_period }}</span></td>
                                <td>{{ $report->deleted_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <form action="{{ route('seo-reports.restore', $report->id) }}" method="POST">
                                        @csrf
                                        <button class="button button-primary restore-button" type="submit">Restore</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="report-empty">Trash is empty.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reports->hasPages())
                    <div class="report-pagination">{{ $reports->links() }}</div>
                @endif
            </section>
        </div>
    </main>
</div>
</body>
</html>