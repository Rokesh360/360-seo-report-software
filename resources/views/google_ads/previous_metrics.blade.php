@include('admin.partials.header', [
    'pageTitle' => 'Google Ads — ' . ($assignService->title ?? 'Service'),
    'active' => 'google-ads',
    'bodyClass' => 'admin-page report-page',
    'includeDataTables' => true,
    'viteAssets' => [
        'resources/css/app.css',
        'resources/css/datatable.css',
        'resources/js/app.js'
    ],
])

@php
    $filled = $report ? 1 : 0;
    $budget = (float) ($assignService->budget ?? 0);
    $spent = $report ? (float) ($report->data['total_cost'] ?? 0) : null;
    $available = $spent !== null ? $budget - $spent : null;

    $typeLabel = $assignService->getServices()
        ->map(fn ($service) => \App\Support\ReportServiceType::googleAdsTypeFromServiceName($service->name))
        ->filter()
        ->unique()
        ->map(fn ($type) => \App\Support\ReportServiceType::label($type))
        ->implode(' + ');
@endphp

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('google-ads.client-services', $assignService->client_id) }}">
                    ← {{ $assignService->client->name }}
                </a>
            </div>

            <h1>Google Ads</h1>

            <p class="page-subtitle">
                <strong>{{ $assignService->client->name }}</strong>
                &nbsp;·&nbsp;
                {{ $assignService->from_date?->format('d M Y') }} —
                {{ $assignService->to_date?->format('d M Y') }}
                &nbsp;·&nbsp;
                {{ $filled }} of 1 entry
            </p>
        </div>

        @if($report)
            <a href="{{ route('google-ads-reports.pdf', $report) }}"
               class="button button-primary">
                Download PDF
            </a>
        @endif
    </section>

    <section class="metrics-section panel report-table-panel">

        <div class="report-table-meta">
            <div>
                <strong>{{ $filled }}</strong>
                {{ \Illuminate\Support\Str::plural('entry', $filled) }}
            </div>

            <span>Google Ads for this service</span>
        </div>

        <div class="report-table-scroll">
            <table id="previousMetricsTable"
                   class="metrics-table master-table report-table display dataTable">

                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th style="width:140px;">Entry Date</th>
                        <th>Type</th>
                        <th style="width:140px;">Spent</th>
                        <th style="width:140px;">Available</th>
                        <th class="report-actions-heading"
                            style="width:220px; text-align:center;">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>1</td>

                        <td>
                            {{ $report?->created_at?->format('d M Y') ?? '—' }}
                        </td>

                        <td>
                            {{ $typeLabel ?: '—' }}
                        </td>

                        <td>
                            {{ $spent !== null ? '₹' . number_format($spent, 2) : '—' }}
                        </td>

                        <td>
                            @if($available !== null)
                                <strong style="color: {{ $available <= 0 ? '#d33' : '#16a34a' }};">
                                    ₹{{ number_format($available, 2) }}
                                </strong>
                            @else
                                —
                            @endif
                        </td>

                        <td class="metrics-actions report-actions-cell"
                            style="text-align:center;">

                            <div class="report-row-actions">

                                @if($report)

                                    <a href="{{ route('google-ads-reports.edit', $report) }}"
                                       class="report-edit-link">
                                        Edit
                                    </a>

                                    <form action="{{ route('google-ads-reports.destroy', $report) }}"
                                          method="POST"
                                          class="report-delete-form js-delete-form"
                                          data-report-date="{{ $report->created_at?->format('d M Y') ?? '' }}"
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

                                    <a href="{{ route('google-ads-reports.create', ['assign_service_id' => $assignService->id]) }}"
                                       class="btn-add-today"
                                       title="Add the monthly entry">
                                        <span aria-hidden="true">+</span> Add
                                    </a>

                                @endif

                            </div>
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>
    </section>

</div>

@include('admin.partials.footer')

{{-- jQuery + DataTables + SweetAlert2 --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {

    // DataTables initialization
    $(document).ready(function () {

        if ($.fn.DataTable.isDataTable('#previousMetricsTable')) {
            return;
        }

        $('#previousMetricsTable').DataTable({
            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'All']
            ],

            order: [[0, 'asc']],

            columnDefs: [
                {
                    orderable: false,
                    searchable: false,
                    targets: [0, 5]
                }
            ],

            language: {
                search: '',
                searchPlaceholder: 'Search entries...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries available',
                zeroRecords: 'No matching entries found',
                emptyTable: 'No Google Ads recorded for this service'
            }
        });
    });

    // SweetAlert delete confirmation
    document.addEventListener('submit', function (event) {

        const form = event.target.closest('.js-delete-form');

        if (!form) {
            return;
        }

        event.preventDefault();

        const clientName = form.dataset.clientName || 'this client';
        const serviceTitle = form.dataset.serviceTitle || 'this service';
        const reportDate = form.dataset.reportDate || '';

        Swal.fire({
            title: 'Move this report to trash?',
            html: `
                You are about to delete the report for
                <strong>${escapeHtml(serviceTitle)}</strong>
                of <strong>${escapeHtml(clientName)}</strong>
                ${reportDate
                    ? `(entry date: <strong>${escapeHtml(reportDate)}</strong>)`
                    : ''}.
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {

            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Escape dynamic text before inserting it into SweetAlert HTML.
    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

})();
</script>