@include('admin.partials.header', [
    'pageTitle' => $title,
    'active' => 'social-media-reports',
    'bodyClass' => 'admin-page report-page',
    'includeBootstrap' => true,
])

@php
use App\Models\AssignService;
use App\Models\Service;

/* --------------------------------------------------------------
 |  Prefill resolution
 -------------------------------------------------------------- */
$prefillAssignServiceId = old('assign_service_id',
    request()->integer('assign_service_id') ?: ($prefillId ?? null)
);

$selectedAssignServiceIds = old('assign_service_ids',
    $report?->assign_service_ids ?? ($prefillAssignServiceId ? [$prefillAssignServiceId] : [])
);
if (!is_array($selectedAssignServiceIds)) {
    $selectedAssignServiceIds = $prefillAssignServiceId ? [$prefillAssignServiceId] : [];
}
$selectedAssignServiceIds = array_map('intval', $selectedAssignServiceIds);

/* --------------------------------------------------------------
 |  Which service types are attached to those assignments?
 -------------------------------------------------------------- */
$serviceNameToType = [
    'WhatsApp Meta Ads Monthly Report'       => 'whatsapp_meta_ads',
    'Lead Generation Ads Monthly Report'     => 'lead_generation_ads',
    'Social Media Engagement Monthly Report' => 'social_media_engagement',
];

$serviceIds = AssignService::whereIn('id', $selectedAssignServiceIds)
    ->get()
    ->flatMap(fn ($a) => is_array($a->service_id) ? $a->service_id : array_filter([(int) $a->service_id]))
    ->filter()
    ->unique();

$activeTypes = Service::whereIn('id', $serviceIds)
    ->pluck('name')
    ->map(fn ($name) => $serviceNameToType[$name] ?? null)
    ->filter()
    ->unique()
    ->values()
    ->all();

$v   = fn ($key, $default = '') => old($key, $report?->{$key} ?? $default);
$err = fn (string $key) => $errors->has($key) ? 'is-invalid' : '';

$showWa = in_array('whatsapp_meta_ads',       $activeTypes, true);
$showLg = in_array('lead_generation_ads',     $activeTypes, true);
$showEn = in_array('social_media_engagement', $activeTypes, true);
$leadOrWhatsappActive = $showWa || $showLg;
@endphp

