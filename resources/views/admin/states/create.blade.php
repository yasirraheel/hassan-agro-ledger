<div class="modal modal-md fade" id="create-state-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('Create State') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.states.store') }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload ">
                    @csrf

                    <div class="col-lg-12 mb-2">
                        <label>{{ __('Select Country') }}</label>
                        <div class="gpt-up-down-arrow position-relative">
                            <select name="country_id" required class="form-control table-select w-100 role">
                                <option value=""> {{ __('Select one') }}</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}"> {{ ucfirst($country->name) }} </option>
                                @endforeach
                            </select>
                            <span></span>
                        </div>
                    </div>

                    <div class="col-lg-12 mt-2">
                        <label>{{ __('Name') }}</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('Enter Name') }}">
                    </div>

                    <div class="col-lg-12">
                        <div class="button-group text-center mt-5">
                            <button type="reset" class="theme-btn border-btn m-2">{{ __('Cancel') }}</button>
                            <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
