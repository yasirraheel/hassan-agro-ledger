<div class="responsive-table m-0">
    <table class="table" id="erp-table">
        <thead>
            <tr>
                <th>
                    <div class="d-flex align-items-center gap-1">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                        <i class="fal fa-trash-alt delete-selected"></i>
                    </div>
                </th>
                <th>{{ __('SL') }}.</th>
                <th>{{ __('Image') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="d-print-none">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($countries as $country)
                <tr>
                    <td class="w-60 checkbox text-start">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item" value="{{ $country->id }}" data-url="{{ route('admin.countries.delete-all') }}">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                        <i></i>
                    </td>

                    <td>{{ ($countries->currentPage() - 1) * $countries->perPage() + $loop->iteration }} <i class="{{ request('id') == $country->id ? 'fas fa-bell text-red' : '' }}"></i></td>
                    <td>
                        <img class="table-img" src="{{ asset($country->image ?? '') }}" alt="img">
                    </td>
                    <td>{{ $country->name }}</td>
                    <td>{{ $country->code }}</td>

                    <td class="text-center w-150">
                        <label class="switch">
                            <input type="checkbox" {{ $country->status == 1 ? 'checked' : '' }} class="status" data-url="{{ route('admin.countries.status', $country->id) }}">
                            <span class="slider round"></span>
                        </label>
                    </td>

                    <td class="d-print-none">
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="{{ route('admin.countries.edit', $country->id) }}">
                                        <i class="fal fa-pencil-alt"></i>
                                        {{ __('Edit') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.countries.destroy', $country->id) }}"
                                        class="confirm-action" data-method="DELETE">
                                        <i class="fal fa-trash-alt"></i>
                                        {{ __('Delete') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div>
    {{ $countries->links('vendor.pagination.bootstrap-5') }}
</div>
