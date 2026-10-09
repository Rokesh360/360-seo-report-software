@include('admin.partials.header', [
'pageTitle' => 'Previous Metrics — ' . ($assignService->title ?? 'Service'),
'active' => 'social-media-reports',
])

@php
$filled = $rows->filter(fn ($r) => $r['report'])->count();
@endphp

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('social-media-reports.client-services', $assignService->client_id) }}">
                    ← {{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}
                </a>
            </div>
            <h1>Previous Metrics</h1>
            <p class="page-subtitle">
                <strong>{{ $assignService->client->name }}</strong>
                &nbsp;·&nbsp;
                {{ $assignService->from_date?->format('d M Y') }} —
                {{ $assignService->to_date?->format('d M Y') }}
                &nbsp;·&nbsp;
                {{ $filled }} of {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('entry', $rows->count()) }}
            </p>
        </div>

        @if($filled > 0)
        <a href="{{ route('social-media-reports.service-pdf', $assignService->id) }}"
            class="button button-primary">Download Overall PDF</a>
        @endif
    </section>

    <section class="metrics-section">
        <div class="metrics-table-wrap">
            <table class="metrics-table master-table">
                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th style="width:140px;">Entry Date</th>
                        <th>Type</th>
                        <th>Platforms</th>
                        <th class="text-center" style="width:220px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                    @php
                    $report = $row['report'];
                    $typeLabel = '';

                    if ($report) {
                    $hasWa = $hasLg = $hasEn = false;

                    foreach ($report->assign_services as $ra) {
                    foreach ($ra->getServices()->pluck('name') as $name) {
                    if ($name === 'WhatsApp Meta Ads Monthly Report') $hasWa = true;
                    if ($name === 'Lead Generation Ads Monthly Report') $hasLg = true;
                    if ($name === 'Social Media Engagement Monthly Report') $hasEn = true;
                    }
                    }

                    $labels = [];
                    if ($hasWa) $labels[] = 'WhatsApp Meta Ads';
                    if ($hasLg) $labels[] = 'Lead Generation Ads';
                    if ($hasEn) $labels[] = 'Social Media Engagement';
                    $typeLabel = $labels ? implode(' + ', $labels) : '—';
                    }
                    @endphp

                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['date']->format('d M Y') }}</td>
                        <td>{{ $typeLabel }}</td>
                        <td>
                            @if($report)
                            {{ $report->assign_services->pluck('platforms')->filter()->unique()->implode(', ') ?: '—' }}
                            @endif
                        </td>
                        <td class="metrics-actions text-center">
                            @if($report)
                            <a href="{{ route('social-media-reports.edit', $report->id) }}"
                                class="report-edit-link">Edit</a>
                            <form action="{{ route('social-media-reports.destroy', $report->id) }}"
                                method="POST"
                                class="inline-form"
                                onsubmit="return confirm('Move this report to trash?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="report-delete-button">Delete</button>
                            </form>
                            @else
                            <a href="{{ route('social-media-reports.create', [
            'assign_service_id' => $assignService->id,
            'date'              => $row['date']->format('Y-m-d'),
       ]) }}"
                                class="report-edit-link">Edit</a>
                            <button type="button" class="report-delete-button" style="opacity:.4;" disabled>Delete</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('admin.partials.footer')