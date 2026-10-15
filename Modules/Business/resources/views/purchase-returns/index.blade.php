@extends('business::layouts.master')

@section('title')
{{ __('Purchase Return List') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16">
                    <h4>{{ __('Purchase Return List') }}</h4>
                </div>

                <div class="table-top-form p-16-0">
                    <form action="{{ route('business.purchase-returns.filter') }}" method="post" class="filter-form" table="#purchase-return-data">
                        @csrf
                        <div class="table-top-left d-flex gap-3 margin-l-16">
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
                        <th>{{ __('Invoice No') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Paid') }}</th>
                        <th>{{ __('Return Amount') }}</th>
                    </tr>
                    </thead>
                    <tbody id="purchase-return-data">
                        @include('business::purchase-returns.datas')
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $purchases->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection



