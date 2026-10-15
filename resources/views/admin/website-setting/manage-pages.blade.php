@extends('layouts.master')

@section('title')
    {{ __('CMS Manage') }}
@endsection

@section('main_content')
    <div class="erp-table-section system-settings">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">


            <div class="table-header p-16">
                <div class="card-bodys">
                    <h4>🚩 {{ __('Page for Updating Website Sections') }}</h4>
                </div>
            </div>
            <div class="tab-content p-16">
                <div class="tab-pane fade active show" id="add-new-petty" role="tabpanel">
                    <div class="table-header border-0">
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-sm-12">
                            <div class="order-form-section">
                                <div class="row">
                                    <div class="col-12 col-sm-12 col-md-4 mb-3">
                                        <div class="cards-header shadow">
                                            <div class="card-body">
                                                <ul class="nav nav-pills flex-column w-280">
                                                    <li class="nav-item">
                                                        <a href="#slider" id="home-tab4"
                                                            class="add-report-btn nav-link active"
                                                            data-bs-toggle="tab">{{ __('Slider Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#header" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Header Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#feature" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Feature Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#interface" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Interface Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#watch" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Watch Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#testimonial" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Testimonial Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#blog" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Blog Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#about_us" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('About us Page') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#contact_us" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Contuct us Page') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#pricing" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Pricing Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#footer" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">{{ __('Footer Section') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#socials" class="add-report-btn nav-link"
                                                            data-bs-toggle="tab">
                                                            {{ __('Social Medias') }}
                                                        </a>
                                                    </li>

                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-12 col-md-8">
                                        <div class="cards-header shadow">
                                            <div class="card-body">
                                                <form
                                                    action="{{ route('admin.website-settings.update', 'manage-pages') }}"
                                                    method="post" class="ajaxform">
                                                    @csrf
                                                    @method('PUT')

                                                    <div class="tab-content no-padding">
                                                        {{-- Slider Section Start --}}
                                                        <div class="tab-pane fade show active" id="slider">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <label>{{ __('Title') }}</label>
                                                                    <input type="text" name="slider_title"
                                                                        value="{{ $page_data['headings']['slider_title'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Title') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-12 col-md-6">
                                                                    <label>{{ __('Button One') }}</label>
                                                                    <input type="text" name="slider_btn1"
                                                                        value="{{ $page_data['headings']['slider_btn1'] ?? '' }}"
                                                                        placeholder="{{ __('Button Text') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-12 col-md-6">
                                                                    <label>{{ __('Button One Link') }}</label>
                                                                    <input type="text" name="slider_btn1_link"
                                                                        value="{{ $page_data['headings']['slider_btn1_link'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Link') }}" class="form-control">
                                                                </div>

                                                                <div class="col-12 col-md-6">
                                                                    <label>{{ __('Button Two') }}</label>
                                                                    <input type="text" name="slider_btn2"
                                                                        value="{{ $page_data['headings']['slider_btn2'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Text') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-12 col-md-6">
                                                                    <label>{{ __('Button Two Link') }}</label>
                                                                    <input type="text" name="slider_btn2_link"
                                                                        value="{{ $page_data['headings']['slider_btn2_link'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Link') }}" class="form-control">
                                                                    <span
                                                                        class="text-danger">{{ __('Note: Enter embedded video link') }}</span>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label>{{ __('Description') }}</label>
                                                                    <textarea name="slider_description" placeholder="{{ __('Enter Description') }}" class="form-control">{{ $page_data['headings']['slider_description'] ?? '' }}</textarea>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label>{{ __('Scanner Text') }}</label>
                                                                    <input type="text" name="slider_scanner_text"
                                                                        value="{{ $page_data['headings']['slider_scanner_text'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Title') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="img-label">{{ __('Scanner Image') }}</label>
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
                                                                            <img class="preview-image d-none" id="scanner_image" src="{{ Storage::url($page_data['scanner_image'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="scanner_image" class="preview-image-input" data-id="#scanner_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="img-label">{{ __('Slider Image') }}</label>
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
                                                                            <img class="preview-image d-none" id="slider_image" src="{{ Storage::url($page_data['slider_image'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="slider_image" class="preview-image-input" data-id="#slider_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="img-label">{{ __('Background Image') }}</label>
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
                                                                            <img class="preview-image d-none" id="slider_bg_img" src="{{ Storage::url($page_data['slider_bg_img'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="slider_bg_img" class="preview-image-input" data-id="#slider_bg_img" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-12">
                                                                    <h4 class="mb-3 mt-2">{{ __('Shop') }}</h4>
                                                                    <button
                                                                        class="w-100 py-3 d-block text-center fw-bold mt-3 admin-collapse bg-light primary border-0"
                                                                        type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#slider_shop"
                                                                        aria-expanded="false" aria-controls="slider_shop">
                                                                        {{ __('Shop') }} <i
                                                                            class="fas fa-arrow-circle-down ms-2"></i>
                                                                    </button>
                                                                    <div class="collapse mt-3" id="slider_shop">
                                                                        @foreach ($page_data['headings']['silder_shop_text'] ?? [] as $key => $silder_shop_text)
                                                                            <div
                                                                                class="sample-form-wrp duplicate-feature pe-3">
                                                                                <div class="row mb-4">
                                                                                    <div class="col-lg-12">
                                                                                        <label>{{ __('Shop') }}-
                                                                                            {{ $key + 1 }} </label>
                                                                                        <input type="text"
                                                                                            name="silder_shop_text[]"
                                                                                            value="{{ $silder_shop_text ?? '' }}"
                                                                                            required class="form-control"
                                                                                            placeholder="{{ __('Enter text') }}">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        {{-- Slider section End --}}

                                                        {{-- Header section start --}}
                                                        <div class="tab-pane fade" id="header">
                                                            <div class="form-group">
                                                                <label>{{ __('Header Button Text') }}</label>
                                                                <input type="text" name="header_btn_text"
                                                                    value="{{ $page_data['headings']['header_btn_text'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Title') }}" required
                                                                    class="form-control">
                                                            </div>

                                                            <div class="form-group">
                                                                <label>{{ __('Header Button Link') }}</label>
                                                                <input type="text" name="header_btn_link"
                                                                    value="{{ $page_data['headings']['header_btn_link'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Header link') }}" class="form-control">
                                                            </div>
                                                        </div>
                                                        {{-- Header section End --}}

                                                        {{-- Feature section start --}}
                                                        <div class="tab-pane fade" id="feature">
                                                            <div class="form-group">
                                                                <label>{{ __('Section Title') }}</label>
                                                                <input type="text" name="feature_title"
                                                                    value="{{ $page_data['headings']['feature_title'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Section Title') }}" required
                                                                    class="form-control">
                                                            </div>
                                                        </div>
                                                        {{-- Feature section End --}}

                                                        {{-- Interface section start --}}
                                                        <div class="tab-pane fade" id="interface">
                                                            <div class="form-group">
                                                                <label>{{ __('Interface Title') }}</label>
                                                                <input type="text" name="interface_title"
                                                                    value="{{ $page_data['headings']['interface_title'] ?? '' }}"
                                                                    required class="form-control"
                                                                    placeholder="{{ __('Enter Title') }}">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('Description') }}</label>
                                                                <textarea name="interface_description" class="form-control" placeholder="{{ __('Enter Description') }}">{{ $page_data['headings']['interface_description'] ?? '' }}</textarea>
                                                            </div>
                                                        </div>
                                                        {{-- Interface section End --}}

                                                        {{-- Watch section start --}}
                                                        <div class="tab-pane fade" id="watch">
                                                            <div class="form-group">
                                                                <label>{{ __('Watch Title') }}</label>
                                                                <input type="text" name="watch_title"
                                                                    value="{{ $page_data['headings']['watch_title'] ?? '' }}"
                                                                    required class="form-control"
                                                                    placeholder="{{ __('Enter Title') }}">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('Button Link') }}</label>
                                                                <input type="text" name="watch_btn_link"
                                                                    value="{{ $page_data['headings']['watch_btn_link'] ?? '' }}"
                                                                    required class="form-control"
                                                                    placeholder="{{ __('Enter Link') }}">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('Description') }}</label>
                                                                <textarea name="watch_description" class="form-control" placeholder="{{ __('Enter Description') }}">{{ $page_data['headings']['watch_description'] ?? '' }}</textarea>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <label class="img-label">{{ __('Watch Image') }}</label>
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
                                                                            <img class="preview-image d-none" id="watch_image" src="{{ Storage::url($page_data['watch_image'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="watch_image" class="preview-image-input" data-id="#watch_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        {{-- Watch section End --}}

                                                        {{-- Blog section start --}}
                                                        <div class="tab-pane fade" id="blog">
                                                            <div class="form-group">
                                                                <label>{{ __('Blog Title') }}</label>
                                                                <input type="text" name="blog_title"
                                                                    value="{{ $page_data['headings']['blog_title'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Title') }}" required
                                                                    class="form-control">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('Read More Button Text') }}</label>
                                                                <input type="text" name="blog_btn_text"
                                                                    value="{{ $page_data['headings']['blog_btn_text'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Title') }}" required
                                                                    class="form-control">
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-lg-6">
                                                                    <label>{{ __('View All Button Text') }}</label>
                                                                    <input type="text" name="blog_view_all_btn_text"
                                                                        value="{{ $page_data['headings']['blog_view_all_btn_text'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Text') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-lg-6">
                                                                    <label>{{ __('View All Link') }}</label>
                                                                    <input type="text" name="blog_view_all_btn_link"
                                                                        value="{{ $page_data['headings']['blog_view_all_btn_link'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Link') }}" required
                                                                        class="form-control">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        {{-- Blog section End --}}

                                                        {{-- Testimonial section start --}}
                                                        <div class="tab-pane fade" id="testimonial">
                                                            <div class="form-group">
                                                                <label>{{ __('Testimonial Title') }}</label>
                                                                <input type="text" name="testimonial_title"
                                                                    value="{{ $page_data['headings']['testimonial_title'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Title') }}" required
                                                                    class="form-control">
                                                            </div>
                                                        </div>
                                                        {{-- Testimonial section End --}}

                                                        {{-- About us section start --}}
                                                        <div class="tab-pane fade" id="about_us">
                                                            <div class="form-group">
                                                                <label>{{ __('Title') }}</label>
                                                                <input type="text" name="about_short_title"
                                                                    value="{{ $page_data['headings']['about_short_title'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Short Title') }}" required
                                                                    class="form-control">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('Long Title') }}</label>
                                                                <input type="text" name="about_title"
                                                                    value="{{ $page_data['headings']['about_title'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Title') }}" required
                                                                    class="form-control">
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <label class="img-label">{{ __('About Image') }}</label>
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
                                                                            <img class="preview-image d-none" id="about_image" src="{{ Storage::url($page_data['about_image'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="about_image" class="preview-image-input" data-id="#about_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('Description One') }}</label>
                                                                <textarea name="about_desc_one" class="form-control" placeholder="{{ __('Enter Description') }}">{{ $page_data['headings']['about_desc_one'] ?? '' }}</textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label>{{ __('Description Two') }}</label>
                                                                <textarea name="about_desc_two" class="form-control" placeholder="{{ __('Enter Description') }}">{{ $page_data['headings']['about_desc_two'] ?? '' }}</textarea>
                                                            </div>

                                                            <div class="col-12">
                                                                <h4 class="mb-3">{{ __('Option') }}</h4>
                                                                <button
                                                                    class="w-100 py-3 d-block text-center fw-bold mt-3 admin-collapse bg-light primary border-0"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#about_us_options"
                                                                    aria-expanded="false"
                                                                    aria-controls="about_us_options">
                                                                    {{ __('Option') }} <i
                                                                        class="fas fa-arrow-circle-down ms-2"></i>
                                                                </button>
                                                                <div class="collapse mt-3" id="about_us_options">
                                                                    @foreach ($page_data['headings']['about_us_options_text'] ?? [] as $key => $about_us_options_text)
                                                                        <div
                                                                            class="sample-form-wrp duplicate-feature pe-3">
                                                                            <div class="row mb-4">
                                                                                <div class="col-lg-12">
                                                                                    <label>{{ __('Option') }}-
                                                                                        {{ $key + 1 }} </label>
                                                                                    <input type="text"
                                                                                        name="about_us_options_text[]"
                                                                                        value="{{ $about_us_options_text ?? '' }}"
                                                                                        required class="form-control"
                                                                                        placeholder="{{ __('Enter text') }}">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>

                                                        </div>
                                                        {{--  About Us section End --}}

                                                        {{-- Contact Us Section Start --}}
                                                        <div class="tab-pane fade" id="contact_us">

                                                            <div>
                                                                <div class="form-group">
                                                                    <label>{{ __('Title') }}</label>
                                                                    <input type="text" name="contact_us_title"
                                                                        value="{{ $page_data['headings']['contact_us_title'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Title') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="form-group">
                                                                    <label>{{ __('Button Text') }}</label>
                                                                    <input type="text" name="contact_us_btn_text"
                                                                        value="{{ $page_data['headings']['contact_us_btn_text'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Text') }}" class="form-control">
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <label class="img-label">{{ __('Icon') }}</label>
                                                                        <div class="custom-upload-wrapper">
                                                                            <div class="custom-image-box">
                                                                                <div class="custom-image-content">
                                                                                    <svg width="30" height="30" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                        <path d="M18.3327 7.5026V12.5026C18.3327 14.8596 18.3327 16.0381 17.6004 16.7704C16.8682 17.5026 15.6897 17.5026 13.3327 17.5026H6.66602C4.309 17.5026 3.13048 17.5026 2.39825 16.7704C1.66602 16.0381 1.66602 14.8596 1.66602 12.5026V9.21404C1.66602 8.39729 1.66602 7.98892 1.76053 7.65502C1.99698 6.81974 2.64982 6.1669 3.48509 5.93046C3.819 5.83594 4.22736 5.83594 5.04409 5.83594C5.34907 5.83594 5.50157 5.83594 5.64361 5.81118C5.99556 5.74983 6.31847 5.577 6.56476 5.3182C6.66415 5.21375 6.91352 4.8397 7.08268 4.58594C7.413 4.09048 7.57815 3.84275 7.80393 3.67233C7.94181 3.56826 8.09502 3.48627 8.25809 3.42927C8.52512 3.33594 8.82287 3.33594 9.41837 3.33594H10.8327" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                                        <path d="M13.3327 11.2474C13.3327 13.0883 11.8403 14.5807 9.99937 14.5807C8.1584 14.5807 6.66602 13.0883 6.66602 11.2474C6.66602 9.40641 8.1584 7.91406 9.99937 7.91406C11.8403 7.91406 13.3327 9.40641 13.3327 11.2474Z" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                                        <path d="M13.334 4.58333H17.5007M15.4173 6.66667V2.5" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                    </svg>
                                                                                    <span class="custom-upload-text">{{ __('Add Icon') }}</span>
                                                                                </div>

                                                                                <!-- Preview image -->
                                                                                <img class="preview-image d-none" id="contact_us_icon" src="{{ Storage::url($page_data['contact_us_icon'] ?? '') }}" alt="Preview">
                                                                                <input type="file" name="contact_us_icon" class="preview-image-input" data-id="#contact_us_icon" accept="image/*">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label>{{ __('Description') }}</label>
                                                                    <textarea name="contact_us_description" class="form-control" placeholder="{{ __('Enter Description') }}">{{ $page_data['headings']['contact_us_description'] ?? '' }}</textarea>
                                                                </div>

                                                            </div>
                                                        </div>
                                                        {{-- Contact Us Section End --}}

                                                        {{-- Pricing section --}}
                                                        <div class="tab-pane fade" id="pricing">
                                                            <div class="form-group">
                                                                <label>{{ __('Title') }}</label>
                                                                <input type="text" name="pricing_title"
                                                                    value="{{ $page_data['headings']['pricing_title'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Title') }}" required
                                                                    class="form-control">
                                                            </div>

                                                            <div class="form-group">
                                                                <label>{{ __('Plan Button Url') }}</label>
                                                                <input type="text" name="pricing_btn_link"
                                                                    value="{{ $page_data['headings']['pricing_btn_link'] ?? '' }}"
                                                                    placeholder="{{ __('Enter Link') }}" required
                                                                    class="form-control">
                                                            </div>

                                                            <div class="form-group">
                                                                <label>{{ __('Description') }}</label>
                                                                <textarea name="pricing_description" class="form-control" placeholder="{{ __('Enter Description') }}">{{ $page_data['headings']['pricing_description'] ?? '' }}</textarea>
                                                            </div>
                                                        </div>
                                                        {{-- pricing section end --}}

                                                        {{-- Footer Section Start --}}
                                                        <div class="tab-pane fade" id="footer">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <label>{{ __('Short Title') }}</label>
                                                                    <input type="text" name="footer_short_title"
                                                                        value="{{ $page_data['headings']['footer_short_title'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Title') }}" required
                                                                        class="form-control">
                                                                </div>
                                                                <div class="col-12">
                                                                    <label>{{ __('Scanner Text') }}</label>
                                                                    <input type="text" name="footer_scanner_title"
                                                                        value="{{ $page_data['headings']['footer_scanner_title'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Title') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-12">
                                                                    <label>{{ __('Right Footer Title') }}</label>
                                                                    <input type="text" name="right_footer_title"
                                                                        value="{{ $page_data['headings']['right_footer_title'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Title') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-12">
                                                                    <label>{{ __('Middle Footer Title') }}</label>
                                                                    <input type="text" name="middle_footer_title"
                                                                        value="{{ $page_data['headings']['middle_footer_title'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Title') }}" required
                                                                        class="form-control">
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="img-label">{{ __('Scanner Image') }}</label>
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
                                                                            <img class="preview-image d-none" id="footer_scanner_image" src="{{ Storage::url($page_data['footer_scanner_image'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="footer_scanner_image" class="preview-image-input" data-id="#footer_scanner_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-6">
                                                                    <label>{{ __('Apple App Link') }}</label>
                                                                    <input type="text" name="footer_apple_app_link"
                                                                        value="{{ $page_data['headings']['footer_apple_app_link'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Link') }}" class="form-control">
                                                                </div>

                                                                <div class="col-6">
                                                                    <label class="img-label">{{ __('Apple App') }}</label>
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
                                                                            <img class="preview-image d-none" id="footer_apple_app_image" src="{{ Storage::url($page_data['footer_apple_app_image'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="footer_apple_app_image" class="preview-image-input" data-id="#footer_apple_app_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-6">
                                                                    <label>{{ __('Google Play Link') }}</label>
                                                                    <input type="text"
                                                                        name="footer_google_play_app_link"
                                                                        value="{{ $page_data['headings']['footer_google_play_app_link'] ?? '' }}"
                                                                        placeholder="{{ __('Enter Link') }}" class="form-control">
                                                                </div>

                                                                <div class="col-6">
                                                                    <label class="img-label">{{ __('Google Play App') }}</label>
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
                                                                            <img class="preview-image d-none" id="footer_google_app_image" src="{{ Storage::url($page_data['footer_google_app_image'] ?? '') }}" alt="Preview">
                                                                            <input type="file" name="footer_google_app_image" class="preview-image-input" data-id="#footer_google_app_image" accept="image/*">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <h4 class="mb-3">{{ __('Right Footer') }}</h4>
                                                                    <button
                                                                        class="w-100 py-3 d-block text-center fw-bold mt-3 admin-collapse bg-light primary border-0"
                                                                        type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#right_footer"
                                                                        aria-expanded="false"
                                                                        aria-controls="right_footer">
                                                                       {{ __('Right Footer') }}
                                                                        <i class="fas fa-arrow-circle-down ms-2"></i>
                                                                    </button>
                                                                    <div class="mt-3 collapse row" id="right_footer">
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_one"
                                                                                    value="{{ $page_data['headings']['right_footer_one'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_link_one"
                                                                                    value="{{ $page_data['headings']['right_footer_link_one'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_two"
                                                                                    value="{{ $page_data['headings']['right_footer_two'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_link_two"
                                                                                    value="{{ $page_data['headings']['right_footer_link_two'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_three"
                                                                                    value="{{ $page_data['headings']['right_footer_three'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_link_three"
                                                                                    value="{{ $page_data['headings']['right_footer_link_three'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_four"
                                                                                    value="{{ $page_data['headings']['right_footer_four'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_link_four"
                                                                                    value="{{ $page_data['headings']['right_footer_link_four'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_five"
                                                                                    value="{{ $page_data['headings']['right_footer_five'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_link_five"
                                                                                    value="{{ $page_data['headings']['right_footer_link_five'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_six"
                                                                                    value="{{ $page_data['headings']['right_footer_six'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="right_footer_link_six"
                                                                                    value="{{ $page_data['headings']['right_footer_link_six'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <h4 class="mb-3">{{ __('Middle Footer') }}</h4>
                                                                    <button
                                                                        class="w-100 py-3 d-block text-center fw-bold mt-3 admin-collapse bg-light primary border-0"
                                                                        type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#middle_footer"
                                                                        aria-expanded="false"
                                                                        aria-controls="middle_footer">
                                                                       {{ __('Middle Footer') }}
                                                                        <i class="fas fa-arrow-circle-down ms-2"></i>
                                                                    </button>
                                                                    <div class="mt-3 collapse row" id="middle_footer">
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_one"
                                                                                    value="{{ $page_data['headings']['middle_footer_one'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_link_one"
                                                                                    value="{{ $page_data['headings']['middle_footer_link_one'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_two"
                                                                                    value="{{ $page_data['headings']['middle_footer_two'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_link_two"
                                                                                    value="{{ $page_data['headings']['middle_footer_link_two'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_three"
                                                                                    value="{{ $page_data['headings']['middle_footer_three'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_link_three"
                                                                                    value="{{ $page_data['headings']['middle_footer_link_three'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_four"
                                                                                    value="{{ $page_data['headings']['middle_footer_four'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_link_four"
                                                                                    value="{{ $page_data['headings']['middle_footer_link_four'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_five"
                                                                                    value="{{ $page_data['headings']['middle_footer_five'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_link_five"
                                                                                    value="{{ $page_data['headings']['middle_footer_link_five'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_six"
                                                                                    value="{{ $page_data['headings']['middle_footer_six'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="middle_footer_link_six"
                                                                                    value="{{ $page_data['headings']['middle_footer_link_six'] ?? '' }}"
                                                                                    required class="form-control">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <h4 class="mb-3">{{ __('Left Footer') }}</h4>
                                                                    <button
                                                                        class="w-100 py-3 d-block text-center fw-bold mt-3 admin-collapse bg-light primary border-0"
                                                                        type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#left_footer"
                                                                        aria-expanded="false" aria-controls="left_footer">
                                                                        {{ __('Left Footer') }}
                                                                        <i class="fas fa-arrow-circle-down ms-2"></i>
                                                                    </button>
                                                                    <div class="mt-3 collapse row" id="left_footer">
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_one"
                                                                                    value="{{ $page_data['headings']['left_footer_one'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_link_one"
                                                                                    value="{{ $page_data['headings']['left_footer_link_one'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_two"
                                                                                    value="{{ $page_data['headings']['left_footer_two'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_link_two"
                                                                                    value="{{ $page_data['headings']['left_footer_link_two'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_three"
                                                                                    value="{{ $page_data['headings']['left_footer_three'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_link_three"
                                                                                    value="{{ $page_data['headings']['left_footer_link_three'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_four"
                                                                                    value="{{ $page_data['headings']['left_footer_four'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_link_four"
                                                                                    value="{{ $page_data['headings']['left_footer_link_four'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Title') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_five"
                                                                                    value="{{ $page_data['headings']['left_footer_five'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                            <div class="col-sm-6">
                                                                                <label>{{ __('Link') }}</label>
                                                                                <input type="text"
                                                                                    name="left_footer_link_five"
                                                                                    value="{{ $page_data['headings']['left_footer_link_five'] ?? '' }}"
                                                                                    class="form-control">
                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        {{-- Footer section End --}}

                                                        {{-- Social section start --}}
                                                        <div class="tab-pane fade" id="socials">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <h4 class="mb-3">{{ __('Footer Socials') }}</h4>
                                                                    <button
                                                                        class="w-100 py-3 d-block text-center fw-bold mt-3 admin-collapse bg-light primary border-0"
                                                                        type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#footer-socials"
                                                                        aria-expanded="false"
                                                                        aria-controls="footer-socials">
                                                                        {{ __('Footer Socials') }} <i
                                                                            class="fas fa-arrow-circle-down ms-2"></i>
                                                                    </button>
                                                                    <div class="collapse mt-3" id="footer-socials">
                                                                        @foreach ($page_data['headings']['footer_socials_links'] ?? [] as $key => $footer_socials_links)
                                                                            <div
                                                                                class="sample-form-wrp duplicate-feature pe-3">
                                                                                <div class="row mb-4">
                                                                                    <div class="col-sm-6">
                                                                                        <label>{{ __('Link') }}</label>
                                                                                        <input type="text" name="footer_socials_links[]"
                                                                                            value="{{ $footer_socials_links ?? '' }}"
                                                                                            required class="form-control">
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <label class="img-label">{{ __('Icon') }}</label>
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
                                                                                                <img class="preview-image d-none" id="{{ 'footer_socials_icons_' . $key }}" src="{{ Storage::url($page_data['footer_socials_icons'][$key] ?? '') }}" alt="Preview">
                                                                                                <input type="file" name="footer_socials_icons[]" class="preview-image-input" data-id="#{{ 'footer_socials_icons_' . $key }}" accept="image/*">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        {{-- Social section end --}}

                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="button-group text-center mt-4">
                                                                    <button
                                                                        class="theme-btn m-2 submit-btn">{{ __('Update') }}</button>
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
                </div>
            </div>

                </div>
                </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
