(function ($) {
    "use strict";

    let modalSubtotal = 0;

    function recalcModal() {
        const dVal = parseFloat($("#modal_discount_amount").val()) || 0;
        const dType = $('select[name="discount_type"]').val();
        const vVal = parseFloat($("#modal_vat_amount").val()) || 0;
        const sVal = parseFloat($("#modal_shipping_charge").val()) || 0;
        const sub = modalSubtotal;

        const discountAmt = dType === "percent" ? Math.min(sub * dVal / 100, sub) : Math.min(dVal, sub);
        const rawTotal = sub - discountAmt + vVal + sVal;
        const calcTotal = Math.max(rawTotal, 0);
        const rounding = calcTotal - Math.round(calcTotal);
        const payable = Math.round(calcTotal);

        $("#modal_sub_total").text(currencyFormat(sub));
        $("#modal_total_amount").text(currencyFormat(payable));
        $("#modal_rounding").text(currencyFormat(Math.abs(rounding)));
        $("#modal_payable_amount").text(currencyFormat(payable));
        $("#modal_calculated_total").val(payable);

        const currentReceive = parseFloat($("#modal_receive_amount").val()) || 0;
        if (currentReceive > payable) {
            $("#modal_receive_amount").val(payable);
        }
        $("#modal_receive_amount").trigger("input");
    }

    function updateDiscountMax() {
        const dType = $('select[name="discount_type"]').val();
        const $input = $("#modal_discount_amount");
        const max = dType === "percent" ? 100 : modalSubtotal;
        $input.attr("max", max > 0 ? max : 0);
        if (parseFloat($input.val()) > max) {
            $input.val(max);
            populateVatAmount();
        }
    }

    function populateVatAmount() {
        const $opt = $('select[name="vat_id"]').find(":selected");
        const rate = parseFloat($opt.data("rate")) || 0;
        const sub = modalSubtotal;
        const dVal = parseFloat($("#modal_discount_amount").val()) || 0;
        const dType = $('select[name="discount_type"]').val();
        const discountAmt = dType === "percent" ? Math.min(sub * dVal / 100, sub) : Math.min(dVal, sub);
        const afterDiscount = sub - discountAmt;
        const vatAmt = rate > 0 ? (afterDiscount * rate) / (100 + rate) : 0;
        $("#modal_vat_amount").val(vatAmt.toFixed(2));
        recalcModal();
    }

    $(document).on("click", ".convert-to-sale-trigger", function () {
        const $btn = $(this);
        const quotationId = $btn.data("quotation-id");

        modalSubtotal = parseFloat($btn.data("subtotal")) || 0;
        const discount = parseFloat($btn.data("discount")) || 0;
        const discountType = $btn.data("discount-type") || "flat";
        const vat = parseFloat($btn.data("vat")) || 0;
        const vatId = $btn.data("vat-id") || "";
        const shipping = parseFloat($btn.data("shipping")) || 0;
        const total = parseFloat($btn.data("total")) || 0;

        $("#convert_quotation_id").val(quotationId);
        $("#convertToSaleForm").attr("action", $btn.data("convert-url"));

        $("#modal_sub_total").text(currencyFormat(modalSubtotal));

        $('select[name="discount_type"]').val(discountType);
        $("#modal_discount_amount").val(discount);
        $('select[name="vat_id"]').val(vatId);
        $("#modal_shipping_charge").val(shipping);

        updateDiscountMax();
        populateVatAmount();

        $("#modal_receive_amount").val(total).trigger("input");
        $("#modal_change_amount").val("0");
        $("#modal_due_amount").val("0");
        $("#convertToSaleForm .main-select").val("cash").trigger("change");
        const note = $btn.data("note") || "";
        $('#convertToSaleForm input[name="note"]').val(note);

        const $container = $("#convertToSaleForm .payment-main-container");
        $container.empty();
        $("#convertToSaleForm .main-select").removeClass("d-none");
        $("#modal_receive_amount").prop("readonly", false);
        $("#convertToSaleForm .add-payment-button").show();
    });

    $(document).on("change input", "#modal_discount_amount, #modal_shipping_charge", function () {
        const $this = $(this);
        if ($this.is("#modal_discount_amount")) {
            const max = parseFloat($this.attr("max"));
            if (max >= 0 && parseFloat($this.val()) > max) {
                $this.val(max);
            }
        }
        populateVatAmount();
    });

    $(document).on("change", 'select[name="discount_type"]', function () {
        updateDiscountMax();
        populateVatAmount();
    });

    $(document).on("change", 'select[name="vat_id"]', function () {
        populateVatAmount();
    });

    $(document).on("input", "#modal_receive_amount", function () {
        const payable = parseFloat($("#modal_payable_amount").text().replace(/[^0-9.-]+/g, "")) || 0;
        const receive = parseFloat($(this).val()) || 0;

        const change = receive > payable ? receive - payable : 0;
        const due = payable > receive ? payable - receive : 0;

        $("#modal_change_amount").val(change.toFixed(2));
        $("#modal_due_amount").val(due.toFixed(2));
    });

    $(document).on("change", "#convertToSaleForm .main-select", function () {
        if ($(this).val() === "online") {
            const payable = parseFloat($("#modal_payable_amount").text().replace(/[^0-9.-]+/g, "")) || 0;
            $("#modal_receive_amount").val(payable).prop("readonly", true).trigger("input");
            $("#convertToSaleForm .add-payment-button").hide();
        } else {
            $("#modal_receive_amount").prop("readonly", false);
            $("#convertToSaleForm .add-payment-button").show();
        }
    });

    $(document).on("submit", "#convertToSaleForm", function (e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find(".convert-btn");
        const originalText = $btn.html();

        const formData = new FormData(this);

        const mainType = $form.find(".main-select").val();
        let payments = { main: { type: mainType, amount: 0 } };

        $form.find(".payment-wrapper").each(function (i) {
            const type = $(this).find(".payment-type-select").val();
            const amount = parseFloat($(this).find(".amount").val()) || 0;
            const cheque = $(this).find('input[name*="cheque_number"]').val() || "";
            payments["extra_" + i] = { type: type, amount: amount, cheque_number: cheque };
        });

        // Validate: overpayment only allowed for cash payments
        var changeAmount = parseFloat($("#modal_change_amount").val()) || 0;
        if (changeAmount > 0) {
            var hasCash = false;
            if (mainType && mainType.toLowerCase() === 'cash') hasCash = true;
            $form.find(".payment-type-select").each(function () {
                var val = $(this).val();
                if (val && val.toLowerCase() === 'cash') hasCash = true;
            });
            if (!hasCash) {
                toastr.error("Overpayment/change is only allowed for cash payments.");
                $btn.html(originalText).attr("disabled", false);
                return;
            }
        }

        formData.append("payments", JSON.stringify(payments));

        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $btn.html($savingLoader).attr("disabled", true);
            },
            success: function (res) {
                $btn.html(originalText).attr("disabled", false);
                if (res.redirect) {
                    sessionStorage.hasPreviousMessage = true;
                    sessionStorage.previousMessage = res.message ?? null;
                    location.href = res.redirect;
                } else {
                    Notify("success", null, res.message ?? "Converted successfully.");
                    $("#convertToSaleModal").modal("hide");
                }
            },
            error: function (err) {
                $btn.html(originalText).attr("disabled", false);
                Notify("error", err);
            }
        });
    });

})(jQuery);