<div class="dashboard-content report-content">

    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">Fill in the fields below and save the report.</p>
        </div>
        <a href="{{ route('social-media-reports.index') }}" class="button button-secondary">Back to reports</a>
    </section>

    <form action="{{ $action }}" method="POST" enctype="multipart/form-data"
          class="report-form" novalidate id="socialReportForm">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        {{-- Hidden assign_service_ids[] --}}
        @foreach($selectedAssignServiceIds as $sid)
            <input type="hidden" name="assign_service_ids[]" value="{{ $sid }}">
        @endforeach

        @error('assign_service_ids')<span class="field-error">{{ $message }}</span>@enderror
        @error('assign_service_ids.*')<span class="field-error">{{ $message }}</span>@enderror

        {{-- ============ WHATSAPP ============ --}}
        @if($showWa)
            <div class="card mb-3">
                <div class="card-header">WhatsApp Campaign Performance</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Total Conversations <span class="text-danger">*</span></label>
                        <input type="number" name="total_conversations"
                               class="form-control {{ $err('total_conversations') }}"
                               required value="{{ $v('total_conversations') }}">
                        @error('total_conversations')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Total Enquiries <span class="text-danger">*</span></label>
                        <input type="number" name="total_enquiries"
                               class="form-control {{ $err('total_enquiries') }}"
                               required value="{{ $v('total_enquiries') }}">
                        @error('total_enquiries')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">WhatsApp Clicks <span class="text-danger">*</span></label>
                        <input type="number" name="whatsapp_clicks"
                               class="form-control {{ $err('whatsapp_clicks') }}"
                               required value="{{ $v('whatsapp_clicks') }}">
                        @error('whatsapp_clicks')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Reach <span class="text-danger">*</span></label>
                        <input type="number" name="reach"
                               class="form-control {{ $err('reach') }}"
                               required value="{{ $v('reach') }}">
                        @error('reach')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Impressions <span class="text-danger">*</span></label>
                        <input type="number" name="impressions"
                               class="form-control {{ $err('impressions') }}"
                               required value="{{ $v('impressions') }}">
                        @error('impressions')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Frequency <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="frequency"
                               class="form-control {{ $err('frequency') }}"
                               required value="{{ $v('frequency') }}">
                        @error('frequency')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        @endif

        {{-- ============ LEAD GEN ============ --}}
        @if($showLg)
            <div class="card mb-3">
                <div class="card-header">Lead Generation Performance</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Total Leads <span class="text-danger">*</span></label>
                        <input type="number" name="total_leads"
                               class="form-control {{ $err('total_leads') }}"
                               required value="{{ $v('total_leads') }}">
                        @error('total_leads')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Form Submissions <span class="text-danger">*</span></label>
                        <input type="number" name="form_submissions"
                               class="form-control {{ $err('form_submissions') }}"
                               required value="{{ $v('form_submissions') }}">
                        @error('form_submissions')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Link Clicks <span class="text-danger">*</span></label>
                        <input type="number" name="link_clicks"
                               class="form-control {{ $err('link_clicks') }}"
                               required value="{{ $v('link_clicks') }}">
                        @error('link_clicks')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        @endif

        {{-- ============ ENGAGEMENT ============ --}}
        @if($showEn)
            <div class="card mb-3">
                <div class="card-header">Engagement Performance</div>
                <div class="card-body row g-3">
                    @foreach([
                        'total_engagement' => 'Total Engagement',
                        'reactions'        => 'Reactions',
                        'comments'         => 'Comments',
                        'shares'           => 'Shares',
                        'saves'            => 'Saves',
                        'profile_visits'   => 'Profile Visits',
                        'new_followers'    => 'New Followers',
                    ] as $field => $label)
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                            <input type="number" name="{{ $field }}"
                                   class="form-control {{ $err($field) }}"
                                   required value="{{ $v($field) }}">
                            @error($field)<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ============ CAMPAIGN / AD SET RESULTS ============ --}}
        @if($leadOrWhatsappActive)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Campaign / Ad Set Results</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addCampaignRow()">+ Add Row</button>
                </div>
                <div class="card-body" id="campaign-rows">
                    @php
                        $campaignResults = old('campaign_results', $report?->campaign_results ?? []);
                        if (empty($campaignResults)) {
                            $campaignResults = [['name'=>'','result'=>'','spend'=>'','note'=>'']];
                        }
                    @endphp

                    @foreach($campaignResults as $i => $row)
                        <div class="row g-2 mb-2 align-items-center campaign-row">
                            <div class="col-md-3">
                                <input name="campaign_results[{{ $i }}][name]"
                                       class="form-control {{ $err("campaign_results.$i.name") }}"
                                       required placeholder="Campaign / Ad Set Name"
                                       value="{{ $row['name'] ?? '' }}">
                                @error("campaign_results.$i.name")<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-2">
                                <input name="campaign_results[{{ $i }}][result]"
                                       class="form-control {{ $err("campaign_results.$i.result") }}"
                                       required placeholder="Result"
                                       value="{{ $row['result'] ?? '' }}">
                                @error("campaign_results.$i.result")<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-2">
                                <input name="campaign_results[{{ $i }}][spend]"
                                       class="form-control {{ $err("campaign_results.$i.spend") }}"
                                       required placeholder="Spend"
                                       value="{{ $row['spend'] ?? '' }}">
                                @error("campaign_results.$i.spend")<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-4">
                                <input name="campaign_results[{{ $i }}][note]"
                                       class="form-control {{ $err("campaign_results.$i.note") }}"
                                       required placeholder="Note"
                                       value="{{ $row['note'] ?? '' }}">
                                @error("campaign_results.$i.note")<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100"
                                        onclick="this.closest('.campaign-row').remove()">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ============ TOP POSTS ============ --}}
        @if($showEn)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Top Performing Posts</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTopPostRow()">+ Add</button>
                </div>
                <div class="card-body" id="top-post-rows">
                    @php
                        $topPosts = old('top_posts', $report?->top_posts ?? []);
                        if (empty($topPosts)) {
                            $topPosts = [['title'=>'','engagements'=>'','type'=>'']];
                        }
                    @endphp

                    @foreach($topPosts as $i => $row)
                        <div class="row g-2 mb-2 align-items-center top-post-row">
                            <div class="col-md-5">
                                <input name="top_posts[{{ $i }}][title]"
                                       class="form-control {{ $err("top_posts.$i.title") }}"
                                       required placeholder="Post Title / Description"
                                       value="{{ $row['title'] ?? '' }}">
                                @error("top_posts.$i.title")<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-3">
                                <input name="top_posts[{{ $i }}][engagements]"
                                       class="form-control {{ $err("top_posts.$i.engagements") }}"
                                       required placeholder="Engagements"
                                       value="{{ $row['engagements'] ?? '' }}">
                                @error("top_posts.$i.engagements")<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-3">
                                <input name="top_posts[{{ $i }}][type]"
                                       class="form-control {{ $err("top_posts.$i.type") }}"
                                       required placeholder="Type (Reel/Image/Carousel)"
                                       value="{{ $row['type'] ?? '' }}">
                                @error("top_posts.$i.type")<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100"
                                        onclick="this.closest('.top-post-row').remove()">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ============ KEY OBSERVATIONS ============ --}}
        <div class="card mb-3">
            <div class="card-header">Key Observations</div>
            <div class="card-body row g-3">
                @foreach([
                    'key_observations[creative_attention]'    => ['key_observations.creative_attention',    'Creative attention'],
                    'key_observations[audience_response]'     => ['key_observations.audience_response',     'Audience response'],
                    'key_observations[placement_interaction]' => ['key_observations.placement_interaction', 'Placement interaction'],
                    'performance_highlights[notes]'           => ['performance_highlights.notes',           'Performance Highlights'],
                ] as $name => [$key, $label])
                    @php $value = old($key, data_get($report, $key, '')); @endphp
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                        <textarea name="{{ $name }}" rows="2"
                                  class="form-control {{ $err($key) }}"
                                  required>{{ $value }}</textarea>
                        @error($key)<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ MONTHLY SUMMARY ============ --}}
        <div class="card mb-3">
            <div class="card-header">Monthly Summary</div>
            <div class="card-body row g-3">
                @foreach([
                    'monthly_summary[overall]'          => ['monthly_summary.overall',          'Overall'],
                    'monthly_summary[enquiry]'          => ['monthly_summary.enquiry',          'Enquiry / Lead Gen'],
                    'monthly_summary[engagement]'       => ['monthly_summary.engagement',       'Audience Engagement'],
                    'monthly_summary[brand_visibility]' => ['monthly_summary.brand_visibility', 'Brand Visibility'],
                ] as $name => [$key, $label])
                    @php $value = old($key, data_get($report, $key, '')); @endphp
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                        <textarea name="{{ $name }}" rows="2"
                                  class="form-control {{ $err($key) }}"
                                  required>{{ $value }}</textarea>
                        @error($key)<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ NEXT MONTH PLAN ============ --}}
        <div class="card mb-3">
            <div class="card-header">Next Month Plan</div>
            <div class="card-body row g-3">
                @foreach([
                    'next_month_plan[test_creatives]'     => ['next_month_plan.test_creatives',     'Test New Creatives'],
                    'next_month_plan[audience_targeting]' => ['next_month_plan.audience_targeting', 'Improve Audience Targeting'],
                    'next_month_plan[retargeting]'        => ['next_month_plan.retargeting',        'Retarget Interested Users'],
                    'next_month_plan[messaging]'          => ['next_month_plan.messaging',          'Test Different Messaging'],
                    'next_month_plan[high_performers]'    => ['next_month_plan.high_performers',    'Focus on High-Performers'],
                    'next_month_plan[quality_leads]'      => ['next_month_plan.quality_leads',      'Increase Quality Leads'],
                ] as $name => [$key, $label])
                    @php $value = old($key, data_get($report, $key, '')); @endphp
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }} <span class="text-danger">*</span></label>
                        <textarea name="{{ $name }}" rows="2"
                                  class="form-control {{ $err($key) }}"
                                  required>{{ $value }}</textarea>
                        @error($key)<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ========== Actions ========== --}}
        <div class="report-form-actions">
            <button type="submit" class="button button-primary">Save report</button>
            <a href="{{ route('social-media-reports.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
