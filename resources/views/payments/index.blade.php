@extends('layouts.web.blank')

@section('main_content')
<section class="payment-method-section">
    <div class="container">
        <div class="payment-method-wrp">
            <div class="row">
                <div class="col-lg-5">
                    <div class="nav d-block payment-method-nav">
                        <div class="row">
                            @foreach ($gateways as $gateway)
                            <div class="col-lg-6">
                                <a href="#{{ str_replace(' ', '-', $gateway->name) }}" data-bs-toggle="pill" @class(['add-report-btn payment-items', 'active' => $loop->first ? true : false])>
                                    {{ ucfirst($gateway->name) }}
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 mt-3 mt-sm-0">
                    <div class="tab-content">
                        @foreach ($gateways as $gateway)
                        <div @class(['tab-pane fade', 'show active' => $loop->first ? true : false]) id="{{ str_replace(' ', '-', $gateway->name) }}">
                            <form action="{{ route('payments-gateways.payment', ['plan_id' => $plan->id, 'gateway_id' => $gateway->id]) }}" method="post" enctype="multipart/form-data">
                                @csrf

                                <div class="payment-list-table">
                                    @if ($errors->any())
                                        @foreach ($errors->all() as $error)
                                        <div class="alert alert-warning alert-dismissible fade show mt-2" role="alert">
                                            {{ $error }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        @endforeach
                                    @endif

                                    <h5 class="payment-title mb-3">{{ ucfirst($gateway->name) }} ({{ optional($gateway->currency)->code }})</h5>
                                    <table class="table table-striped">
                                        <tbody>

                                            @php
                                                $amount = convert_money($plan->offerPrice ?? $plan->subscriptionPrice, $gateway->currency);
                                            @endphp

                                            <tr>
                                                <th class="text-start ">{{ __('Gateway Name') }}</th>
                                                <td class="text-end">{{ $gateway->name }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('Gateway Currency') }}</th>
                                                <td class="text-end">{{ optional($gateway->currency)->code }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('Gateway Rate') }}</th>
                                                <td class="text-end">{{ currency_format($gateway->currency->rate, currency:$gateway->currency) }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('Subscription Name') }}</th>
                                                <td class="text-end">{{ $plan->subscriptionName }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('Subscription Price') }}</th>
                                                <td class="text-end">{{ currency_format($amount, currency : $gateway->currency) }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('Gateway Charge') }}</th>
                                                <td class="text-end">{{ currency_format($gateway->charge, currency: $gateway->currency) }}</td>
                                            </tr>
                                            <tr>
                                                <th class="text-start">{{ __('Payable Amount') }}</th>
                                                <td class="text-end">{{ currency_format($amount + $gateway->charge, currency: $gateway->currency) }}</td>
                                            </tr>
                                            @if ($gateway->phone_required == 1)
                                            <tr>
                                                <th>
                                                    <label for="phone" class="required">{{ __('Phone Number') }}</label>
                                                </th>
                                                <td>
                                                    <input type="text" name="phone" id="phone" class="form-control w-100" placeholder="{{ __('Enter your phone number with country code') }}" required>
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>

                                    @if ($gateway->instructions)
                                    <div class="mb-3">
                                        <h5 class="payment-title mb-3">{{ __('Instructions') }}</h5>
                                        <p>{!! $gateway->instructions !!}</p>
                                    </div>
                                    @endif

                                    @if ($gateway->is_manual)
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            @if ($gateway->accept_img)
                                            <div class="form-group">
                                                <label for="">{{ __('Screenshot/Proof Image') }}</label>
                                                <div class="custom-upload-wrapper">
                                                    <div class="custom-file-box">
                                                        <div class="custom-file-content">
                                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M12 5V19M5 12H19" stroke="#4B5563" stroke-width="1.5" stroke-linecap="round"/>
                                                            </svg>
                                                            <span class="custom-upload-text">{{ __('Add File') }}</span>
                                                        </div>

                                                        <p class="preview-file d-none"></p>
                                                        <input type="file" name="attachment" class="preview-file-input">
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            @foreach ($gateway->manual_data['label'] ?? [] as $key => $row)
                                            <div class="form-group mt-3">
                                                <label for="">{{ $row }}</label>
                                                <input type="text" name="manual_data[]" @required($gateway->manual_data['is_required'][$key] == 1) class="form-control" placeholder="{{ __('Enter ').$row }}">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-md payment-btn">{{ __('Pay Now') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/css/payments.css') }}">
@endpush
