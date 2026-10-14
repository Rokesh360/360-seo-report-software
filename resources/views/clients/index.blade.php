@include('admin.partials.header', [
'pageTitle' => 'Clients',
'active' => 'clients',
'bodyClass' => 'admin-page report-page',
'includeDataTables' => true,
'viteAssets' => ['resources/css/app.css', 'resources/css/datatable.css', 'resources/js/app.js'],
])
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
            <table id="clientsTable" class="report-table display dataTable">
                <thead>
                    <tr>
                        <th>S.NO</th>
                        <th>CLIENT</th>
                        <th>DATE OF CAMPAIGN</th>
                        <th>CONTACT</th>
                        <th>STATUS</th>
                        <th class="report-actions-heading" style="text-align: center;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                    <tr>
                        <td>{{ ($clients->firstItem() ?? 0) + $loop->index }}</td>
                        <td>
                            <strong class="report-client">{{ $client->name }}</strong>
                            <span class="report-preparer">{{ $client->email ?: 'No email' }}</span>
                        </td>
                        <td><span class="report-website">{{ $client->date_of_campaign ? \Carbon\Carbon::parse($client->date_of_campaign)->format('d-m-Y') : '-' }}</span></td>
                        <td>
                            <span class="client-contact">{{ $client->email ?: 'No email' }}</span>
                            <span class="report-preparer">{{ $client->phone ?: 'No phone' }}</span>
                        </td>
                        <td>
                            <span @class([ 'client-status' , 'client-status-active'=> $client->status,
                                'client-status-inactive' => ! $client->status
                                ])>{{ $client->status ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="report-actions-cell">
                            <div class="report-row-actions">
                                <a href="{{ route('clients.edit', $client->id) }}" class="report-edit-link">Edit</a>
                                <form action="{{ route('clients.destroy', $client->id) }}"
                                    method="POST"
                                    class="report-delete-form"
                                    data-client-name="{{ $client->name }}">

                                    @csrf
                                    @method('DELETE')

                                    <button class="report-delete-button"
                                        type="submit"
                                        aria-label="Delete {{ $client->name }}"
                                        title="Delete client">

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

        @if($clients->hasPages())
        <div class="report-pagination">{{ $clients->links() }}</div>
        @endif
    </section>
</div>
{{-- jQuery --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- DataTables --}}
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $(document).on('submit', '.report-delete-form', function(e) {

            e.preventDefault();

            const form = this;
            const clientName = form.dataset.clientName || 'this client';

            Swal.fire({
                title: 'Delete client?',
                html: `You are about to delete <strong>${clientName}</strong>`,
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

        $('#clientsTable').DataTable({
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
                targets: [0, 5]
            }],
            language: {
                search: '',
                searchPlaceholder: 'Search clients...',
                lengthMenu: 'Show _MENU_ clients',
                info: 'Showing _START_ to _END_ of _TOTAL_ clients',
                infoEmpty: 'No clients available',
                zeroRecords: 'No matching clients found',
                emptyTable: 'No clients available'
            },
            initComplete: function() {
                const api = this.api();
                const container = $(api.table().container());

                const toolbar = $(`
        <div class="dt-date-range-toolbar">
            <div class="dt-date-range-filter">
                <label for="campaign-from-date">From Date</label>
                <input
                    type="date"
                    id="campaign-from-date"
                    value="{{ $fromDate ?? '' }}"
                >

                <label for="campaign-to-date">To Date</label>
                <input
                    type="date"
                    id="campaign-to-date"
                    value="{{ $toDate ?? '' }}"
                >
            </div>
        </div>
    `);

                // Place the date filters beside the DataTables search.
                const searchContainer = container.find('.dt-search');
                searchContainer.addClass('dt-toolbar-search');

                searchContainer.after(toolbar);

                function applyDateRange() {
                    const fromDate = $('#campaign-from-date').val();
                    const toDate = $('#campaign-to-date').val();

                    if (fromDate && toDate && toDate < fromDate) {
                        return;
                    }

                    const url = new URL(window.location.href);

                    if (fromDate) {
                        url.searchParams.set('from_date', fromDate);
                    } else {
                        url.searchParams.delete('from_date');
                    }

                    if (toDate) {
                        url.searchParams.set('to_date', toDate);
                    } else {
                        url.searchParams.delete('to_date');
                    }

                    url.searchParams.delete('page');

                    window.location.href = url.toString();
                }

                $('#campaign-from-date, #campaign-to-date').on(
                    'change',
                    applyDateRange
                );
            }
        });
    });
</script>
@include('admin.partials.footer')