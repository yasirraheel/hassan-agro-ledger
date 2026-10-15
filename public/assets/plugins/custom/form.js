"use strict";

const CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content");

$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": CSRF_TOKEN,
    },
});

let $savingLoader = `<div class="spinner-border spinner-border-sm custom-text-primary" role="status">
    <span class="visually-hidden">${window.trans?.loading ?? "Loading..."}</span>
</div>`;

const $ajaxform = $(".ajaxform");

// Init validation
$ajaxform.initFormValidation();

// Handle submit
$(document).on("submit", ".ajaxform", function (e) {
    e.preventDefault();
    
    const $form = $(this);
    const $btn = $form.find(".submit-btn");
    const originalText = $btn.html();

    if (!$ajaxform.valid()) {
        return;
    }

    $.ajax({
        type: "POST",
        url: this.action,
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,

        beforeSend: function () {
            $btn.html($savingLoader).attr("disabled", true);
        },

        success: function (res) {
            $btn.html(originalText)
                .removeClass("disabled")
                .attr("disabled", false);

            if (res.redirect) {
                sessionStorage.hasPreviousMessage = true;
                sessionStorage.previousMessage = res.message ?? null;

                if (res.secondary_redirect_url) {
                    window.open(res.secondary_redirect_url, "_blank");
                }

                window.location.href = res.redirect;
                return;
            }

            Notify("success", null, res ?? "Operation Successful.");
        },

        error: function (err) {
            $btn.html(originalText).attr("disabled", false);
            Notify("error", err);
        },
    });
});

let $ajaxform_instant_reload = $(".ajaxform_instant_reload");
$ajaxform_instant_reload.initFormValidation(),
    $(document).on("submit", ".ajaxform_instant_reload", function (e) {
        e.preventDefault();
        let t = $(this).find(".submit-btn"),
            a = t.html();
        $ajaxform_instant_reload.valid() &&
            $.ajax({
                type: "POST",
                url: this.action,
                data: new FormData(this),
                dataType: "json",
                contentType: !1,
                cache: !1,
                processData: !1,
                positionClass: "toast-top-left",
                beforeSend: function () {
                    t.html($savingLoader)
                        .addClass("disabled")
                        .attr("disabled", !0);
                },
                success: function (e) {
                    t.html(a).removeClass("disabled").attr("disabled", !1),
                        (window.sessionStorage.hasPreviousMessage = !0),
                        (window.sessionStorage.previousMessage =
                            e.message ?? null),
                        e.redirect && (location.href = e.redirect);

                    if (e.billing_address !== undefined) {
                        $("#billing_address").text(e.billing_address || "No Address");
                        $('.edit-address-btn[data-address-type="billing"]')
                            .attr("data-billing-address", e.billing_address);
                    }
                    if (e.shipping_address !== undefined) {
                        $("#shipping_address").text(e.shipping_address || "No Address");
                        $('.edit-address-btn[data-address-type="shipping"]')
                            .attr("data-shipping-address", e.shipping_address);
                    }
                    Notify("success", null, e.message ?? "Operation successful.");
                    $(".modal.show").modal("hide");
                },
                error: function (e) {
                    t.html(a).removeClass("disabled").attr("disabled", false);

                    if (e.responseJSON?.redirect) {
                        sessionStorage.hasPreviousMessage = true;
                        sessionStorage.previousMessage = e.responseJSON?.message ?? null;
                        sessionStorage.notifyType = 'warning';
                        location.href = e.responseJSON?.redirect;
                        return;
                    }

                    showInputErrors(e.responseJSON);
                    Notify("error", e);
                },
            });
    });

