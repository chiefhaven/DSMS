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
            margin: 0;
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
            gap: 1.5rem;
            padding: 2rem 1rem;
        }
        .error-content {
            width: 100%;
            max-width: 32rem;
            padding: clamp(1.5rem, 5vw, 3rem);
            text-align: center;
        }
        .brand { margin: 0 0 1.5rem; font-size: .875rem; font-weight: 600; letter-spacing: .12em; }
        .error-artwork { display: block; width: 100%; max-width: 22rem; height: auto; margin: 0 auto 1rem; }
        .error-code {
            margin: 0;
            color: #0d6efd;
            font-size: clamp(3rem, 12vw, 4rem);
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -.05em;
        }
        h1 { margin: 1rem 0 .75rem; font-size: 1.5rem; font-weight: 600; }
        .message { margin: 0; color: #526175; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin-top: 2rem; }
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
        @media (max-width: 360px) { .actions { flex-direction: column; } }
    </style>
</head>
<body>
    <main class="error-page">
        <section class="error-content" aria-labelledby="error-title">
            <p class="brand">MBIRA DSMS</p>
            @yield('artwork')
            <p class="error-code">@yield('code')</p>
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