<script>
/* Dynamic rows — Campaign / Ad Set */
let cIndex = document.getElementById('campaign-rows')?.children.length ?? 0;

function addCampaignRow() {
    document.getElementById('campaign-rows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 align-items-center campaign-row">
            <div class="col-md-3"><input name="campaign_results[${cIndex}][name]" class="form-control" required placeholder="Campaign / Ad Set Name"></div>
            <div class="col-md-2"><input name="campaign_results[${cIndex}][result]" class="form-control" required placeholder="Result"></div>
            <div class="col-md-2"><input name="campaign_results[${cIndex}][spend]" class="form-control" required placeholder="Spend"></div>
            <div class="col-md-4"><input name="campaign_results[${cIndex}][note]" class="form-control" required placeholder="Note"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.campaign-row').remove()">×</button></div>
        </div>`);
    cIndex++;
}

/* Dynamic rows — Top Posts */
let tpIndex = document.getElementById('top-post-rows')?.children.length ?? 0;

function addTopPostRow() {
    document.getElementById('top-post-rows').insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 align-items-center top-post-row">
            <div class="col-md-5"><input name="top_posts[${tpIndex}][title]" class="form-control" required placeholder="Post Title / Description"></div>
            <div class="col-md-3"><input name="top_posts[${tpIndex}][engagements]" class="form-control" required placeholder="Engagements"></div>
            <div class="col-md-3"><input name="top_posts[${tpIndex}][type]" class="form-control" required placeholder="Type (Reel/Image/Carousel)"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.top-post-row').remove()">×</button></div>
        </div>`);
    tpIndex++;
}
</script>

@include('admin.partials.footer')