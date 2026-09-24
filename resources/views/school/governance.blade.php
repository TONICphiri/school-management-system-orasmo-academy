@extends('layouts.app')
@section('title', 'School governance')
@section('content')
<div class="page-head">
    <div><h1>School governance</h1><div class="sub">Members get read only summaries by SMS and can raise concerns with the head teacher</div></div>
    <div class="actions">
        <form method="POST" action="{{ route('school.governance.summary') }}" data-confirm="Send the term summary by SMS to all governance members now?">@csrf<button class="btn secondary" type="submit">{{ icon('send', 16) }} Send term summary</button></form>
    </div>
</div>
@if (isset($bodies['BOG']))
    <div class="stats">
        <div class="stat"><div class="label">Board of Governors</div><div class="value">{{ $bogTotal }}<small> of 13</small></div><div class="progress"><span style="width:{{ min(100, $bogTotal / 13 * 100) }}%"></span></div></div>
        <div class="stat blue"><div class="label">Voting members</div><div class="value">{{ $bogVoting }}<small> of 9</small></div><div class="progress"><span style="width:{{ min(100, $bogVoting / 9 * 100) }}%"></span></div></div>
    </div>
@endif
<div class="grid grid-main">
    <div class="stack">
        @foreach ($bodies as $key => $name)
            <div class="panel">
                <div class="panel-head"><h2>{{ $name }}</h2><span class="hint">{{ ($members[$key] ?? collect())->count() }} members</span></div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Name</th><th>Position</th>@if($key === 'BOG')<th>Vote</th>@endif<th>Contact</th><th>Term ends</th><th>Account</th></tr></thead>
                    <tbody>
                    @forelse ($members[$key] ?? [] as $m)
                        <tr>
                            <td class="strong">{{ $m->user->name }}</td>
                            <td>{{ $m->position }}</td>
                            @if($key === 'BOG')<td>@if($m->is_voting)<span class="badge ok">Voting</span>@else<span class="badge neutral">Ex officio</span>@endif</td>@endif
                            <td class="small">{{ $m->user->phone ?: $m->user->email }}</td>
                            <td>{{ $m->term_ends?->format('M Y') ?? 'Open' }}</td>
                            <td><span class="badge {{ status_tone($m->user->status) }}">{{ label($m->user->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No members recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        @endforeach
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Add a member</h2></div>
        <form method="POST" action="{{ route('school.governance.store') }}">@csrf
            <div class="panel-body stack-sm">
                <div class="field"><label>Full name</label><input type="text" name="name" value="{{ old('name') }}" required></div>
                <div class="field"><label>Body</label><select name="body">@foreach ($bodies as $k => $v)<option value="{{ $k }}" @selected(old('body') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label>Position</label><input type="text" name="position" value="{{ old('position') }}" placeholder="Chairperson, Treasurer, Member" required></div>
                <label class="check" data-show-when="body=BOG"><input type="checkbox" name="is_voting" value="1" @checked(old('is_voting'))> Voting member of the Board</label>
                <div class="field"><label>Mobile number</label><input type="tel" name="phone" value="{{ old('phone') }}"></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}"></div>
                <div class="field"><label>Send activation by</label><select name="channel"><option value="SMS">SMS</option><option value="EMAIL" @selected(old('channel') === 'EMAIL')>Email</option></select></div>
                <div class="field"><label>Term of office ends</label><input type="date" name="term_ends" value="{{ old('term_ends') }}"></div>
            </div>
            <div class="panel-foot"><span class="small muted">The board is limited to 13 members, 9 voting.</span><button class="btn" type="submit">Add member</button></div>
        </form>
    </div>
</div>
@endsection
