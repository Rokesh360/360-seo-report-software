@include('admin.partials.header', [
'pageTitle' => 'Previous Metrics — ' . ($assignService->title ?? 'Service'),
'active' => 'metrics-reports',
'bodyClass' => 'admin-page report-page',
'includeDataTables' => true,
'viteAssets' => ['resources/css/app.css', 'resources/css/datatable.css', 'resources/js/app.js'],
])

@php
$filled = $rows->filter(fn ($r) => !empty($r['report']))->count();
$budget = (int) ($assignService->budget ?? 0);
@endphp

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('metrics-reports.client-services', $assignService->client_id) }}">
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
        <a href="{{ route('metrics-reports.service-pdf', $assignService->id) }}"
            class="button button-primary">Download PDF</a>
        @endif
    </section>

    <section class="metrics-section panel report-table-panel">
        <div class="report-table-meta">
            <div>
                <strong>{{ $rows->count() }}</strong>
                {{ \Illuminate\Support\Str::plural('entry', $rows->count()) }}
            </div>
            <span>Previous metrics for this service</span>
        </div>

        <div class="report-table-scroll">
            <table id="previousMetricsTable" class="metrics-table master-table report-table display dataTable">
                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th style="width:140px;">Entry Date</th>
                        <th>Type</th>
                        <th style="width:140px;">Spent</th>
                        <th style="width:140px;">Available</th>
                        <th class="report-actions-heading" style="width:220px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                    @php
                    $report = $row['report'];
                    $spent = null;
                    $available = null;

                    if ($report) {
                    $spent = collect($report->campaign_results ?? [])
                    ->sum(fn ($r) => (int) preg_replace(
                    '/\D/',
                    '',
                    (string) ($r['spend'] ?? 0)
                    ));

                    $available = $budget - $spent;
                    }
                    $typeLabel = '';

                    if ($report) {
                    $hasWa = $hasLg = $hasEn = false;

                    if ($report->assignService) {
                    foreach ($report->assignService->getServices()->pluck('name') as $name) {
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
                        <td>{{ $row['date'] ? $row['date']->format('d M Y') : '—' }}</td>
                        <td>{{ $typeLabel }}</td>
                        <td>{{ $spent !== null ? '₹' . number_format($spent) : '—' }}</td>
                        <td>
                            @if($available !== null)
                            <strong style="color: {{ $available <= 0 ? '#d33' : '#16a34a' }};">
                                ₹{{ number_format($available) }}
                            </strong>
                            @else
                            —
                            @endif
                        </td>
                        <td class="metrics-actions report-actions-cell" style="text-align: center;">
                            <div class="report-row-actions">
                                @if($report)
                                {{-- Report exists → Edit + Delete only --}}
                                <a href="{{ route('metrics-reports.edit', $report->id) }}"
                                    class="report-edit-link">Edit</a>

                                <form action="{{ route('metrics-reports.destroy', $report->id) }}"
                                    method="POST"
                                    class="report-delete-form js-delete-form"
                                    data-report-id="{{ $report->id }}"
                                    data-report-date="{{ $row['date'] ? $row['date']->format('d M Y') : '' }}"
                                    data-client-name="{{ $assignService->client->name }}"
                                    data-service-title="{{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="report-delete-button"
                                        aria-label="Delete report"
                                        title="Delete report">
                                        <svg viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M4 6h12M8 6V4h4v2m3 0-.7 10H5.7L5 6m3 3v4m4-4v4" />
                                        </svg>
                                    </button>
                                </form>
                                @else
                                {{-- No report → Add only --}}
                                <a href="{{ route('metrics-reports.create', ['assign_service_id' => $assignService->id]) }}"
                                    class="btn-add-today"
                                    title="Add the monthly entry">
                                    <span aria-hidden="true">+</span> Add
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('admin.partials.footer')

{{-- ============================================================ --}}
{{-- jQuery + DataTables + SweetAlert2                            --}}
{{-- ============================================================ --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    (function() {

        // ---------- DataTable initialization ----------
        $(document).ready(function() {
            $('#previousMetricsTable').DataTable({
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],
                order: [
                    [0, 'asc']
                ],
                columnDefs: [{
                    orderable: false,
                    searchable: false,
                    targets: [0, 5] // S.No + Actions column
                }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search entries...',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries available',
                    zeroRecords: 'No matching entries found',
                    emptyTable: 'No previous metrics recorded for this service'
                }
            });
        });

        // ---------- SweetAlert delete confirmation (event-delegated) ----------
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('.js-delete-form');
            if (!form) return;

            e.preventDefault();

            const clientName = form.dataset.clientName || 'this client';
            const serviceTitle = form.dataset.serviceTitle || 'this service';
            const reportDate = form.dataset.reportDate || '';

            Swal.fire({
                title: 'Move this report to trash?',
                html: `
                    You are about to delete the report for
                    <strong>${serviceTitle}</strong>
                    of <strong>${clientName}</strong>
                    ${reportDate ? `(entry date: <strong>${reportDate}</strong>)` : ''}.
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

    })();
</script>