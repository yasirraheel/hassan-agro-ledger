@extends('layouts.master')

@section('title')
    {{ __('Language Settings') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <form action="{{ route('admin.translations.bulk-update', $lang->code) }}" method="post" class="ajaxform">
                <div class="card">
                    <div class="card-bodys">
                        <div class="table-header p-2">
                            <h4>{{  __('Translations') }} <span>({{ __('For') }} {{ $lang->name }})</span></h4>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0)" class="add-translation-btn" data-bs-toggle="modal" data-bs-target="#addTraslationModal">
                                    + {{ __('Add Translation') }}
                                </a>
                                <button type="submit" class="save-change-btn submit-btn">
                                    {{ __('Save Changes') }}
                                </button>
                            </div>
                        </div>

                        <div class="traslation-alert">🔄️ {{ __('Please reload the page after saving translations to apply the changes.') }}</div>

                        <div class="table-top-form p-16-0">
                            <div class="table-top-left d-flex gap-3 margin-l-16">
                                <!-- Search -->
                                <div class="table-search position-relative">
                                    <input class="form-control searchInput" type="text" placeholder="Search..." name="search" value="{{ request('search') }}" data-url="{{ route('admin.languages.show', $lang->code) }}">
                                    <input type="hidden" id="update-url" value="{{ route('admin.translations.update', $lang->code) }}">
                                    <span class="position-absolute">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.582 14.582L18.332 18.332" stroke="#4D4D4D" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M16.668 9.16797C16.668 5.02584 13.3101 1.66797 9.16797 1.66797C5.02584 1.66797 1.66797 5.02584 1.66797 9.16797C1.66797 13.3101 5.02584 16.668 9.16797 16.668C13.3101 16.668 16.668 13.3101 16.668 9.16797Z" stroke="#4D4D4D" stroke-width="1.25" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="translate-data">
                        @include('admin.languages.translates.datas')
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('modal')
        <div class="modal modal-md fade" id="addTraslationModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{ __('Add Translation') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <form action="{{ route('admin.translations.store') }}" method="post" class="add_new_translation">
                            <div>
                                <label>{{ __('Key') }}</label>
                                <input type="text" name="key" required class="form-control new_key" placeholder="{{ __('Enter Key') }}">
                                <input type="hidden" name="lang_code" value="{{ $lang->code }}">
                            </div>

                             <div>
                                <label>{{ __('Value') }}</label>
                                <input type="text" name="value" required class="form-control new_value" placeholder="{{ __('Enter Value') }}">
                            </div>

                            <div class="col-lg-12">
                                <div class="button-group text-center mt-3">
                                    <button type="reset" class="theme-btn border-btn m-2">{{ __('Cancel') }}</button>
                                    <button type="submit" class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

