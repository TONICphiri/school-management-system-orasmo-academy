@extends('layouts.app')
@section('title', 'SMS and email outbox')
@section('content')
<div class="page-head"><div><h1>SMS and email outbox</h1><div class="sub">Messages sent by the system. Codes and passwords are masked.</div></div></div>
<div class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Sent</th><th>Channel</th><th>Recipient</th><th>Message</th><th>Status</th><th>Reference</th></tr></thead>
        <tbody>
        @forelse ($messages as $m)
            <tr>
                <td class="nowrap num">{{ $m->created_at->format('j M Y, H:i') }}</td>
                <td><span class="badge {{ $m->channel === 'SMS' ? 'info' : 'neutral' }}">{{ $m->channel }}</span></td>
                <td class="nowrap">{{ $m->recipient }}</td>
                <td>@if($m->subject)<strong>{{ $m->subject }}</strong><br>@endif<span class="small">{{ $m->body }}</span></td>
                <td><span class="badge {{ status_tone($m->status) }}">{{ label($m->status) }}</span></td>
                <td class="small muted">{{ $m->provider_reference }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No messages sent yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $messages->links() }}
</div>
@endsection
