@include('admin.partials.header', [
'pageTitle' => $report ? 'Edit Google Ads Report' : 'Create Google Ads Report',
'active' => 'google-ads',
'bodyClass' => 'admin-page report-page',
'includeBootstrap' => true,
])


@php
$report = $report ?? null;

$data = old('data', $report?->data ?? []);
$images = $report?->overview_images ?? [];

// Determine the Google Ads service types.
$serviceNames = $assignService->getServices()->pluck('name');

$serviceTypes = $serviceNames
->map(fn ($name) => \App\Support\ReportServiceType::googleAdsTypeFromServiceName($name))
->filter()
->unique()
->values();

$isYoutube = $serviceTypes->contains('google_youtube_ads');
$isCallAds = $serviceTypes->contains('google_call_ads');

// Fixed gender options.
$genderOptions = ['Male', 'Female', 'Unknown'];

$savedDemographics = collect($data['demographics'] ?? [])
->keyBy(fn ($row) => strtolower($row['gender'] ?? ''));

$demographics = collect($genderOptions)->map(function ($gender, $index) use ($savedDemographics) {
$saved = $savedDemographics->get(strtolower($gender), []);

return [
'gender' => $gender,
'clicks' => old(
"data.demographics.{$index}.clicks",
$saved['clicks'] ?? 0
),
];
});

// Preserve the existing dynamic sections.
$locations = old('data.locations', $data['locations'] ?? [
['campaign' => '', 'cpc' => '', 'impressions' => '', 'avg_cpc' => ''],
]);

$keyMetrics = old('data.key_metrics', $data['key_metrics'] ?? [
['metric' => '', 'value' => '', 'insight' => ''],
]);

