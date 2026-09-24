<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | MoEST School Management System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
</head>
<body>
<div class="guest">
    <section class="guest-aside">
        <div>
            <div class="brand" style="padding:0;border:0">
                <div class="brand-mark">MW</div>
                <div class="brand-text"><strong>Ministry of Education</strong><span>Republic of Malawi</span></div>
            </div>
            <h1>School Management System</h1>
            <p>One record for every learner, teacher and school. Continuous assessment, results approval, MANEB registration and supervision from the zone to the Ministry.</p>
        </div>
        <div>
            <div class="facts">
                <div><strong>6</strong><span>Education divisions</span></div>
                <div><strong>40 / 60</strong><span>Assessment and examination weighting</span></div>
                <div><strong>PSLCE, JCE, MSCE</strong><span>MANEB registers</span></div>
            </div>
            <div class="flag-bar" style="margin-top:1.5rem"><span style="background:#111"></span><span style="background:#a8231c"></span><span style="background:#2d7a3e"></span></div>
        </div>
    </section>
    <section class="guest-main">
        <div style="max-width:400px;width:100%;margin:0 auto">
            @include('partials.flash')
            @yield('content')
            <p class="small muted" style="margin-top:2rem">Use of this system is monitored. Every action is recorded in the audit log in line with the Ministry data protection and child safeguarding policies.</p>
        </div>
    </section>
</div>
<script src="{{ asset('js/app.js') }}?v=3"></script>
</body>
</html>