// bulk product upload in cart
let $cartUploadform = $(".bulk_cart_upload_form");
$cartUploadform.initFormValidation();
$(document).on("submit", ".bulk_cart_upload_form", function (e) {
    e.preventDefault();

    let t = $(this).find(".submit-btn"),
        a = t.html();

    if (!$cartUploadform.valid()) return;

    $.ajax({
        type: "POST",
        url: this.action,
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
            t.html($savingLoader).attr("disabled", true);
        },
        success: function (response) {
            t.html(a).removeClass("disabled").attr("disabled", false);

            if (response.success) {
                fetchUpdatedCart(calTotalAmount);
                $("#bulk-upload-modal").modal("hide");
                $cartUploadform[0].reset();
                toastr.success(response.message);

            } else {
                toastr.error(response.message || "Some items failed to import.");
            }
        },
        error: function (xhr) {
            t.html(a).attr("disabled", false);

            // Show error from server response
            if (xhr.responseJSON && xhr.responseJSON.message) {
                toastr.error(xhr.responseJSON.message);
            } else if (xhr.responseText) {
                toastr.error(xhr.responseText);
            } else {
                toastr.error("Something went wrong.");
            }
        }
    });
});

// login form
const $loginForm = $(".login_form");
$loginForm.initFormValidation();
$(document).on("submit", ".login_form", function (e) {
    e.preventDefault();

    const $submitButton = $(this).find(".submit-btn");
    const originalButtonText = $submitButton.html();

    if (!$loginForm.valid()) return;

    $.ajax({
        type: "POST",
        url: this.action,
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        positionClass: "toast-top-left",
        beforeSend: function () {
            $submitButton
                .html($savingLoader)
                .addClass("disabled")
                .attr("disabled", true);
        },
        success: function (response) {
            $submitButton
                .html(originalButtonText)
                .removeClass("disabled")
                .attr("disabled", false);

            window.sessionStorage.hasPreviousMessage = true;
            window.sessionStorage.previousMessage = response.message || null;

            if (response.redirect) {
                location.href = response.redirect;
            }
        },
        error: function (error) {
            let response = error.responseJSON;

            $submitButton
                .html(originalButtonText)
                .removeClass("disabled")
                .attr("disabled", false);

            if (response.redirect) {
                window.sessionStorage.hasPreviousMessage = true;
                window.sessionStorage.notifyType = "warning";
                window.sessionStorage.previousMessage = response.message || null;
                location.href = response.redirect;
            } else {
                showInputErrors(error.responseJSON);
                Notify("error", error);
            }
        },
    });
});

// sign up form
let $sign_up_form = $(".sign_up_form");
$sign_up_form.initFormValidation();

$(document).on("submit", ".sign_up_form", function (e) {
    e.preventDefault();

    let t = $(this).find(".submit-btn"),
        a = t.html();

    if ($sign_up_form.valid()) {
        $.ajax({
            type: "POST",
            url: this.action,
            data: new FormData(this),
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                t.html($savingLoader) // Show loader
                    .addClass("disabled")
                    .attr("disabled", true);
            },
            success: function (response) {
                if (response.business_categories.length > 0) {
                    response.business_categories.forEach((category) => {
                        $(".business-categories").append(
                            `<option value="${category.id}">${category.name}</option>`
                        );
                    })
                }

                $("#createFreeAccount").modal("hide");

                if (response.otp_expiration) {
                    $("#dynamicEmail").text(response.email);
                    $("#verifymodal").modal("show");
                    startCountdown(response.otp_expiration);
                } else {
                    $("#setupAccountModal").modal("show");
                }
            },
            error: function (e) {
                // Handle error response
                showInputErrors(e.responseJSON);
                Notify("error", e);
            },
            complete: function () {
                t.html(a).removeClass("disabled").attr("disabled", false);
            },
        });
    }
});

// Verify OTP submission
let $verify_form = $(".verify_form");
$verify_form.initFormValidation();

