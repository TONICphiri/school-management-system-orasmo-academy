@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="page-head">
    <div><h1>Notifications</h1><div class="sub">{{ $unread }} unread</div></div>
    <div class="actions">
        @if ($unread)
            <form method="POST" action="{{ route('notices.readAll') }}">@csrf<button class="btn secondary" type="submit">{{ icon('check', 16) }} Mark all as read</button></form>
        @endif
    </div>
</div>
<div class="tabs">
    <a href="{{ route('notices.index') }}" class="{{ ! request('show') && ! request('category') ? 'active' : '' }}">All</a>
    <a href="{{ route('notices.index', ['show' => 'unread']) }}" class="{{ request('show') === 'unread' ? 'active' : '' }}">Unread</a>
    @foreach (\App\Models\Notice::CATEGORIES as $key => $name)
        <a href="{{ route('notices.index', ['category' => $key]) }}" class="{{ request('category') === $key ? 'active' : '' }}">{{ $name }}</a>
    @endforeach
</div>
<div class="panel">
    @forelse ($notices as $n)
        <a href="{{ route('notices.open', $n) }}" class="notice-row {{ $n->read_at ? '' : 'unread' }} p-[.9rem_1rem]">
            <span class="notice-icon {{ $n->category }} {{ $n->priority === 'HIGH' ? 'high' : '' }}">{{ icon(\App\Models\Notice::ICONS[$n->category] ?? 'bell', 16) }}</span>
            <span class="flex-1">
                <span class="notice-title">{{ $n->title }}</span>
                @if ($n->priority === 'HIGH') <span class="badge bad ml-[.4rem]">Important</span> @endif
                <span class="notice-body line-clamp-none">{{ $n->body }}</span>
            </span>
            <span class="notice-time nowrap">{{ $n->created_at->format('j M Y, H:i') }}</span>
        </a>
    @empty
        <div class="empty"><strong>Nothing here</strong>You have no notifications in this view.</div>
    @endforelse
    {{ $notices->links() }}
</div>
@endsection
