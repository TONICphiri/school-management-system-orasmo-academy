<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overview') | MoEST School Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="print:bg-white">
@php $me = auth()->user(); @endphp
<div class="grid min-h-screen grid-cols-[var(--spacing-side)_1fr] max-md:grid-cols-1 print:block">
    <aside data-sidebar class="sticky top-0 flex h-screen flex-col overflow-y-auto bg-brand-dark text-side-text max-md:fixed max-md:left-0 max-md:z-50 max-md:w-side max-md:-translate-x-full max-md:transition-transform print:hidden">
        <div class="flex items-center gap-3 border-b border-white/10 px-[1.1rem] pt-[1.1rem] pb-4">
            <div class="grid size-[38px] flex-none place-items-center border-t-[5px] border-b-[5px] border-t-flag-black border-b-flag-red bg-white text-[.8rem] font-bold tracking-[.04em] text-brand-dark">MW</div>
            <div>
                <strong class="block text-[.95rem] text-white">MoEST SMS</strong>
                <span class="text-[.75rem] text-side-muted">Ministry of Education</span>
            </div>
        </div>
        <div class="border-b border-white/10 px-[1.1rem] py-[.85rem] text-[.8rem] text-side-muted">
            @if ($navSchool)
                <strong class="block text-[.88rem] font-semibold text-white">{{ $navSchool->name }}</strong>
                {{ $navSchool->code }} &middot; {{ $navSchool->district->name }}
            @else
                <strong class="block text-[.88rem] font-semibold text-white">{{ $me->roleLabel() }}</strong>
                {{ $me->scopeLabel() }}
            @endif
        </div>
        @include('partials.nav')
        <div class="border-t border-white/10 px-[1.1rem] py-[.9rem] text-[.75rem] text-side-group">Republic of Malawi<br>Ministry of Education</div>
    </aside>

    <div class="flex min-w-0 flex-col">
        <header class="sticky top-0 z-20 flex h-[60px] items-center gap-4 border-b border-line bg-panel px-6 print:hidden">
            <button data-menu class="inline-grid border border-line bg-transparent px-[.45rem] py-[.35rem] md:hidden" type="button" aria-label="Open menu">{{ icon('menu') }}</button>
            <div class="min-w-0 flex-1 truncate text-[.85rem] text-muted">@yield('crumbs')<strong class="font-semibold text-ink">@yield('title', 'Overview')</strong></div>
            @if ($navTerm)
                <span class="border border-line bg-canvas px-[.6rem] py-1 text-[.8rem] whitespace-nowrap text-ink-2 max-md:hidden">{{ $navTerm->label() }}</span>
            @endif

            <div class="relative">
                <button class="relative grid size-[38px] place-items-center border border-line bg-transparent text-ink-2 hover:bg-canvas" type="button" data-toggle="bell-panel" aria-label="Notifications">
                    {{ icon('bell', 19) }}
                    @if ($bellCount)<span class="absolute -top-1.5 -right-1.5 grid h-[19px] min-w-[19px] place-items-center bg-danger px-1 text-[.7rem] font-bold text-white">{{ $bellCount > 99 ? '99+' : $bellCount }}</span>@endif
                </button>
                <div data-dropdown id="bell-panel" class="absolute top-[calc(100%+8px)] right-0 z-40 hidden w-[380px] border border-line bg-panel shadow-[0_8px_24px_rgba(20,30,25,.12)] max-md:w-[320px]">
                    <div class="flex items-center justify-between border-b border-line px-[.9rem] py-[.7rem]">
                        <strong class="text-[.9rem]">Notifications</strong>
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
                    <div class="border-t border-line px-[.9rem] py-[.6rem] text-center text-[.85rem]"><a href="{{ route('notices.index') }}">View all notifications</a></div>
                </div>
            </div>

            <div class="relative">
                <button class="flex items-center gap-[.55rem] border border-line bg-transparent py-1 pr-[.6rem] pl-1 text-ink" type="button" data-toggle="user-panel">
                    <span class="grid size-[30px] place-items-center bg-brand text-[.75rem] font-bold text-white">{{ $me->initials() }}</span>
                    <span class="text-left leading-[1.15] max-md:hidden"><strong class="block text-[.85rem]">{{ $me->name }}</strong><span class="text-[.72rem] text-muted">{{ $me->roleLabel() }}</span></span>
                </button>
                <div data-dropdown id="user-panel" class="absolute top-[calc(100%+8px)] right-0 z-40 hidden w-[220px] border border-line bg-panel shadow-[0_8px_24px_rgba(20,30,25,.12)] [&_a]:block [&_a]:border-b [&_a]:border-line-2 [&_a]:px-[.9rem] [&_a]:py-[.6rem] [&_a]:text-[.88rem] [&_a]:text-ink [&_a:hover]:bg-canvas [&_a:hover]:no-underline">
                    <a href="{{ route('account.edit') }}">My account</a>
                    <a href="{{ route('notices.index') }}">Notifications</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="block w-full bg-transparent px-[.9rem] py-[.6rem] text-left text-[.88rem] text-ink hover:bg-canvas">Sign out</button></form>
                </div>
            </div>
        </header>

        <main class="w-full max-w-[1440px] p-6 max-md:p-4 print:p-0">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
