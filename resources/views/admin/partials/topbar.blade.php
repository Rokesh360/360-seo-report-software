<header class="topbar">
    <div class="breadcrumbs"><span>Workspace</span><span class="crumb-slash">/</span><strong>{{ $pageTitle }}</strong></div>
    <div class="topbar-right">
        <span class="report-period"><span class="period-dot"></span> {{ now()->format('F Y') }}</span>
        <span class="topbar-divider"></span>
        <span class="avatar-small">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
    </div>
</header>