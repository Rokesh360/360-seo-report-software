@include('admin.partials.header', [
'pageTitle' => 'Services',
'active' => 'services',
'bodyClass' => 'admin-page report-page',
'includeDataTables' => true,
'viteAssets' => ['resources/css/app.css', 'resources/css/datatable.css', 'resources/js/app.js'],
])
<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><span class="status-dot status-dot-dark"></span> SERVICE DIRECTORY</div>
            <h1>Services</h1>
            <p class="page-subtitle">Manage the services offered to clients.</p>
        </div>
        <!-- <a href="{{ route('services.create') }}" class="button button-primary"><span aria-hidden="true">+</span> New service</a> -->
    </section>

    <!-- @if(session('success')) <div class="report-flash" role="status">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="report-validation" role="alert">{{ session('error') }}</div> @endif -->

    <section class="panel report-table-panel" aria-label="Service directory">
        <div class="report-table-meta">
            <div><strong>{{ $services->total() }}</strong> {{ \Illuminate\Support\Str::plural('service', $services->total()) }}</div>
            <span>Available services</span>
        </div>
        <div class="report-table-scroll">
            <table id="servicesTable" class="report-table display dataTable">
                <thead>
                    <tr>
                        <th>S.NO</th>
                        <th>SERVICE</th>
                        <th>STATUS</th>
                        <!-- <th class="report-actions-heading" style="text-align: center;">ACTIONS</th> -->
                    </tr>
                </thead>
                <tbody>
                    @foreach($services as $service)
                    <tr>
                        <td>{{ ($services->firstItem() ?? 0) + $loop->index }}</td>
                        <td>
                            <strong class="report-client">{{ $service->name }}</strong>
                        </td>
                        <td>
                            <span @class([ 'client-status' , 'client-status-active'=> (int) $service->status === 1,
                                'client-status-inactive' => (int) $service->status !== 1,
                                ])>{{ $service->status ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <!-- <td class="report-actions-cell">
                            <div class="report-row-actions">
                                <a href="{{ route('services.edit', $service->id) }}" class="report-edit-link">Edit</a>
                                <form action="{{ route('services.destroy', $service->id) }}" method="POST" onsubmit="return confirm('Delete this service?')">
                                    @csrf @method('DELETE')
                                    <button class="report-delete-button" type="submit" aria-label="Delete {{ $service->name }}" title="Delete service">
                                        <svg viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M4 6h12M8 6V4h4v2m3 0-.7 10H5.7L5 6m3 3v4m4-4v4" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td> -->
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
        <div class="report-pagination">{{ $services->links() }}</div>
        @endif
    </section>
</div>
{{-- jQuery --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- DataTables --}}
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#servicesTable').DataTable({
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
                targets: [0, 2] // S.NO and ACTIONS columns
            }],
            language: {
                search: '',
                searchPlaceholder: 'Search services...',
                lengthMenu: 'Show _MENU_ services',
                info: 'Showing _START_ to _END_ of _TOTAL_ services',
                infoEmpty: 'No services available',
                zeroRecords: 'No matching services found',
                emptyTable: 'No services available'
            }
        });
    });
</script>
@include('admin.partials.footer')