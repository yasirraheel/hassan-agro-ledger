@extends('business::layouts.master')

@section('title')
{{ __('Transaction Reports') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">

        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-print-none">
                    <h4>{{ __('Due Collection Transactions') }}</h4>
                </div>

                <div class="table-header justify-content-center border-0 d-none d-block d-print-block  text-center">
                    @include('business::print.header')
                    <h4 class="mt-2">{{ __('Due Collection Transactions') }}</h4>
                </div>

                <div class="table-top-form p-16">
                    <form action="{{ route('business.transaction-history-reports.filter') }}" method="post" class="filter-form" table="#transactions-reports-data">
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
                                <a href="{{ route('business.transaction-history-reports.csv') }}">
                                    <img src="{{ asset('assets/images/logo/csv.svg') }}" alt="">
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('business.transaction-history-reports.excel') }}">
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
                        <th class="text-start">{{ __('Invoice Number') }}</th>
                        <th class="text-start">{{ __('Party Name') }}</th>
                        <th class="text-start">{{ __('Total Due') }}</th>
                        <th class="text-start">{{ __('Pay Due Amount') }}</th>
                        <th class="text-start">{{ __('Payment Type') }}</th>
                        <th class="text-start">{{ __('Payment Date') }}</th>
                    </tr>
                    </thead>
                    <tbody id="transactions-reports-data">
                        @include('business::reports.transaction-history.datas')
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $transcations->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection



