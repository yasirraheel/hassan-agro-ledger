@extends('layouts.master')

@section('title')
    {{ __('Edit Subscription Plan') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card border-0">
                <div class="card-bodys shadow-sm">
                    <div class="table-header p-16">
                        <h4>{{ __('Edit Package') }}</h4>
                        @can('plans-read')
                            <a href="{{ route('admin.plans.index') }}"
                                class="add-order-btn rounded-2 {{ Route::is('admin.users.create') ? 'active' : '' }}"><i
                                    class="far fa-list" aria-hidden="true"></i> {{ __('Package List') }}</a>
                        @endcan
                    </div>
                    <div class="order-form-section p-16">
                        <form action="{{ route('admin.plans.update', $plan->id) }}" method="POST"
                            class="ajaxform_instant_reload">
                            @csrf
                            @method('put')
                            <div class="add-suplier-modal-wrapper d-block">
                                <div class="row">
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Package Name') }}</label>
                                        <input value="{{ $plan->subscriptionName }}" type="text" name="subscriptionName"
                                            required class="form-control" placeholder="{{ __('Enter Package Name') }}"
                                            @readonly($plan->subscriptionName == 'Free')>
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Duration in Days') }}</label>
                                        <input value="{{ $plan->duration }}" type="number" step="any" name="duration"
                                            required class="form-control" placeholder="{{ __('Enter Duration Days') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Offer Price') }}</label>
                                        <input value="{{ $plan->offerPrice }}" type="number" step="any"
                                            name="offerPrice" class="form-control price"
                                            placeholder="{{ __('Enter Plan Price') }}" @readonly($plan->subscriptionName == 'Free')>
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Subscription Price') }}</label>
                                        <input value="{{ $plan->subscriptionPrice }}" type="number" step="any"
                                            name="subscriptionPrice" required class="form-control"
                                            placeholder="{{ __('Enter Subscription Price') }}" @readonly($plan->subscriptionName == 'Free')>
                                    </div>

                                    @if (moduleCheck('MultiBranchAddon'))
                                        <div class="col-lg-6 mb-2">
                                            <label>{{ __('Multibranch Allowed') }}</label>
                                            <div
                                                class="form-control d-flex justify-content-between align-items-center radio-switcher">
                                                <p class="dynamic-text">
                                                    {{ $plan->allow_multibranch == 1 ? 'Active' : 'Deactive' }}</p>
                                                <label class="switch m-0">
                                                    <input type="checkbox" name="allow_multibranch" class="change-text"
                                                        {{ $plan->allow_multibranch == 1 ? 'checked' : '' }}>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    @if (moduleCheck('CustomDomainAddon'))
                                        <div class="col-lg-6 mb-2">
                                            <label>{{ __('Addon Domain Limit') }}</label>
                                            <input type="number" min="0" name="addon_domain_limit"
                                                value="{{ $plan->addon_domain_limit }}" class="form-control"
                                                placeholder="{{ __('Enter Addon Domain limit') }}">
                                        </div>

                                        <div class="col-lg-6 mb-2">
                                            <label>{{ __('Subdomain Limit') }}</label>
                                            <input type="number" min="0" name="subdomain_limit"
                                                value="{{ $plan->subdomain_limit }}" class="form-control"
                                                placeholder="{{ __('Enter Subdomain limit') }}">
                                        </div>
                                    @endif

                                    <div class="col-lg-6 mb-2 check-input-container">
                                        <label>{{ __('User Limit') }}</label>
                                        <input type="number" name="user_limit" value="{{ $plan->user_limit }}" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="user_unlimited" class="table-hidden-checkbox" value="1" @checked($plan->user_limit == -1)>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2 check-input-container" data-module="products">
                                        <label>{{ __('Product Limit') }}</label>
                                        <input type="number" name="product_limit" value="{{ $plan->product_limit }}" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="product_unlimited" class="table-hidden-checkbox" value="1" @checked($plan->product_limit == -1)>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2 check-input-container" data-module="parties">
                                        <label>{{ __('Customer Limit') }}</label>
                                        <input type="number" name="customer_limit" value="{{ $plan->customer_limit }}" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="customer_unlimited" class="table-hidden-checkbox" value="1" @checked($plan->customer_limit == -1)>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2 check-input-container" data-module="parties">
                                        <label>{{ __('Supplier Limit') }}</label>
                                        <input type="number" name="supplier_limit" value="{{ $plan->supplier_limit }}" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="supplier_unlimited" class="table-hidden-checkbox" value="1" @checked($plan->supplier_limit == -1)>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    @if (moduleCheck('WarehouseAddon'))
                                    <div class="col-lg-6 mb-2 check-input-container" data-module="warehouses">
                                        <label>{{ __('Warehouse Limit') }}</label>
                                        <input type="number" name="warehouse_limit" value="{{ $plan->warehouse_limit }}" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="warehouse_unlimited" class="table-hidden-checkbox" value="1" @checked($plan->warehouse_limit == -1)>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endif

                                    @if (moduleCheck('MultiBranchAddon'))
                                    <div class="col-lg-6 mb-2 check-input-container" id="branch-limit-container" data-module="branches">
                                        <label>{{ __('Branch Limit') }}</label>
                                        <input type="number" name="branch_limit" value="{{ $plan->branch_limit }}" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="branch_unlimited" class="table-hidden-checkbox" value="1" @checked($plan->branch_limit == -1)>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endif


                                    <div class="col-lg-12 mt-4">
                                        <div class="enabled-modules-section mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                                    <h5 class="mb-0 fw-bold">{{ __('Enabled Modules') }}</h5>
                                                    <label class="table-custom-checkbox">
                                                        <span class="">{{ __('Select All') }}</span>
                                                        <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox" value="0">
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>

                                                @php
                                                    $modules = $plan->available_modules ?? [];
                                                    $moduleLabels = [
                                                        'sales' => 'Sales',
                                                        'purchases' => 'Purchase',
                                                        'products' => 'Products',
                                                        'stocks' => 'Stock List',
                                                        'parties' => 'Parties',
                                                        'finance-accounts' => 'Finance & Accounts',
                                                        'incomes' => 'Incomes',
                                                        'expenses' => 'Expenses',
                                                        'vats' => 'Tax Settings',
                                                        'dues' => 'Due List',
                                                        'loss-profits' => 'Profit & Loss',
                                                        'payment-types' => 'Payment Type',
                                                        'reports' => 'Reports',
                                                        'custom-reports' => 'Custom Reports',
                                                        'party-reports' => 'Party Reports',
                                                    ];

                                                    if (moduleCheck('WarehouseAddon')) {
                                                        $moduleLabels['warehouses'] = 'Warehouse';
                                                    }

                                                    if (moduleCheck('MultiBranchAddon') || moduleCheck('WarehouseAddon')) {
                                                        $moduleLabels['transfers'] = 'Transfer';
                                                    }

                                                    if (moduleCheck('MultiBranchAddon')) {
                                                        $moduleLabels['branches'] = 'Branch';
                                                    }

                                                    if (moduleCheck('HrmAddon')) {
                                                        $moduleLabels['hrm'] = 'HRM';
                                                    }

                                                    if (moduleCheck('MarketingAddon')) {
                                                        $moduleLabels['sms-marketing'] = 'SMS Marketing';
                                                    }
                                                @endphp
                                                <div class="row g-4">
                                                    @foreach ($moduleLabels as $key => $label)
                                                        <div class="col-lg-3">
                                                            <label class="table-custom-checkbox">
                                                                <span class="recommended-text">{{ $label }}</span>
                                                                 <input type="checkbox" name="available_modules[{{ $key }}]" class="table-hidden-checkbox checkbox-item" value="1"
                                                                     {{ (isset($modules[$key]) && (
                                                                         (is_bool($modules[$key]) && $modules[$key]) ||
                                                                         (is_array($modules[$key]) && ($modules[$key]['read'] ?? '0') === '1') ||
                                                                         $modules[$key] === '1'
                                                                     )) ? 'checked' : '' }}>
                                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                        </div>
                                    </div>


                                    <div class="col-lg-6">
                                        <label>{{ __('Add New Features') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control add-feature border-0 bg-transparent"
                                                placeholder="{{ __('Enter features') }}">
                                            <button class="feature-btn" id="feature-btn">{{ __('Save') }}</button>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="row feature-list">
                                            @foreach ($plan->features ?? [] as $key => $item)
                                                <div class="col-lg-6 mt-4 remove-list feature-item">
                                                    <div class="feature-wrp">
                                                        <div
                                                            class="form-control d-flex justify-content-between align-items-center">
                                                            <input name="features[features_{{ $key }}][]" required
                                                                class="border-0"
                                                                type="text" value="{{ $item[0] ?? '' }}">
                                                            <div class="d-flex align-items-center gap-4 switch-container">
                                                                <label class="switch m-0">
                                                                    <input type="checkbox"
                                                                        name="features[features_{{ $key }}][]"
                                                                        @checked(isset($item[1])) value="1">
                                                                    <span class="slider round"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <svg class="delete-feature" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <rect y="-2" width="21" height="21" rx="10.5" fill="#F54336"/>
                                                            <path d="M14 5L6 13" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M6 5L14 13" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if ($plan->planSubscribes()->exists())
                                    <div class="col-lg-12">
                                        <div class="p-4 rounded mt-4 mb-4 subscriber-warning-box">
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="fas fa-exclamation-triangle subscriber-warning-icon"></i>
                                                <h5 class="mb-0 fw-bold subscriber-warning-title">{{ __('Subscriber Update') }}</h5>
                                            </div>
                                            <div class="d-flex align-items-center gap-0 mb-2">
                                                <input class="form-check-input m-0 flex-shrink-0" type="checkbox" name="apply_to_subscribers" id="apply_to_subscribers" value="1">
                                                <label for="apply_to_subscribers" class="subscriber-update-label mb-0 cursor-pointer">
                                                    {{ __('Apply these changes to existing subscribers') }}
                                                </label>
                                            </div>
                                            <div class="ms-4">
                                                <div class="text-muted subscriber-warning-text">
                                                    {{ __('When checked, all existing subscribers of this plan will be updated with the latest plan configuration. If unchecked, only the plan will be updated.') }}
                                                    <strong>{{ __('This action will update all active subscribers currently using this plan.') }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-lg-12">
                                        <div class="button-group text-center mt-5">
                                            <button type="reset"
                                                class="theme-btn border-btn m-2">{{ __('Cancel') }}</button>
                                            <button class="theme-btn m-2 submit-btn">{{ __('Update') }}</button>
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
@endsection

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
