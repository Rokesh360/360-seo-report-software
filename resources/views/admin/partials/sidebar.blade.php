<aside class="sidebar">
    <a class="brand sidebar-brand" href="{{ route('dashboard') }}">
        <img src="{{ asset('assets/360degreeinfo.webp') }}" alt="360 Info">
    </a>

    <div class="workspace-label"></div>

    <!-- OVERVIEW GROUP -->
    <nav class="side-nav" aria-label="Main navigation">
        <div class="nav-group-label">Dashboard</div>
        <a @class(['side-link', 'is-active'=> $active === 'overview']) href="{{ route('dashboard') }}" @if($active === 'overview') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true">
                <rect x="3" y="3" width="5" height="5" rx="1" />
                <rect x="12" y="3" width="5" height="5" rx="1" />
                <rect x="3" y="12" width="5" height="5" rx="1" />
                <rect x="12" y="12" width="5" height="5" rx="1" />
            </svg>
            Overview
        </a>

        <!-- MASTER GROUP -->
        <div class="nav-group-label">Master</div>
        <a @class(['side-link', 'is-active'=> $active === 'clients']) href="{{ route('clients.index') }}" @if($active === 'clients') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true">
                <circle cx="7" cy="7" r="3" />
                <path d="M2.5 16.5v-1.2A3.3 3.3 0 0 1 5.8 12h2.4a3.3 3.3 0 0 1 3.3 3.3v1.2M13 4.5a3 3 0 0 1 0 5.8M14.2 12h.2a3.1 3.1 0 0 1 3.1 3.1v1.4" />
            </svg>
            Clients
        </a>
        <a @class(['side-link', 'is-active'=> $active === 'services'])
            href="{{ route('services.index') }}"
            @if($active === 'services') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 2.5 2.5 6.5 10 10.5l7.5-4L10 2.5z" />
                <path d="M2.5 10 10 14l7.5-4" />
                <path d="M2.5 13.5 10 17.5l7.5-4" />
            </svg>
            Services
        </a>
        <a @class(['side-link', 'is-active'=> $active === 'assign-services'])
            href="{{ route('assign-services.index') }}"
            @if($active === 'assign-services') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 3.5h6a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1z" />
                <path d="M8.5 3.5V2.5h3v1" />
                <path d="M8 8h4M8 11h4M8 14h2.5" />
                <path d="M13 13.5l1.5 1.5L17 12" />
            </svg>
            Assign services
        </a>

         <!-- REPORTS GROUP -->
        <div class="nav-group-label">Reports</div>
        <a @class(['side-link', 'is-active'=> $active === 'reports']) href="{{ route('seo-reports.index') }}" @if($active === 'reports') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true">
                <path d="M5 2.75h7l3 3v11.5H5z" />
                <path d="M12 2.75v3h3M7.5 9h5M7.5 12h5M7.5 15h3" />
            </svg>
            SEO reports
        </a>
        <a @class(['side-link', 'is-active'=> $active === 'metrics-reports'])
            href="{{ route('metrics-reports.index') }}"
            @if($active === 'metrics-reports') aria-current="page" @endif>
            <svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 2.5h7l4 4V17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1z" />
                <path d="M12 2.5v4h4" />
                <path d="M7.5 12v3M10 10v5M12.5 13v2" />
            </svg>
            Metrics reports
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="profile-row">
            <span class="profile-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="profile-copy"><strong>{{ auth()->user()->name }}</strong></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="icon-button logout-button" type="submit" aria-label="Sign out" title="Sign out">
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M8 4H4.5v12H8M11 6l4 4-4 4M15 10H8" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>