@include('admin.partials.header', [
'pageTitle' => 'Assign Services',
'documentTitle' => 'Assign Services',
'active' => 'assign-services',
'includeDataTables' => true,
'viteAssets' => ['resources/css/app.css', 'resources/css/datatable.css', 'resources/js/app.js'],
])

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <span class="status-dot status-dot-dark"></span>
                MASTER
            </div>
            <h1>Assigned services</h1>
            <p class="page-subtitle">
                Assign services to clients with a validity period.
            </p>
        </div>
        @if($selectedClientId)
        <div class="report-heading-actions">
            <a href="{{ route('assign-services.index') }}" class="button button-secondary">Back to clients</a>
        </div>
        @endif
    </section>

    @if(session('success'))
    <div class="report-flash" role="status">
        {{ session('success') }}
    </div>
    @endif

    @if($selectedClientId)
    <div class="report-table-meta">
        <div>
            <!-- <strong>Assigned services</strong>
            for {{ $clients->firstWhere('id', $selectedClientId)->name }} -->
        </div>
        <a href="{{ route('assign-services.create', ['client' => $selectedClientId]) }}"
            class="button button-primary">
            <span aria-hidden="true">+</span>
            New assign service
        </a>
    </div>

    <section class="panel report-table-panel" aria-label="Assigned services">
        <div class="report-table-meta">
            <div>
                <strong>{{ $assignServices->total() }}</strong>
                {{ \Illuminate\Support\Str::plural('assignment', $assignServices->total()) }}
            </div>
            <span>Newest assign services first</span>
        </div>

        <div class="report-table-scroll">
            <table id="assignServicesTable" class="report-table display dataTable">
                <thead>
                    <tr>
                        <th>S.NO</th>
                        <th>CLIENT</th>
                        <th>SERVICE</th>
                        <th>TITLE</th>
                        <th>PERIOD</th>
                        <th>STATUS</th>
                        <th class="report-actions-heading" style="text-align:center;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignServices as $assignService)
                    <tr>
                        <td>{{ ($assignServices->firstItem() ?? 0) + $loop->index }}</td>
                        <td>
                            <strong class="report-client">
                                {{ $assignService->client->name ?? '—' }}
                            </strong>
                            @if($assignService->client?->email)
                            <span class="report-preparer">{{ $assignService->client->email }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="report-period-tag">
                                {{ $assignService->service_names ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="report-website">{{ $assignService->title }}</span>
                        </td>
                        <td>
                            <span class="report-preparer">
                                {{ $assignService->from_date?->format('d M Y') }} —
                                {{ $assignService->to_date?->format('d M Y') }}
                            </span>
                        </td>
                        <td>
                            <span @class([ 'client-status' , 'client-status-active'=> $assignService->status,
                                'client-status-inactive' => ! $assignService->status
                                ])>
                                {{ $assignService->status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="report-row-actions">
                                <a href="{{ route('assign-services.edit', $assignService->id) }}"
                                    class="report-edit-link">
                                    Edit
                                </a>

                                <form action="{{ route('assign-services.destroy', $assignService->id) }}"
                                    method="POST"
                                    class="report-delete-form"
                                    data-client-name="{{ $assignService->client->name ?? 'this client' }}"
                                    data-service-name="{{ $assignService->service->name ?? 'this service' }}">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="report-delete-button"
                                        aria-label="Delete assignment"
                                        title="Delete">

                                        <svg viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M4 6h12M8 6V4h4v2m3 0-.7 10H5.7L5 6m3 3v4m4-4v4" />
                                        </svg>

                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($assignServices->hasPages())
        <div class="report-pagination">{{ $assignServices->links() }}</div>
        @endif
    </section>
    @else
    <section aria-label="Select a client to view assigned services">
        <!-- <div class="report-table-meta">
            <div><strong>Select a client</strong> to view their assigned services</div>
        </div> -->

        <div class="client-card-grid">
            @forelse($clients->where('status', true) as $client)
            <a href="{{ route('assign-services.index', ['client' => $client->id]) }}"
                class="client-card">
                <div class="client-card__avatar">
                    {{ strtoupper(substr($client->name, 0, 1)) }}
                </div>
                <div class="client-card__body">
                    <h3>{{ $client->name }}</h3>
                    <p>{{ $client->assign_services_count }}
                        {{ \Illuminate\Support\Str::plural('assign service', $client->assign_services_count) }}
                    </p>
                </div>
                <span class="client-card__arrow" aria-hidden="true">→</span>
            </a>
            @empty
            <p class="empty-state">No active clients found. <a href="{{ route('clients.create') }}">Add one</a>.</p>
            @endforelse
        </div>
    </section>
    @endif
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $(document).on('submit', '.report-delete-form', function(e) {

            e.preventDefault();

            const form = this;
            const clientName = form.dataset.clientName || 'this client';
            const serviceName = form.dataset.serviceName || 'this service';

            Swal.fire({
                title: 'Delete assignment?',
                html: `You are about to delete the <strong>${serviceName}</strong> assignment for <strong>${clientName}</strong>`,
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

        if ($('#assignServicesTable').length) {
            $('#assignServicesTable').DataTable({
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
                    targets: [0, 6]
                }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search assign services...',
                    lengthMenu: 'Show _MENU_ assign services',
                    info: 'Showing _START_ to _END_ of _TOTAL_ assign services',
                    infoEmpty: 'No assign services available',
                    zeroRecords: 'No matching assign services found',
                    emptyTable: 'No assign services available'
                }
            });
        }
    });
</script>

@include('admin.partials.footer')