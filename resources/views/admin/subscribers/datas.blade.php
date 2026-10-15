<div class="responsive-table m-0">
    <table class="table" id="datatable">
        <thead>
            <tr>
                <th>{{ __('SL') }}.</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Shop Name') }}</th>
                <th>{{ __('Category') }}</th>
                <th>{{ __('Package') }}</th>
                <th>{{ __('Started') }}</th>
                <th>{{ __('End') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('Method') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscribers as $subscriber)
                <tr>
                    <td>{{ ($subscribers->currentPage() - 1) * $subscribers->perPage() + $loop->iteration }} <i class="{{ request('id') == $subscriber->id ? 'fas fa-bell text-red' : '' }}"></i>
                    </td>
                    <td>{{ formatted_date($subscriber->created_at) }}</td>
                    <td>{{ $subscriber->business->companyName ?? 'N/A' }}</td>
                    <td>{{ $subscriber->business?->category?->name ?? 'N/A' }}</td>
                    <td>{{ $subscriber->plan->subscriptionName ?? 'N/A' }}</td>
                    <td>{{ formatted_date($subscriber->created_at) }}</td>
                    <td>
                        {{ $subscriber->created_at ? formatted_date($subscriber->created_at->addDays($subscriber->duration)) : '' }}
                    </td>
                    <td>
                        {{ currency_format($subscriber->plan?->offerPrice ?: $subscriber->plan?->subscriptionPrice) }}
                    </td>
                    <td>{{ $subscriber->gateway->name ?? 'N/A' }}</td>
                    <td>
                        <div
                            class="badge bg-{{ $subscriber->payment_status == 'reject' ? 'danger' : ($subscriber->payment_status == 'unpaid' ? 'warning' : 'primary') }}">
                            {{ ucfirst($subscriber->payment_status) }}
                        </div>
                    </td>
                    <td>
                        <div class="dropdown table-action">
                            <a title="{{__('Invoice')}}" data-bs-toggle="tooltip" target="_blank"
                                href="{{ route('admin.subscription-orders.invoice', $subscriber->id) }}">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 7.125C3.42893 7.125 1.75 8.80393 1.75 10.875V16.875C1.75 17.8415 2.5335 18.625 3.5 18.625H7.5V16.375H17.5V18.625H21.5C22.4665 18.625 23.25 17.8415 23.25 16.875V10.875C23.25 8.80393 21.5711 7.125 19.5 7.125H5.5ZM19 10.375C18.4477 10.375 18 10.8227 18 11.375C18 11.9273 18.4477 12.375 19 12.375H19.009C19.5613 12.375 20.009 11.9273 20.009 11.375C20.009 10.8227 19.5613 10.375 19.009 10.375H19Z" fill="#A7A7A7"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.75 3.375C6.75 2.13236 7.75736 1.125 9 1.125H16C17.2426 1.125 18.25 2.13236 18.25 3.375V5.375C18.25 5.78921 17.9142 6.125 17.5 6.125H7.5C7.08579 6.125 6.75 5.78921 6.75 5.375V3.375Z" fill="#A7A7A7"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.5 15.875C6.5 15.3227 6.94772 14.875 7.5 14.875H17.5C18.0523 14.875 18.5 15.3227 18.5 15.875V19.875C18.5 21.5319 17.1569 22.875 15.5 22.875H9.5C7.84315 22.875 6.5 21.5319 6.5 19.875V15.875ZM8.5 16.875V19.875C8.5 20.4273 8.94772 20.875 9.5 20.875H15.5C16.0523 20.875 16.5 20.4273 16.5 19.875V16.875H8.5Z" fill="#A7A7A7"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>
</div>
<div class="mt-3">
    {{ $subscribers->links('vendor.pagination.bootstrap-5') }}
</div>
