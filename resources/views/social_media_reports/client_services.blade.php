@include('admin.partials.header', [
    'pageTitle' => $client->name . ' — Services',
    'active' => 'social-media-reports',
])

<div class="dashboard-content report-content">

    {{-- ============================================================ --}}
    {{-- PAGE HEADING                                                  --}}
    {{-- ============================================================ --}}
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('social-media-reports.index') }}">← All clients</a>
            </div>
            <h1>{{ $client->name }}</h1>
            <p class="page-subtitle">
                Select a service to open its metrics — or expand "Previous Metrics" inline.
            </p>
        </div>
    </section>

    @if(session('success'))
        <div class="report-flash" role="status">{{ session('success') }}</div>
    @endif

    {{-- ============================================================ --}}
    {{-- MASTER TABLE — one master row per AssignService              --}}
    {{-- ============================================================ --}}
    <section class="metrics-section">
        <div class="metrics-table-wrap">
            <table class="metrics-table master-table">
                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th>Client Name</th>
                        <th>Title</th>
                        <th style="width:230px;">Previous Metrics</th>
                        <th style="width:220px;" class="text-center">Today Metrics</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignServices as $index => $assignService)
                        @php
                            // ---- Previous reports for this assign service ----
                            $previousReports = \App\Models\SocialMediaReport::query()
                                ->whereJsonContains('assign_service_ids', $assignService->id)
                                ->whereDate('created_at', '<', \Carbon\Carbon::today())
                                ->orderByDesc('created_at')
                                ->get();

                            // ---- Today's reports for this assign service ----
                            $todayReports = \App\Models\SocialMediaReport::query()
                                ->whereJsonContains('assign_service_ids', $assignService->id)
                                ->whereDate('created_at', \Carbon\Carbon::today())
                                ->orderByDesc('id')
                                ->get();

                            $todayEntryMissing = $todayReports->isEmpty();
                            $canAddToday = (bool) ($assignService->status ?? true)
                                && \Carbon\Carbon::today()->betweenIncluded(
                                    $assignService->from_date,
                                    $assignService->to_date
                                );

                            $masterId = 'svc-' . $assignService->id;
                        @endphp

                        {{-- ===================== MASTER ROW ===================== --}}
                        <tr class="master-row">
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <strong>{{ $client->name }}</strong>
                            </td>

                            <td>
                                <strong class="service-title">
                                    <a                                       class="service-title-link">
                                        {{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}
                                    </a>
                                </strong>
                                <span class="service-dates">
                                    {{ $assignService->from_date?->format('d M Y') }} —
                                    {{ $assignService->to_date?->format('d M Y') }}
                                </span>
                            </td>

                            {{-- Previous Metrics accordion trigger --}}
                            <td>
                                <button type="button"
                                        class="accordion-trigger"
                                        data-target="#prev-metrics-{{ $assignService->id }}"
                                        aria-expanded="false"
                                        aria-controls="prev-metrics-{{ $assignService->id }}">
                                    <span class="accordion-trigger__chevron">
                                        <svg viewBox="0 0 20 20" width="14" height="14">
                                            <path d="M6 8l4 4 4-4" fill="none"
                                                  stroke="currentColor" stroke-width="1.8"
                                                  stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <span>
                                        Previous Metrics
                                        <small>({{ $previousReports->count() }})</small>
                                    </span>
                                </button>
                            </td>

                            {{-- Today Metrics column: View / Delete / Add --}}
                            <td>
                                @if($todayEntryMissing && $canAddToday)
                                    <div class="today-actions">
                                        <a href="{{ route('social-media-reports.create', ['assign_service_id' => $assignService->id]) }}"
                                           class="btn-add-today"
                                           title="No entry for today — add one">
                                            + Add today
                                        </a>
                                    </div>
                                @elseif($todayEntryMissing)
                                    <span class="service-dates">Today's entry is unavailable because this assignment is inactive or outside its date range.</span>
                                @else
                                    @foreach($todayReports as $today)
                                        <div class="today-actions">
                                            <span class="service-dates">
                                                {{ $today->created_at?->format('d M Y') }}
                                            </span>
                                            <a href="{{ route('social-media-reports.pdf', $today->id) }}"
                                               class="btn-view"
                                               title="View report">
                                                View
                                            </a>
                                            <form action="{{ route('social-media-reports.destroy', $today->id) }}"
                                                  method="POST"
                                                  class="inline-form"
                                                  onsubmit="return confirm('Move this report to trash?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-delete" title="Delete report">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        </tr>

                        {{-- =============== ACCORDION SUB-TABLE =============== --}}
                        <tr id="prev-metrics-{{ $assignService->id }}"
                            class="accordion-detail-row"
                            hidden>
                            <td colspan="5">
                                <div class="accordion-subtable-wrap">
                                    @if($previousReports->isEmpty())
                                        <p class="accordion-empty">No previous reports.</p>
                                    @else
                                        <table class="subtable">
                                            <thead>
                                                <tr>
                                                    <th style="width:44px;"></th>
                                                    <th>Entry Date</th>
                                                    <th>Type</th>
                                                    <th>Platforms</th>
                                                    <th class="text-center" style="width:200px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($previousReports as $report)
                                                    @php $repId = 'report-' . $report->id; @endphp

                                                    {{-- Sub summary row --}}
                                                    <tr class="sub-accordion-row"
                                                        data-target="#{{ $repId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $repId }}">
                                                        <td class="accordion-toggle-cell">
                                                            <span class="accordion-chevron">
                                                                <svg viewBox="0 0 20 20" width="12" height="12">
                                                                    <path d="M6 8l4 4 4-4" fill="none"
                                                                          stroke="currentColor" stroke-width="1.8"
                                                                          stroke-linecap="round" stroke-linejoin="round"/>
                                                                </svg>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            {{ $report->created_at?->format('d M Y') }}
                                                        </td>
                                                        <td>{{ $report->report_type_label }}</td>
                                                        <td>{{ $report->platforms ?? '—' }}</td>
                                                        <td class="metrics-actions">
                                                            <a href="{{ route('social-media-reports.pdf', $report->id) }}"
                                                               class="report-export-link">PDF</a>
                                                            <a href="{{ route('social-media-reports.edit', $report->id) }}"
                                                               class="report-edit-link">Edit</a>
                                                            <form action="{{ route('social-media-reports.destroy', $report->id) }}"
                                                                  method="POST"
                                                                  onsubmit="return confirm('Move this report to trash?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="report-delete-button">Delete</button>
                                                            </form>
                                                        </td>
                                                    </tr>

                                                    {{-- Nested full-detail row (flat table) --}}
                                                    <tr id="{{ $repId }}" class="sub-accordion-detail" hidden>
                                                        <td colspan="5">
                                                            <table class="accordion-flat-table">
                                                                <tbody>
                                                                    <tr class="flat-section-head"><th colspan="2">Overview</th></tr>
                                                                    <tr><th>Campaign Objective</th><td>{{ $report->campaign_objective ?? '—' }}</td></tr>
                                                                    <tr><th>Campaign Overview</th><td>{{ $report->campaign_overview ?? '—' }}</td></tr>
                                                                    <tr><th>Target Audience</th><td>{{ $report->target_audience ?? '—' }}</td></tr>
                                                                    <tr><th>Campaign Focus</th><td>{{ $report->campaign_focus ?? '—' }}</td></tr>
                                                                    <tr><th>Key Highlights</th><td>{{ $report->key_highlights ?? '—' }}</td></tr>

                                                                    <tr class="flat-section-head"><th colspan="2">Shared Metrics</th></tr>
                                                                    <tr><th>Reach</th><td>{{ $report->reach !== null ? number_format((int) $report->reach) : '—' }}</td></tr>
                                                                    <tr><th>Impressions</th><td>{{ $report->impressions !== null ? number_format((int) $report->impressions) : '—' }}</td></tr>
                                                                    <tr><th>Frequency</th><td>{{ $report->frequency ?? '—' }}</td></tr>

                                                                    <tr class="flat-section-head"><th colspan="2">WhatsApp Meta Ads</th></tr>
                                                                    <tr><th>Total Conversations</th><td>{{ $report->total_conversations !== null ? number_format((int) $report->total_conversations) : '—' }}</td></tr>
                                                                    <tr><th>Total Enquiries</th><td>{{ $report->total_enquiries !== null ? number_format((int) $report->total_enquiries) : '—' }}</td></tr>
                                                                    <tr><th>WhatsApp Clicks</th><td>{{ $report->whatsapp_clicks !== null ? number_format((int) $report->whatsapp_clicks) : '—' }}</td></tr>

                                                                    <tr class="flat-section-head"><th colspan="2">Lead Generation</th></tr>
                                                                    <tr><th>Total Leads</th><td>{{ $report->total_leads !== null ? number_format((int) $report->total_leads) : '—' }}</td></tr>
                                                                    <tr><th>Form Submissions</th><td>{{ $report->form_submissions !== null ? number_format((int) $report->form_submissions) : '—' }}</td></tr>
                                                                    <tr><th>Link Clicks</th><td>{{ $report->link_clicks !== null ? number_format((int) $report->link_clicks) : '—' }}</td></tr>

                                                                    <tr class="flat-section-head"><th colspan="2">Engagement</th></tr>
                                                                    <tr><th>Total Engagement</th><td>{{ $report->total_engagement !== null ? number_format((int) $report->total_engagement) : '—' }}</td></tr>
                                                                    <tr><th>Reactions</th><td>{{ $report->reactions !== null ? number_format((int) $report->reactions) : '—' }}</td></tr>
                                                                    <tr><th>Comments</th><td>{{ $report->comments !== null ? number_format((int) $report->comments) : '—' }}</td></tr>
                                                                    <tr><th>Shares</th><td>{{ $report->shares !== null ? number_format((int) $report->shares) : '—' }}</td></tr>
                                                                    <tr><th>Saves</th><td>{{ $report->saves !== null ? number_format((int) $report->saves) : '—' }}</td></tr>
                                                                    <tr><th>Profile Visits</th><td>{{ $report->profile_visits !== null ? number_format((int) $report->profile_visits) : '—' }}</td></tr>
                                                                    <tr><th>New Followers</th><td>{{ $report->new_followers !== null ? number_format((int) $report->new_followers) : '—' }}</td></tr>

                                                                    @if(!empty(array_filter((array) $report->campaign_results)))
                                                                        <tr class="flat-section-head"><th colspan="2">Campaign / Ad Set Results</th></tr>
                                                                        <tr>
                                                                            <th>Rows</th>
                                                                            <td>
                                                                                <table class="flat-nested-table">
                                                                                    <thead><tr><th>Name</th><th>Result</th><th>Spend</th><th>Note</th></tr></thead>
                                                                                    <tbody>
                                                                                        @foreach($report->campaign_results as $row)
                                                                                            @if(!empty(array_filter((array) $row)))
                                                                                                <tr>
                                                                                                    <td>{{ $row['name'] ?? '—' }}</td>
                                                                                                    <td>{{ $row['result'] ?? '—' }}</td>
                                                                                                    <td>{{ $row['spend'] ?? '—' }}</td>
                                                                                                    <td>{{ $row['note'] ?? '—' }}</td>
                                                                                                </tr>
                                                                                            @endif
                                                                                        @endforeach
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    @endif

                                                                    @if(!empty(array_filter((array) $report->top_posts)))
                                                                        <tr class="flat-section-head"><th colspan="2">Top Performing Posts</th></tr>
                                                                        <tr>
                                                                            <th>Posts</th>
                                                                            <td>
                                                                                <table class="flat-nested-table">
                                                                                    <thead><tr><th>Title</th><th>Engagements</th><th>Type</th></tr></thead>
                                                                                    <tbody>
                                                                                        @foreach($report->top_posts as $row)
                                                                                            @if(!empty(array_filter((array) $row)))
                                                                                                <tr>
                                                                                                    <td>{{ $row['title'] ?? '—' }}</td>
                                                                                                    <td>{{ $row['engagements'] ?? '—' }}</td>
                                                                                                    <td>{{ $row['type'] ?? '—' }}</td>
                                                                                                </tr>
                                                                                            @endif
                                                                                        @endforeach
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    @endif

                                                                    @foreach(['creative_performance' => 'Creative Performance',
                                                                              'audience_performance' => 'Audience Performance',
                                                                              'content_analysis' => 'Content Analysis',
                                                                              'performance_highlights' => 'Performance Highlights',
                                                                              'key_observations' => 'Key Observations',
                                                                              'monthly_summary' => 'Monthly Summary',
                                                                              'next_month_plan' => 'Next Month Plan'] as $field => $label)
                                                                        @if(!empty(array_filter((array) $report->{$field})))
                                                                            <tr class="flat-section-head"><th colspan="2">{{ $label }}</th></tr>
                                                                            @foreach($report->{$field} as $key => $val)
                                                                                @if($val)
                                                                                    <tr>
                                                                                        <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                                                        <td>{{ $val }}</td>
                                                                                    </tr>
                                                                                @endif
                                                                            @endforeach
                                                                        @endif
                                                                    @endforeach

                                                                    <tr class="flat-actions-row">
                                                                        <th>Actions</th>
                                                                        <td>
                                                                            <div class="metrics-actions metrics-actions--left">
                                                                                <a href="{{ route('social-media-reports.pdf', $report->id) }}" class="report-export-link">Export PDF</a>
                                                                                <a href="{{ route('social-media-reports.edit', $report->id) }}" class="report-edit-link">Edit</a>
                                                                                <form action="{{ route('social-media-reports.destroy', $report->id) }}"
                                                                                      method="POST"
                                                                                      onsubmit="return confirm('Move this report to trash?')">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <button type="submit" class="report-delete-button">Delete</button>
                                                                                </form>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <p class="empty-state">No services assigned to this client yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('admin.partials.footer')

<script>
(function () {
    // ---------- Master accordion (Previous Metrics per service) ----------
    document.querySelectorAll('.accordion-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const detail = document.querySelector(btn.dataset.target);
            if (!detail) return;

            const isOpen = !detail.hidden;

            // Close all other master panels + sub panels
            document.querySelectorAll('.accordion-detail-row').forEach(function (el) {
                if (el !== detail) el.hidden = true;
            });
            document.querySelectorAll('.accordion-trigger').forEach(function (b) {
                if (b !== btn) {
                    b.classList.remove('is-open');
                    b.setAttribute('aria-expanded', 'false');
                }
            });
            document.querySelectorAll('.sub-accordion-detail').forEach(function (el) {
                el.hidden = true;
            });
            document.querySelectorAll('.sub-accordion-row').forEach(function (r) {
                r.classList.remove('is-open');
                r.setAttribute('aria-expanded', 'false');
            });

            detail.hidden = isOpen;
            btn.classList.toggle('is-open', !isOpen);
            btn.setAttribute('aria-expanded', String(!isOpen));
        });
    });

    // ---------- Sub accordion (individual previous report) ----------
    document.querySelectorAll('.sub-accordion-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, form, input, label')) return;

            const detail = document.querySelector(row.getAttribute('data-target'));
            if (!detail) return;

            const isOpen = !detail.hidden;

            // Close sibling sub panels within the same parent subtable
            const parentTable = row.closest('.subtable');
            if (parentTable) {
                parentTable.querySelectorAll('.sub-accordion-detail').forEach(function (el) {
                    if (el !== detail) el.hidden = true;
                });
                parentTable.querySelectorAll('.sub-accordion-row').forEach(function (r) {
                    if (r !== row) {
                        r.classList.remove('is-open');
                        r.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            detail.hidden = isOpen;
            row.classList.toggle('is-open', !isOpen);
            row.setAttribute('aria-expanded', String(!isOpen));
        });
    });
})();
</script>