@extends('layouts.master')

@section('title')
    {{ __('Mobile App Customization') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-bodys">
                    <div class="table-header p-16 d-flex align-items-center justify-content-between">
                        <h4><i class="fa-solid fa-mobile-screen-button me-2 text-primary"></i>{{ __('Mobile App Customization') }}</h4>
                        <span class="badge bg-success-subtle text-success fs-12 px-3 py-2">{{ __('Live Remote Sync') }}</span>
                    </div>

                    <div class="order-form-section p-16">
                        <form action="{{ route('admin.app-settings.store') }}" method="post" enctype="multipart/form-data" class="ajaxform">
                            @csrf
                            
                            <div class="add-suplier-modal-wrapper d-block">
                                {{-- Section: App & Splash Branding --}}
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <h5 class="border-bottom pb-2 text-primary">
                                            <i class="fa-solid fa-palette me-2"></i>{{ __('Branding & Splash Screen') }}
                                        </h5>
                                    </div>

                                    <div class="col-lg-6 mt-2">
                                        <label class="form-label font-weight-bold">{{ __('App Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="app_name" value="{{ $settings['app_name'] ?? 'Business Manager' }}" required class="form-control" placeholder="{{ __('e.g., Business Manager') }}">
                                        <small class="text-muted">{{ __('Displayed throughout the mobile app header and title') }}</small>
                                    </div>

                                    <div class="col-lg-6 mt-2">
                                        <label class="form-label font-weight-bold">{{ __('Powered By Text') }}</label>
                                        <input type="text" name="powered_by" value="{{ $settings['powered_by'] ?? 'Business Manager' }}" class="form-control" placeholder="{{ __('e.g., Hassan Agro / Business Manager') }}">
                                        <small class="text-muted">{{ __('Appears at the bottom of the splash screen ("Powered by ...")') }}</small>
                                    </div>

                                    <div class="col-lg-6 mt-3">
                                        <label class="form-label font-weight-bold">{{ __('Company Website') }}</label>
                                        <input type="url" name="company_website" value="{{ $settings['company_website'] ?? 'https://hassanagro.com' }}" class="form-control" placeholder="{{ __('https://...') }}">
                                    </div>

                                    <div class="col-lg-6 mt-3">
                                        <label class="form-label font-weight-bold">{{ __('App Version') }}</label>
                                        <input type="text" name="app_version" value="{{ $settings['app_version'] ?? '1.0.0' }}" class="form-control" placeholder="{{ __('e.g., 1.0.0') }}">
                                    </div>

                                    <div class="col-lg-6 mt-3">
                                        <label class="form-label font-weight-bold">{{ __('Splash Screen Background Color') }}</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="splash_bg_color_picker" value="{{ $settings['splash_bg_color'] ?? '#C52127' }}" oninput="document.getElementById('splash_bg_color').value = this.value" style="max-width: 60px;">
                                            <input type="text" name="splash_bg_color" id="splash_bg_color" value="{{ $settings['splash_bg_color'] ?? '#C52127' }}" class="form-control" oninput="document.getElementById('splash_bg_color_picker').value = this.value">
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mt-3">
                                        <label class="form-label font-weight-bold">{{ __('App Primary Theme Color') }}</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="primary_color_picker" value="{{ $settings['primary_color'] ?? '#C52127' }}" oninput="document.getElementById('primary_color').value = this.value" style="max-width: 60px;">
                                            <input type="text" name="primary_color" id="primary_color" value="{{ $settings['primary_color'] ?? '#C52127' }}" class="form-control" oninput="document.getElementById('primary_color_picker').value = this.value">
                                        </div>
                                    </div>
                                </div>

                                {{-- Section: Visual Assets --}}
                                <div class="row mt-4">
                                    <div class="col-12 mb-3">
                                        <h5 class="border-bottom pb-2 text-primary">
                                            <i class="fa-solid fa-image me-2"></i>{{ __('App Logos & Visual Assets') }}
                                        </h5>
                                    </div>

                                    <div class="col-lg-6 settings-image-upload mt-2">
                                        <label class="title font-weight-bold">{{ __('Splash Screen Logo / Image') }}</label>
                                        <p class="text-muted fs-12 mb-2">{{ __('Recommended: 512x512 or 1024x1024 PNG with transparent or solid background') }}</p>
                                        <div class="upload-img-v2">
                                            <label class="upload-v4 settings-upload-v4">
                                                <div class="img-wrp p-2 d-flex align-items-center justify-content-center" style="min-height: 140px; background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 8px;">
                                                    <img src="{{ !empty($settings['splash_logo']) ? asset($settings['splash_logo']) : asset('assets/images/icons/upload-icon.svg') }}" alt="Splash Logo" id="splash_logo_preview" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                                                </div>
                                                <input type="file" name="splash_logo" class="d-none" accept="image/*" onchange="if(this.files[0]) document.getElementById('splash_logo_preview').src = window.URL.createObjectURL(this.files[0])">
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 settings-image-upload mt-2">
                                        <label class="title font-weight-bold">{{ __('App Icon / Header Logo') }}</label>
                                        <p class="text-muted fs-12 mb-2">{{ __('Recommended: 512x512 PNG square format') }}</p>
                                        <div class="upload-img-v2">
                                            <label class="upload-v4 settings-upload-v4">
                                                <div class="img-wrp p-2 d-flex align-items-center justify-content-center" style="min-height: 140px; background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 8px;">
                                                    <img src="{{ !empty($settings['app_logo']) ? asset($settings['app_logo']) : asset('assets/images/icons/upload-icon.svg') }}" alt="App Logo" id="app_logo_preview" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                                                </div>
                                                <input type="file" name="app_logo" class="d-none" accept="image/*" onchange="if(this.files[0]) document.getElementById('app_logo_preview').src = window.URL.createObjectURL(this.files[0])">
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Section: Support Contact --}}
                                <div class="row mt-4">
                                    <div class="col-12 mb-3">
                                        <h5 class="border-bottom pb-2 text-primary">
                                            <i class="fa-solid fa-headset me-2"></i>{{ __('In-App Support & Contact') }}
                                        </h5>
                                    </div>

                                    <div class="col-lg-6 mt-2">
                                        <label class="form-label font-weight-bold">{{ __('Support Email') }}</label>
                                        <input type="email" name="support_email" value="{{ $settings['support_email'] ?? '' }}" class="form-control" placeholder="{{ __('support@hassanagro.com') }}">
                                    </div>

                                    <div class="col-lg-6 mt-2">
                                        <label class="form-label font-weight-bold">{{ __('Support Phone / WhatsApp') }}</label>
                                        <input type="text" name="support_phone" value="{{ $settings['support_phone'] ?? '' }}" class="form-control" placeholder="{{ __('+92 300 1234567') }}">
                                    </div>
                                </div>

                                {{-- Section: Onboarding Screens --}}
                                <div class="row mt-4">
                                    <div class="col-12 mb-3">
                                        <h5 class="border-bottom pb-2 text-primary">
                                            <i class="fa-solid fa-sliders me-2"></i>{{ __('Onboarding Screens Customization') }}
                                        </h5>
                                    </div>

                                    <div class="col-lg-4 mt-2">
                                        <div class="card p-3 border">
                                            <h6 class="font-weight-bold mb-2">{{ __('Screen 1') }}</h6>
                                            <label class="form-label fs-12">{{ __('Title') }}</label>
                                            <input type="text" name="onboarding_title_1" value="{{ $settings['onboarding_title_1'] ?? 'Easy Business Management' }}" class="form-control mb-2">
                                            <label class="form-label fs-12">{{ __('Description') }}</label>
                                            <textarea name="onboarding_desc_1" rows="2" class="form-control">{{ $settings['onboarding_desc_1'] ?? 'Manage your sales, inventory, and accounts with ease.' }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 mt-2">
                                        <div class="card p-3 border">
                                            <h6 class="font-weight-bold mb-2">{{ __('Screen 2') }}</h6>
                                            <label class="form-label fs-12">{{ __('Title') }}</label>
                                            <input type="text" name="onboarding_title_2" value="{{ $settings['onboarding_title_2'] ?? 'Real-time Reports' }}" class="form-control mb-2">
                                            <label class="form-label fs-12">{{ __('Description') }}</label>
                                            <textarea name="onboarding_desc_2" rows="2" class="form-control">{{ $settings['onboarding_desc_2'] ?? 'Track your profits, losses, and cashflow in real-time.' }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 mt-2">
                                        <div class="card p-3 border">
                                            <h6 class="font-weight-bold mb-2">{{ __('Screen 3') }}</h6>
                                            <label class="form-label fs-12">{{ __('Title') }}</label>
                                            <input type="text" name="onboarding_title_3" value="{{ $settings['onboarding_title_3'] ?? 'Fast POS Billing' }}" class="form-control mb-2">
                                            <label class="form-label fs-12">{{ __('Description') }}</label>
                                            <textarea name="onboarding_desc_3" rows="2" class="form-control">{{ $settings['onboarding_desc_3'] ?? 'Quick invoice printing and barcode scanning at your fingertips.' }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12 mt-4 text-end">
                                    <button type="submit" class="btn submit-btn btn-primary px-4 py-2">
                                        <i class="fa-solid fa-save me-2"></i>{{ __('Save Settings') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
