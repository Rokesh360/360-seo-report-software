@include('admin.partials.header', [
'pageTitle' => 'GST',
'active' => 'gst',
'bodyClass' => 'admin-page report-page',
'includeDataTables' => true,
'viteAssets' => ['resources/css/app.css', 'resources/css/datatable.css', 'resources/js/app.js'],
])
<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><span class="status-dot status-dot-dark"></span> MASTERS</div>
            <h1>GST</h1>
            <p class="page-subtitle">Manage GST names and their percentages.</p>
        </div>
        <!-- <a href="{{ route('gst-masters.create') }}" class="button button-primary"><span aria-hidden="true">+</span> New GST</a> -->
    </section>

    <section class="panel report-table-panel" aria-label="GST">
        <div class="report-table-meta">
            <div><strong>{{ $gstMasters->count() }}</strong> GST {{ \Illuminate\Support\Str::plural('entry', $gstMasters->count()) }}</div>
            <span>GST rates used across the system</span>
        </div>
        <div class="report-table-scroll">
            <table id="gstTable" class="report-table display dataTable">
                <thead>
                    <tr>
                        <th>S.NO</th>
                        <th>NAME</th>
                        <th>PERCENTAGE</th>
                        <th>STATUS</th>
                        <th class="report-actions-heading" style="text-align: center;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gstMasters as $gst)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong class="report-client">{{ $gst->name }}</strong></td>
                        <td>{{ rtrim(rtrim(number_format($gst->percentage, 2), '0'), '.') }}%</td>
                        <td>
                            <span @class([ 'client-status' , 'client-status-active'=> $gst->status,
                                'client-status-inactive' => ! $gst->status
                                ])>{{ $gst->status ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="report-actions-cell">
                            <div class="report-row-actions">
                                <a href="{{ route('gst-masters.edit', $gst->id) }}" class="report-edit-link">Edit</a>
                                <!-- <form action="{{ route('gst-masters.destroy', $gst->id) }}"
                                    method="POST"
                                    class="report-delete-form"
                                    data-gst-name="{{ $gst->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="report-delete-button" type="submit"
                                        aria-label="Delete {{ $gst->name }}" title="Delete GST">
                                        <svg viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M4 6h12M8 6V4h4v2m3 0-.7 10H5.7L5 6m3 3v4m4-4v4" />
                                        </svg>
                                    </button>
                                </form> -->
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $(document).on('submit', '.report-delete-form', function(e) {
            e.preventDefault();
            const form = this;
            const name = form.dataset.gstName || 'this GST';

            Swal.fire({
                title: 'Delete GST?',
                html: `You are about to delete <strong>${name}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true,
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });

        $('#gstTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [[0, 'asc']],
            columnDefs: [{ orderable: false, searchable: false, targets: [0, 4] }],
            language: {
                search: '',
                searchPlaceholder: 'Search GST...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No GST available',
                zeroRecords: 'No matching GST found',
                emptyTable: 'No GST available'
            }
        });
    });
</script>
@include('admin.partials.footer')