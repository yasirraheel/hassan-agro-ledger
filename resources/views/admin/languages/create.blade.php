<div class="modal modal-md fade" id="addLanguageModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('Add Language') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="{{ route('admin.languages.store') }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload">

                    <div>
                        <label>{{ __('Name') }}</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('Enter Name') }}">
                    </div>

                    <div>
                        <label>{{ __('Locale') }}</label>
                        <input type="text" name="code" required class="form-control" placeholder="{{ __('Enter Locale') }}">
                    </div>

                    <div>
                        <label>{{ __('Order') }}</label>
                        <input type="number" name="order" required class="form-control" placeholder="{{ __('Ex-1') }}">
                    </div>

                    <div class="language-upload-wrapper">
                        <label class="language-upload-label">{{ __('Flag') }}</label>

                        <div class="language-upload-box" id="languageUploadBox">
                            <input type="file" name="icon" id="languageFileInput" accept="image/*" hidden>

                            <div class="language-upload-content" id="languageUploadContent">
                                <div class="language-upload-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.5 12.5V15.8333C17.5 16.2754 17.3244 16.6993 17.0118 17.0118C16.6993 17.3244 16.2754 17.5 15.8333 17.5H4.16667C3.72464 17.5 3.30072 17.3244 2.98816 17.0118C2.67559 16.6993 2.5 16.2754 2.5 15.8333V12.5" stroke="#999999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M14.1654 6.66667L9.9987 2.5L5.83203 6.66667" stroke="#999999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10 2.5V12.5" stroke="#999999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <p>{{ __('Drag & drop your Image') }}</p>
                            </div>

                            <img id="languagePreviewImage" alt="Preview">
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="col-lg-12">
                        <div class="button-group text-center mt-3">
                            <button type="reset" class="theme-btn border-btn m-2">{{ __('Cancel') }}</button>
                            <button type="submit" class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
