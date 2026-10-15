<!DOCTYPE html>
@if (app()->getLocale() == 'ar')
<html lang="ar" dir="rtl">
@else
<html lang="en" dir="auto">
@endif
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="{{__('IE=edge')}}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="{{__('width=device-width, initial-scale=1.0')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title') @yield('title') | @endif {{ get_option('general')['title'] ?? config('app.name') }}</title>

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
    
    @include('layouts.partials.css')
</head>
<body>

<!-- Side Bar Start -->
@include('layouts.partials.side-bar')
<!-- Side Bar End -->
<div class="section-container">
    <!-- header start -->
    @include('layouts.partials.header')
    <!-- header end -->
    <!-- erp-state-overview-section start -->
    <div class="min-h-100vh">
        @yield('main_content')
    </div>
    @include('layouts.partials.footer')
    <!-- erp-state-overview-section end -->
    @stack('modal')
</div>

@include('layouts.partials.script')
</body>
</html>
