@include('admin.partials.header', [
    'pageTitle' => 'SEO reports',
    'documentTitle' => 'SEO Reports',
    'active' => 'reports',
    'includeDataTables' => true,
    'viteAssets' => ['resources/css/app.css', 'resources/css/datatable.css', 'resources/js/app.js'],
])

            <div class="dashboard-content report-content">

                <section class="page-heading report-page-heading">

                    <div>
                        <div class="eyebrow">
                            <span class="status-dot status-dot-dark"></span>
                            REPORT LIBRARY
                        </div>

                        <h1>SEO monthly reports</h1>

                        <p class="page-subtitle">
                            Create, review, and export client performance reports.
                        </p>
                    </div>

                    <div class="report-heading-actions">

                        <!-- <a href="{{ route('seo-reports.trash') }}"
                            class="button button-secondary">
                            View trash
                        </a> -->

                        <a href="{{ route('seo-reports.create') }}"
                            class="button button-primary">
                            <span aria-hidden="true">+</span>
                            New report
                        </a>

                    </div>

                </section>

                @if(session('success'))
                <div class="report-flash" role="status">
                    {{ session('success') }}
                </div>
                @endif


                <section class="panel report-table-panel"
                    aria-label="SEO monthly reports">

                    <div class="report-table-meta">

                        <div>
                            <strong>{{ $reports->total() }}</strong>
                            {{ \Illuminate\Support\Str::plural('report', $reports->total()) }}
                        </div>

                        <span>Newest reports first</span>

                    </div>


                    <div class="report-table-scroll">

                        <table id="seoReportsTable"
                            class="report-table display dataTable">

                            <thead>
                                <tr>
                                    <th>S.NO</th>
                                    <th>CLIENT</th>
                                    <th>WEBSITE</th>
                                    <th>PERIOD</th>
                                    <th>KEYWORDS</th>
                                    <th class="report-actions-heading" style="text-align: center;">
                                        ACTIONS
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($reports as $report)
                                <tr>
                                    <td>
                                        {{ ($reports->firstItem() ?? 0) + $loop->index }}
                                    </td>

                                    <td>
                                        <strong class="report-client">
                                            {{ $report->client->name }}
                                        </strong>

                                        <span class="report-preparer">
                                            Prepared by {{ $report->prepared_by }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="report-website">
                                            {{ $report->client->website }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="report-period-tag">
                                            {{ $report->reporting_period }}
                                        </span>
                                    </td>

                                    <td data-order="{{ count($report->keywords ?? []) }}">
                                        {{ count($report->keywords ?? []) }}
                                    </td>

                                    <td>
                                        <div class="report-row-actions">

                                            <a href="{{ route('seo-reports.pdf', $report->id) }}"
                                                class="report-export-link"
                                                title="Export {{ $report->client->name }} report as PDF">

                                                <svg viewBox="0 0 20 20" aria-hidden="true">
                                                    <path d="M10 3v9m0 0 3-3m-3 3L7 9M4 13v3h12v-3" />
                                                </svg>

                                                Export PDF
                                            </a>

                                            <a href="{{ route('seo-reports.edit', $report->id) }}"
                                                class="report-edit-link">
                                                Edit
                                            </a>

                                            <form action="{{ route('seo-reports.destroy', $report->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Move this report to trash?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="report-delete-button"
                                                    aria-label="Move {{ $report->client->name }} report to trash"
                                                    title="Move to trash">

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

                </section>

            </div>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>


    <script>
        $(document).ready(function() {

            $('#seoReportsTable').DataTable({

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
                    searchPlaceholder: 'Search reports...',
                    lengthMenu: 'Show _MENU_ reports',
                    info: 'Showing _START_ to _END_ of _TOTAL_ reports',
                    infoEmpty: 'No reports available',
                    zeroRecords: 'No matching reports found',
                    emptyTable: 'No reports available'
                }

            });

        });
    </script>

@include('admin.partials.footer')