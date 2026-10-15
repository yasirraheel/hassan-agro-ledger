<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @hasSection('title')
            @yield('title') |
        @endif
        {{ config('app.name') }}
    </title>

    <!-- Dynamic theme style -->
    @if(isset($theme))
    <style>
        :root {
            --dynamic-primary: {{ $theme['primary_color'] ?? '#c52127' }};
            --dynamic-secondary: {{ $theme['secondary_color'] ?? '#0071bc' }};
            --dynamic-sidebar: {{ $theme['sidebar_color'] ?? '#201415' }};
            --dynamic-accent: {{ $theme['accent_color'] ?? '#fef0f1' }};
        }
    </style>
    @endif
    
    <script src="{{ asset('assets/js/theme-color.js') }}?v={{ time() }}"></script>
    @include('layouts.partials.css')
</head>

<body>
    @yield('main_content')
    @stack('modal')

    @include('layouts.auth.partials.scripts')
</body>
</html>
