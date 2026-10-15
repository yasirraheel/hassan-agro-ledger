@extends('layouts.master')

@section('title')
    {{ __('Language Settings') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card ">
                <div class="card-bodys">
                    <div class="table-header p-2">
                        <h4>{{  __('Language') }}</h4>
                       <a href="#" class="add-order-btn rounded-2" data-bs-toggle="modal" data-bs-target="#addLanguageModal">
                            <i class="fas fa-plus-circle me-1"></i> {{ __('Add Language') }}
                        </a>
                    </div>
                    <div class="table-top-form p-16-0">
                        <form action="{{ route('admin.languages.index') }}" method="GET" class="filter-form" table="#languages-data">

                            <div class="table-top-left d-flex gap-3 margin-l-16">
                                <!-- Per Page Select -->
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="per_page" class="form-control">
                                        <option @selected(request('per_page') == 20) value="20">{{ __('Show 20') }}</option>
                                        <option @selected(request('per_page') == 50) value="50">{{ __('Show 50') }}</option>
                                        <option @selected(request('per_page') == 100) value="100">{{ __('Show 100') }}</option>
                                        <option @selected(request('per_page') == 500) value="500">{{ __('Show 500') }}</option>
                                    </select>
                                    <span></span>
                                </div>

                                <!-- Search -->
                                <div class="table-search position-relative">
                                    <input class="form-control searchInput" type="text" name="search" placeholder="Search..." value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.582 14.582L18.332 18.332" stroke="#4D4D4D" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M16.668 9.16797C16.668 5.02584 13.3101 1.66797 9.16797 1.66797C5.02584 1.66797 1.66797 5.02584 1.66797 9.16797C1.66797 13.3101 5.02584 16.668 9.16797 16.668C13.3101 16.668 16.668 13.3101 16.668 9.16797Z" stroke="#4D4D4D" stroke-width="1.25" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="delete-item delete-show d-none">
                    <div class="delete-item-show">
                        <p class="fw-bold"><span class="selected-count"></span> {{ __('items show') }}</p>
                        <button data-bs-toggle="modal" class="trigger-modal" data-bs-target="#multi-delete-modal" data-url="{{ route('admin.languages.delete-all') }}">{{ __('Delete') }}</button>
                    </div>
                </div>

                <div id="languages-data">
                    @include('admin.languages.datas')
                </div>

            </div>
        </div>
    </div>
    @push('modal')
        @include('admin.components.multi-delete-modal')
        @include('admin.languages.create')
        @include('admin.languages.edit')
    @endpush

@endsection



