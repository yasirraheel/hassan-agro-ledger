<!DOCTYPE html>
@if (in_array(app()->getLocale(), ['ar', 'arbh', 'eg-ar', 'fa', 'prs', 'ps', 'ur']))
    <html lang="{{ app()->getLocale() }}" dir="rtl">
    @else
    <html lang="{{ app()->getLocale() }}" dir="auto">
@endif
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="{{__('IE=edge')}}">
    <meta name="viewport" content="{{__('width=device-width, initial-scale=1.0')}}">
    <title>@hasSection('title') @yield('title') | @endif {{ get_option('general')['title'] ?? config('app.name') }}</title>
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
    @include('layouts.business.partials.css')
</head>
<body>
@yield('main_content')

@include('layouts.business.partials.script')

</body>
</html>