$(document).on("submit", ".verify_form", function (e) {
    e.preventDefault();

    let t = $(this).find(".submit-btn"),
        a = t.html();

    const email = $("#dynamicEmail").text();

    // Get the OTP input values from the form
    const otpInputs = $(this).find(".otp-input");
    let otpValues = otpInputs
        .map(function () {
            return $(this).val();
        })
        .get()
        .join("");

    // Validate OTP and form before submitting
    if ($verify_form.valid()) {
        let formData = new FormData(this);
        formData.append("email", email);
        formData.append("otp", otpValues);

        $.ajax({
            type: "POST",
            url: this.action,
            data: formData,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                t.html($savingLoader).addClass("disabled").attr("disabled", !0);
            },
            success: function (res) {
                t.html(a).removeClass("disabled").attr("disabled", false);
                $("#verifymodal").modal("hide");
                $("#setupAccountModal").modal("show");
                if (res.business_categories.length > 0) {
                    res.business_categories.forEach((category) => {
                        $(".business-categories").append(
                            `<option value="${category.id}">${category.name}</option>`
                        );
                    })
                }
            },
            error: function (response) {
                t.html(a).removeClass("disabled").attr("disabled", false);
                toastr.error(
                    response.responseJSON.message || "An error occurred."
                );
            },
        });
    } else {
        toastr.error("Please enter all OTP digits.");
    }
});

// business setup form
let $business_setup_form = $(".business_setup_form");
$business_setup_form.initFormValidation();

$(document).on("submit", ".business_setup_form", function (e) {
    e.preventDefault();

    let t = $(this).find(".submit-btn"),
        a = t.html();

    if ($business_setup_form.valid()) {
        $.ajax({
            type: "POST",
            url: this.action,
            data: new FormData(this),
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                t.html($savingLoader) // Show loader
                    .addClass("disabled")
                    .attr("disabled", true);
            },
            success: function (res) {
                if (res.redirect) {
                    window.sessionStorage.hasPreviousMessage = true;
                    window.sessionStorage.previousMessage = res.message || 'Operation successfully.';
                    location.href = res.redirect;
                } else {
                    $("#setupAccountModal").modal("hide");
                    $("#successModal").modal("show");
                    Notify("success", null, res.message ?? 'Operation Successful.');
                }
            },
            error: function (e) {
                // Handle error response
                showInputErrors(e.responseJSON);
                Notify("error", e);
            },
            complete: function () {
                t.html(a).removeClass("disabled").attr("disabled", false);
            },
        });
    }
});

// OTP input field--------------------->
const pinInputs = document.querySelectorAll(".pin-input");

pinInputs.forEach((inputField, index) => {
    inputField.addEventListener("input", () => {
        inputField.value = inputField.value.replace(/[^0-9]/g, "").slice(0, 1);

        if (inputField.value && index < pinInputs.length - 1) {
            pinInputs[index + 1].focus();
        }
    });

    inputField.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !inputField.value && index > 0) {
            pinInputs[index - 1].focus();
        }
    });

    inputField.addEventListener("paste", (e) => {
        e.preventDefault();
    });
});

function showInputErrors(e) {
    if (e.errors !== undefined) {
        $.each(e.errors, function (field, message) {
            $("#" + field + "-error").remove();

            let errorLabel = `
                <label id="${field}-error" class="error" for="${field}">${message}</label>
            `;

            $("#" + field)
                .parents()
                .hasClass("form-check")
                ? $("#" + field).parents().find(".form-check").append(errorLabel)
                : $("#" + field).addClass("error").after(errorLabel);
        });
    }
}

function ajaxSuccess(response, Notify) {
    if (response.redirect) {
        if (response.message) {
            window.sessionStorage.hasPreviousMessage = true;
            window.sessionStorage.previousMessage = response.message ?? null;
        }

        location.href = response.redirect;
    } else if (response.message) {
        Notify("success", response);
    }
}

//PREVIEW IMAGE
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            var inputId = $(input).attr("id");

            // Select the image element based on the input's ID
            var imageElement = $("img.product-img").filter(function () {
                return $(this).closest("label").attr("for") === inputId;
            });
            imageElement.attr("src", e.target.result);
            imageElement.hide().fadeIn(650);
        };

        reader.readAsDataURL(input.files[0]);
    }
}

// Status button Change
$(".change-text").on("change", function () {
    var $dynamicText = $(this).closest(".form-control").find(".dynamic-text");

    if (this.checked) {
        $dynamicText.text("Active");
    } else {
        $dynamicText.text("Deactive");
    }
});

