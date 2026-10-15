@extends('layouts.master')

@section('title')
    {{ __('Create Blog') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Create Blog') }}</h4>
                        <a href="{{ route('admin.blogs.index') }}" class="theme-btn print-btn text-light">
                            <i class="fas fa-list me-1"></i>
                            {{ __('Blog List') }}
                        </a>
                    </div>

                    <div class="tab-content order-summary-tab p-16 mt-0">
                        <div class="tab-pane fade mt-1 show active" id="add-new-user">
                            <div class="order-form-section">
                                <form action="{{ route('admin.blogs.store') }}" method="post" enctype="multipart/form-data"
                                    class="ajaxform_instant_reload">
                                    @csrf

                                    <div class="add-suplier-modal-wrapper">
                                        <div class="row">
                                            <div class="col-lg-12 mt-2">
                                                <label>{{ __('Title') }}</label>
                                                <input type="text" name="title" required class="form-control" placeholder="{{ __('Enter Title') }}">
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{ __('Status') }}</label>
                                                <div class="gpt-up-down-arrow position-relative">
                                                    <select name="status" required=""
                                                        class="form-control select-dropdown">
                                                        <option value="">{{ __('Select a status') }}</option>
                                                        <option value="1">{{ __('Active') }}</option>
                                                        <option value="0">{{ __('Deactive') }}</option>
                                                    </select>
                                                    <span></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-2">
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

                                                        <!-- Preview image -->
                                                        <img class="preview-image d-none" id="image" src="" alt="Preview">
                                                        <input type="file" name="image" class="preview-image-input" data-id="#image" accept="image/*">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mt-2">
                                                <label>{{ __('Description') }}</label>
                                                <textarea type="text" name="descriptions" class="form-control summernote-blogs" placeholder="{{ __('Enter Description') }}"></textarea>
                                            </div>

                                            <div class="col-12 mb-2">
                                                <div class="manual-rows" id="dynamic-input-fields">
                                                    <div class="row single-tags">
                                                        <div class="col-md-6">
                                                            <div class="row row-items">
                                                                <div class="col-sm-10">
                                                                    <label for="">{{ __('Tags') }}</label>
                                                                    <input type="text" name="tags[]" class="form-control" required
                                                                        placeholder="{{ __('Enter tags name') }}">
                                                                </div>
                                                                <div class="col-sm-2 align-self-center mt-3">
                                                                    <button type="button" class="btn text-danger trash remove-btn-features"
                                                                        onclick="removeDynamicField(this)"><i
                                                                            class="fas fa-trash"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12 mt-2">
                                                        <a href="javascript:void(0)" class="fw-bold text-primary add-new-tag" onclick="addDynamicField()"><i class="fas fa-plus-circle"></i>{{ __('Add new row') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="mt-5">{{ __('SEO Meta Tags') }}</h6>
                                            <div class="col-lg-12 mt-2">
                                                <label>{{ __('Meta Title') }}</label>
                                                <input type="text" name="meta[title]" class="form-control" placeholder="{{ __('Enter Title') }}">
                                            </div>
                                            <div class="col-lg-12 mt-2">
                                                <label>{{ __('Meta Description') }}</label>
                                                <textarea type="text" name="meta[description]" class="form-control" placeholder="{{ __('Enter meta Description') }}" ></textarea>
                                            </div>

                                            <div class="col-lg-12">
                                                <div class="button-group text-center mt-5">
                                                    <a href=""
                                                        class="theme-btn border-btn m-2">{{ __('Cancel') }}</a>
                                                    <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
    <script src="{{ asset('assets/js/summernote-lite.js') }}"></script>
@endpush

