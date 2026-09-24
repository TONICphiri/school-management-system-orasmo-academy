<div class="table-wrap">
    <table class="table">
        <thead><tr><th>When</th><th>User</th>@if(! empty($showSchool))<th>School</th>@endif<th>Action</th><th>Description</th><th>IP address</th></tr></thead>
        <tbody>
        @forelse ($logs as $log)
            <tr>
                <td class="nowrap num">{{ $log->created_at->format('j M Y, H:i:s') }}</td>
                <td>{{ $log->user?->name ?? 'System' }}@if($log->user)<div class="small muted">{{ $log->user->roleLabel() }}</div>@endif</td>
                @if(! empty($showSchool))<td>{{ $log->school?->name ?? 'National' }}</td>@endif
                <td><span class="badge {{ str_starts_with($log->action, 'security') ? 'bad' : 'neutral' }}">{{ $log->action }}</span></td>
                <td>{{ $log->description }}</td>
                <td class="small muted">{{ $log->ip_address }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No entries recorded.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $logs->links() }}
