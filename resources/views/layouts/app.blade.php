<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Repetitor LMS') — Onlayn o'quv markazi</title>
    <meta name="description" content="@yield('description', 'Repetitor bilan onlayn kurslar va video darslar. Bepul namuna dars, qulay to\'lov va sifatli bilim.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="flex min-h-screen flex-col">
    @unless (View::hasSection('hide_chrome'))
        @include('partials.navbar')
    @endunless

    <main class="flex-1">
        @yield('content')
    </main>

    @unless (View::hasSection('hide_chrome'))
        @include('partials.footer')
    @endunless

    @include('partials.flash')
</body>
</html>
