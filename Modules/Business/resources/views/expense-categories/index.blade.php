@extends('business::layouts.master')

@section('title')
    {{ __('Expense Category List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">

                        <div class="table-header p-16">
                            <h4>{{ __('Expense Category List') }}</h4>
                            <a type="button" href="#expense-categories-create-modal" data-bs-toggle="modal"
                                class="add-order-btn rounded-2 {{ Route::is('admin.expense-categories.create') ? 'active' : '' }}"
                                class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add new') }}</a>
                        </div>

                    <div class="table-top-form p-16-0">
                        <form action="{{ route('business.expense-categories.filter') }}" method="post" class="filter-form"
                            table="#expense-categories-data">
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
                                    <input type="text" name="search" class="form-control"
                                        placeholder="{{ __('Search...') }}">
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
                        <button data-bs-toggle="modal" class="trigger-modal" data-bs-target="#multi-delete-modal" data-url="{{ route('business.expense-categories.delete-all') }}">{{ __('Delete') }}</button>
                    </div>
                </div>

                <div class="responsive-table m-0">
                    <table class="table" id="datatable">
                        <thead>
                            <tr>
                                <th class="w-60">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="checkbox" class="select-all-delete multi-delete">
                                    </div>
                                </th>
                                <th>{{ __('SL') }}.</th>
                                <th class="text-start">{{ __('Category Name') }}</th>
                                <th class="text-start">{{ __('Description') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="expense-categories-data">
                            @include('business::expense-categories.datas')
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $expense_categories->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('business::component.delete-modal')
    @include('business::expense-categories.create')
    @include('business::expense-categories.edit')
@endpush
