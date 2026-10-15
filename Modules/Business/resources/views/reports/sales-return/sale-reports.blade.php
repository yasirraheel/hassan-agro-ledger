@extends('business::layouts.master')

@section('title')
    {{ __('Sale Return Reports') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="mb-4 d-flex loss-flex  gap-3 loss-profit-container d-print-none">
            <div class="d-flex align-items-center justify-content-center gap-3">
                <div class="profit-card p-3 text-white">
                    <p class="stat-title">{{ __('Total Sale Return') }}</p>
                    <p class="stat-value">{{ currency_format($total_return, 'icon', 2, business_currency()) }}</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-print-none">
                    <h4>{{ __('Sale Return Report List') }}</h4>
                </div>

                <div class="table-header justify-content-center border-0 d-none d-block d-print-block  text-center">
                    @include('business::print.header')
                    <h4 class="mt-2">{{ __('Sale Return Report List') }}</h4>
                </div>

                <div class="table-top-form p-16">
                    <form action="{{ route('business.sale-return-reports.filter') }}" method="post" class="filter-form" table="#sale-return-reports-data">
                        @csrf
                        <div class="table-top-left d-flex gap-3 d-print-none">
                            <div class="gpt-up-down-arrow position-relative">
                                <select name="per_page" class="form-control">
                                    <option value="10">{{__('Show- 10')}}</option>
                                    <option value="25">{{__('Show- 25')}}</option>
                                    <option value="50">{{__('Show- 50')}}</option>
                                    <option value="100">{{__('Show- 100')}}</option>
                                </select>
                                <span></span>
                            </div>
                            <div class="table-search position-relative">
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
                                        <a href="{{ route('business.sales-return-report.csv') }}">
                                            <img src="{{ asset('assets/images/logo/csv.svg') }}" alt="">

                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ route('business.sales-return-report.excel') }}">
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
                            <th>{{ __('Invoice No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Paid') }}</th>
                            <th>{{ __('Return Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody id="sale-return-reports-data">
                        @include('business::reports.sales-return.datas')
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $sales->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