// Status button Change
$(".cnge-text").on("change", function () {
    var $test = $(this).closest(".form-control").find(".is-live-text");

    if (this.checked) {
        $test.text("Yes");
    } else {
        $test.text("No");
    }
});

/** STATUS CHANGE */
$(".status").on("change", function () {
    var checkbox = $(this);
    var status = checkbox.prop("checked") ? 1 : 0;
    var url = checkbox.data("url");
    var method = checkbox.data("method");

    $.ajax({
        url: url,
        type: method ?? "POST",
        data: {
            status: status,
        },
        success: function (res) {
            if(status === 1){
                toastr.success(res.message + ' ' + window.trans.status_published);
            }
            else{
                toastr.success(res.message + ' ' + window.trans.status_unpublished);
            }
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON.message);
        },
    });
});

$(document).ready(function () {

    const urlParams = new URLSearchParams(window.location.search);
    const success_modal = urlParams.get('success_modal');
    const setup_business = urlParams.get('setup_business');

    if (success_modal == 1) {
        $("#successModal").modal("show");
    }

    if (setup_business == 1) {
        $.ajax({
            type: "GET",
            url: $("#get-business-categories").val(),
            success: function (res) {
                $("#setupAccountModal").modal("show");
                if (res.length > 0) {
                    res.forEach((category) => {
                        $(".business-categories").append(
                            `<option value="${category.id}">${category.name}</option>`
                        );
                    })
                }
            }
        });
    }

    /** SEARCH */
    $(".searchInput").on("input", function (e) {
        e.preventDefault();
        const searchText = $(this).val();
        const url = $(this).attr("action");
        $.ajax({
            url: url,
            type: "GET",
            data: {
                search: searchText,
            },
            success: function (res) {
                $(".searchResults").html(res.data);
            },
            error: function (xhr) {
                console.log(xhr.responseText);
            },
        });
    });

    // Handle the "x" icon click event
    $(".clearSearchInput").on("click", function () {
        $(".searchInput").val(""); // Clear the search input
        $(".clearSearchInput").addClass("d-none");
        $(this).closest(".searchForm").submit();
    });

    // Show/hide "delete" button based on input value
    $(".searchInput").on("input", function () {
        if ($(this).val().trim() !== "") {
            $(".clearSearchInput").removeClass("d-none");
        } else {
            $(".clearSearchInput").addClass("d-none");
        }
    });

    // Select all checkboxes when the checkbox in the header is clicked
    $(document).on("click", ".selectAllCheckbox", function () {
        $(".checkbox-item").prop("checked", this.checked);
        if (this.checked) {
            $(".delete-selected").addClass("text-danger");
        } else {
            $(".delete-selected").removeClass("text-danger");
        }
    });

    // Perform the delete action for selected elements when the delete icon is clicked
    $(document).on("click", ".delete-selected", function () {
        var checkedCheckboxes = $(".checkbox-item:checked");
        if (checkedCheckboxes.length === 0) {
            toastr.error(
                "No items selected. Please select at least one item to delete."
            );
        } else {
            $("#multi-delete-modal").modal("show");
        }
    });

    $(".multi-delete-btn").on("click", function () {
        var ids = $(".checkbox-item:checked")
            .map(function () {
                return $(this).val();
            })
            .get();

        let submitButton = $(this);
        let originalButtonText = submitButton.html();
        let del_url = $(".checkbox-item").data("url");

        $.ajax({
            type: "POST",
            url: del_url,
            data: {
                ids,
            },
            dataType: "json",
            beforeSend: function () {
                submitButton.html($savingLoader).attr("disabled", true);
            },
            success: function (res) {
                submitButton.html(originalButtonText).attr("disabled", false);
                window.sessionStorage.hasPreviousMessage = true;
                window.sessionStorage.previousMessage = res.message ?? null;
                res.redirect && (location.href = res.redirect);
            },
            error: function (xhr) {
                submitButton.html(originalButtonText).attr("disabled", false);
                Notify("error", xhr);
            },
        });
    });
});

/** system setting start */
// Initial label text
var initialLabelText = $("#mail-driver-type-select option:selected").val();

$("#mail-driver-type-select").on("change", function () {
    var selectedOptionValue = $(this).val();
    $("#mail-driver-label").text(selectedOptionValue);
});

$("#mail-driver-label").text(initialLabelText);

/** system setting end */

//Subscriber view modal
$(".subscriber-view").on("click", function () {
    $(".business_name").text($(this).data("name"));
    $("#image").attr("src", $(this).data("image"));
    $("#category").text($(this).data("category"));
    $("#package").text($(this).data("package"));
    $("#gateway").text($(this).data("gateway"));
    $("#enroll_date").text($(this).data("enroll"));
    $("#expired_date").text($(this).data("expired"));

    var gateway_img = $(this).data("manul-attachment");

    if (gateway_img) {
        var img = new Image();
        img.onload = function () {
            $("#manual_img").removeClass('d-none');
            $("#manul_attachment").attr("src", gateway_img);
        };
        img.onerror = function () {
            $("#manual_img").addClass('d-none');
        };
        img.src = gateway_img;
    } else {
        $("#manual_img").addClass('d-none');
    }
});

$('.subscribe-plan').on('click', function(e) {
    $('#createFreeAccount').modal('show');

    $('#plan_id').val($(this).data('plan-id'));
    $('.google-login').attr('href', $(this).data('google-url'));
    $('.x-login').attr('href', $(this).data('twitter-url'));
})


// Affilite part
let $afl_sign_up_form = $(".afl_sign_up_form");
$afl_sign_up_form.initFormValidation();

$(document).on("submit", ".afl_sign_up_form", function (e) {
    e.preventDefault();

    let t = $(this).find(".submit-btn"),
        a = t.html();

    if ($afl_sign_up_form.valid()) {
        $.ajax({
            type: "POST",
            url: this.action,
            data: new FormData(this),
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                t.html($savingLoader) // Show loader
                    .addClass("disabled")
                    .attr("disabled", true);
            },
            success: function (response) {
                $("#dynamicEmail").text(response.email);
                $("#verifymodal").modal("show");
                startCountdown(response.otp_expiration);
            },
            error: function (e) {
                showInputErrors(e.responseJSON);
                Notify("error", e);
            },
            complete: function () {
                t.html(a).removeClass("disabled").attr("disabled", false);
            },
        });
    }
});

let $afl_verify_form = $(".afl_verify_form");
$afl_verify_form.initFormValidation();

$(document).on("submit", ".afl_verify_form", function (e) {
    e.preventDefault();

    let t = $(this).find(".submit-btn"),
        a = t.html();

    const email = $("#dynamicEmail").text();

    const otpInputs = $(this).find(".otp-input");
    let otpValues = otpInputs
        .map(function () {
            return $(this).val();
        })
        .get()
        .join("");

    if ($afl_verify_form.valid()) {
        let formData = new FormData(this);
        formData.append("email", email);
        formData.append("otp", otpValues);

        $.ajax({
            type: "POST",
            url: this.action,
            data: formData,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                t.html($savingLoader).addClass("disabled").attr("disabled", !0);
            },
            success: function (res) {
                t.html(a).removeClass("disabled").attr("disabled", false);
                if (res.redirect) {
                    window.sessionStorage.hasPreviousMessage = true;
                    window.sessionStorage.previousMessage = res.message || 'Operation successfully.';
                    location.href = res.redirect;
                } else {
                    $("#setupAccountModal").modal("hide");
                    Notify("success", null, res.message ?? 'Operation Successful.');
                }

            },
            error: function (response) {
                t.html(a).removeClass("disabled").attr("disabled", false);
                toastr.error(
                    response.responseJSON.message || "An error occurred."
                );
            },
        });
    } else {
        toastr.error("Please enter all OTP digits.");
    }
});

$("#payout_type").on("change", function () {
    var type = $(this).val();
    $('.payout-bank, .payout-mfs').addClass('d-none');

    if (type == 'bank') {
        $('.payout-bank').removeClass('d-none');
    } else if (type == 'mfs') {
        $('.payout-mfs').removeClass('d-none');
    }
});

// invoice setting done

$(".invoice-size-radio").on("change", function () {
    let $form = $(this).closest('.invoice_form');
    let submitUrl = $form.attr('action');

    $.ajax({
        type: "POST",
        url: submitUrl,
        data: new FormData($form[0]),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
            Notify("info", null, "Updating invoice setting...");
        },
        success: function (response) {
            Notify("success", null, response ?? 'Invoice setting updated successfully.');
        },
        error: function (xhr) {
            let errorMsg = xhr.responseJSON?.message || 'Something went wrong!';
            Notify("error", null, errorMsg);
        }
    });
});

$(document).on('change', '.serial-update', function () {
    // Get the checkbox value (1 if checked, 0 if unchecked)
    let show_serial = $(this).is(':checked') ? 1 : 0;

    // Update hidden input value (optional, keeps DOM in sync)
    $('#hidden_show_serial').val(show_serial);

    // Get the route from hidden input
    let route = $('#serial_route').val();

    // Send AJAX request
    $.ajax({
        url: route,
        type: 'POST',
        data: {
            show_serial: show_serial
        },
        success: function (res) {
            Notify('success', null, res.message ?? 'IMEI/Serial updated successfully.');
        },
        error: function (err) {
            Notify('error', err);
        }
    });
});

// ************ LANGUAGE START ************

const $addNewTranslationForm = $(".add_new_translation");
$addNewTranslationForm.initFormValidation();

$(document).on("submit", ".add_new_translation", function (event) {
    event.preventDefault();

    const $form = $(this);
    const $submitBtn = $form.find(".submit-btn");
    const originalBtnHtml = $submitBtn.html();

    // Stop if form is not valid
    if (!$addNewTranslationForm.valid()) {
        return;
    }

    $.ajax({
        type: "POST",
        url: $form.attr("action"),
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        beforeSend() {
            $submitBtn.html($savingLoader).addClass("disabled").attr("disabled", true);
        },
        success(res) {
            $submitBtn.html(originalBtnHtml).removeClass("disabled").attr("disabled", false);
            $('#addTraslationModal').modal('hide');
            Notify("success", null, res.message);

            let firstItem = parseInt($('#datatable').data('first-item')) || 1;
            let translation_rows = firstItem + $('#translation-rows tr').length;

            let key = $('.new_key').val();
            let value = $('.new_value').val();

            let newRow = `<tr>
                            <td>${translation_rows}</td>
                            <td class="text-start">${key}</td>
                            <td><input type="text" class="form-control value-input" name="translations[${key}]" id="value-${translation_rows}" value="${value}"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2 justify-content-center">
                                    <button type="button" class="traslation-save" data-row="${translation_rows}" data-url="${$('#update-url').val()}" data-key="${key}">${window.trans.save}</button>
                                    <a class="traslation-delete" href="">
                                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M16.8408 14.4032C16.7817 15.3562 16.7348 16.1125 16.6387 16.7165C16.5401 17.336 16.3824 17.8519 16.0667 18.3032C15.778 18.716 15.4063 19.0645 14.9753 19.3263C14.5041 19.6125 13.9779 19.7377 13.3516 19.7975L8.63283 19.7975C8.00586 19.7375 7.47913 19.6121 7.00759 19.3253C6.57623 19.063 6.2044 18.714 5.91579 18.3004C5.6003 17.8484 5.44302 17.3318 5.3452 16.7114C5.24981 16.1064 5.20397 15.3491 5.14619 14.3947L4.64453 6.10938H17.355L16.8408 14.4032ZM9.07393 16.1556C8.75485 16.1556 8.49618 15.9004 8.49618 15.5853V11.0226C8.49618 10.7076 8.75485 10.4522 9.07393 10.4522C9.393 10.4522 9.65167 10.7076 9.65167 11.0226V15.5853C9.65167 15.9004 9.393 16.1556 9.07393 16.1556ZM13.5033 11.0226C13.5033 10.7076 13.2446 10.4522 12.9256 10.4522C12.6065 10.4522 12.3478 10.7076 12.3478 11.0226V15.5853C12.3478 15.9004 12.6065 16.1556 12.9256 16.1556C13.2446 16.1556 13.5033 15.9004 13.5033 15.5853V11.0226Z" fill="white"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1179 2.22642C12.5875 2.26236 13.0289 2.38496 13.4079 2.62681C13.6883 2.80567 13.883 3.02461 14.0493 3.26173C14.2035 3.48146 14.3588 3.75541 14.5348 4.06618L14.8895 4.69206H18.4778C18.9368 4.69206 19.3088 5.01038 19.3088 5.40305C19.3088 5.79571 18.9368 6.11403 18.4778 6.11403C13.4912 6.11403 8.50513 6.11403 3.51857 6.11403C3.05959 6.11403 2.6875 5.79571 2.6875 5.40305C2.6875 5.01038 3.05959 4.69206 3.51857 4.69206H7.18337L7.47907 4.13711C7.65073 3.81489 7.80191 3.53111 7.95377 3.3034C8.11758 3.05777 8.3115 2.83047 8.59523 2.64426C8.97894 2.39243 9.4292 2.26476 9.90939 2.22736C10.2709 2.19921 10.6353 2.20308 10.9982 2.20358C11.4227 2.20416 11.8043 2.20243 12.1179 2.22642ZM9.01006 4.69206H13.0423C12.8539 4.35976 12.7334 4.14913 12.6242 3.99352C12.4644 3.76577 12.2729 3.66594 11.9698 3.64274C11.7543 3.62625 11.4735 3.62559 11.0268 3.62559C10.5691 3.62559 10.281 3.62627 10.0601 3.64347C9.74916 3.66768 9.55535 3.77132 9.39745 4.00807C9.29399 4.16321 9.18135 4.37089 9.01006 4.69206Z" fill="white"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>`

            $("#translation-rows").append(newRow);

            $form[0].reset();
        },
        error(xhr) {
            $submitBtn.html(originalBtnHtml).removeClass("disabled").attr("disabled", false);
            showInputErrors(xhr.responseJSON);
            Notify("error", xhr);
        }
    });
});

