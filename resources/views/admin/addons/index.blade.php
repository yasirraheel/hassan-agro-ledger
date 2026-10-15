@extends('layouts.master')

@section('title')
    {{ __('Addons List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Addons List') }}</h4>
                        <a type="button" href="#addon-modal" data-bs-toggle="modal" class="add-order-btn rounded-2 active" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> {{ __('Install / Update Addon') }}
                        </a>
                    </div>
                </div>

                <div class="responsive-table mt-3">
                    <table class="table" id="datatable">
                        <thead>
                            <tr>
                                <th>{{ __('SL') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Version') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody id="addon-data" class="searchResults">
                            @include('admin.addons.search')
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- Create Modal --}}
<div class="modal modal-md fade" id="addon-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('Install / Update Addon') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.addons.store') }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload">
                    @csrf

                    <div>
                        <label>{{ __('Enter purchase code') }}</label>
                        <input type="text" name="purchase_code" class="form-control" placeholder="{{ __('Enter addon purchase code') }}" required>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Upload addons zip file') }}</label>
                        <div class="row align-items-center">
                            <div class="col-lg-12">
                                <div class="custom-upload-wrapper">
                                    <div class="custom-file-box">
                                        <div class="custom-file-content">
                                            <svg width="30" height="30" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 5V19M5 12H19" stroke="#4B5563" stroke-width="2.0" stroke-linecap="round"/>
                                            </svg>
                                            <span class="custom-upload-text">{{ __('Add File') }}</span>
                                        </div>

                                        <p class="preview-file d-none"></p>
                                        <input type="file" name="file" class="preview-file-input" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="button-group text-center mt-5">
                            <button type="reset" class="theme-btn border-btn m-2">{{ __('Cancel') }}</button>
                            <button class="theme-btn m-2 submit-btn">{{ __('Install') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
