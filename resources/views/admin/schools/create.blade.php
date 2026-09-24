@extends('layouts.app')
@section('title', 'Register a school')
@section('crumbs')<a href="{{ route('admin.schools.index') }}">Schools</a> / @endsection
@section('content')
<div class="page-head"><div><h1>Register a school</h1><div class="sub">Creates the school, its academic calendar and the head teacher account in one step</div></div></div>
<form method="POST" action="{{ route('admin.schools.store') }}">
    @csrf
    <div class="panel">
        <div class="panel-body"><div class="form-grid">@include('admin.schools._details')</div></div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Academic calendar</h2><span class="hint">Three terms with a mid-term break, following the Ministry calendar</span></div>
        <div class="panel-body">
            <div class="form-grid four">
                <div class="field"><label>Academic year</label><input type="text" name="year_name" value="{{ old('year_name', $startYear.'/'.($startYear + 1)) }}" required></div>
            </div>
            <table class="table bordered compact mt-[1rem]">
                <thead><tr><th>Term</th><th>Opens</th><th>Closes</th><th>Break name</th><th>Break starts</th><th>Break ends</th></tr></thead>
                <tbody>
                @foreach ($terms as $n => $t)
                    <tr>
                        <td class="strong">Term {{ $n }}</td>
                        <td><input type="date" name="terms[{{ $n }}][starts_on]" value="{{ old("terms.$n.starts_on", $t['starts_on']) }}" required></td>
                        <td><input type="date" name="terms[{{ $n }}][ends_on]" value="{{ old("terms.$n.ends_on", $t['ends_on']) }}" required></td>
                        <td><input type="text" name="terms[{{ $n }}][break_name]" value="{{ old("terms.$n.break_name", $t['break_name']) }}"></td>
                        <td><input type="date" name="terms[{{ $n }}][break_start]" value="{{ old("terms.$n.break_start", $t['break_start']) }}"></td>
                        <td><input type="date" name="terms[{{ $n }}][break_end]" value="{{ old("terms.$n.break_end", $t['break_end']) }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>School System Administrator</h2><span class="hint">This person registers staff, classes and subjects once the account is active</span></div>
        <div class="panel-body">
            <div class="form-grid">
                <div class="field full"><label>Who will administer the school system?</label>
                    <div class="checks">
                        <label class="check"><input type="radio" name="admin_role" value="FACILITY_ADMIN" @checked(old('admin_role', 'FACILITY_ADMIN') === 'FACILITY_ADMIN')> The head teacher, acting as system administrator</label>
                        <label class="check"><input type="radio" name="admin_role" value="SCHOOL_ADMIN" @checked(old('admin_role') === 'SCHOOL_ADMIN')> A separate School System Administrator, who then registers the head teacher</label>
                    </div>
                </div>
                <div class="field"><label>Full name</label><input type="text" name="admin_name" value="{{ old('admin_name') }}" required></div>
                <div class="field"><label>National ID number</label><input type="text" name="admin_national_id" value="{{ old('admin_national_id') }}" required></div>
                <div class="field"><label>Gender</label><select name="admin_gender"><option @selected(old('admin_gender') === 'Female')>Female</option><option @selected(old('admin_gender') === 'Male')>Male</option></select></div>
                <div class="field" data-show-when="admin_role=FACILITY_ADMIN">
                    <label>Highest teaching qualification</label>
                    <select name="admin_qualification">
                        @foreach (\App\Models\TeacherProfile::QUALIFICATIONS as $k => $v)<option value="{{ $k }}" @selected(old('admin_qualification', 'DIPLOMA') === $k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="field"><label>Mobile number</label><input type="tel" name="admin_phone" value="{{ old('admin_phone') }}" placeholder="+265 99 123 4567"></div>
                <div class="field"><label>Email</label><input type="email" name="admin_email" value="{{ old('admin_email') }}"></div>
                <div class="field">
                    <span class="label-text">Send activation by</span>
                    <label class="check"><input type="radio" name="admin_channel" value="SMS" @checked(old('admin_channel', 'SMS') === 'SMS')> SMS to the mobile number</label>
                    <label class="check"><input type="radio" name="admin_channel" value="EMAIL" @checked(old('admin_channel') === 'EMAIL')> Email</label>
                </div>
                <div class="field">
                    <span class="label-text">Activation method</span>
                    <label class="check"><input type="radio" name="admin_credential" value="OTP" @checked(old('admin_credential', 'OTP') === 'OTP')> One time code, valid for 15 minutes</label>
                    <label class="check"><input type="radio" name="admin_credential" value="TEMP_PASSWORD" @checked(old('admin_credential') === 'TEMP_PASSWORD')> Temporary password, valid for 72 hours</label>
                </div>
            </div>
        </div>
        <div class="panel-foot">
            <span class="small muted">Codes are stored only as a secure hash. The head teacher must change the password and accept the policies on first sign in.</span>
            <div class="actions"><a class="btn secondary" href="{{ route('admin.schools.index') }}">Cancel</a><button class="btn" type="submit">Register school and send activation</button></div>
        </div>
    </div>
</form>
@endsection