$(document).on('click', '.traslation-save', function() {
    let btn = $(this);
    let rowId = btn.data('row');
    let key = btn.data('key').trim();
    let value = $('#value-' + rowId).val().trim();
    let route = btn.data('url');

    const originalBtnHtml = btn.html();

    $.ajax({
        type: "PUT",
        url: route,
        data: {
            key: key,
            value: value,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend() {
            btn.html($savingLoader).addClass("disabled").attr("disabled", true);
        },
        success(res) {
            btn.html(originalBtnHtml).removeClass("disabled").attr("disabled", false);
            Notify("success", null, res.message);
        },
        error(xhr) {
            btn.html(originalBtnHtml).removeClass("disabled").attr("disabled", false);
            showInputErrors(xhr.responseJSON);
            Notify("error", xhr);
        }
    });
});

$(document).on('input', '.searchInput', function() {

    let btn = $(this);
    let route = btn.data('url');
    let search = btn.val();

    $.ajax({
        type: "GET",
        url: route,
        data: {
            search: search
        },
        success(res) {
            $('#translate-data').html(res.data);
        },
        error(xhr) {
            showInputErrors(xhr.responseJSON);
            Notify("error", xhr);
        }
    });
});

$(document).on('click', '.traslation-delete', function (e) {
    const btn = $(this);
    const route = btn.data('url');
    const key = btn.data('key').trim();
    const $row = $(this).closest('tr');

    if (!key) return;

    $.ajax({
        url: route,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            key: key
        },
        success: function (res) {
            toastr.success(res.message);
            $row.remove();
        },
        error: function (xhr) {
            Notify("error", xhr);
        }
    });
});

// ************ LANGUAGE END ************
