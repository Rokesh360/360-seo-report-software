<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Overview | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-page">
    <div class="admin-shell">
        @include('admin.partials.sidebar', ['active' => 'overview'])

        <main class="admin-main" id="overview">
            <header class="topbar">
                <div class="breadcrumbs"><span>Workspace</span><span class="crumb-slash">/</span><strong>Overview</strong></div>
                <div class="topbar-right">
                    <span class="report-period"><span class="period-dot"></span> September 2026</span>
                    <span class="topbar-divider"></span>
                    <span class="avatar-small">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
            </header>

            <div class="dashboard-content">
                <section class="page-heading">
                    <div>
                        <div class="eyebrow"><span class="status-dot status-dot-dark"></span> MONTHLY REPORT <span class="heading-date">01 SEP — 30 SEP 2026</span></div>
                        <h1>Good morning, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
                        <p class="page-subtitle">Here’s how your search visibility performed this month.</p>
                    </div>
                    <span class="sample-label"><span aria-hidden="true">i</span> Sample report data</span>
                </section>

                <section class="metric-grid" aria-label="Monthly SEO metrics">
                    <article class="metric-card">
                        <div class="metric-top"><span>Organic clicks</span><span class="metric-icon metric-icon-green" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M4 14.5 8 10l2.5 2.5L16 6"/><path d="M11.5 6H16v4.5"/></svg></span></div>
                        <div class="metric-value">24,860</div>
                        <div class="metric-foot"><span class="change-up">&#8593; 12.8%</span><span>vs. previous month</span></div>
                        <div class="metric-spark spark-green" aria-hidden="true"><span style="height:30%"></span><span style="height:43%"></span><span style="height:37%"></span><span style="height:52%"></span><span style="height:44%"></span><span style="height:67%"></span><span style="height:58%"></span><span style="height:82%"></span><span style="height:72%"></span><span style="height:94%"></span></div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span>Search impressions</span><span class="metric-icon metric-icon-blue" aria-hidden="true"><svg viewBox="0 0 20 20"><circle cx="8.5" cy="8.5" r="5.5"/><path d="m13 13 4 4"/></svg></span></div>
                        <div class="metric-value">486.2K</div>
                        <div class="metric-foot"><span class="change-up">&#8593; 8.3%</span><span>vs. previous month</span></div>
                        <div class="metric-spark spark-blue" aria-hidden="true"><span style="height:42%"></span><span style="height:55%"></span><span style="height:48%"></span><span style="height:70%"></span><span style="height:62%"></span><span style="height:78%"></span><span style="height:68%"></span><span style="height:87%"></span><span style="height:79%"></span><span style="height:96%"></span></div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span>Average position</span><span class="metric-icon metric-icon-amber" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M10 3v14M5 8l5-5 5 5M5 12l5 5 5-5"/></svg></span></div>
                        <div class="metric-value">12.4</div>
                        <div class="metric-foot"><span class="change-up">&#8593; 2.1 positions</span><span>vs. previous month</span></div>
                        <div class="metric-spark spark-amber" aria-hidden="true"><span style="height:38%"></span><span style="height:50%"></span><span style="height:42%"></span><span style="height:65%"></span><span style="height:57%"></span><span style="height:72%"></span><span style="height:66%"></span><span style="height:83%"></span><span style="height:77%"></span><span style="height:96%"></span></div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span>Tracked keywords</span><span class="metric-icon metric-icon-coral" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 4h10M5 8h10M5 12h6M5 16h6"/><circle cx="15" cy="14" r="3"/></svg></span></div>
                        <div class="metric-value">148</div>
                        <div class="metric-foot"><span class="change-up">&#8593; 9 keywords</span><span>vs. previous month</span></div>
                        <div class="metric-spark spark-coral" aria-hidden="true"><span style="height:48%"></span><span style="height:40%"></span><span style="height:57%"></span><span style="height:54%"></span><span style="height:68%"></span><span style="height:62%"></span><span style="height:79%"></span><span style="height:70%"></span><span style="height:86%"></span><span style="height:96%"></span></div>
                    </article>
                </section>

                <section class="content-grid">
                    <article class="panel performance-panel" id="performance">
                        <div class="panel-heading">
                            <div><h2>Organic performance</h2><p>Clicks and impressions over time</p></div>
                            <span class="chart-period">Daily, September</span>
                        </div>
                        <div class="chart-legend"><span><i class="legend-dot clicks-dot"></i> Clicks</span><span><i class="legend-dot impressions-dot"></i> Impressions</span><span class="chart-change">+12.8% <small>vs. last month</small></span></div>
                        <div class="chart-wrap" role="img" aria-label="Organic clicks and impressions trend increased through September">
                            <div class="chart-y-labels"><span>30K</span><span>20K</span><span>10K</span><span>0</span></div>
                            <svg class="performance-chart" viewBox="0 0 760 210" preserveAspectRatio="none" aria-hidden="true">
                                <defs><linearGradient id="clickFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#4b9a70" stop-opacity=".19"/><stop offset="1" stop-color="#4b9a70" stop-opacity="0"/></linearGradient></defs>
                                <path class="gridline" d="M0 18H760M0 72H760M0 126H760M0 180H760"/>
                                <path class="impressions-line" d="M0 123 C35 116 42 95 75 104 S120 127 151 98 S197 88 227 94 S272 110 303 78 S348 70 379 81 S423 94 455 68 S500 82 531 59 S576 72 607 49 S650 66 683 42 S728 54 760 28"/>
                                <path class="click-area" d="M0 158 C35 154 44 140 75 145 S120 158 151 137 S196 130 227 136 S272 143 303 119 S348 116 379 125 S423 129 455 110 S500 119 531 101 S576 108 607 88 S650 98 683 75 S728 82 760 65 L760 180H0Z"/>
                                <path class="clicks-line" d="M0 158 C35 154 44 140 75 145 S120 158 151 137 S196 130 227 136 S272 143 303 119 S348 116 379 125 S423 129 455 110 S500 119 531 101 S576 108 607 88 S650 98 683 75 S728 82 760 65"/>
                                <circle class="chart-point" cx="607" cy="88" r="4"/>
                            </svg>
                            <div class="chart-x-labels"><span>Sep 01</span><span>Sep 06</span><span>Sep 11</span><span>Sep 16</span><span>Sep 21</span><span>Sep 26</span><span>Sep 30</span></div>
                        </div>
                    </article>

                    <article class="panel channel-panel" id="site-health">
                        <div class="panel-heading"><div><h2>Site health</h2><p>Technical SEO score</p></div><span class="health-check-label">43 checks</span></div>
                        <div class="health-score">
                            <svg viewBox="0 0 120 120" role="img" aria-label="Site health score 92 out of 100"><circle class="health-track" cx="60" cy="60" r="48"/><circle class="health-progress" cx="60" cy="60" r="48"/></svg>
                            <div class="health-score-label"><strong>92</strong><span>out of 100</span></div>
                        </div>
                        <div class="health-status"><span class="health-status-dot"></span><strong>Great shape</strong><span>+4 pts this month</span></div>
                        <div class="health-list"><div><span><i class="health-mini-dot dot-green"></i>Passed checks</span><strong>38</strong></div><div><span><i class="health-mini-dot dot-amber"></i>Needs attention</span><strong>4</strong></div><div><span><i class="health-mini-dot dot-red"></i>Critical issues</span><strong>1</strong></div></div>
                    </article>
                </section>

                <section class="panel rankings-panel" id="rankings">
                    <div class="panel-heading rankings-heading">
                        <div><h2>Keyword rankings</h2><p>Top movers across your tracked search terms</p></div>
                        <span class="table-summary">4 tracked terms</span>
                    </div>
                    <div class="table-scroll">
                        <table>
                            <thead><tr><th>KEYWORD</th><th>POSITION</th><th>CHANGE</th><th>SEARCH VOLUME</th><th>VISIBILITY</th></tr></thead>
                            <tbody>
                                <tr><td><span class="keyword-name">technical SEO audit</span><span class="keyword-url">northstarstudio.com</span></td><td><span class="rank-pill rank-top">#3</span></td><td><span class="change-up">&#8593; 4</span></td><td>2,400</td><td><span class="visibility-value">78%</span><span class="visibility-track"><i style="width:78%"></i></span></td></tr>
                                <tr><td><span class="keyword-name">local SEO services</span><span class="keyword-url">northstarstudio.com</span></td><td><span class="rank-pill rank-top">#5</span></td><td><span class="change-up">&#8593; 2</span></td><td>1,900</td><td><span class="visibility-value">64%</span><span class="visibility-track"><i style="width:64%"></i></span></td></tr>
                                <tr><td><span class="keyword-name">SEO monthly reporting</span><span class="keyword-url">northstarstudio.com</span></td><td><span class="rank-pill">#8</span></td><td><span class="change-down">&#8595; 1</span></td><td>880</td><td><span class="visibility-value">52%</span><span class="visibility-track"><i style="width:52%"></i></span></td></tr>
                                <tr><td><span class="keyword-name">content strategy agency</span><span class="keyword-url">northstarstudio.com</span></td><td><span class="rank-pill">#11</span></td><td><span class="change-up">&#8593; 6</span></td><td>720</td><td><span class="visibility-value">39%</span><span class="visibility-track"><i style="width:39%"></i></span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <footer class="dashboard-footer"><span>Report generated October 1, 2026</span><span>Data shown is illustrative</span></footer>
            </div>
        </main>
    </div>
</body>
</html>