<div class="responsive-table mt-2">
  <table class="table" id="datatable">
    <thead>
        <tr>
            <th class="w-60">
                <div class="d-flex align-items-center gap-3">
                    <input type="checkbox" class="select-all-delete multi-delete">
                </div>
            </th>
            <th>{{__('SL')}}</th>
            <th>{{__('Flag')}}</th>
            <th>{{__('Name')}}</th>
            <th>{{__('Locale')}}</th>
            <th>{{__('Is Active')}}</th>
            <th>{{__('Is Default')}}</th>
            <th>{{__('Action')}}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($languages as $language)
            <tr>
                <td class="w-60 checkbox">
                    <input type="checkbox" name="ids[]" class="delete-checkbox-item multi-delete" value="{{ $language->id }}">
                </td>
                <td>{{ $languages->firstItem() + $loop->index }}</td>
                <td>
                    <img src="{{ asset($language->icon ?? 'assets/img/icon/no-img-available.jpg') }}" class="table-product-img" alt="">
                </td>
                <td>{{ $language->name }}</td>
                <td>{{ $language->code }}</td>
                <td>
                    <label class="switch">
                        <input type="checkbox" {{ $language->status == 1 ? 'checked' : '' }} class="status"
                            data-url="{{ route('admin.languages.status', $language->id) }}">
                        <span class="slider round"></span>
                    </label>
                </td>
                 <td>
                   <label class="switch">
                        <input type="checkbox" {{ $language->is_default == 1 ? 'checked' : '' }} class="default-lang"
                            data-url="{{ route('admin.languages.default', $language->id) }}">
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
                                <a href="#" class="language-edit-btn" data-bs-toggle="modal" data-bs-target="#editLanguageModal"
                                    data-url="{{ route('admin.languages.update', $language->id) }}"
                                    data-name="{{ $language->name }}" data-code="{{ $language->code }}"
                                    data-order="{{ $language->order }}"
                                    data-icon="{{ asset($language->icon ?? 'assets/img/icon/no-img-available.jpg') }}">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12.1606 3.73679L13.2119 2.68547C13.7925 2.10484 14.7339 2.10484 15.3145 2.68547C15.8951 3.2661 15.8951 4.20748 15.3145 4.78811L14.2632 5.83943M12.1606 3.73679L8.23515 7.66222C7.4512 8.4462 7.05919 8.83815 6.79228 9.31582C6.52535 9.7935 6.2568 10.9214 6 12C7.07857 11.7432 8.2065 11.4746 8.68418 11.2077C9.16185 10.9408 9.5538 10.5488 10.3378 9.76485L14.2632 5.83943M12.1606 3.73679L14.2632 5.83943" stroke="#4B5563" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M15.75 9C15.75 12.1819 15.75 13.773 14.7615 14.7615C13.773 15.75 12.1819 15.75 9 15.75C5.81802 15.75 4.22703 15.75 3.23851 14.7615C2.25 13.773 2.25 12.1819 2.25 9C2.25 5.81802 2.25 4.22703 3.23851 3.23851C4.22703 2.25 5.81802 2.25 9 2.25" stroke="#4B5563" stroke-width="1.2" stroke-linecap="round"/>
                                    </svg>
                                    {{__('Edit')}}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.languages.show', $language->code ?? '') }}">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2.25 9.75V6H15.75V9.75C15.75 12.5784 15.75 13.9927 14.8713 14.8713C13.9927 15.75 12.5784 15.75 9.75 15.75H8.25C5.42157 15.75 4.00736 15.75 3.12868 14.8713C2.25 13.9927 2.25 12.5784 2.25 9.75Z" stroke="#4B5563" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M2.25 6L2.89904 4.55769C3.40241 3.4391 3.65408 2.87981 4.16423 2.5649C4.67438 2.25 5.32875 2.25 6.6375 2.25H11.3625C12.6712 2.25 13.3256 2.25 13.8358 2.5649C14.3459 2.87981 14.5976 3.4391 15.1009 4.55769L15.75 6" stroke="#4B5563" stroke-linecap="round"/>
                                        <path d="M9 6V2.25" stroke="#4B5563" stroke-linecap="round"/>
                                        <path d="M6.375 10.125H10.5C11.3284 10.125 12 10.7966 12 11.625C12 12.4534 11.3284 13.125 10.5 13.125H9.75M7.5 8.625L6 10.125L7.5 11.625" stroke="#4B5563" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    {{__('Translate')}}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.languages.destroy', $language->id) }}" class="confirm-action" data-method="DELETE">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14.625 4.125L14.1602 11.6438C14.0414 13.5648 13.9821 14.5253 13.5006 15.2159C13.2625 15.5573 12.956 15.8455 12.6005 16.062C11.8816 16.5 10.9192 16.5 8.99452 16.5C7.06734 16.5 6.10372 16.5 5.38429 16.0612C5.0286 15.8443 4.722 15.5556 4.48401 15.2136C4.00266 14.5219 3.94459 13.5601 3.82846 11.6364L3.375 4.125" stroke="#4B5563" stroke-width="1.2" stroke-linecap="round"/>
                                        <path d="M6.75 8.79688H11.25" stroke="#4B5563" stroke-width="1.2" stroke-linecap="round"/>
                                        <path d="M7.875 11.7422H10.125" stroke="#4B5563" stroke-width="1.2" stroke-linecap="round"/>
                                        <path d="M2.25 4.125H15.75M12.0416 4.125L11.5297 3.0688C11.1896 2.3672 11.0195 2.01639 10.7261 1.79761C10.6611 1.74908 10.5922 1.7059 10.5201 1.66852C10.1953 1.5 9.80542 1.5 9.02572 1.5C8.22645 1.5 7.82685 1.5 7.49662 1.67559C7.42343 1.71451 7.35359 1.75943 7.28783 1.80988C6.99109 2.03753 6.82533 2.40116 6.49381 3.12844L6.03955 4.125" stroke="#4B5563" stroke-width="1.2" stroke-linecap="round"/>
                                    </svg>
                                    {{__('Delete') }}
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
    {{ $languages->links('vendor.pagination.bootstrap-5') }}
</div>
