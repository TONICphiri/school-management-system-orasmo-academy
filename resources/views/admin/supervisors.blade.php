@extends('layouts.app')
@section('title', 'Supervisors')
@section('content')
<div class="page-head"><div><h1>Supervisors</h1><div class="sub">Education Division Managers, District Education Managers and Primary Education Advisors have read only access to schools in their area</div></div></div>
<div class="grid grid-main">
    <div class="panel">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Name</th><th>Role</th><th>Covers</th><th>Contact</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($supervisors as $u)
                <tr>
                    <td class="strong">{{ $u->name }}</td>
                    <td><span class="badge info">{{ $u->role }}</span> <span class="small muted">{{ $u->roleLabel() }}</span></td>
                    <td>{{ $u->scopeLabel() }}</td>
                    <td class="small">{{ $u->email }}<br>{{ $u->phone }}</td>
                    <td><span class="badge {{ status_tone($u->status) }}">{{ label($u->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No supervisors registered.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Add a supervisor</h2></div>
        <form method="POST" action="{{ route('admin.supervisors.store') }}">
            @csrf
            <div class="panel-body">
                <div class="field mb-[.8rem]"><label>Full name</label><input type="text" name="name" value="{{ old('name') }}" required></div>
                <div class="field mb-[.8rem]"><label>Role</label>
                    <select name="role">
                        <option value="EDM" @selected(old('role') === 'EDM')>Education Division Manager (secondary)</option>
                        <option value="DEM" @selected(old('role') === 'DEM')>District Education Manager (primary)</option>
                        <option value="PEA" @selected(old('role') === 'PEA')>Primary Education Advisor (zone)</option>
                    </select>
                </div>
                <div class="field mb-[.8rem]" data-show-when="role=EDM"><label>Division</label>
                    <select name="division_id"><option value="">Select</option>@foreach ($divisions as $d)<option value="{{ $d->id }}" @selected(old('division_id') == $d->id)>{{ $d->name }}</option>@endforeach</select>
                </div>
                <div class="field mb-[.8rem]" data-show-when="role=DEM"><label>District</label>
                    <select name="district_id"><option value="">Select</option>@foreach ($districts as $d)<option value="{{ $d->id }}" @selected(old('district_id') == $d->id)>{{ $d->name }}</option>@endforeach</select>
                </div>
                <div class="field mb-[.8rem]" data-show-when="role=PEA"><label>Zone</label>
                    <select name="zone_id"><option value="">Select</option>@foreach ($zones as $z)<option value="{{ $z->id }}" @selected(old('zone_id') == $z->id)>{{ $z->name }}, {{ $z->district->name }}</option>@endforeach</select>
                </div>
                <div class="field mb-[.8rem]"><label>Email</label><input type="email" name="email" value="{{ old('email') }}"></div>
                <div class="field mb-[.8rem]"><label>Mobile number</label><input type="tel" name="phone" value="{{ old('phone') }}"></div>
                <div class="field"><label>Send activation code by</label><select name="channel"><option value="EMAIL">Email</option><option value="SMS" @selected(old('channel') === 'SMS')>SMS</option></select></div>
            </div>
            <div class="panel-foot"><span></span><button class="btn" type="submit">Create and send code</button></div>
        </form>
    </div>
</div>
@endsection
