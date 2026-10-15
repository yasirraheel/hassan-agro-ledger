@extends('business::layouts.master')

@section('title')
{{ __('Subscription Reports') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">

        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-print-none">
                    <h4>{{ __('Subscription Reports') }}</h4>
                </div>

                <div class="table-header justify-content-center border-0 d-none d-block d-print-block  text-center">
                    @include('business::print.header')
                    <h4 class="mt-2">{{ __('Subscription Reports') }}</h4>
                </div>

                <div class="table-top-form p-16">
                    <form action="{{ route('business.subscription-reports.filter') }}" method="post" class="filter-form" table="#subscription-report-data">
                        @csrf
                        <div class="table-top-left d-flex gap-3 ">
                            <div class="gpt-up-down-arrow position-relative d-print-none">
                                <select name="per_page" class="form-control">
                                    <option value="10">{{__('Show- 10')}}</option>
                                    <option value="25">{{__('Show- 25')}}</option>
                                    <option value="50">{{__('Show- 50')}}</option>
                                    <option value="100">{{__('Show- 100')}}</option>
                                </select>
                                <span></span>
                            </div>
                            <div class="table-search position-relative d-print-none">
                                <input type="text" name="search" class="form-control" placeholder="{{ __('Search...') }}">
                                <span class="position-absolute">
                                    <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                </span>
                            </div>
                        </div>
                    </form>

                    <div class="table-top-btn-group d-print-none">
                        <ul>

                            <li>
                                <a href="{{ route('business.subscription-reports.csv') }}">
                                    <img src="{{ asset('assets/images/logo/csv.svg') }}" alt="">
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('business.subscription-reports.excel') }}">
                                    <img src="{{ asset('assets/images/logo/excel.svg') }}" alt="">
                                </a>
                            </li>

                            <li>
                                <a onclick="window.print()" class="print-window">
                                    <img src="{{ asset('assets/images/logo/printer.svg') }}" alt="">
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            <div class="responsive-table m-0">
                <table class="table" id="datatable">
                    <thead>
                    <tr>
                        <th>{{ __('SL') }}.</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Started') }}</th>
                        <th>{{ __('End') }}</th>
                        <th>{{ __('Gateway Method') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody id="subscription-report-data">
                        @include('business::reports.subscription-reports.datas')
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $subscribers->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection



