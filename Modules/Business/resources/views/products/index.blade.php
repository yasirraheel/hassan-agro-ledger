@extends('business::layouts.master')

@section('title')
    {{ __('Product List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card ">
                <div class="card-bodys">
                    <div class="table-header p-16 d-print-none">
                        <h4>{{ __('Product List') }}</h4>
                            <a type="button" href="{{ route('business.products.create') }}"
                                class="add-order-btn rounded-2 {{ Route::is('business.products.create') ? 'active' : '' }}"
                                class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add new Product') }}</a>
                    </div>

                    <div class="table-header justify-content-center border-0 text-center d-none d-block d-print-block">
                        @include('business::print.header')
                        <h4 class="mt-2">{{ __('Product List') }}</h4>
                    </div>

                    <div class="table-top-form p-16">
                        <form action="{{ route('business.products.filter') }}" method="post" class="filter-form"
                            table="#product-data">
                            @csrf

                            <div class="table-top-left d-flex gap-3">
                                <div class="gpt-up-down-arrow position-relative d-print-none">
                                    <select name="per_page" class="form-control">
                                        <option value="10">{{ __('Show- 10') }}</option>
                                        <option value="25">{{ __('Show- 25') }}</option>
                                        <option value="50">{{ __('Show- 50') }}</option>
                                        <option value="100">{{ __('Show- 100') }}</option>
                                    </select>
                                    <span></span>
                                </div>

                                <div class="table-search position-relative d-print-none">
                                    <input class="form-control searchInput" type="text" name="search"
                                        placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                    </span>
                                </div>
                            </div>
                        </form>

                        <div class="table-top-btn-group d-print-none">
                            <ul>
                                <li>
                                    <a href="{{ route('business.products.csv') }}">
                                        <img src="{{ asset('assets/images/logo/csv.svg') }}" alt="">

                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('business.products.excel') }}">
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

                <div class="delete-item delete-show d-none">
                    <div class="delete-item-show">
                        <p class="fw-bold"><span class="selected-count"></span> {{ __('items show') }}</p>
                        <button data-bs-toggle="modal" class="trigger-modal" data-bs-target="#multi-delete-modal" data-url="{{ route('business.products.delete-all') }}">{{ __('Delete') }}</button>
                    </div>
                </div>

                <div class="responsive-table m-0">
                    <table class="table" id="datatable">
                        <thead>
                            <tr>
                                    <th class="w-60 d-print-none">
                                        <div class="d-flex align-items-center gap-3">
                                            <input type="checkbox" class="select-all-delete multi-delete">
                                        </div>
                                    </th>
                                <th> {{ __('SL') }}. </th>
                                <th> {{ __('Image') }} </th>
                                <th> {{ __('Product Name') }} </th>
                                <th> {{ __('Code') }} </th>
                                <th> {{ __('Brand') }} </th>
                                <th> {{ __('Category') }} </th>
                                <th> {{ __('Unit') }} </th>
                                <th> {{ __('Purchase price') }}</th>
                                <th> {{ __('Sale price') }}</th>
                                <th> {{ __('Stock') }}</th>
                                <th> {{ __('Action') }} </th>
                            </tr>
                        </thead>
                        <tbody id="product-data">
                            @include('business::products.datas')
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $products->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('business::component.delete-modal')
    @include('business::products.view')
@endpush

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
