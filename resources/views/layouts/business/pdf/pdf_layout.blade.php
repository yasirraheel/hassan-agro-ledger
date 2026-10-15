<!DOCTYPE html>
<html lang="en" dir="{{ in_array(app()->getLocale(), ['ar', 'arbh', 'eg-ar', 'fa', 'prs', 'ps', 'ur']) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Dynamic theme style -->
    <style id="theme-style"></style>
    <title>{{ __('Pdf Print') }}</title>
    @stack('css')
</head>

<body style="padding: 0; margin: 0; box-sizing: border-box;">
    <div style="padding: 0; margin: 0;" class="table-header">
        <h4>@yield('pdf_title')</h4>
    </div>
    <div class="" style="padding: 0; margin: 0;">
        @yield('pdf_content')
    </div>

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
    <script src="{{ asset('assets/js/theme.js') }}?v={{ time() }}"></script>
</body>

</html>
