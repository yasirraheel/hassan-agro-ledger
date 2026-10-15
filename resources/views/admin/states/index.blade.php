@extends('layouts.master')

@section('title')
    {{ __('State List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('State List') }}</h4>
                        <a type="button" href="#create-state-modal" data-bs-toggle="modal" class="add-order-btn rounded-2 active" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> {{ __('Create State') }}</a>
                    </div>

                    <div class="table-top-form p-16-0">
                        <form action="{{ route('admin.states.index') }}" method="GET" class="filter-form mb-0" table="#table-data">

                            <div class="table-top-left d-flex gap-3 margin-l-16">
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="per_page" class="form-control">
                                        <option @selected(request('per_page') == 20) value="20">{{ __('Show 20') }}</option>
                                        <option @selected(request('per_page') == 50) value="50">{{ __('Show 50') }}</option>
                                        <option @selected(request('per_page') == 100) value="100">{{ __('Show 100') }}</option>
                                        <option @selected(request('per_page') == 500) value="500">{{ __('Show 500') }}</option>
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div id="table-data">
                    @include('admin.states.datas')
                </div>

            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('admin.components.multi-delete-modal')
    @include('admin.states.create')
    @include('admin.states.edit')
@endpush



