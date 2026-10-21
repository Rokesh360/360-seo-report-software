@include('admin.partials.header', [
    'pageTitle' => $client->name . ' — Google Ads',
    'active' => 'google-ads',
    'bodyClass' => 'admin-page report-page',
    'includeDataTables' => true,
    'viteAssets' => [
        'resources/css/app.css',
        'resources/css/datatable.css',
        'resources/js/app.js'
    ],
])

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('google-ads.index') }}">
                    ← Google Ads reports
                </a>
            </div>

            <h1>{{ $client->name }}</h1>

            <p class="page-subtitle">
                Choose an assigned YouTube Ads or Call Ads service.
            </p>
        </div>
    </section>

    @if(session('success'))
        <div class="report-flash" role="status">
            {{ session('success') }}
        </div>
    @endif

    <section class="metrics-section panel report-table-panel">

        <div class="report-table-meta">
            <div>
                <strong>{{ $assignments->count() }}</strong>
                {{ \Illuminate\Support\Str::plural('service', $assignments->count()) }}
            </div>

            <span>Google Ads assignments</span>
        </div>

        <div class="report-table-scroll">
            <table id="googleAdsAssignmentsTable"
                   class="metrics-table master-table report-table display dataTable">

                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th>Service</th>
                        <th style="width:230px;">Dates</th>
                        <th style="width:140px;">Budget</th>
                        <th style="width:180px; text-align:center;">Report</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <strong class="service-title">
                                    {{ $assignment->title ?? 'Service #' . $assignment->id }}
                                </strong>

                                <span class="service-dates report-preparer">
                                    {{ $assignment->service_names }}
                                </span>
                            </td>

                            <td data-order="{{ $assignment->from_date?->format('Y-m-d') ?? '' }}">
                                {{ $assignment->from_date?->format('d M Y') ?? '—' }}
                                —
                                {{ $assignment->to_date?->format('d M Y') ?? '—' }}
                            </td>

                            <td data-order="{{ (float) ($assignment->budget ?? 0) }}">
                                ₹{{ number_format((float) ($assignment->budget ?? 0), 2) }}
                            </td>

                            <td class="metrics-actions report-actions-cell"
                                style="text-align:center;">

                                <a href="{{ route('google-ads.assign-service', $assignment) }}"
                                   class="button button-secondary">
                                    Open report
                                    <span aria-hidden="true">→</span>
                                </a>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state-cell">
                                No Google Ads assignments found for this client.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </section>

</div>

@include('admin.partials.footer')

{{-- jQuery + DataTables --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>

<script>
(function () {

    $(document).ready(function () {

        if ($.fn.DataTable.isDataTable('#googleAdsAssignmentsTable')) {
            return;
        }

        $('#googleAdsAssignmentsTable').DataTable({
            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'All']
            ],

            order: [[1, 'asc']],

            columnDefs: [
                {
                    orderable: false,
                    searchable: false,
                    targets: [0, 4]
                }
            ],

            language: {
                search: '',
                searchPlaceholder: 'Search assignments...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries available',
                zeroRecords: 'No matching assignments found',
                emptyTable: 'No Google Ads assignments available'
            }
        });

    });

})();
</script>