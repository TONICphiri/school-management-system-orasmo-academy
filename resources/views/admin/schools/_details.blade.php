@php $s = $school ?? null; @endphp
<div class="form-section">School details</div>
<div class="field"><label>School name</label><input type="text" name="name" value="{{ old('name', $s?->name) }}" required placeholder="For example Chilomoni Primary School"></div>
<div class="field"><label>EMIS school code</label><input type="text" name="code" value="{{ old('code', $s?->code) }}" required></div>
@unless ($s)
<div class="field">
    <label>School type</label>
    <select name="type" required>
        @foreach (\App\Models\School::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>@endforeach
    </select>
</div>
<div class="field">
    <label>Education structure</label>
    <select name="structure" required>
        @foreach (\App\Models\School::STRUCTURES as $k => $v)<option value="{{ $k }}" @selected(old('structure', '8-4-4') === $k)>{{ $v }}</option>@endforeach
    </select>
    <div class="help">The 1-6-6-3 option is for schools piloting the new structure.</div>
</div>
@endunless
<div class="field">
    <label>Category</label>
    <select name="category" required>
        @foreach (\App\Models\School::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(old('category', $s?->category) === $k)>{{ $v }}</option>@endforeach
    </select>
</div>
@unless ($s)
<div class="field">
    <label>Initial status</label>
    <select name="status" required>
        @foreach (\App\Models\School::STATUSES as $k => $v)<option value="{{ $k }}" @selected(old('status', 'PENDING_ACTIVATION') === $k)>{{ $v }}</option>@endforeach
    </select>
    <div class="help">Pending schools become active when the head teacher activates the account.</div>
</div>
@endunless

<div class="form-section">Location and supervision</div>
<div class="field">
    <label>Education division</label>
    <select name="division_id" required>
        <option value="">Select division</option>
        @foreach ($divisions as $d)<option value="{{ $d->id }}" @selected(old('division_id', $s?->division_id) == $d->id)>{{ $d->name }}</option>@endforeach
    </select>
</div>
<div class="field">
    <label>District</label>
    <select name="district_id" required>
        <option value="">Select district</option>
        @foreach ($districts as $d)<option value="{{ $d->id }}" data-parent="{{ $d->division_id }}" @selected(old('district_id', $s?->district_id) == $d->id)>{{ $d->name }}</option>@endforeach
    </select>
</div>
<div class="field">
    <label>Zone</label>
    <select name="zone_id">
        <option value="">No zone</option>
        @foreach ($zones as $z)<option value="{{ $z->id }}" data-parent="{{ $z->district_id }}" @selected(old('zone_id', $s?->zone_id) == $z->id)>{{ $z->name }} ({{ $z->district->name }})</option>@endforeach
    </select>
    <div class="help">Primary schools are supervised by the PEA of the zone.</div>
</div>
<div class="field"><label>MANEB centre number</label><input type="text" name="maneb_centre_number" value="{{ old('maneb_centre_number', $s?->maneb_centre_number) }}"></div>
<div class="field"><label>Postal address</label><input type="text" name="postal_address" value="{{ old('postal_address', $s?->postal_address) }}"></div>
<div class="field"><label>School phone</label><input type="tel" name="phone" value="{{ old('phone', $s?->phone) }}"></div>
<div class="field"><label>School email</label><input type="email" name="email" value="{{ old('email', $s?->email) }}"></div>
