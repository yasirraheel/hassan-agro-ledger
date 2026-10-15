@extends('layouts.master')

@section('title')
    {{ __('Edit Country') }}
@endsection

@section('main_content')
    <div class="order-form-section">
        <div class="erp-table-section">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-bodys">
                        <div class="table-header p-16">
                            <h4>{{ __('Edit Country') }}</h4>
                            <a href="{{ route('admin.countries.index') }}" class="add-order-btn rounded-2 {{ Route::is('admin.countries.create') ? 'active' : '' }}"><i class="far fa-list me-1" aria-hidden="true"></i> {{ __('View List') }}</a>
                        </div>

                        <form action="{{ route('admin.countries.update', $country->id) }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload">
                            @csrf
                            @method('put')

                            <div class="row p-16">
                                <div class="col-lg-6 ">
                                    <label>{{ __('Name') }}</label>
                                    <input type="text" name="name" value="{{ $country->name }}" required class="form-control" placeholder="{{ __('Enter Name') }}">
                                </div>

                                <div class="col-lg-6 ">
                                    <label>{{ __('Code') }}</label>
                                    <input type="text" name="code" value="{{ $country->code }}" required class="form-control" placeholder="{{ __('Enter Code') }}">
                                </div>


                                <div class="col-lg-6">
                                    <label class="img-label">{{ __('Image') }}</label>
                                    <div class="custom-upload-wrapper">
                                        <div class="custom-image-box">
                                            <div class="custom-image-content">
                                                <svg width="30" height="30" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M18.3327 7.5026V12.5026C18.3327 14.8596 18.3327 16.0381 17.6004 16.7704C16.8682 17.5026 15.6897 17.5026 13.3327 17.5026H6.66602C4.309 17.5026 3.13048 17.5026 2.39825 16.7704C1.66602 16.0381 1.66602 14.8596 1.66602 12.5026V9.21404C1.66602 8.39729 1.66602 7.98892 1.76053 7.65502C1.99698 6.81974 2.64982 6.1669 3.48509 5.93046C3.819 5.83594 4.22736 5.83594 5.04409 5.83594C5.34907 5.83594 5.50157 5.83594 5.64361 5.81118C5.99556 5.74983 6.31847 5.577 6.56476 5.3182C6.66415 5.21375 6.91352 4.8397 7.08268 4.58594C7.413 4.09048 7.57815 3.84275 7.80393 3.67233C7.94181 3.56826 8.09502 3.48627 8.25809 3.42927C8.52512 3.33594 8.82287 3.33594 9.41837 3.33594H10.8327" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M13.3327 11.2474C13.3327 13.0883 11.8403 14.5807 9.99937 14.5807C8.1584 14.5807 6.66602 13.0883 6.66602 11.2474C6.66602 9.40641 8.1584 7.91406 9.99937 7.91406C11.8403 7.91406 13.3327 9.40641 13.3327 11.2474Z" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M13.334 4.58333H17.5007M15.4173 6.66667V2.5" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span class="custom-upload-text">{{ __('Add Image') }}</span>
                                            </div>

                                            <img class="preview-image d-none" id="image" src="{{ asset($country->image) }}" alt="Preview">
                                            <input type="file" name="image" class="preview-image-input" data-id="#image" accept="image/*">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="button-group text-center mt-5">
                                        <button type="reset" class="theme-btn border-btn m-2">{{ __('Reset') }}</button>
                                        <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
