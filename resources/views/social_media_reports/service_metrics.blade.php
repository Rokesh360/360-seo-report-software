@include('admin.partials.header', [
'pageTitle' => 'Metrics — ' . ($assignService->title ?? 'Service'),
'active' => 'metrics-reports',
])

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('metrics-reports.client-services', $assignService->client_id) }}">
                    ← {{ $assignService->client->name }}
                </a>
            </div>
            <h1>{{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}</h1>
            <p class="page-subtitle">
                {{ $assignService->from_date?->format('d M Y') }} —
                {{ $assignService->to_date?->format('d M Y') }}
            </p>
        </div>
    </section>

    @if(session('success'))
    <div class="report-flash" role="status">{{ session('success') }}</div>
    @endif

    {{-- ============================================================ --}}
    {{-- MASTER TABLE — Previous Metrics accordion + Today Metrics   --}}
    {{-- ============================================================ --}}
    <section class="metrics-section">
        <div class="metrics-table-wrap">
            <table class="metrics-table master-table">
                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th>Client Name</th>
                        <th>Assigned Service Title</th>
                        <th style="width:230px;">Previous Metrics</th>
                        <th style="width:220px;" class="text-center">Today Metrics</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    // Master row shows the assign service context once.
                    // Previous rows are listed directly below.
                    $masterRowspan = max($previousReports->count(), 1);
                    @endphp

                    {{-- ===================== MASTER ROW ===================== --}}
                    <tr class="master-row">
                        <td>1</td>
                        <td>
                            <strong>{{ $assignService->client->name }}</strong>
                        </td>
                        <td>
                            <strong class="service-title">
                                {{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}
                            </strong>
                            <span class="service-dates">
                                {{ $assignService->from_date?->format('d M Y') }} —
                                {{ $assignService->to_date?->format('d M Y') }}
                            </span>
                        </td>

                        {{-- Previous Metrics → dedicated page --}}
                        <td>
                            <a href="{{ route('metrics-reports.previous-metrics', $assignService->id) }}"
                                class="button button-secondary btn-previous-metrics"
                                title="View all previous metrics">
                                <span>
                                    Previous Metrics
                                    <small>({{ $previousReports->count() }})</small>
                                </span>
                                <span class="btn-arrow" aria-hidden="true">→</span>
                            </a>
                        </td>

                        {{-- Today Metrics column: View / Delete / Add --}}
                        <td>
                            @if($todayEntryMissing && $canAddToday)
                            <div class="today-actions">
                                <a href="{{ route('metrics-reports.create', ['assign_service_id' => $assignService->id]) }}"
                                    class="btn-add-today"
                                    title="No entry for today — add one">
                                    + Add today
                                </a>
                            </div>
                            @elseif($todayEntryMissing)
                            <span class="service-dates">Today's entry is unavailable because this assign service is inactive or outside its date range.</span>
                            @else
                            @foreach($todayReports as $today)
                            <div class="today-actions">
                                <span class="service-dates">
                                    {{ $today->created_at?->format('d M Y') }}
                                </span>
                                <a href="{{ route('metrics-reports.pdf', $today->id) }}"
                                    class="btn-view"
                                    title="View report">
                                    View
                                </a>
                                <form action="{{ route('metrics-reports.destroy', $today->id) }}"
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
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('admin.partials.footer')

<script>
    (function() {
        // ---------- Master accordion (Previous Metrics) ----------
        document.querySelectorAll('.accordion-trigger').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const detail = document.querySelector(btn.dataset.target);
                if (!detail) return;

                const isOpen = !detail.hidden;

                // Close all other master panels + sub panels
                document.querySelectorAll('.accordion-detail-row').forEach(function(el) {
                    if (el !== detail) el.hidden = true;
                });
                document.querySelectorAll('.accordion-trigger').forEach(function(b) {
                    if (b !== btn) {
                        b.classList.remove('is-open');
                        b.setAttribute('aria-expanded', 'false');
                    }
                });
                document.querySelectorAll('.sub-accordion-detail').forEach(function(el) {
                    el.hidden = true;
                });
                document.querySelectorAll('.sub-accordion-row').forEach(function(r) {
                    r.classList.remove('is-open');
                    r.setAttribute('aria-expanded', 'false');
                });

                detail.hidden = isOpen;
                btn.classList.toggle('is-open', !isOpen);
                btn.setAttribute('aria-expanded', String(!isOpen));
            });
        });

        // ---------- Sub accordion (individual previous report) ----------
        document.querySelectorAll('.sub-accordion-row').forEach(function(row) {
            row.addEventListener('click', function(event) {
                if (event.target.closest('a, button, form, input, label')) return;

                const detail = document.querySelector(row.getAttribute('data-target'));
                if (!detail) return;

                const isOpen = !detail.hidden;

                // Close sibling sub panels within the same parent subtable
                const parentTable = row.closest('.subtable');
                if (parentTable) {
                    parentTable.querySelectorAll('.sub-accordion-detail').forEach(function(el) {
                        if (el !== detail) el.hidden = true;
                    });
                    parentTable.querySelectorAll('.sub-accordion-row').forEach(function(r) {
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