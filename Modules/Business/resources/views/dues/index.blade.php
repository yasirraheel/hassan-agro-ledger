@extends('business::layouts.master')

@section('title')
{{ __('Due List') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">

        <div class="mb-4 d-flex loss-flex  gap-3 loss-profit-container d-print-none">
            <div class="d-flex align-items-center justify-content-center gap-3">
                <div class="profit-card p-3 text-white">
                    <p class="stat-title">{{ __('Supplier Due') }}</p>
                    <p class="stat-value">{{ currency_format($total_supplier_due, 'icon', 2, business_currency()) }}</p>
                </div>

                <div class="loss-card p-3 text-white">
                    <p class="stat-title">{{ __('Customer Due') }}</p>
                    <p class="stat-value">{{ currency_format($total_customer_due, 'icon', 2, business_currency()) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16">
                    <h4>{{ __('Due List') }}</h4>
                </div>
                <div class="table-top-form p-16">
                    <form action="{{ route('business.dues.filter') }}" method="post" class="filter-form" table="#due-reports-data">
                        @csrf
                        <div class="table-top-left d-flex gap-3 ">
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
                </div>
            </div>

            <div class="responsive-table m-0">
                <table class="table" id="datatable">
                    <thead>
                    <tr>
                        <th>{{ __('SL') }}.</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Due Amount') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody id="due-reports-data">
                        @include('business::dues.datas')
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $dues->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection



