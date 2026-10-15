@extends('business::layouts.master')

@section('title')
    {{ __('Sale Reports') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="mb-4 d-flex loss-flex  gap-3 loss-profit-container d-print-none">
            <div class="d-flex align-items-center justify-content-center gap-3">
                <div class="profit-card p-3 text-white">
                    <p class="stat-title">{{ __('Total Sale') }}</p>
                    <p class="stat-value">{{ currency_format($total_sale, 'icon', 2, business_currency()) }}</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-print-none">
                    <h4>{{ __('Sales Report List') }}</h4>
                </div>

                <div class="table-header justify-content-center border-0 d-none d-block d-print-block  text-center">
                    @include('business::print.header')
                    <h4 class="mt-2">{{ __('Sales Report List') }}</h4>
                </div>
                <div class="table-top-form p-16">

                    <div class="loss-profit-form-container d-print-none">

                    <form action="{{ route('business.sale-reports.filter') }}" method="post" class="filter-form" table="#sale-report-data">
                        @csrf
                        <div class="table-top-left d-flex gap-3">
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

                    <div class="m-0 p-0 d-print-none">
                            <form action="{{ route('business.sale-reports.filter') }}" method="post" class="filter-form"
                                table="#sale-report-data">
                                @csrf
                                <div class="grid-5">
                                    <div class="input-wrapper align-items-center ">
                                        <label class="header-label">{{ __('From Date') }}</label>
                                        <input type="date" name="from_date"
                                            value="{{ \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}"
                                            class="form-control">
                                    </div>
                                    <div class="input-wrapper align-items-center ">
                                        <label class="header-label">{{ __('To Date') }}</label>
                                        <input type="date" name="to_date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}"
                                            class="form-control">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                        <div class="table-top-btn-group d-print-none">
                            <ul>
                                <li>
                                    <a href="{{ route('business.sales.reports.csv') }}">
                                        <img src="{{ asset('assets/images/logo/csv.svg') }}" alt="">

                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('business.sales.reports.excel') }}">
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
                        <th class="text-start">{{ __('Invoice No') }}</th>
                        <th class="text-start">{{ __('Party Name') }}</th>
                        <th class="text-start">{{ __('Total Amount') }}</th>
                        <th class="text-start">{{ __('Discount Amount') }}</th>
                        <th class="text-start">{{ __('Paid Amount') }}</th>
                        <th class="text-start">{{ __('Due Amount') }}</th>
                        <th class="text-start">{{ __('Payment Type') }}</th>
                        <th class="text-start">{{ __('Sale Date') }}</th>
                    </tr>
                    </thead>
                    <tbody id="sale-report-data">
                        @include('business::reports.sales.datas')
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $sales->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
