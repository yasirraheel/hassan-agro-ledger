@extends('business::layouts.master')

@section('title')
    {{ __('Currency') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys ">
                    <div class="table-top-form p-16-0">
                        <form action="{{ route('business.currencies.filter') }}" method="post" class="filter-form" table="#currencies-data">
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
                                    <input class="form-control searchInput" type="text" name="search" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="responsive-table m-0">
                    <table class="table" id="erp-table">
                        <thead>
                            <tr>
                                <th>{{ __('SL') }}.</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Country Name') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Symbol') }}</th>
                                <th>{{ __('Default') }}</th>
                                <th class="print-d-none">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="currencies-data">
                            @include('business::currencies.datas')
                        </tbody>
                    </table>
                </div>
                <div>
                    {{ $currencies->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

