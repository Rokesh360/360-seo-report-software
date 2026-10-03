<aside class="sidebar">
    <a class="brand sidebar-brand" href="{{ route('dashboard') }}">
        <span class="brand-mark" aria-hidden="true">360</span>
        <span>360<span class="brand-muted">/</span>SEO</span>
    </a>

    <div class="workspace-label">WORKSPACE</div>
    <nav class="side-nav" aria-label="Main navigation">
        <a @class(['side-link', 'is-active' => $active === 'overview']) href="{{ route('dashboard') }}" @if($active === 'overview') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="12" y="3" width="5" height="5" rx="1"/><rect x="3" y="12" width="5" height="5" rx="1"/><rect x="12" y="12" width="5" height="5" rx="1"/></svg>
            Overview
        </a>
        <a class="side-link" href="{{ route('dashboard') }}#performance">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 15.5 7.2 11l3 2.5 6.8-8"/><path d="M12.7 5.5H17v4.3"/></svg>
            Performance
        </a>
        <a class="side-link" href="{{ route('dashboard') }}#rankings">
            <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5"/><path d="m13 13 4 4M6.5 8.5h4M8.5 6.5v4"/></svg>
            Keywords
        </a>
        <a class="side-link" href="{{ route('dashboard') }}#site-health">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2.5 16 5v4.4c0 3.8-2.4 6.5-6 8.1-3.6-1.6-6-4.3-6-8.1V5l6-2.5Z"/><path d="m7.5 9.8 1.7 1.7 3.5-3.8"/></svg>
            Site health
        </a>
        <a @class(['side-link', 'is-active' => $active === 'reports']) href="{{ route('seo-reports.index') }}" @if($active === 'reports') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 2.75h7l3 3v11.5H5z"/><path d="M12 2.75v3h3M7.5 9h5M7.5 12h5M7.5 15h3"/></svg>
            SEO reports
        </a>
        <!-- <a class="side-link side-link-secondary" href="{{ route('seo-reports.create') }}">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>
            New report
        </a> -->
        <a @class(['side-link', 'is-active' => $active === 'clients']) href="{{ route('clients.index') }}" @if($active === 'clients') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="7" cy="7" r="3"/><path d="M2.5 16.5v-1.2A3.3 3.3 0 0 1 5.8 12h2.4a3.3 3.3 0 0 1 3.3 3.3v1.2M13 4.5a3 3 0 0 1 0 5.8M14.2 12h.2a3.1 3.1 0 0 1 3.1 3.1v1.4"/></svg>
            Clients
        </a>
        <!-- <a class="side-link side-link-secondary" href="{{ route('clients.create') }}">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>
            New client
        </a> -->
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-divider"></div>
        <div class="workspace-card">
            <span class="workspace-icon" aria-hidden="true">N</span>
            <span class="workspace-copy"><strong>Northstar Studio</strong><small>SEO workspace</small></span>
        </div>
        <div class="profile-row">
            <span class="profile-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="profile-copy"><strong>{{ auth()->user()->name }}</strong><small>Administrator</small></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="icon-button logout-button" type="submit" aria-label="Sign out" title="Sign out">
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8 4H4.5v12H8M11 6l4 4-4 4M15 10H8"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>