@include('admin.partials.header', [
    'pageTitle' => $client->name . ' — Services',
    'active' => 'social-media-reports',
])

<div class="dashboard-content report-content">

    {{-- ============================================================ --}}
    {{-- PAGE HEADING                                                  --}}
    {{-- ============================================================ --}}
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow">
                <a href="{{ route('social-media-reports.index') }}">← All clients</a>
            </div>
            <h1>{{ $client->name }}</h1>
            <p class="page-subtitle">
                Select a service to open its metrics — or expand "Previous Metrics" inline.
            </p>
        </div>
    </section>

    @if(session('success'))
        <div class="report-flash" role="status">{{ session('success') }}</div>
    @endif

    {{-- ============================================================ --}}
    {{-- MASTER TABLE — one master row per AssignService              --}}
    {{-- ============================================================ --}}
    <section class="metrics-section">
        <div class="metrics-table-wrap">
            <table class="metrics-table master-table">
                <thead>
                    <tr>
                        <th style="width:60px;">S.No</th>
                        <th>Client Name</th>
                        <th>Title</th>
                        <th style="width:230px;">Metrics</th>
                        <!-- <th style="width:220px;" class="text-center">Today Metrics</th> -->
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignServices as $index => $assignService)
                        @php
                            // ---- Previous reports for this assign service ----
                            $previousReports = \App\Models\SocialMediaReport::forAssignService($assignService->id)
                                ->whereDate('created_at', '<', \Carbon\Carbon::today())
                                ->orderByDesc('created_at')
                                ->get();

                            // ---- Today's reports for this assign service ----
                            $todayReports = \App\Models\SocialMediaReport::forAssignService($assignService->id)
                                ->whereDate('created_at', \Carbon\Carbon::today())
                                ->orderByDesc('id')
                                ->get();

                            $todayEntryMissing = $todayReports->isEmpty();
                            $canAddToday = (bool) ($assignService->status ?? true)
                                && \Carbon\Carbon::today()->betweenIncluded(
                                    $assignService->from_date,
                                    $assignService->to_date
                                );

                            $masterId = 'svc-' . $assignService->id;
                        @endphp

                        {{-- ===================== MASTER ROW ===================== --}}
                        <tr class="master-row">
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <strong>{{ $client->name }}</strong>
                            </td>

                            <td>
                                <strong class="service-title">
                                    <a class="service-title-link">
                                        {{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}
                                    </a>
                                </strong>
                                <span class="service-dates">
                                    {{ $assignService->from_date?->format('d M Y') }} —
                                    {{ $assignService->to_date?->format('d M Y') }}
                                </span>
                            </td>

                            {{-- Previous Metrics → dedicated page --}}
                            <td>
                                <a href="{{ route('social-media-reports.previous-metrics', $assignService->id) }}"
                                   class="button button-secondary btn-previous-metrics"
                                   title="View all previous metrics">
                                    <span>
                                        Metrics
                                    </span>
                                    <span class="btn-arrow" aria-hidden="true">→</span>
                                </a>
                            </td>

                            {{-- Today Metrics column: Edit / Delete / Add --}}
                            <!-- <td>
                                @if($todayEntryMissing && $canAddToday)
                                    <div class="today-actions">
                                        <a href="{{ route('social-media-reports.create', ['assign_service_id' => $assignService->id]) }}"
                                           class="btn-add-today"
                                           title="No entry for today — add one">
                                            + Add today
                                        </a>
                                    </div>
                                @elseif($todayEntryMissing)
                                    <span class="service-dates">
                                        Today's entry is unavailable because this assignment is inactive or outside its date range.
                                    </span>
                                @else
                                    @foreach($todayReports as $today)
                                        <div class="today-actions">
                                            <span class="service-dates">
                                                {{ $today->created_at?->format('d M Y') }}
                                            </span>
                                            <a href="{{ route('social-media-reports.edit', $today->id) }}"
                                               class="btn-view"
                                               title="Edit report">
                                                Edit
                                            </a>

                                            {{-- Delete form — SweetAlert intercepts submission --}}
                                            <form action="{{ route('social-media-reports.destroy', $today->id) }}"
                                                  method="POST"
                                                  class="inline-form js-delete-form"
                                                  data-report-id="{{ $today->id }}"
                                                  data-report-date="{{ $today->created_at?->format('d M Y') }}"
                                                  data-client-name="{{ $client->name }}"
                                                  data-service-title="{{ $assignService->title ?? $assignService->service_names ?? 'Service #' . $assignService->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-delete" title="Delete report">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                @endif
                            </td> -->
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5">
                                <p class="empty-state">No services assigned to this client yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('admin.partials.footer')

{{-- ============================================================ --}}
{{-- SweetAlert2 --}}
{{-- ============================================================ --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    (function () {
        // ---------- Master accordion (Previous Metrics per service) ----------
        document.querySelectorAll('.accordion-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const detail = document.querySelector(btn.dataset.target);
                if (!detail) return;

                const isOpen = !detail.hidden;

                document.querySelectorAll('.accordion-detail-row').forEach(function (el) {
                    if (el !== detail) el.hidden = true;
                });
                document.querySelectorAll('.accordion-trigger').forEach(function (b) {
                    if (b !== btn) {
                        b.classList.remove('is-open');
                        b.setAttribute('aria-expanded', 'false');
                    }
                });
                document.querySelectorAll('.sub-accordion-detail').forEach(function (el) {
                    el.hidden = true;
                });
                document.querySelectorAll('.sub-accordion-row').forEach(function (r) {
                    r.classList.remove('is-open');
                    r.setAttribute('aria-expanded', 'false');
                });

                detail.hidden = isOpen;
                btn.classList.toggle('is-open', !isOpen);
                btn.setAttribute('aria-expanded', String(!isOpen));
            });
        });

        // ---------- Sub accordion (individual previous report) ----------
        document.querySelectorAll('.sub-accordion-row').forEach(function (row) {
            row.addEventListener('click', function (event) {
                if (event.target.closest('a, button, form, input, label')) return;

                const detail = document.querySelector(row.getAttribute('data-target'));
                if (!detail) return;

                const isOpen = !detail.hidden;

                const parentTable = row.closest('.subtable');
                if (parentTable) {
                    parentTable.querySelectorAll('.sub-accordion-detail').forEach(function (el) {
                        if (el !== detail) el.hidden = true;
                    });
                    parentTable.querySelectorAll('.sub-accordion-row').forEach(function (r) {
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

        // ---------- SweetAlert delete confirmation ----------
        document.querySelectorAll('.js-delete-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
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
        });
    })();
</script>