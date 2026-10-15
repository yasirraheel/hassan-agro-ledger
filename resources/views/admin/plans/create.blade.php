@extends('layouts.master')

@section('title')
    {{ __('Add Subscription Plan') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card border-0">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Add new Package') }}</h4>
                        @can('plans-read')
                            <a href="{{ route('admin.plans.index') }}"
                                class="add-order-btn rounded-2 {{ Route::is('admin.users.create') ? 'active' : '' }}"><i
                                    class="far fa-list me-1" aria-hidden="true"></i> {{ __('Package List') }}</a>
                        @endcan
                    </div>
                    <div class="order-form-section  p-16">
                        <form action="{{ route('admin.plans.store') }}" method="POST" class="ajaxform_instant_reload">
                            @csrf
                            <div class="add-suplier-modal-wrapper d-block">
                                <div class="row">
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Package Name') }}</label>
                                        <input type="text" name="subscriptionName" required class="form-control"
                                            placeholder="{{ __('Enter Package Name') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Duration in Days') }}</label>
                                        <input type="number" step="any" name="duration" required class="form-control"
                                            placeholder="{{ __('Enter number') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Offer Price') }}</label>
                                        <input type="number" step="any" name="offerPrice" class="form-control price"
                                            placeholder="{{ __('Enter Plan Price') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Subscription Price') }}</label>
                                        <input type="number" step="any" name="subscriptionPrice" required
                                            class="form-control discount"
                                            placeholder="{{ __('Enter Subscription Price') }}">
                                    </div>

                                    @if (moduleCheck('MultiBranchAddon'))
                                        <div class="col-lg-6 mb-2">
                                            <div class="col-lg-12 my-2">
                                                <label>{{ __('Multibranch Allowed') }}</label>
                                                <div
                                                    class="form-control d-flex justify-content-between align-items-center radio-switcher">
                                                    <p class="dynamic-text">Active</p>
                                                    <label class="switch m-0">
                                                        <input type="checkbox" name="allow_multibranch" class="change-text"
                                                            checked>
                                                        <span class="slider round"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if (moduleCheck('CustomDomainAddon'))
                                        <div class="col-lg-6 mb-2">
                                            <label>{{ __('Custom Domain Limit') }}</label>
                                            <input type="number" min="0" name="addon_domain_limit"
                                                class="form-control" placeholder="{{ __('Enter Custom domain limit') }}">
                                        </div>

                                        <div class="col-lg-6 mb-2">
                                            <label>{{ __('Subdomain Limit') }}</label>
                                            <input type="number" min="0" name="subdomain_limit" class="form-control"
                                                placeholder="{{ __('Enter Subdomain limit') }}">
                                        </div>
                                    @endif

                                    <div class="col-lg-6 mb-2 check-input-container">
                                        <label>{{ __('User Limit') }}</label>
                                        <input type="number" name="user_limit" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="user_unlimited" class="table-hidden-checkbox" value="1" checked>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2 check-input-container" data-module="products">
                                        <label>{{ __('Product Limit') }}</label>
                                        <input type="number" name="product_limit" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="product_unlimited" class="table-hidden-checkbox" value="1" checked>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2 check-input-container" data-module="parties">
                                        <label>{{ __('Customer Limit') }}</label>
                                        <input type="number" name="customer_limit" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="customer_unlimited" class="table-hidden-checkbox" value="1" checked>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2 check-input-container" data-module="parties">
                                        <label>{{ __('Supplier Limit') }}</label>
                                        <input type="number" name="supplier_limit" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="supplier_unlimited" class="table-hidden-checkbox" value="1" checked>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    @if (moduleCheck('WarehouseAddon'))
                                    <div class="col-lg-6 mb-2 check-input-container" data-module="warehouses">
                                        <label>{{ __('Warehouse Limit') }}</label>
                                        <input type="number" name="warehouse_limit" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="warehouse_unlimited" class="table-hidden-checkbox" value="1" checked>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endif

                                    @if (moduleCheck('MultiBranchAddon'))
                                    <div class="col-lg-6 mb-2 check-input-container" id="branch-limit-container" data-module="branches">
                                        <label>{{ __('Branch Limit') }}</label>
                                        <input type="number" name="branch_limit" class="form-control" placeholder="{{ __('Ex: 10') }}">
                                        <div class="check-inputLabel">
                                            <label class="table-custom-checkbox">
                                                <input type="checkbox" name="branch_unlimited" class="table-hidden-checkbox" value="1" checked>
                                                <span class="table-custom-checkmark custom-checkmark"></span>
                                                <span class="recommended-text">{{ __('Unlimited') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-lg-12 mt-4">
                                        <div class="enabled-modules-section mb-4">
                                            <div
                                                class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                                <h5 class="mb-0 fw-bold">{{ __('Enabled Modules') }}</h5>
                                                <label class="table-custom-checkbox">
                                                    <span class="">{{ __('Select All') }}</span>
                                                    <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox" value="0">
                                                    <span class="table-custom-checkmark custom-checkmark"></span>
                                                </label>
                                            </div>
                                            <div class="row g-4">
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Sales</span>
                                                        <input type="checkbox" name="available_modules[sales]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Purchase</span>
                                                        <input type="checkbox" name="available_modules[purchases]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Products</span>
                                                        <input type="checkbox" name="available_modules[products]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @if (moduleCheck('WarehouseAddon'))
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Warehouse</span>
                                                        <input type="checkbox" name="available_modules[warehouses]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @endif
                                                @if (moduleCheck('MultiBranchAddon') || moduleCheck('WarehouseAddon'))
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Transfer</span>
                                                        <input type="checkbox" name="available_modules[transfers]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @endif
                                                @if (moduleCheck('MultiBranchAddon'))
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Branch</span>
                                                        <input type="checkbox" name="available_modules[branches]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @endif
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Stock List</span>
                                                        <input type="checkbox" name="available_modules[stocks]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Parties</span>
                                                        <input type="checkbox" name="available_modules[parties]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Incomes</span>
                                                        <input type="checkbox" name="available_modules[incomes]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Expenses</span>
                                                        <input type="checkbox" name="available_modules[expenses]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Tax Settings</span>
                                                        <input type="checkbox" name="available_modules[vats]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Due List</span>
                                                        <input type="checkbox" name="available_modules[dues]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Finance & Accounts</span>
                                                        <input type="checkbox" name="available_modules[finance-accounts]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Profit & Loss</span>
                                                        <input type="checkbox" name="available_modules[loss-profits]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Payment Type</span>
                                                        <input type="checkbox" name="available_modules[payment-types]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @if (moduleCheck('HrmAddon'))
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">HRM</span>
                                                        <input type="checkbox" name="available_modules[hrm]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @endif
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Reports</span>
                                                        <input type="checkbox" name="available_modules[reports]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Custom Reports</span>
                                                        <input type="checkbox" name="available_modules[custom-reports]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">Party Reports</span>
                                                        <input type="checkbox" name="available_modules[party-reports]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @if (moduleCheck('MarketingAddon'))
                                                <div class="col-lg-3">
                                                    <label class="table-custom-checkbox">
                                                        <span class="recommended-text">SMS Marketing</span>
                                                        <input type="checkbox" name="available_modules[sms-marketing]" class="table-hidden-checkbox checkbox-item" value="1" checked>
                                                        <span class="table-custom-checkmark custom-checkmark"></span>
                                                    </label>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-lg-6 mt-2">
                                        <label>{{ __('Add New Features') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control add-feature border-0 bg-transparent"
                                                placeholder="{{ __('Enter features') }}">
                                            <button class="feature-btn" id="feature-btn">{{ __('Save') }}</button>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="row feature-list">
                                            {{-- Will added dynamically. --}}
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="button-group text-center mt-5">
                                            <button type="reset"
                                                class="theme-btn border-btn m-2">{{ __('Cancel') }}</button>
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
@endsection

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
    @if (moduleCheck('MultiBranchAddon'))
    <script>
        $(document).ready(function () {
            function toggleBranchLimit() {
                var isChecked = $('input[name="allow_multibranch"]').is(':checked');
                $('#branch-limit-container').toggleClass('d-none', !isChecked);
                if (!isChecked) {
                    $('#branch-limit-container input[type="number"]').val('');
                    $('#branch-limit-container input[type="checkbox"]').prop('checked', false);
                }
            }
            $('input[name="allow_multibranch"]').on('change', toggleBranchLimit);
            toggleBranchLimit();
        });
    </script>
    @endif
@endpush
