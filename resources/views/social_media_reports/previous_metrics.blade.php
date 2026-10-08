@include('admin.partials.header', [
    'pageTitle' => 'Previous Metrics — ' . ($assignService->title ?? 'Service'),
    'active' => 'social-media-reports',
])

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
                {{ $reports->count() }} {{ \Illuminate\Support\Str::plural('entry', $reports->count()) }}
            </p>
        </div>
    </section>

    <section class="metrics-section">
        <div class="metrics-table-wrap">
            @if($reports->isEmpty())
                <p class="accordion-empty">No previous reports.</p>
            @else
                <table class="metrics-table master-table">
                    <thead>
                        <tr>
                            <th style="width:60px;">S.No</th>
                            <th style="width:140px;">Entry Date</th>
                            <th>Type</th>
                            <th>Platforms</th>
                            <th class="text-center" style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $index => $report)
                            @php
                                // Resolve which report types this report covers
                                $reportServiceIds = is_array($report->assign_service_ids)
                                    ? $report->assign_service_ids
                                    : [];

                                $reportAssignServices = $reportServiceIds
                                    ? \App\Models\AssignService::whereIn('id', $reportServiceIds)->get()
                                    : collect();

                                $reportTypeMap = [];
                                foreach ($reportAssignServices as $ra) {
                                    foreach ($ra->getServices()->pluck('name') as $name) {
                                        $key = match ($name) {
                                            'WhatsApp Meta Ads Monthly Report'       => 'whatsapp_meta_ads',
                                            'Lead Generation Ads Monthly Report'     => 'lead_generation_ads',
                                            'Social Media Engagement Monthly Report' => 'social_media_engagement',
                                            default                                   => null,
                                        };
                                        if ($key) $reportTypeMap[$key] = true;
                                    }
                                }

                                $hasWa = $reportTypeMap['whatsapp_meta_ads']       ?? false;
                                $hasLg = $reportTypeMap['lead_generation_ads']     ?? false;
                                $hasEn = $reportTypeMap['social_media_engagement'] ?? false;

                                if (! $hasWa && ! $hasLg && ! $hasEn) {
                                    $hasWa = $report->total_conversations !== null
                                          || $report->total_enquiries !== null
                                          || $report->whatsapp_clicks !== null;
                                    $hasLg = $report->total_leads !== null
                                          || $report->form_submissions !== null
                                          || $report->link_clicks !== null;
                                    $hasEn = $report->total_engagement !== null
                                          || $report->reactions !== null
                                          || $report->comments !== null
                                          || $report->shares !== null
                                          || $report->saves !== null
                                          || $report->profile_visits !== null
                                          || $report->new_followers !== null;
                                }

                                $labels = [];
                                if ($hasWa) $labels[] = 'WhatsApp Meta Ads';
                                if ($hasLg) $labels[] = 'Lead Generation Ads';
                                if ($hasEn) $labels[] = 'Social Media Engagement';
                                $typeLabel = $labels ? implode(' + ', $labels) : '—';
                            @endphp

                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $report->created_at?->format('d M Y') }}</td>
                                <td>{{ $typeLabel }}</td>
                                <td>{{ $report->assign_services->pluck('platforms')->filter()->unique()->implode(', ') ?: '—' }}</td>
                                <td class="metrics-actions text-center">
                                    <a href="{{ route('social-media-reports.pdf', $report->id) }}"
                                       class="report-export-link">PDF</a>
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</div>

@include('admin.partials.footer')