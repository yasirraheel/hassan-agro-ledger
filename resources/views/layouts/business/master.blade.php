<!DOCTYPE html>
@if (in_array(app()->getLocale(), ['ar', 'arbh', 'eg-ar', 'fa', 'prs', 'ps', 'ur']))
    <html lang="{{ app()->getLocale() }}" dir="rtl">
    @else
    <html lang="{{ app()->getLocale() }}" dir="auto">
@endif
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="{{__('IE=edge')}}">
    <meta name="viewport" content="{{ __('width=device-width, initial-scale=1.0') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title') @yield('title') | @endif {{ get_option('general')['title'] ?? config('app.name') }}</title>
   

    @include('layouts.business.partials.css')

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

</head>
<body>

<!-- Side Bar Start -->
@include('layouts.business.partials.side-bar')
<!-- Side Bar End -->
<div @class(['section-container', 'active' => Request::routeIs('business.sales.create', 'business.sales.edit', 'business.sales.inventory', 'business.purchases.create', 'business.purchases.edit', 'business.quotations.create', 'business.quotations.edit')])>
    <!-- header start -->
    @include('layouts.business.partials.header')
    <!-- header end -->
    <!-- erp-state-overview-section start -->
    <div class="main-content min-h-100vh">
        @yield('main_content')
    </div>
    <!-- erp-state-overview-section end -->
    <!-- footer start -->
    @include('layouts.business.partials.footer')
    <!-- footer end -->
    @stack('modal')
</div>

@include('layouts.business.partials.script')
</body>
</html>
