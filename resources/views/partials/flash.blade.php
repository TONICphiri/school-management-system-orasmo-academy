@if (session('status'))
    <div class="alert success" role="status">{{ session('status') }}</div>
@endif
@if (session('dev_code'))
    <div class="alert warn">
        <strong>Test mode code:</strong> <span class="dev-code">{{ session('dev_code') }}</span>
        <div class="small muted">Shown on screen because SHOW_CODES_ON_SCREEN is on in the .env file. Turn it off on a live server so codes only go by SMS or email.</div>
    </div>
@endif
@if ($errors->any())
    <div class="alert error" role="alert">
        <strong>Please check the following:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