// Helper: mark a field invalid if it has an error.
$err = fn (string $key) => $errors->has($key) ? 'is-invalid' : '';
@endphp

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <div class="eyebrow"><a href="{{ route('google-ads.assign-service', $assignService) }}">← {{ $assignService->title }}</a></div>
            <h1>{{ $report ? 'Edit Google Ads report' : 'Create Google Ads report' }}</h1>
            <p class="page-subtitle">{{ $assignService->client->name }} · {{ $assignService->service_names }}</p>
        </div>
    </section>

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data"
        class="report-form google-ads-report-form">
        @csrf
        @if($method !== 'POST') @method($method) @endif
        <input type="hidden" name="assign_service_id" value="{{ $assignService->id }}">

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>1. Campaign Overview <span class="required-star">*</span></span>
                <button type="button" class="btn btn-sm btn-primary" id="add-image-btn">+ Add Images</button>
            </div>
            <div class="card-body">
                <input type="file" id="overview-images-input" name="overview_images[]" class="d-none"
                    accept="image/jpeg,image/png,image/webp" multiple>

                <small class="text-muted d-block mb-2">
                    JPG, PNG or WebP, up to 10 MB each, maximum 12 images in total. You can add images in several batches.
                </small>

                <div id="image-client-error" class="field-error mb-2" style="display:none;" role="alert"></div>
                @error('overview_images')<div class="field-error">{{ $message }}</div>@enderror
                @foreach($errors->get('overview_images.*') as $messages)
                @foreach($messages as $message)<div class="field-error">{{ $message }}</div>@endforeach
                @endforeach

                @if (!empty($images))
                <div class="fw-semibold mb-1">Saved images</div>
                <div class="row g-3 mb-3">
                    @foreach ($images as $image)
                    <div class="col-6 col-md-3">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}"
                            alt="Campaign overview screenshot" class="img-thumbnail"
                            style="height:150px;width:100%;object-fit:contain;">
                        <label class="form-check mt-2">
                            <input type="checkbox" name="remove_images[]" value="{{ $image }}" class="form-check-input">
                            <span class="form-check-label">Remove image</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @endif

                <div class="fw-semibold mb-1" id="new-images-title" style="display:none;">New images</div>
                <div class="row g-3" id="new-image-previews"></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Campaign Performance</div>
            <div class="card-body row g-3">
                @foreach(['clicks' => 'Clicks', 'impressions' => 'Impressions'] as $key => $label)
                <div class="col-md-4">
                    <label class="form-label">{{ $label }} <span class="required-star">*</span></label>
                    <input type="number" min="0" name="data[{{ $key }}]" id="google-ads-{{ $key }}"
                        class="form-control {{ $err("data.$key") }}"
                        required value="{{ old("data.$key", $data[$key] ?? '') }}">
                    @error("data.$key")<div class="field-error">{{ $message }}</div>@enderror
                </div>
                @endforeach

                @if($isYoutube || (! $isCallAds && ! $isYoutube))
                @foreach(['views' => 'Views', 'interactions' => 'Interactions'] as $key => $label)
                <div class="col-md-4">
                    <label class="form-label">{{ $label }} @if($isYoutube)<span class="required-star">*</span>@endif</label>
                    <input type="number" min="0" name="data[{{ $key }}]"
                        class="form-control {{ $err("data.$key") }}"
                        @required($isYoutube) value="{{ old("data.$key", $data[$key] ?? '') }}">
                    @error("data.$key")<div class="field-error">{{ $message }}</div>@enderror
                </div>
                @endforeach
                @endif

                @if($isCallAds || (! $isCallAds && ! $isYoutube))
                <div class="col-md-4">
                    <label class="form-label">Calls @if($isCallAds)<span class="required-star">*</span>@endif</label>
                    <input type="number" min="0" name="data[calls]"
                        class="form-control {{ $err('data.calls') }}"
                        @required($isCallAds) value="{{ old('data.calls', $data['calls'] ?? '') }}">
                    @error('data.calls')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                @endif

                <div class="col-md-4">
                    <label class="form-label">Total Cost (₹) <span class="required-star">*</span></label>
                    <input type="number" min="0" step="0.01" name="data[total_cost]" id="google-ads-cost"
                        class="form-control {{ $err('data.total_cost') }}"
                        required value="{{ old('data.total_cost', $data['total_cost'] ?? '') }}">
                    @error('data.total_cost')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Balance Amount (₹)</label>
                    <input type="text" id="google-ads-balance" class="form-control" readonly
                        data-budget="{{ $assignService->budget }}"
                        value="{{ number_format($assignService->budget - (float) ($data['total_cost'] ?? 0), 2, '.', '') }}">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">2. Audience Demographics — Gender</h5>
            </div>

            <div class="card-body">
                <p class="text-muted mb-3">
                    Enter approximate clicks for Male, Female, and Unknown. The combined
                    clicks must not exceed total campaign clicks, and percentages are
                    calculated automatically to total 100% when all clicks are allocated.
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Gender</th>
                                <th style="width: 35%;">Approx. Clicks <span class="required-star">*</span></th>
                                <th style="width: 30%;">Percentage</th>
                            </tr>
                        </thead>

                        <tbody id="gender-rows">
                            @foreach ($demographics as $index => $demographic)
                            <tr>
                                <td>
                                    <input
                                        type="hidden"
                                        name="data[demographics][{{ $index }}][gender]"
                                        value="{{ $demographic['gender'] }}">

                                    <select class="form-select" disabled>
                                        <option value="Male"
                                            @selected($demographic['gender']==='Male' )>
                                            Male
                                        </option>
                                        <option value="Female"
                                            @selected($demographic['gender']==='Female' )>
                                            Female
                                        </option>
                                        <option value="Unknown"
                                            @selected($demographic['gender']==='Unknown' )>
                                            Unknown
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="data[demographics][{{ $index }}][clicks]"
                                        class="form-control gender-clicks {{ $err("data.demographics.$index.clicks") }}"
                                        min="0"
                                        step="1"
                                        value="{{ $demographic['clicks'] }}"
                                        required>
                                    @error("data.demographics.$index.clicks")
                                    <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </td>

                                <td>
                                    <span class="gender-percentage fw-semibold">0%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr class="table-light">
                                <th>Total</th>
                                <th>
                                    <span id="gender-clicks-total">0</span>
                                </th>
                                <th>
                                    <span id="gender-percentage-total">0%</span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @error('data.demographics')
                <div class="field-error mt-2">{{ $message }}</div>
                @enderror

                <div
                    id="gender-clicks-error"
                    class="field-error mt-2"
                    style="display: none;"
                    role="alert">
                    Combined gender clicks cannot exceed total campaign clicks.
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>3. Location Performance <span class="required-star">*</span></span>
                <button type="button" class="btn btn-sm btn-primary" id="add-location-row">+ Add Location</button>
            </div>
            <div class="card-body" id="location-rows">
                @foreach($locations as $index => $row)
                <div class="row g-2 mb-2 location-row">
                    <div class="{{ $isYoutube ? 'col-md-3' : 'col-md-3' }}">
                        <input
                            name="data[locations][{{ $index }}][campaign]"
                            class="form-control {{ $err("data.locations.$index.campaign") }}"
                            required
                            placeholder="Campaign / Location *"
                            value="{{ old("data.locations.$index.campaign", $row['campaign'] ?? '') }}">
                        @error("data.locations.$index.campaign")
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <input
                            type="tel"
                            name="data[locations][{{ $index }}][cpc]"
                            class="form-control {{ $err("data.locations.$index.cpc") }}"
                            required
                            placeholder="CPC (₹) *"
                            value="{{ old("data.locations.$index.cpc", $row['cpc'] ?? '') }}">
                        @error("data.locations.$index.cpc")
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="{{ $isYoutube ? 'col-md-3' : 'col-md-3' }}">
                        <input
                            type="number"
                            min="0"
                            name="data[locations][{{ $index }}][impressions]"
                            class="form-control {{ $err("data.locations.$index.impressions") }}"
                            required
                            placeholder="Impressions *"
                            value="{{ old("data.locations.$index.impressions", $row['impressions'] ?? '') }}">
                        @error("data.locations.$index.impressions")
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    @unless($isYoutube)
                    <div class="col-md-2">
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            name="data[locations][{{ $index }}][avg_cpc]"
                            class="form-control {{ $err("data.locations.$index.avg_cpc") }}"
                            required
                            placeholder="Avg. CPC (₹) *"
                            value="{{ old("data.locations.$index.avg_cpc", $row['avg_cpc'] ?? '') }}">
                        @error("data.locations.$index.avg_cpc")
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    @endunless

                    <div class="col-md-2">
                        <button
                            type="button"
                            class="btn btn-outline-danger w-100 remove-google-row">
                            Remove
                        </button>
                    </div>
                </div>
                @endforeach
                @error('data.locations')<div class="field-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>4. Key Metrics Analysis <span class="required-star">*</span></span>
                <button type="button" class="btn btn-sm btn-primary" id="add-key-metric-row">+ Add Metric</button>
            </div>
            <div class="card-body" id="key-metric-rows">
                @foreach($keyMetrics as $index => $row)
                <div class="row g-2 mb-2 key-metric-row">
                    <div class="col-md-3">
                        <input name="data[key_metrics][{{ $index }}][metric]"
                            class="form-control {{ $err("data.key_metrics.$index.metric") }}"
                            required placeholder="Metric *"
                            value="{{ old("data.key_metrics.$index.metric", $row['metric'] ?? '') }}">
                        @error("data.key_metrics.$index.metric")
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <input name="data[key_metrics][{{ $index }}][value]"
                            class="form-control {{ $err("data.key_metrics.$index.value") }}"
                            required placeholder="Value *"
                            value="{{ old("data.key_metrics.$index.value", $row['value'] ?? '') }}">
                        @error("data.key_metrics.$index.value")
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-5">
                        <input name="data[key_metrics][{{ $index }}][insight]"
                            class="form-control {{ $err("data.key_metrics.$index.insight") }}"
                            required placeholder="Insight *"
                            value="{{ old("data.key_metrics.$index.insight", $row['insight'] ?? '') }}">
                        @error("data.key_metrics.$index.insight")
                        <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100 remove-google-row">Remove</button>
                    </div>
                </div>
                @endforeach
                @error('data.key_metrics')<div class="field-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="report-form-actions">
            <button type="submit" class="button button-primary">{{ $report ? 'Update report' : 'Save report' }}</button>
            <a href="{{ route('google-ads.assign-service', $assignService) }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    (() => {
        const genderRows = document.getElementById('gender-rows');
        const genderClicksTotal = document.getElementById('gender-clicks-total');
        const genderPercentageTotal = document.getElementById('gender-percentage-total');
        const genderError = document.getElementById('gender-clicks-error');

        const cost = document.getElementById('google-ads-cost');
        const balance = document.getElementById('google-ads-balance');
        const form = document.querySelector('.report-form');

        // ---------- Multi image upload (accumulates across picks) ----------
        const imageInput = document.getElementById('overview-images-input');
        const addImageBtn = document.getElementById('add-image-btn');
        const previews = document.getElementById('new-image-previews');
        const previewsTitle = document.getElementById('new-images-title');
        const imageError = document.getElementById('image-client-error');
        const MAX_IMAGES = 12;
        const MAX_SIZE = 10 * 1024 * 1024;
        const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
        let pickedFiles = [];

        const savedImageCount = () =>
            document.querySelectorAll('input[name="remove_images[]"]:not(:checked)').length;

        const showImageError = (messages) => {
            imageError.innerHTML = '';
            messages.forEach((text) => {
                const line = document.createElement('div');
                line.textContent = text;
                imageError.appendChild(line);
            });
            imageError.style.display = messages.length ? 'block' : 'none';
        };

        const syncImageInput = () => {
            const transfer = new DataTransfer();
            pickedFiles.forEach((file) => transfer.items.add(file));
            imageInput.files = transfer.files;
        };

        const renderPreviews = () => {
            previews.innerHTML = '';
            previewsTitle.style.display = pickedFiles.length ? 'block' : 'none';

            pickedFiles.forEach((file, index) => {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-3';

                const img = document.createElement('img');
                img.className = 'img-thumbnail';
                img.style.cssText = 'height:150px;width:100%;object-fit:contain;';
                img.alt = file.name;
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);

                const name = document.createElement('small');
                name.className = 'd-block text-muted text-truncate mt-1';
                name.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-outline-danger btn-sm w-100 mt-1 remove-new-image';
                remove.dataset.index = index;
                remove.textContent = 'Remove';

                col.append(img, name, remove);
                previews.appendChild(col);
            });
        };

        addImageBtn?.addEventListener('click', () => imageInput.click());

        imageInput?.addEventListener('change', () => {
            const problems = [];

            [...imageInput.files].forEach((file) => {
                const duplicate = pickedFiles.some((f) =>
                    f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);

                if (duplicate) return;
                if (!ALLOWED_TYPES.includes(file.type)) {
                    problems.push(`${file.name}: only JPG, PNG or WebP images are allowed.`);
                    return;
                }
                if (file.size > MAX_SIZE) {
                    problems.push(`${file.name}: larger than 10 MB.`);
                    return;
                }
                if (savedImageCount() + pickedFiles.length >= MAX_IMAGES) {
                    problems.push(`${file.name}: maximum of ${MAX_IMAGES} images reached.`);
                    return;
                }
                pickedFiles.push(file);
            });

            syncImageInput(); // keep only the accumulated list in the real input
            renderPreviews();
            showImageError(problems);
        });

        previews?.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-new-image');
            if (!button) return;

            pickedFiles.splice(Number(button.dataset.index), 1);
            syncImageInput();
            renderPreviews();
            showImageError([]);
        });

        // Require at least one image (saved and kept, or newly picked) before submitting.
        form?.addEventListener('submit', (event) => {
            if (savedImageCount() + pickedFiles.length === 0) {
                event.preventDefault();
                showImageError(['Upload at least one campaign overview screenshot.']);
                imageError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });

        let locationIndex = document.querySelectorAll('#location-rows .location-row').length;
        let keyMetricIndex = document.querySelectorAll('#key-metric-rows .key-metric-row').length;

        function updateGenderPercentages() {
            if (!genderRows) return true;

            const campaignClicksInput = document.getElementById('google-ads-clicks');
            const campaignClicks = Math.max(
                0,
                Number(campaignClicksInput?.value) || 0
            );

            const inputs = [...genderRows.querySelectorAll('.gender-clicks')];

            let totalGenderClicks = 0;

            inputs.forEach((input) => {
                const clicks = Math.max(0, Number(input.value) || 0);
                totalGenderClicks += clicks;

                // Each gender's clicks cannot exceed campaign clicks.
                input.max = campaignClicks;
                input.setCustomValidity(
                    clicks > campaignClicks ?
                    'Gender clicks cannot exceed total campaign clicks.' :
                    ''
                );
            });

            // Calculate percentages against total campaign clicks.
            let totalPercentage = 0;

            inputs.forEach((input, index) => {
                const clicks = Math.max(0, Number(input.value) || 0);

                let percentage = campaignClicks > 0 ?
                    (clicks / campaignClicks) * 100 :
                    0;

                // Round each percentage to two decimal places.
                percentage = Math.round(percentage * 100) / 100;

                // Make the final row absorb any rounding difference.
                if (index === inputs.length - 1) {
                    percentage = Math.max(
                        0,
                        Math.round((100 - totalPercentage) * 100) / 100
                    );

                    // If clicks are not fully allocated, use the actual ratio instead.
                    if (totalGenderClicks < campaignClicks) {
                        percentage = campaignClicks > 0 ?
                            Math.round((clicks / campaignClicks) * 10000) / 100 :
                            0;
                    }
                }

                input.closest('tr').querySelector('.gender-percentage').textContent =
                    `${percentage.toFixed(2)}%`;

                totalPercentage += percentage;
            });

            if (genderClicksTotal) {
                genderClicksTotal.textContent = totalGenderClicks;
            }

            // Show the actual allocated percentage, not an assumed 100%.
            const allocatedPercentage = campaignClicks > 0 ?
                (totalGenderClicks / campaignClicks) * 100 :
                0;

            if (genderPercentageTotal) {
                genderPercentageTotal.textContent =
                    `${allocatedPercentage.toFixed(2)}%`;
            }

            const invalid = totalGenderClicks > campaignClicks;

            inputs.forEach((input) => {
                input.classList.toggle(
                    'is-invalid',
                    input.validity.rangeOverflow || invalid
                );
            });

            if (genderError) {
                genderError.textContent =
                    `Combined gender clicks (${totalGenderClicks}) cannot exceed ` +
                    `total campaign clicks (${campaignClicks}).`;

                genderError.style.display = invalid ? 'block' : 'none';
            }

            return !invalid;
        }

        function updateBalance() {
            if (!cost || !balance) return;

            const budget = Number(balance.dataset.budget) || 0;
            const totalCost = Number(cost.value) || 0;

            balance.value = (budget - totalCost).toFixed(2);
        }

        function addRow(container, html) {
            container.insertAdjacentHTML('beforeend', html);
        }

        form?.addEventListener('invalid', (event) => {
            event.target.classList.add('is-invalid');
        }, true);

        form?.addEventListener('input', (event) => {
            if (event.target.matches('.form-control, .form-select') && event.target.validity.valid) {
                event.target.classList.remove('is-invalid');
            }
        });

        // Add location row.
        document.getElementById('add-location-row')?.addEventListener('click', () => {
            addRow(
                document.getElementById('location-rows'),
                `<div class="row g-2 mb-2 location-row">
                <div class="col-md-3">
                    <input name="data[locations][${locationIndex}][campaign]"
                        class="form-control" required placeholder="Campaign / Location *">
                </div>
                <div class="col-md-2">
                    <input type="number" min="0" step="0.01"
                        name="data[locations][${locationIndex}][cpc]"
                        class="form-control" required placeholder="CPC (₹) *">
                </div>
                <div class="col-md-3">
                    <input type="number" min="0"
                        name="data[locations][${locationIndex}][impressions]"
                        class="form-control" required placeholder="Impressions *">
                </div>
                <div class="col-md-2">
                    <input type="number" min="0" step="0.01"
                        name="data[locations][${locationIndex}][avg_cpc]"
                        class="form-control" required placeholder="Avg. CPC (₹) *">
                </div>
                <div class="col-md-2">
                    <button type="button"
                        class="btn btn-outline-danger w-100 remove-google-row">
                        Remove
                    </button>
                </div>
            </div>`
            );

            locationIndex++;
        });

        // Add key metric row.
        document.getElementById('add-key-metric-row')?.addEventListener('click', () => {
            addRow(
                document.getElementById('key-metric-rows'),
                `<div class="row g-2 mb-2 key-metric-row">
                <div class="col-md-3">
                    <input name="data[key_metrics][${keyMetricIndex}][metric]"
                        class="form-control" required placeholder="Metric *">
                </div>
                <div class="col-md-2">
                    <input name="data[key_metrics][${keyMetricIndex}][value]"
                        class="form-control" required placeholder="Value *">
                </div>
                <div class="col-md-5">
                    <input name="data[key_metrics][${keyMetricIndex}][insight]"
                        class="form-control" required placeholder="Insight *">
                </div>
                <div class="col-md-2">
                    <button type="button"
                        class="btn btn-outline-danger w-100 remove-google-row">
                        Remove
                    </button>
                </div>
            </div>`
            );

            keyMetricIndex++;
        });

        // Recalculate values and remove dynamic rows.
        form?.addEventListener('input', (event) => {
            if (
                event.target.matches('.gender-clicks') ||
                event.target.matches('#google-ads-clicks')
            ) {
                updateGenderPercentages();
            }

            if (event.target.matches('#google-ads-cost')) {
                updateBalance();
            }
        });

        form?.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-google-row');

            if (!button) return;

            const row = button.closest('tr, .location-row, .key-metric-row');

            if (row) {
                row.remove();
                updateGenderPercentages();
            }
        });

        // Prevent submitting when gender percentages total more than 100%.
        form?.addEventListener('submit', (event) => {
            if (!updateGenderPercentages()) {
                event.preventDefault();

                if (genderError) {
                    genderError.style.display = 'block';
                }

                genderRows?.querySelector('.gender-clicks')?.focus();
            }
        });

        updateGenderPercentages();
        updateBalance();
    })();
</script>

@include('admin.partials.footer')