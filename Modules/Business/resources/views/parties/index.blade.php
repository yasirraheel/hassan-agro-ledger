@extends('business::layouts.master')

@section('title')
    {{ request('type') !== 'Supplier' ? __('Customer List') : __('Supplier List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card ">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ ucfirst(request('type')) . __(' List') }}</h4>
                            <a type="button" href="{{ route('business.parties.create', ['type' => request('type')]) }}"
                               class="add-order-btn rounded-2 {{ Route::is('business.parties.create') ? 'active' : '' }}">
                                <i class="fas fa-plus-circle me-1"></i>{{ __('Add new ') . ucfirst(request('type')) }}
                            </a>
                    </div>


                    <div class="table-top-form p-16-0">
                        <form action="{{ route('business.parties.filter', ['type' => request('type')]) }}" method="post" class="filter-form" table="#parties-data">
                            @csrf

                            <div class="table-top-left d-flex gap-3 margin-l-16">
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="per_page" class="form-control">
                                        <option value="10">{{ __('Show- 10') }}</option>
                                        <option value="25">{{ __('Show- 25') }}</option>
                                        <option value="50">{{ __('Show- 50') }}</option>
                                        <option value="100">{{ __('Show- 100') }}</option>
                                    </select>
                                    <span></span>
                                </div>

                                <div class="table-search position-relative">
                                    <input class="form-control searchInput" type="text" name="search"
                                        placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="delete-item delete-show d-none">
                    <div class="delete-item-show">
                        <p class="fw-bold"><span class="selected-count"></span> {{ __('items show') }}</p>
                        <button data-bs-toggle="modal" class="trigger-modal" data-bs-target="#multi-delete-modal" data-url="{{ route('business.parties.delete-all') }}">{{ __('Delete') }}</button>
                    </div>
                </div>

                <div class="responsive-table m-0">
                    <table class="table" id="datatable">
                        <thead>
                            <tr>
                                <th class="w-60">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="checkbox" class="select-all-delete  multi-delete">
                                    </div>
                                </th>
                                <th> {{ __('SL') }}. </th>
                                <th> {{ __('Image') }} </th>
                                <th> {{ __('Name') }} </th>
                                <th> {{ __('Email') }} </th>
                                <th> {{ __('Type') }} </th>
                                <th> {{ __('Phone') }} </th>
                                <th> {{ __('Due') }} </th>
                                <th> {{ __('Action') }} </th>
                            </tr>
                        </thead>
                        <tbody id="parties-data">
                            @include('business::parties.datas')
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $parties->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('business::component.delete-modal')
    @include('business::parties.view')
@endpush

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
