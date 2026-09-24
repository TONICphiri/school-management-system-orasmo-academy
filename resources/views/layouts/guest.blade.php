<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | MoEST School Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="grid min-h-screen grid-cols-[minmax(0,1fr)_520px] max-lg:grid-cols-1">
    <section class="flex flex-col justify-between bg-brand-dark p-12 text-side-text max-lg:hidden">
        <div>
            <div class="flex items-center gap-3">
                <div class="grid size-[38px] flex-none place-items-center border-t-[5px] border-b-[5px] border-t-flag-black border-b-flag-red bg-white text-[.8rem] font-bold tracking-[.04em] text-brand-dark">MW</div>
                <div>
                    <strong class="block text-[.95rem] text-white">Ministry of Education</strong>
                    <span class="text-[.75rem] text-side-muted">Republic of Malawi</span>
                </div>
            </div>
            <h1 class="mt-6 mb-4 max-w-[520px] text-[2rem] text-white">School Management System</h1>
            <p class="max-w-[520px] text-[#b9ccc0]">One record for every learner, teacher and school. Continuous assessment, results approval, MANEB registration and supervision from the zone to the Ministry.</p>
        </div>
        <div>
            <div class="grid max-w-[560px] grid-cols-3 gap-px border border-white/10 bg-white/10 [&>div]:bg-brand-dark [&>div]:p-4 [&_strong]:block [&_strong]:text-[1.1rem] [&_strong]:text-white [&_span]:text-[.8rem] [&_span]:text-side-muted">
                <div><strong>6</strong><span>Education divisions</span></div>
                <div><strong>40 / 60</strong><span>Assessment and examination weighting</span></div>
                <div><strong>PSLCE, JCE, MSCE</strong><span>MANEB registers</span></div>
            </div>
            <div class="mt-6 flex h-1.5 w-[120px]"><span class="flex-1 bg-flag-black"></span><span class="flex-1 bg-flag-red"></span><span class="flex-1 bg-flag-green"></span></div>
        </div>
    </section>
    <section class="guest-form flex flex-col justify-center border-l border-line bg-white p-12 max-md:p-6">
        <div class="mx-auto w-full max-w-[400px]">
            @include('partials.flash')
            @yield('content')
            <p class="mt-8 text-[.85rem] text-muted">Use of this system is monitored. Every action is recorded in the audit log in line with the Ministry data protection and child safeguarding policies.</p>
        </div>
    </section>
</div>
</body>
</html>
