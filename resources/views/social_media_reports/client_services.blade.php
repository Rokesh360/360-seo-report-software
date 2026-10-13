@include('admin.partials.header', [
    'pageTitle' => $client->name . ' — Services',
    'active' => 'daily-metrics-reports',
    'bodyClass' => 'admin-page report-page',
    'includeDataTables' => true,
    'viteAssets' => ['resources/css/app.css', 'resources/css/datatable.css', 'resources/js/app.js'],
])

<div class="dashboard-content report-content">

    {{-- ============================================================ --}}
    {{-- PAGE HEADING                                                  --}}
    {{-- ============================================================ --}}
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('daily-metrics-reports.index') }}">← All clients</a>
            </div>
            <h1>{{ $client->name }}</h1>
            <p class="page-subtitle">
                Select a service to open its metrics.
            </p>
        </div>
    </section>

    @if(session('success'))
        <div class="report-flash" role="status">{{ session('success') }}</div>
    @endif

    {{-- ============================================================ --}}
    {{-- MASTER TABLE — one master row per AssignService              --}}
    {{-- ============================================================ --}}
    <section class="metrics-section panel report-table-panel">
        <div class="report-table-meta">
            <div>
                <strong>{{ $assignServices->count() }}</strong>
                {{ \Illuminate\Support\Str::plural('service', $assignServices->count()) }}
            </div>
            <span>Assigned services for {{ $client->name }}</span>
        </div>

        <div class="report-table-scroll">
            <table id="servicesTable" class="metrics-table master-table report-table display dataTable">
                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th>Client Name</th>
                        <th>Title</th>
                        <th style="width:230px;" class="report-actions-heading">Metrics</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignServices as $index => $assignService)
                        @php
                            $masterId = 'svc-' . $assignService->id;
                        @endphp

                        {{-- ===================== MASTER ROW ===================== --}}
                        <tr class="master-row">
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <strong class="report-client">{{ $client->name }}</strong>
                            </td>

                            <td>
                                <strong class="service-title">
                                    <a class="service-title-link">
                                        {{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}
                                    </a>
                                </strong>
                                <span class="service-dates report-preparer">
                                    {{ $assignService->from_date?->format('d M Y') }} —
                                    {{ $assignService->to_date?->format('d M Y') }}
                                </span>
                            </td>

                            {{-- Previous Metrics → dedicated page --}}
                            <td class="report-actions-cell">
                                <a href="{{ route('daily-metrics-reports.previous-metrics', $assignService->id) }}"
                                   class="button button-secondary btn-previous-metrics"
                                   title="View all previous metrics">
                                    <span>Metrics</span>
                                    <span class="btn-arrow" aria-hidden="true">→</span>
                                </a>
                            </td>
                        </tr>

                    @empty
                        <tr class="empty-row">
                            <td></td>
                            <td></td>
                            <td class="empty-state-cell">
                                <p class="empty-state">No services assigned to this client yet.</p>
                            </td>
                            <td></td>
                        </tr>
                    @endforelse
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
    (function () {

        // ---------- DataTable initialization ----------
        $(document).ready(function () {
            $('#servicesTable').DataTable({
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
                        targets: [0, 3] // S.No + Metrics action column
                    }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search services...',
                    lengthMenu: 'Show _MENU_ services',
                    info: 'Showing _START_ to _END_ of _TOTAL_ services',
                    infoEmpty: 'No services available',
                    zeroRecords: 'No matching services found',
                    emptyTable: 'No services assigned to this client yet'
                }
            });
        });

        // ---------- SweetAlert delete confirmation (event-delegated so it
        //            survives DataTable re-renders) ----------
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('.js-delete-form');
            if (!form) return;

            e.preventDefault();

            const clientName   = form.dataset.clientName   || 'this client';
            const serviceTitle = form.dataset.serviceTitle || 'this service';
            const reportDate   = form.dataset.reportDate   || 'today';

            Swal.fire({
                title: 'Delete this report?',
                html: `
                    You are about to delete the report for
                    <strong>${serviceTitle}</strong>
                    of <strong>${clientName}</strong>
                    (entry date: <strong>${reportDate}</strong>).
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