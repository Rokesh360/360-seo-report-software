@include('admin.partials.header', [
'pageTitle' => $title,
'active' => 'social-media-reports',
'bodyClass' => 'admin-page report-page',
'includeBootstrap' => true,
])

@php
$v = fn ($key, $default = '') => old($key, $report?->{$key} ?? $default);

$reportType = old('report_type', $report?->report_type ?? '');

// Reporting period split
$startDate = old('reporting_period_start', $report?->reporting_period_start?->format('Y-m-d') ?? '');
$endDate = old('reporting_period_end', $report?->reporting_period_end?->format('Y-m-d') ?? '');

$reportTypes = [
'whatsapp_meta_ads' => 'WhatsApp Meta Ads Monthly Report',
'lead_generation_ads' => 'Lead Generation Ads Monthly Report',
'social_media_engagement' => 'Social Media Engagement Monthly Report',
'all' => 'All Reports (WhatsApp + Lead Gen + Engagement)',
];

// Helper: which types should each block show for
// For "all", show every block. For specific, show only matching block.
$blockTypeAttr = 'whatsapp_meta_ads lead_generation_ads social_media_engagement all';
@endphp

<div class="dashboard-content report-content">
    <section class="page-heading report-page-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p class="page-subtitle">
                Select a report type and fill in the related fields.
            </p>
        </div>
        <a href="{{ route('social-media-reports.index') }}" class="button button-secondary">
            Back to reports
        </a>
    </section>

    <form action="{{ $action }}" method="POST" enctype="multipart/form-data"
        class="report-form" novalidate id="socialReportForm">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        {{-- ==================== Report Type Selector ==================== --}}
        <div class="card mb-3">
            <div class="card-header">Report Type</div>
            <div class="card-body">
                <label class="form-label">Choose Report Type</label>
                <select name="report_type" id="report_type"
                    class="form-select @error('report_type') is-invalid @enderror" required>
                    <option value="">-- Select Report Type --</option>
                    @foreach($reportTypes as $val => $label)
                    <option value="{{ $val }}" @selected($reportType===$val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('report_type')
                <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- ==================== Common / Basic Details ==================== --}}
        <div class="card mb-3 report-type-block" data-type="{{ $blockTypeAttr }}">
            <div class="card-header">Report Details</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Client</label>
                    <select name="client_id"
                        class="form-select @error('client_id') is-invalid @enderror">
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}"
                            @selected(old('client_id', $report?->client_id ?? '') == $client->id)>
                            {{ $client->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('client_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Reporting Period From</label>
                    <input type="date" name="reporting_period_start"
                        class="form-control @error('reporting_period_start') is-invalid @enderror"
                        value="{{ $startDate }}">
                    @error('reporting_period_start')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Reporting Period To</label>
                    <input type="date" name="reporting_period_end"
                        class="form-control @error('reporting_period_end') is-invalid @enderror"
                        value="{{ $endDate }}">
                    @error('reporting_period_end')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Platforms</label>
                    <input name="platforms" class="form-control @error('platforms') is-invalid @enderror"
                        value="{{ $v('platforms', 'Facebook & Instagram') }}"
                        placeholder="e.g. Facebook & Instagram">
                    @error('platforms')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Campaign Objective</label>
                    <input name="campaign_objective"
                        class="form-control @error('campaign_objective') is-invalid @enderror"
                        value="{{ $v('campaign_objective') }}"
                        placeholder="e.g. WhatsApp Enquiries / Lead Generation">
                    @error('campaign_objective')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Campaign Overview</label>
                    <textarea name="campaign_overview" rows="3"
                        class="form-control @error('campaign_overview') is-invalid @enderror">{{ $v('campaign_overview') }}</textarea>
                    @error('campaign_overview')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Target Audience</label>
                    <textarea name="target_audience" rows="2"
                        class="form-control @error('target_audience') is-invalid @enderror">{{ $v('target_audience') }}</textarea>
                    @error('target_audience')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Main Campaign Focus</label>
                    <textarea name="campaign_focus" rows="2"
                        class="form-control @error('campaign_focus') is-invalid @enderror">{{ $v('campaign_focus') }}</textarea>
                    @error('campaign_focus')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Key Highlights</label>
                    <textarea name="key_highlights" rows="3"
                        class="form-control @error('key_highlights') is-invalid @enderror">{{ $v('key_highlights') }}</textarea>
                    @error('key_highlights')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        {{-- ==================== WhatsApp Meta Ads Metrics ==================== --}}
        <div class="card mb-3 report-type-block" data-type="whatsapp_meta_ads all">
            <div class="card-header">WhatsApp Campaign Performance</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Total WhatsApp Conversations</label>
                    <input type="number" name="total_conversations" min="0"
                        class="form-control @error('total_conversations') is-invalid @enderror"
                        value="{{ $v('total_conversations') }}">
                    @error('total_conversations')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Total WhatsApp Enquiries</label>
                    <input type="number" name="total_enquiries" min="0"
                        class="form-control @error('total_enquiries') is-invalid @enderror"
                        value="{{ $v('total_enquiries') }}">
                    @error('total_enquiries')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reach</label>
                    <input type="number" name="reach" min="0"
                        class="form-control @error('reach') is-invalid @enderror"
                        value="{{ $v('reach') }}">
                    @error('reach')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Impressions</label>
                    <input type="number" name="impressions" min="0"
                        class="form-control @error('impressions') is-invalid @enderror"
                        value="{{ $v('impressions') }}">
                    @error('impressions')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">WhatsApp Clicks</label>
                    <input type="number" name="whatsapp_clicks" min="0"
                        class="form-control @error('whatsapp_clicks') is-invalid @enderror"
                        value="{{ $v('whatsapp_clicks') }}">
                    @error('whatsapp_clicks')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Frequency</label>
                    <input type="number" step="0.01" name="frequency" min="0"
                        class="form-control @error('frequency') is-invalid @enderror"
                        value="{{ $v('frequency') }}">
                    @error('frequency')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        {{-- ==================== Lead Generation Metrics ==================== --}}
        <div class="card mb-3 report-type-block" data-type="lead_generation_ads all">
            <div class="card-header">Lead Generation Performance</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Total Leads</label>
                    <input type="number" name="total_leads" min="0"
                        class="form-control @error('total_leads') is-invalid @enderror"
                        value="{{ $v('total_leads') }}">
                    @error('total_leads')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reach</label>
                    <input type="number" name="reach" min="0"
                        class="form-control @error('reach') is-invalid @enderror"
                        value="{{ $v('reach') }}">
                    @error('reach')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Impressions</label>
                    <input type="number" name="impressions" min="0"
                        class="form-control @error('impressions') is-invalid @enderror"
                        value="{{ $v('impressions') }}">
                    @error('impressions')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Form Submissions</label>
                    <input type="number" name="form_submissions" min="0"
                        class="form-control @error('form_submissions') is-invalid @enderror"
                        value="{{ $v('form_submissions') }}">
                    @error('form_submissions')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Link Clicks</label>
                    <input type="number" name="link_clicks" min="0"
                        class="form-control @error('link_clicks') is-invalid @enderror"
                        value="{{ $v('link_clicks') }}">
                    @error('link_clicks')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Frequency</label>
                    <input type="number" step="0.01" name="frequency" min="0"
                        class="form-control @error('frequency') is-invalid @enderror"
                        value="{{ $v('frequency') }}">
                    @error('frequency')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        {{-- ==================== Social Media Engagement Metrics ==================== --}}
        <div class="card mb-3 report-type-block" data-type="social_media_engagement all">
            <div class="card-header">Engagement Performance</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Total Engagement</label>
                    <input type="number" name="total_engagement" min="0"
                        class="form-control @error('total_engagement') is-invalid @enderror"
                        value="{{ $v('total_engagement') }}">
                    @error('total_engagement')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reactions</label>
                    <input type="number" name="reactions" min="0"
                        class="form-control @error('reactions') is-invalid @enderror"
                        value="{{ $v('reactions') }}">
                    @error('reactions')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Comments</label>
                    <input type="number" name="comments" min="0"
                        class="form-control @error('comments') is-invalid @enderror"
                        value="{{ $v('comments') }}">
                    @error('comments')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Shares</label>
                    <input type="number" name="shares" min="0"
                        class="form-control @error('shares') is-invalid @enderror"
                        value="{{ $v('shares') }}">
                    @error('shares')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Saves</label>
                    <input type="number" name="saves" min="0"
                        class="form-control @error('saves') is-invalid @enderror"
                        value="{{ $v('saves') }}">
                    @error('saves')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Profile Visits</label>
                    <input type="number" name="profile_visits" min="0"
                        class="form-control @error('profile_visits') is-invalid @enderror"
                        value="{{ $v('profile_visits') }}">
                    @error('profile_visits')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">New Followers</label>
                    <input type="number" name="new_followers" min="0"
                        class="form-control @error('new_followers') is-invalid @enderror"
                        value="{{ $v('new_followers') }}">
                    @error('new_followers')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reach</label>
                    <input type="number" name="reach" min="0"
                        class="form-control @error('reach') is-invalid @enderror"
                        value="{{ $v('reach') }}">
                    @error('reach')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Impressions</label>
                    <input type="number" name="impressions" min="0"
                        class="form-control @error('impressions') is-invalid @enderror"
                        value="{{ $v('impressions') }}">
                    @error('impressions')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        {{-- ==================== Campaign / Ad Set Results (WhatsApp + Lead Gen + All) ==================== --}}
        <div class="card mb-3 report-type-block" data-type="whatsapp_meta_ads lead_generation_ads all">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Campaign / Ad Set Results</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addCampaignRow()">+ Add Row</button>
            </div>
            <div class="card-body" id="campaign-rows">
                @php
                $campaignResults = old('campaign_results', $report?->campaign_results ?? []);
                if (empty($campaignResults)) $campaignResults = [['name' => '', 'result' => '', 'spend' => '', 'note' => '']];
                @endphp
                @foreach($campaignResults as $i => $row)
                <div class="row g-2 mb-2 align-items-center campaign-row">
                    <div class="col-md-3">
                        <input name="campaign_results[{{ $i }}][name]" class="form-control"
                            placeholder="Campaign / Ad Set Name" value="{{ $row['name'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <input name="campaign_results[{{ $i }}][result]" class="form-control"
                            placeholder="Result" value="{{ $row['result'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <input name="campaign_results[{{ $i }}][spend]" class="form-control"
                            placeholder="Spend" value="{{ $row['spend'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <input name="campaign_results[{{ $i }}][note]" class="form-control"
                            placeholder="Note" value="{{ $row['note'] ?? '' }}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100"
                            onclick="this.closest('.campaign-row').remove()">×</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ==================== Creative Performance (WhatsApp + Lead Gen + All) ==================== --}}
        <div class="card mb-3 report-type-block" data-type="whatsapp_meta_ads lead_generation_ads all">
            <div class="card-header">Creative &amp; Audience Performance</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Best-Performing Creatives</label>
                    <textarea name="creative_performance[best_creatives]" rows="2" class="form-control">{{ old('creative_performance.best_creatives', $report?->creative_performance['best_creatives'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Video / Image Performance</label>
                    <textarea name="creative_performance[video_image]" rows="2" class="form-control">{{ old('creative_performance.video_image', $report?->creative_performance['video_image'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ad Copy Performance</label>
                    <textarea name="creative_performance[ad_copy]" rows="2" class="form-control">{{ old('creative_performance.ad_copy', $report?->creative_performance['ad_copy'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">CTA Performance</label>
                    <textarea name="creative_performance[cta]" rows="2" class="form-control">{{ old('creative_performance.cta', $report?->creative_performance['cta'] ?? '') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Audience Performance</label>
                    <textarea name="audience_performance[audience]" rows="2" class="form-control">{{ old('audience_performance.audience', $report?->audience_performance['audience'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Age-Group Performance</label>
                    <textarea name="audience_performance[age_group]" rows="2" class="form-control">{{ old('audience_performance.age_group', $report?->audience_performance['age_group'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location Performance</label>
                    <textarea name="audience_performance[location]" rows="2" class="form-control">{{ old('audience_performance.location', $report?->audience_performance['location'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Placement Performance</label>
                    <textarea name="audience_performance[placement]" rows="2" class="form-control">{{ old('audience_performance.placement', $report?->audience_performance['placement'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ==================== Top Posts (Engagement Report + All) ==================== --}}
        <div class="card mb-3 report-type-block" data-type="social_media_engagement all">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Top Performing Posts</span>
                <button type="button" class="btn btn-sm btn-primary" onclick="addTopPostRow()">+ Add Post</button>
            </div>
            <div class="card-body" id="top-post-rows">
                @php
                $topPosts = old('top_posts', $report?->top_posts ?? []);
                if (empty($topPosts)) $topPosts = [['title' => '', 'engagements' => '', 'type' => '']];
                @endphp
                @foreach($topPosts as $i => $row)
                <div class="row g-2 mb-2 align-items-center top-post-row">
                    <div class="col-md-5">
                        <input name="top_posts[{{ $i }}][title]" class="form-control"
                            placeholder="Post Title / Description" value="{{ $row['title'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <input name="top_posts[{{ $i }}][engagements]" class="form-control"
                            placeholder="Engagements" value="{{ $row['engagements'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <input name="top_posts[{{ $i }}][type]" class="form-control"
                            placeholder="Type (Reel/Image/Carousel)" value="{{ $row['type'] ?? '' }}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100"
                            onclick="this.closest('.top-post-row').remove()">×</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ==================== Content Analysis (Engagement Report + All) ==================== --}}
        <div class="card mb-3 report-type-block" data-type="social_media_engagement all">
            <div class="card-header">Content Analysis</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Best-Performing Content Type</label>
                    <input name="content_analysis[best_type]" class="form-control"
                        value="{{ old('content_analysis.best_type', $report?->content_analysis['best_type'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Most Engaging Topic</label>
                    <input name="content_analysis[engaging_topic]" class="form-control"
                        value="{{ old('content_analysis.engaging_topic', $report?->content_analysis['engaging_topic'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Best-Performing Format</label>
                    <input name="content_analysis[best_format]" class="form-control"
                        value="{{ old('content_analysis.best_format', $report?->content_analysis['best_format'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Facebook vs Instagram</label>
                    <input name="content_analysis[fb_vs_ig]" class="form-control"
                        value="{{ old('content_analysis.fb_vs_ig', $report?->content_analysis['fb_vs_ig'] ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Audience Response</label>
                    <textarea name="content_analysis[audience_response]" rows="2" class="form-control">{{ old('content_analysis.audience_response', $report?->content_analysis['audience_response'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ==================== Key Observations ==================== --}}
        <div class="card mb-3 report-type-block" data-type="{{ $blockTypeAttr }}">
            <div class="card-header">Key Observations</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Which creative attracted more attention</label>
                    <textarea name="key_observations[creative_attention]" rows="2" class="form-control">{{ old('key_observations.creative_attention', $report?->key_observations['creative_attention'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Which audience responded better</label>
                    <textarea name="key_observations[audience_response]" rows="2" class="form-control">{{ old('key_observations.audience_response', $report?->key_observations['audience_response'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Which placements generated stronger interaction</label>
                    <textarea name="key_observations[placement_interaction]" rows="2" class="form-control">{{ old('key_observations.placement_interaction', $report?->key_observations['placement_interaction'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Performance Highlights</label>
                    <textarea name="performance_highlights[notes]" rows="2" class="form-control">{{ old('performance_highlights.notes', $report?->performance_highlights['notes'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ==================== Monthly Summary ==================== --}}
        <div class="card mb-3 report-type-block" data-type="{{ $blockTypeAttr }}">
            <div class="card-header">Monthly Summary</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Overall Performance</label>
                    <textarea name="monthly_summary[overall]" rows="2" class="form-control">{{ old('monthly_summary.overall', $report?->monthly_summary['overall'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Enquiry / Lead Generation</label>
                    <textarea name="monthly_summary[enquiry]" rows="2" class="form-control">{{ old('monthly_summary.enquiry', $report?->monthly_summary['enquiry'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Audience Engagement</label>
                    <textarea name="monthly_summary[engagement]" rows="2" class="form-control">{{ old('monthly_summary.engagement', $report?->monthly_summary['engagement'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Brand Visibility</label>
                    <textarea name="monthly_summary[brand_visibility]" rows="2" class="form-control">{{ old('monthly_summary.brand_visibility', $report?->monthly_summary['brand_visibility'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ==================== Next Month Plan ==================== --}}
        <div class="card mb-3 report-type-block" data-type="{{ $blockTypeAttr }}">
            <div class="card-header">Next Month Plan</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Test New Creatives</label>
                    <textarea name="next_month_plan[test_creatives]" rows="2" class="form-control">{{ old('next_month_plan.test_creatives', $report?->next_month_plan['test_creatives'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Improve Audience Targeting</label>
                    <textarea name="next_month_plan[audience_targeting]" rows="2" class="form-control">{{ old('next_month_plan.audience_targeting', $report?->next_month_plan['audience_targeting'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Retarget Interested Users</label>
                    <textarea name="next_month_plan[retargeting]" rows="2" class="form-control">{{ old('next_month_plan.retargeting', $report?->next_month_plan['retargeting'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Test Different Messaging</label>
                    <textarea name="next_month_plan[messaging]" rows="2" class="form-control">{{ old('next_month_plan.messaging', $report?->next_month_plan['messaging'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Focus on High-Performing Audiences</label>
                    <textarea name="next_month_plan[high_performers]" rows="2" class="form-control">{{ old('next_month_plan.high_performers', $report?->next_month_plan['high_performers'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Increase Quality Leads / Enquiries</label>
                    <textarea name="next_month_plan[quality_leads]" rows="2" class="form-control">{{ old('next_month_plan.quality_leads', $report?->next_month_plan['quality_leads'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="report-form-actions">
            <button class="button button-primary" type="submit">Save report</button>
            <a href="{{ route('social-media-reports.index') }}" class="button button-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    /* ---------- Show/hide blocks by report type + disable hidden inputs ---------- */
    function toggleReportBlocks() {
        const selected = document.getElementById('report_type').value;

        document.querySelectorAll('.report-type-block').forEach(block => {
            const types = (block.dataset.type || '').split(/\s+/);
            const visible = types.includes(selected);

            block.style.display = visible ? '' : 'none';

            // KEY FIX: disable all form fields inside hidden blocks
            // so their duplicate names don't override the visible block's values
            block.querySelectorAll('input, select, textarea').forEach(field => {
                // Never disable the report_type selector itself
                if (field.name === 'report_type') return;
                field.disabled = !visible;
            });
        });
    }
    document.getElementById('report_type').addEventListener('change', toggleReportBlocks);
    toggleReportBlocks();

    /* ---------- Campaign rows ---------- */
    let cIndex = document.getElementById('campaign-rows').children.length;

    function addCampaignRow() {
        document.getElementById('campaign-rows').insertAdjacentHTML('beforeend', `
            <div class="row g-2 mb-2 align-items-center campaign-row">
                <div class="col-md-3"><input name="campaign_results[${cIndex}][name]" class="form-control" placeholder="Campaign / Ad Set Name"></div>
                <div class="col-md-2"><input name="campaign_results[${cIndex}][result]" class="form-control" placeholder="Result"></div>
                <div class="col-md-2"><input name="campaign_results[${cIndex}][spend]" class="form-control" placeholder="Spend"></div>
                <div class="col-md-4"><input name="campaign_results[${cIndex}][note]" class="form-control" placeholder="Note"></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.campaign-row').remove()">×</button></div>
            </div>`);
        cIndex++;
    }

    /* ---------- Top Post rows ---------- */
    let tpIndex = document.getElementById('top-post-rows').children.length;

    function addTopPostRow() {
        document.getElementById('top-post-rows').insertAdjacentHTML('beforeend', `
            <div class="row g-2 mb-2 align-items-center top-post-row">
                <div class="col-md-5"><input name="top_posts[${tpIndex}][title]" class="form-control" placeholder="Post Title / Description"></div>
                <div class="col-md-3"><input name="top_posts[${tpIndex}][engagements]" class="form-control" placeholder="Engagements"></div>
                <div class="col-md-3"><input name="top_posts[${tpIndex}][type]" class="form-control" placeholder="Type (Reel/Image/Carousel)"></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.top-post-row').remove()">×</button></div>
            </div>`);
        tpIndex++;
    }
</script>

@include('admin.partials.footer')