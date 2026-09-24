<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overview') | MoEST School Management System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
</head>
<body>
@php $me = auth()->user(); @endphp
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">MW</div>
            <div class="brand-text">
                <strong>MoEST SMS</strong>
                <span>Ministry of Education</span>
            </div>
        </div>
        <div class="side-school">
            @if ($navSchool)
                <strong>{{ $navSchool->name }}</strong>
                {{ $navSchool->code }} &middot; {{ $navSchool->district->name }}
            @else
                <strong>{{ $me->roleLabel() }}</strong>
                {{ $me->scopeLabel() }}
            @endif
        </div>
        @include('partials.nav')
        <div class="side-foot">Republic of Malawi<br>Ministry of Education</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open menu">{{ icon('menu') }}</button>
            <div class="crumbs">@yield('crumbs')<strong>@yield('title', 'Overview')</strong></div>
            @if ($navTerm)
                <span class="term-chip">{{ $navTerm->label() }}</span>
            @endif

            <div class="bell">
                <button class="bell-btn" type="button" data-toggle="bell-panel" aria-label="Notifications">
                    {{ icon('bell', 19) }}
                    @if ($bellCount)<span class="bell-count">{{ $bellCount > 99 ? '99+' : $bellCount }}</span>@endif
                </button>
                <div class="dropdown" id="bell-panel">
                    <div class="dropdown-head">
                        <strong>Notifications</strong>
                        @if ($bellCount)
                            <form method="POST" action="{{ route('notices.readAll') }}" class="inline">@csrf
                                <button class="btn ghost small" type="submit">Mark all read</button>
                            </form>
                        @endif
                    </div>
                    @forelse ($bellNotices as $n)
                        <a href="{{ route('notices.open', $n) }}" class="notice-row {{ $n->read_at ? '' : 'unread' }}">
                            <span class="notice-icon {{ $n->category }} {{ $n->priority === 'HIGH' ? 'high' : '' }}">{{ icon(\App\Models\Notice::ICONS[$n->category] ?? 'bell', 16) }}</span>
                            <span>
                                <span class="notice-title">{{ $n->title }}</span>
                                <span class="notice-body">{{ $n->body }}</span>
                                <span class="notice-time">{{ $n->created_at->diffForHumans() }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="empty"><strong>No notifications yet</strong>Updates about your work will appear here.</div>
                    @endforelse
                    <div class="dropdown-foot"><a href="{{ route('notices.index') }}">View all notifications</a></div>
                </div>
            </div>

            <div class="user-menu">
                <button class="user-btn" type="button" data-toggle="user-panel">
                    <span class="avatar">{{ $me->initials() }}</span>
                    <span class="who"><strong>{{ $me->name }}</strong><span>{{ $me->roleLabel() }}</span></span>
                </button>
                <div class="dropdown" id="user-panel">
                    <a href="{{ route('account.edit') }}">My account</a>
                    <a href="{{ route('notices.index') }}">Notifications</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
                </div>
            </div>
        </header>

        <main class="content">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('js/app.js') }}?v=3"></script>
</body>
</html>
