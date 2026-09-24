<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Visit date</th><th>School</th><th>Supervisor</th><th>Directorate</th><th>Rating</th><th>Follow up</th><th></th></tr></thead>
        <tbody>
        @forelse ($reports as $r)
            <tr>
                <td class="nowrap">{{ $r->visit_date->format('j M Y') }}</td>
                <td class="strong">{{ $r->school->name }}</td>
                <td>{{ $r->supervisor->name }}<div class="small muted">{{ $r->supervisor->roleLabel() }}</div></td>
                <td>{{ $r->directorate }}</td>
                <td><span class="badge {{ status_tone($r->overall_rating) }}">{{ label($r->overall_rating) }}</span></td>
                <td>@if($r->flag_follow_up)<span class="badge {{ $r->follow_up_by?->isPast() ? 'bad' : 'warn' }}">By {{ $r->follow_up_by?->format('j M Y') }}</span>@else <span class="muted small">None</span> @endif</td>
                <td class="right">
                    @if (auth()->user()->isSupervisor())
                        <a class="btn secondary small" href="{{ route('supervisor.inspections.show', $r) }}">Open</a>
                    @else
                        <details><summary class="btn secondary small">Read</summary><div class="small" style="text-align:left;max-width:520px;white-space:pre-line;padding:.5rem 0"><strong>Findings</strong>
{{ $r->findings }}

<strong>Recommendations</strong>
{{ $r->recommendations }}</div></details>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty"><strong>No inspection reports</strong>Reports submitted by EDM, DEM and PEA officers appear here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $reports->links() }}
