<section class="pricing-plan-section plans-list">
    <div class="container">
        <div class="section-title text-center">
            <h2>{{ $page_data['headings']['pricing_title'] ?? '' }}</h2>
            <p class="section-description">
                {{ $page_data['headings']['pricing_description'] ?? '' }}
            </p>
            <div class="d-flex align-items-center justify-content-center gap-4">
                <div class="w-100 d-flex flex-column align-items-center">

                    <div class="tab-content w-100">
                        <div class="tab-pane fade show active" id="nav-monthly" role="tabpanel"
                            aria-labelledby="nav-monthly-tab">
                            <div class="row d-flex align-items-stretch">
                                @foreach ($plans as $plan)
                                    <div class="col-12 col-md-6 col-lg-4 mt-3">
                                        <div class="card h-100">
                                            <div class="card-header py-3 border-0 font-size-update">
                                                <p>{{ $plan['subscriptionName'] ?? '' }}</p>
                                                <h4>
                                                    @if (($plan['offerPrice'] && $plan['subscriptionPrice'] !== null) || $plan['offerPrice'] || $plan['subscriptionPrice'])
                                                        @if ($plan['offerPrice'])
                                                            {{ currency_format($plan['offerPrice']) }}
                                                        @else
                                                            {{ currency_format($plan['subscriptionPrice']) }}
                                                        @endif
                                                    @else
                                                        @if ($plan['offerPrice'] || $plan['subscriptionPrice'])
                                                            {{ currency_format($plan['offerPrice'] ?? $plan['subscriptionPrice']) }}
                                                        @else
                                                            {{ __('Free') }}
                                                        @endif
                                                    @endif
                                                    <span class="price-span">/{{ $plan['duration'] . ' ' . __('Days') }}</span>
                                                </h4>
                                            </div>

                                            <div class="card-body text-start d-flex flex-column">
                                                @php
                                                    $moduleLabels = [
                                                        'sales' => __('Sales'),
                                                        'purchases' => __('Purchase'),
                                                        'products' => __('Products'),
                                                        'stocks' => __('Stock List'),
                                                        'parties' => __('Parties'),
                                                        'finance-accounts' => __('Finance & Accounts'),
                                                        'incomes' => __('Incomes'),
                                                        'expenses' => __('Expenses'),
                                                        'vats' => __('Tax Settings'),
                                                        'dues' => __('Due List'),
                                                        'loss-profits' => __('Profit & Loss'),
                                                        'payment-types' => __('Payment Type'),
                                                        'reports' => __('Reports'),
                                                        'custom-reports' => __('Custom Reports'),
                                                        'party-reports' => __('Party Reports'),
                                                    ];
                                                    if (moduleCheck('WarehouseAddon')) $moduleLabels['warehouses'] = __('Warehouse');
                                                    if (moduleCheck('MultiBranchAddon') || moduleCheck('WarehouseAddon')) $moduleLabels['transfers'] = __('Transfer');
                                                    if (moduleCheck('MultiBranchAddon')) $moduleLabels['branches'] = __('Branch');
                                                    if (moduleCheck('HrmAddon')) $moduleLabels['hrm'] = __('HRM');
                                                    if (moduleCheck('MarketingAddon')) $moduleLabels['sms-marketing'] = __('SMS Marketing');

                                                    $enabledModules = collect($plan['available_modules'] ?? [])
                                                        ->filter(fn($v) => is_bool($v) ? $v : (is_array($v) ? ($v['read'] ?? '0') === '1' : $v === '1'))
                                                        ->keys()
                                                        ->map(fn($k) => $moduleLabels[$k] ?? __(ucfirst(str_replace('-', ' ', $k))));
                                                @endphp
                                                <ul>
                                                    @if (moduleCheck('CustomDomainAddon'))
                                                        @if($plan->addon_domain_limit > 0)
                                                        <li><i class="fas fa-check-circle text-success me-1"></i>
                                                            {{ __('Addon Domain Limit:') . ' ' . $plan->addon_domain_limit }}
                                                        </li>
                                                        @endif
                                                        @if($plan->subdomain_limit > 0)
                                                        <li><i class="fas fa-check-circle text-success me-1"></i>
                                                            {{ __('Subdomain Limit:') . ' ' . $plan->subdomain_limit }}
                                                        </li>
                                                        @endif
                                                    @endif

                                                    @php
                                                        $limitFields = [
                                                            'user_limit' => __('User Limit'),
                                                            'product_limit' => __('Product Limit'),
                                                            'customer_limit' => __('Customer Limit'),
                                                            'supplier_limit' => __('Supplier Limit'),
                                                        ];
                                                        if (moduleCheck('WarehouseAddon')) $limitFields['warehouse_limit'] = __('Warehouse Limit');
                                                        if (moduleCheck('MultiBranchAddon')) $limitFields['branch_limit'] = __('Branch Limit');

                                                        $moduleRequired = [
                                                            'product_limit' => 'products',
                                                            'customer_limit' => 'parties',
                                                            'supplier_limit' => 'parties',
                                                            'warehouse_limit' => 'warehouses',
                                                            'branch_limit' => 'branches',
                                                        ];
                                                    @endphp

                                                    @foreach ($limitFields as $field => $label)
                                                        @if ($field === 'branch_limit' && moduleCheck('MultiBranchAddon') && $plan->allow_multibranch == 1)
                                                        <li><i class="fas fa-check-circle text-success me-1"></i>
                                                            {{ __('Multi-branch Allowed') }}
                                                        </li>
                                                        @endif
                                                        @php
                                                            $value = $plan[$field] ?? null;
                                                            $displayValue = $value === -1 ? __('Unlimited') : ($value !== null ? $value : null);
                                                            $requiredMod = $moduleRequired[$field] ?? null;
                                                            $modValue = $plan['available_modules'][$requiredMod] ?? '0';
                                                            $modEnabled = !$requiredMod || (is_bool($modValue) ? $modValue : $modValue === '1');
                                                        @endphp
                                                        @if ($modEnabled && $value !== 0 && $value !== null && $value !== '0')
                                                        <li>
                                                            <i class="fas fa-check-circle text-success me-1"></i>
                                                            {{ $label }}: <strong>{{ $displayValue }}</strong>
                                                        </li>
                                                        @endif
                                                    @endforeach

                                                    @if($enabledModules->isNotEmpty())
                                                        @foreach($enabledModules as $label)
                                                        <li>
                                                            <i class="fas fa-check-circle text-success me-1"></i>
                                                            {{ $label }}
                                                        </li>
                                                        @endforeach
                                                    @endif

                                                    @foreach ($plan['features'] ?? [] as $key => $item)
                                                        @if(isset($item[1]))
                                                        <li>
                                                            <i class="fas fa-check-circle text-success me-1"></i>
                                                            {{ $item[0] ?? '' }}
                                                        </li>
                                                        @endif
                                                    @endforeach

                                                </ul>

                                                <a class="btn subscribe-plan d-block mt-auto mb-2" data-plan-id="{{ $plan->id }}" data-google-url="{{ url('login/google?plan_id=') . $plan->id }}" data-twitter-url="{{ url('login/twitter?plan_id=') . $plan->id }}">{{ __('Buy Now') }}</a>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<input type="hidden" value="{{ route('get-business-categories') }}" id="get-business-categories">
