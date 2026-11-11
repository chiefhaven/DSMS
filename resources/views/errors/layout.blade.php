<!doctype html>
<html lang="{{ str_replace('_', '-', config('app.locale', 'en')) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') | @yield('title') - DSMS</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0 !important;
            color: #243247;
            background: #f4f6fa;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            line-height: 1.5;
        }
        .error-page {
            min-height: 100vh;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 1rem;
        }
        .error-content {
            width: 100%;
            max-width: 32rem;
            text-align: center;
        }
        .brand { margin: 0 0 .75rem; font-size: .875rem; font-weight: 600; letter-spacing: .12em; }
        .error-artwork { display: block; width: min(100%, 18rem); height: auto; max-height: 25svh; margin: 0 auto .5rem; }
        .artwork-car { animation: car-arrive 1.5s ease-out both; }
        .artwork-route { animation: route-trace 4s linear both; }
        .artwork-tool {
            transform-box: fill-box;
            transform-origin: center;
            animation: tool-turn 1s ease-in-out 4;
        }
        @keyframes car-arrive {
            from { transform: translateX(-12px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes route-trace {
            from { stroke-dashoffset: 52; }
            to { stroke-dashoffset: 0; }
        }
        @keyframes tool-turn {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(-12deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            .artwork-car, .artwork-route, .artwork-tool { animation: none; }
        }
        h1 { margin: .5rem 0; font-size: 1.375rem; font-weight: 600; }
        .message { margin: 0; color: #526175; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin-top: 1rem; }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: .625rem 1.5rem;
            border: 1px solid #0d6efd;
            border-radius: 50rem;
            color: #fff;
            background: #0d6efd;
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
        }
        .button:hover { background: #0b5ed7; border-color: #0b5ed7; }
        .button-secondary { color: #243247; background: #f4f6fa; border-color: #e6eaf0; }
        .button-secondary:hover { background: #e6eaf0; border-color: #d8dee8; }
        a:focus-visible { outline: 3px solid #0d6efd; outline-offset: 4px; }
        footer { color: #526175; text-align: center; font-size: .8125rem; }
        footer a { color: #40516a; text-underline-offset: .2em; }
        @media (max-height: 500px) {
            .error-page { gap: .5rem; padding: .5rem 1rem; }
            .brand { margin-bottom: .25rem; }
            .error-artwork { max-height: 18svh; margin-bottom: .25rem; }
            h1 { margin: .25rem 0; }
            .actions { margin-top: .5rem; }
        }
    </style>
</head>
<body>
    <main class="error-page">
        <section class="error-content" aria-labelledby="error-title">
            <p class="brand">MBIRA DSMS</p>
            @yield('artwork')
            <h1 id="error-title">@yield('title')</h1>
            <p class="message">@yield('message')</p>
            <nav class="actions" aria-label="Recovery options">
                <a class="button" href="{{ url('/') }}">Go home</a>
                <a class="button button-secondary" href="{{ url('/login') }}">Sign in</a>
            </nav>
        </section>
        <footer>
            Supported by <a href="https://www.havenplustechnologies.co.mw/">HavenPlus Technologies</a>
        </footer>
    </main>
</body>
</html>
