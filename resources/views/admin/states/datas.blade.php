<div class="responsive-table m-0">
    <table class="table" id="datatable">
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
                <th>{{ __('SL') }}</th>
                <th>{{ __('Country') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($states as $state)
                <tr>
                    <td class="w-60 checkbox text-start">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                                value="{{ $state->id }}" data-url="{{ route('admin.states.delete-all') }}">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                    </td>

                    <td>{{ $states->perPage() * ($states->currentPage() - 1) + $loop->iteration }}</td>

                    <td>{{ $state->country?->name }}</td>
                    <td>{{ $state->name }}</td>

                    <td class="text-center">
                        <label class="switch">
                            <input type="checkbox" {{ $state->status == 1 ? 'checked' : '' }} class="status"
                                data-url="{{ route('admin.states.status', $state->id) }}">
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#edit-state-modal" class="state-edit-btn" data-bs-toggle="modal"
                                        data-url="{{ route('admin.states.update', $state->id) }}"
                                        data-name="{{ $state->name }}"
                                        data-country-id="{{ $state->country_id }}"
                                        >
                                        <i class="fal fa-edit"></i>
                                        {{ __('Edit') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.states.destroy', $state->id) }}" class="confirm-action"
                                        data-method="DELETE">
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
<div class="mt-3">
    {{ $states->links('vendor.pagination.bootstrap-5') }}
</div>
