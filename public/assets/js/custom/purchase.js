$(document).ready(function () {
    var $sidebarPlan = $(".lg-sub-plan");
    var $subPlan = $(".sub-plan");
    var isActive = $(window).width() >= 1150;

    if (isActive) {
        $sidebarPlan.hide();
        $subPlan.show();

        $(".side-bar-addon, .side-bar-addon-2, .side-bar-addon-3").hide();
    } else {
        $sidebarPlan.show();
        $subPlan.hide();

        $(".side-bar-addon, .side-bar-addon-2, .side-bar-addon-3").show();
    }
});

// get number only
function getNumericValue(value) {
    return parseFloat(value.replace(/[^0-9.-]+/g, "")) || 0;
}

// Update the cart list and call the callback once complete
function fetchUpdatedCart(callback) {
    let url = $("#purchase-cart").val();
    $.ajax({
        url: url,
        type: "GET",
        success: function (response) {
            $("#purchase_cart_list").html(response);
            if (typeof callback === "function") callback(); // Call the callback after updating the cart
        },
    });
}

//increase quantity
$(document).on("click", ".plus-btn", function (e) {
    e.preventDefault();

    let $row = $(this).closest("tr");

    if (parseInt($row.data("has_serial")) === 1) return;

    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());

    let newQty = currentQty + 1;
    $qtyInput.val(newQty);
    updateCartQuantity(rowId, newQty, updateRoute);
});

//decrease quantity
$(document).on("click", ".minus-btn", function (e) {
    e.preventDefault();

    let $row = $(this).closest("tr");

    if (parseInt($row.data("has_serial")) === 1) return;

    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());

    // Ensure quantity does not go below 1
    if (currentQty > 1) {
        let newQty = currentQty - 1;
        $qtyInput.val(newQty);
        updateCartQuantity(rowId, newQty, updateRoute);
    }
});

// Cart quantity input field change event
$(document).on("change", ".cart-qty", function () {

    let $row = $(this).closest("tr");

    if (parseInt($row.data("has_serial")) === 1) return;

    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let newQty = parseFloat($(this).val());

    // Ensure quantity does not go below 1
    if (newQty >= 0) {
        updateCartQuantity(rowId, newQty, updateRoute);
    }
});

// Remove item from the cart
$(document).on("click", ".remove-btn", function (e) {
    e.preventDefault();
    var $row = $(this).closest("tr");
    var destroyRoute = $row.data("destroy_route");

    $.ajax({
        url: destroyRoute,
        type: "DELETE",
        success: function (response) {
            if (response.success) {
                // Item was successfully removed, fade out and remove the row from DOM
                $row.fadeOut(400, function () {
                    $(this).remove();
                });
                // Recalculate and update cart totals
                fetchUpdatedCart(function () {
                    calTotalAmount();
                    lockSerialCartRows();
                });

            } else {
                toastr.error(response.message || "Failed to remove item");
            }
        },
        error: function () {
            toastr.error("Error removing item from cart");
        },
    });
});

function updateCartQuantity(rowId, qty, updateRoute, serials = null) {
        $.ajax({
        url: updateRoute,
        type: "PUT",
        data: {
            rowId: rowId,
            qty: qty,
            serial_numbers: serials
        },
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(function () {
                    calTotalAmount();
                    lockSerialCartRows();
                });
            } else {
                toastr.error(response.message || "Failed to update cart");
            }
        },
        error: function () {
            toastr.error("Error updating cart");
        }
    });
}

// Clear the cart and then refresh the UI with updated values
function clearCart(cartType) {
    let route = $("#clear-cart").val();
    $.ajax({
        type: "POST",
        url: route,
        data: {
            type: cartType,
        },
        dataType: "json",
        success: function (response) {
            fetchUpdatedCart(function () {
                calTotalAmount();
            });
            // Call calTotalAmount after cart fetch completes
        },
        error: function () {
            console.error("There was an issue clearing the cart.");
        },
    });
}

/** Add to cart start **/

let selectedProduct = {};
let purchaseSerials = [];
let serialContext = null;

$(document).on("click", "#single-product", function () {
    showProductModal($(this));
});

function autoOpenModal(id) {
    let element = $("#products-list").find(".single-product." + id);
    showProductModal(element);
}

function showProductModal(element) {

    // reset state on every product click
    purchaseSerials = [];
    serialContext = null;

    selectedProduct = {
        product_id: element.data("product_id"),
        product_code: element.data("product_code"),
        product_unit_id: element.data("product_unit_id"),
        product_unit_name: element.data("product_unit_name"),
        product_image: element.data("product_image"),
        product_name: element.find(".product_name").text(),
        brand: element.data("brand"),
        stock: element.data("stock"),
        purchase_price: element.data("purchase_price"),
        price_without_tax: element.data("price_without_tax"),
        profit_percent: element.data("profit_percent"),
        sales_price: element.data("sales_price"),
        whole_sale_price: element.data("whole_sale_price"),
        dealer_price: element.data("dealer_price"),
        product_type: element.data("product_type"),
        has_serial: element.data("has_serial"),
        batch_no: element.data("batch_no"),
        expire_date: element.data("expire_date"),
        vat_rate: element.data("vat_rate"),
    };

    // Set modal display values
    $("#product_name").text(selectedProduct.product_name);
    $("#brand").text(selectedProduct.brand);
    $("#stock").text(selectedProduct.stock);
    $("#purchase_price").val(selectedProduct.purchase_price);
    $("#sales_price").val(selectedProduct.sales_price);
    $("#whole_sale_price").val(selectedProduct.whole_sale_price);
    $("#dealer_price").val(selectedProduct.dealer_price);
    $("#profit_percent").val(selectedProduct.profit_percent);
    $("#batch_no").val(selectedProduct.batch_no);
    $("#expire_date").val(selectedProduct.expire_date);

    // serial product
    if (parseInt(selectedProduct.has_serial) === 1) {
        // Serial addon disabled
        if (!parseInt($('#has-serial-code-addon').val())) {
            toastr.error(
                'This product has serial numbers. Please enable Serial Code Addon to add this product.'
            );
            return;
        }

        // reset previous serials
        $("#serial-inputs").html("");
        $("#serialList").html("");
        $("#enteredCount").text(0);

        serialContext = "purchase";
        $("#serialModal").modal("show");
        return;
    }

    // show/hide variant field according to Product Type
    if (selectedProduct.product_type === "variant") {
        $("#variant_name").closest(".col-lg-6").removeClass("d-none");
        $("#variant_name").prop("disabled", false);
        loadVariantOptions(selectedProduct.product_id);
    } else {
        $("#variant_name").closest(".col-lg-6").addClass("d-none");
        $("#variant_name").prop("disabled", true);
        $("#variant_name").html('<option value="">Select Variant</option>');
    }

    // Read expire_date_type safely from hidden input
    const settingExpireType = $("#setting_expire_type").val() || "dmy";
    const $expireInput = $("#expire_date");

    if ($expireInput.length) {
        if (settingExpireType === "my") {
            $expireInput.attr("type", "month");
        } else {
            $expireInput.attr("type", "date");
        }
    }

    $("#product-modal").modal("show");
}

$(".product-filter").on("submit", function (e) {
    e.preventDefault();
});

let savingLoader = `<div class="spinner-border spinner-border-sm custom-text-primary" role="status"><span class="visually-hidden">${window.trans.loading}</span></div>`,
    $purchase_modal_reload = $("#purchase_modal");
    $purchase_modal_reload.initFormValidation(),
    // item modal action
    $("#purchase_modal").on("submit", function (e) {
        e.preventDefault();
        let t = $(this).find(".submit-btn"),
            a = t.html();
        let url = $(this).data("route");
        let quantity = parseFloat($("#product_qty").val());
        $purchase_modal_reload.valid() &&
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    type: "purchase",
                    id: selectedProduct.product_id,
                    name: selectedProduct.product_name,
                    quantity: quantity,
                    serial_numbers: purchaseSerials,
                    price: parseFloat($("#purchase_price").val()) || selectedProduct.purchase_price,
                    profit_percent: parseFloat($("#profit_percent").val()) || selectedProduct.profit_percent,
                    sales_price: parseFloat($("#sales_price").val()),
                    whole_sale_price: parseFloat($("#whole_sale_price").val()),
                    dealer_price: parseFloat($("#dealer_price").val()),
                    batch_no: $("#batch_no").val(),
                    expire_date: $("#expire_date").val(),
                    price_without_tax: selectedProduct.price_without_tax,
                    product_code: selectedProduct.product_code,
                    product_unit_id: selectedProduct.product_unit_id,
                    product_unit_name: selectedProduct.product_unit_name,
                    product_image: selectedProduct.product_image,
                    warehouse_id: $("#warehouse_id").val(),
                    variant_name: $("#variant_name").val(),
                },
                beforeSend: function () {
                    t.html(savingLoader).attr("disabled", !0);
                },
                success: function (response) {
                    t.html(a).removeClass("disabled").attr("disabled", !1);

                    if (response.success) {
                        fetchUpdatedCart(function () {
                            calTotalAmount();
                            lockSerialCartRows();
                        });
                        // Update totals after cart fetch completes
                        $("#product-modal").modal("hide");
                        $("#product_qty").val("");
                        $("#variant_name").val("");
                        $("#warehouse_id").val("");
                        $("#expire_date").val("");
                    } else {
                        toastr.error(
                            response.message || "Failed to add product to cart."
                        );
                    }
                },
                error: function (xhr) {
                    toastr.error(
                        "An error occurred while adding product to cart."
                    );
                },
            });
    });

// Profit Percent Calculation
let profitOption = $("#profit_option").val();
function getVatRate() {
    return parseFloat(selectedProduct.vat_rate) || 0;
}
function getExclusivePrice(inclusive) {
    let vatRate = getVatRate();
    if (!vatRate) return inclusive;

    return inclusive / (1 + vatRate / 100);
}

function calculateSalesPrice(inclusivePrice, profitPercent) {

    let exclusive = getExclusivePrice(inclusivePrice);

    let sale = 0;

    if (profitOption === "margin") {
        sale = exclusive / (1 - profitPercent / 100);
    } else {
        sale = exclusive + (exclusive * profitPercent / 100);
    }

    return sale;
}

function calculateProfitPercent(inclusivePrice, salePrice) {

    let exclusive = getExclusivePrice(inclusivePrice);

    let profit = 0;

    if (profitOption === "margin") {
        profit = ((salePrice - exclusive) / salePrice) * 100;
    } else {
        profit = ((salePrice - exclusive) / exclusive) * 100;
    }

    return profit;
}

// Purchase price, Sale Price, Profit Percent change action
$(document).on("input change", "#purchase_price", function () {

    let purchasePrice = parseFloat($(this).val());
    let profitPercent = parseFloat($("#profit_percent").val());

    if (purchasePrice && profitPercent) {

        let salePrice = calculateSalesPrice(purchasePrice, profitPercent);

        $("#sales_price").val(salePrice.toFixed(2));
    }
    // update exclusive price
    selectedProduct.price_without_tax = getExclusivePrice(purchasePrice);
});
$(document).on("input change", "#profit_percent", function () {

    let purchasePrice = parseFloat($("#purchase_price").val());
    let profitPercent = parseFloat($(this).val());

    if (purchasePrice && profitPercent) {

        let salePrice = calculateSalesPrice(purchasePrice, profitPercent);

        $("#sales_price").val(salePrice.toFixed(2));
    }

});
$(document).on("input change", "#sales_price", function () {

    let purchasePrice = parseFloat($("#purchase_price").val());
    let salePrice = parseFloat($(this).val());

    if (purchasePrice && salePrice) {

        let profit = calculateProfitPercent(purchasePrice, salePrice);

        $("#profit_percent").val(profit.toFixed(2));
    }

});
/** Add to cart End **/

// Function to load variant options
function loadVariantOptions(product_id) {
    let url = $("#get-product-variants").val() + '/' + product_id;
    $.ajax({
        url: url,
        type: "GET",
        success: function (res) {
            let options = '<option value="">Select Variant</option>';
            res.forEach(function (variant) {
                // Store batch_no as data attribute
                options += `<option value="${variant.variant_name}" data-batch="${variant.batch_no || ''}" data-expire_date="${variant.expire_date || ''}">
                               ${variant.variant_name} (${variant.batch_no || '-'})
                            </option>`;
            });
            $("#variant_name").html(options);

            // Clear field when product modal opens
            $("#batch_no").val('');
            $("#expire_date").val('');

        },
    });
}

// variant options on change event
$(document).on("change", "#variant_name", function () {
    let selectedOption = $(this).find(":selected");

    let batchNo = selectedOption.data("batch") || '';
    $("#batch_no").val(batchNo);

    let expireDate = selectedOption.data("expire_date") || '';

    // Adjust format if type=month
    const $expireInput = $("#expire_date");
    if ($expireInput.attr("type") === "month" && expireDate) {
        expireDate = expireDate.slice(0, 7); // yyyy-MM
    }

    $expireInput.val(expireDate);
});

// Trigger calculation whenever Discount, or Receive Amount fields change
$("#discount_amount, #receive_amount, #shipping_charge").on(
    "input",
    function () {
        calTotalAmount();
    }
);

// vat calculation
$(".vat_select").on("change", function () {
    let vatRate = parseFloat($(this).find(":selected").data("rate")) || 0;
    let subtotal = getNumericValue($("#sub_total").text()) || 0;

    let vatAmount = (subtotal * vatRate) / 100;

    $("#vat_amount").val(vatAmount.toFixed(2));
    calTotalAmount();
});

// discount calculation
$(".discount_type").on("change", function () {
    calTotalAmount();
});

// Function to calculate the total amount
function calTotalAmount() {
    let subtotal = 0;

    // Calculate subtotal from cart list
    $("#purchase_cart_list tr").each(function () {
        let cart_subtotal =
            getNumericValue($(this).find(".cart-subtotal").text()) || 0;
        subtotal += cart_subtotal;
    });

    $("#sub_total").text(currencyFormat(subtotal));

    // Discount (applied first on subtotal)
    let discount_amount = getNumericValue($("#discount_amount").val()) || 0;
    let discount_type = $(".discount_type").val();

    if (discount_type == "percent") {
        discount_amount = (subtotal * discount_amount) / 100;

        if (discount_amount > subtotal) {
            toastr.error("Discount cannot be more than " + currencyFormat(subtotal) + "!");
            discount_amount = subtotal;
            $("#discount_amount").val(100);
        }
    } else {
        if (discount_amount > subtotal) {
            toastr.error("Discount cannot be more than " + currencyFormat(subtotal) + "!");
            discount_amount = subtotal;
            $("#discount_amount").val(discount_amount);
        }
    }

    // Subtotal after discount (before VAT)
    let subtotal_after_discount = subtotal - discount_amount;

    // VAT (applied on subtotal after discount)
    let vat_rate =
        parseFloat($(".vat_select option:selected").data("rate")) || 0;
    let vat_amount = (subtotal_after_discount * vat_rate) / 100;
    $("#vat_amount").val(vat_amount.toFixed(2));

    // Shipping Charge
    let shipping_charge = getNumericValue($("#shipping_charge").val()) || 0;

    // Total Amount
    let total_amount = subtotal_after_discount + vat_amount + shipping_charge;
    $("#total_amount").text(currencyFormat(total_amount));

    // Receive Amount
    let receive_amount = getNumericValue($("#receive_amount").val()) || 0;
    if (receive_amount < 0) {
        toastr.error("Receive amount cannot be less than 0!");
        receive_amount = 0;
        $("#receive_amount").val(receive_amount);
    }

    // Change Amount
    let change_amount =
        receive_amount > total_amount ? receive_amount - total_amount : 0;
    $("#change_amount").val(change_amount.toFixed(2));

    // Due Amount
    let due_amount =
        total_amount > receive_amount ? total_amount - receive_amount : 0;
    $("#due_amount").val(due_amount.toFixed(2));
}
calTotalAmount();

// Cancel btn action
$(".cancel-sale-btn").on("click", function (e) {
    e.preventDefault();
    clearCart("purchase");
});

$(".product-filter").on("input", ".search-input", function (e) {
    fetchProducts();
});

let autoOpenedForSearch = false; // Prevent repeated modal open per search
function fetchProducts() {
    var form = $("form.product-filter")[0];

    $.ajax({
        type: "POST",
        url: $(form).attr("action"),
        data: new FormData(form),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            // Show products
            $("#products-list").html(res.data);

            // Open modal if one product found and not opened yet
            const searchTerm = $("#purchase_product_search").val().trim();
            if (
                res.total_products &&
                res.product_id &&
                searchTerm !== "" &&
                !autoOpenedForSearch
            ) {
                autoOpenedForSearch = true;
                autoOpenModal(res.product_id);
                $("#purchase_product_search").val("");

                // Reset when modal closes and reload list
                $("#product-modal").one("hidden.bs.modal", function () {
                    autoOpenedForSearch = false;
                    fetchProducts();
                });
            }
        },
    });
}

// Quick product create from purchase page
$(document).on("submit", "#quick-product-form", function (e) {
    e.preventDefault();
    let $form = $(this);
    let $btn = $form.closest(".modal-content").find(".modal-footer .submit-btn");
    let originalText = $btn.html();

    $.ajax({
        url: $form.attr("action"),
        type: "POST",
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
            $btn.html(savingLoader).attr("disabled", true);
        },
        success: function (response) {
            $btn.html(originalText).attr("disabled", false);
            $("#quick-product-modal").modal("hide");
            toastr.success(response.message || "Product created successfully.");
            $form[0].reset();
            $form.find(".preview-wrapper").remove();
            $form.find(".upload-box").removeClass("uploaded");
            $form.find("#productTypeContainer").empty();
            $form.find("#quick-serial-inputs").empty();
            fetchProducts();
        },
        error: function (xhr) {
            $btn.html(originalText).attr("disabled", false);
            if (xhr.responseJSON?.message) {
                toastr.error(xhr.responseJSON.message);
            } else {
                toastr.error("An error occurred while creating the product.");
            }
        },
    });
});

// Reset quick product modal state on every open
$("#quick-product-modal").on("shown.bs.modal", function () {
    let $modal = $(this);
    if ($modal.data("reopening-from-serial")) {
        $modal.data("reopening-from-serial", false);
        return;
    }
    let $form = $modal.find("#quick-product-form");

    $form[0].reset();
    $form.find(".preview-wrapper").remove();
    $form.find(".upload-box").removeClass("uploaded");
    $form.find("#quick-serial-inputs").empty();
    $modal.find(".modal-footer .submit-btn")
        .html("Save & Published")
        .attr("disabled", false);

    // Re-populate default Single Product view
    $form.find("#product-type").trigger("change");
});

$(document).on("click", ".category-content", function () {
    let url = $("#category-filter").val();
    const categoryId = $(this).data("id");
    $.ajax({
        type: "POST",
        url: url,
        data: { category_id: categoryId },
        success: function (response) {
            $(".product-list-container").html(response.data);
        },
    });
});

$(document).on("click", ".brand-search", function () {
    let url = $("#brand-filter").val();
    const brandId = $(this).data("id");

    $.ajax({
        type: "POST",
        url: url,
        data: { brand_id: brandId },
        success: function (response) {
            $(".product-list-container").html(response.data);
        },
    });
});

// Category Filter
$(".category-search").on("input", function (e) {
    e.preventDefault();
    // Get search query
    const search = $(this).val();
    const route = $(this).closest("form").data("route");

    $.ajax({
        type: "POST",
        url: route,
        data: {
            search: search,
        },
        success: function (response) {
            $("#category-data").html(response.categories);
        },
    });
});

// brand Filter
$(".brand-search").on("input", function (e) {
    e.preventDefault();

    // Get search query
    const search = $(this).val();

    const route = $(this).closest("form").data("route");

    $.ajax({
        type: "POST",
        url: route,
        data: {
            search: search,
        },
        success: function (response) {
            $("#brand-data").html(response.brands);
        },
    });
});

$(document).on("submit", "#categorySearchForm, #brandSearchForm", function (e) {
    e.preventDefault();
});

// When select brand or product
$(document).on("click", ".category-list, .brand-list", function () {
    const isCategory = $(this).hasClass("category-list");
    const filterType = isCategory ? "category_id" : "brand_id";
    const filterId = $(this).data("id");
    const route = $(this).data("route"); // product filter route

    const searchTerm = $("#purchase_product_search").val();

    $.ajax({
        type: "POST",
        url: route,
        data: {
            search: searchTerm,
            [filterType]: filterId, // Dynamically set category_id or brand_id
        },
        success: function (response) {
            $("#products-list").html(response.data);
            $("#category-list").html(response.categories);
            $("#brand-list").html(response.brands);
        },
    });
});

// Update cart
$(document).on("change", ".batch_no, .expire_date, .price", function () {
    let $row = $(this).closest("tr");
    let updateRoute = $row.data("update_route");

    // Get values
    let expire_date = $row.find(".expire_date").val();
    let priceVal = $row.find(".price").val();
    let batch_no = $row.find(".batch_no").val();

    // Parse and validate price
    let price = parseFloat(priceVal);
    if (isNaN(price) || price < 0) {
        toastr.error("Price cannot be negative or empty.");
        return;
    }

    // Optional: clean batch_no
    if (batch_no === null || batch_no === undefined) {
        batch_no = "";
    }

    // Prepare data
    let data = {
        batch_no: batch_no,
        expire_date: expire_date,
        price: price,
    };

    // Call update function
    updateCart(data, updateRoute);
});

// Function to update cart item with the new quantity
function updateCart(data, updateRoute) {
    $.ajax({
        url: updateRoute,
        type: "PUT",
        data: data,
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(function () {
                    calTotalAmount();
                });
                // Refresh the cart and recalculate totals
            } else {
                toastr.error(response.message || "Failed to update cart");
            }
        },
    });
}


/** Serial Code Start **/
let activeCartRow = null;

// handle serial save
$(document).on("click", "#serialModal .submit-btn", function () {

    if (serialContext === "purchase-cart") {

        const $row = $(".serial-cell-button.active").closest("tr");

        // update row serials
        $row.data("serials", [...purchaseSerials]);

        // update qty from serial count
        $row.find(".cart-qty").val(purchaseSerials.length);

        // update hidden serial input
        $row.find("input.serials-input").val(JSON.stringify(purchaseSerials));

        const rowId = $row.data("row_id");
        const updateRoute = $row.data("update_route");

        updateCartQuantity(rowId, purchaseSerials.length, updateRoute, purchaseSerials.length ? purchaseSerials : null);

        $("#serialModal").modal("hide");
        serialContext = null;
        return;
    }

    if (serialContext !== "purchase") return;

    purchaseSerials = [];

    $("#serial-inputs input").each(function () {
        purchaseSerials.push($(this).val());
    });

    if (purchaseSerials.length === 0) {
        toastr.error("Please add at least one serial number.");
        return;
    }

    $("#serialModal").modal("hide");

    const quantity = purchaseSerials.length;

    $.ajax({
        url: $("#purchase_modal").data("route"),
        type: "POST",
        data: {
            type: "purchase",
            id: selectedProduct.product_id,
            name: selectedProduct.product_name,
            quantity: quantity,
            price: selectedProduct.purchase_price,
            purchase_price: selectedProduct.purchase_price,
            price_without_tax: selectedProduct.price_without_tax,
            profit_percent: selectedProduct.profit_percent ?? null,
            sales_price: selectedProduct.sales_price ?? null,
            whole_sale_price: selectedProduct.whole_sale_price ?? null,
            dealer_price: selectedProduct.dealer_price ?? null,
            product_code: selectedProduct.product_code,
            product_unit_id: selectedProduct.product_unit_id,
            product_unit_name: selectedProduct.product_unit_name,
            product_image: selectedProduct.product_image,
            warehouse_id: $("#warehouse_id").val(),
            batch_no: null,
            expire_date: null,
            variant_name: null,
            product_type: selectedProduct.product_type,
            has_serial: selectedProduct.has_serial,
            serial_numbers: purchaseSerials
        },
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(function () {
                    calTotalAmount();
                    lockSerialCartRows();
                });

                toastr.success("Product added to cart successfully.");
            } else {
                toastr.error(response.message || "Failed to add product.");
            }
        },
        error: function (xhr) {
            if (xhr.responseJSON && xhr.responseJSON.message) {
                toastr.error(xhr.responseJSON.message);
            } else {
                toastr.error("An error occurred while adding product to cart.");
            }
        }

    });

    purchaseSerials = [];
    serialContext = null;
});

$('#serialModal').on('hidden.bs.modal', function () {
    if (serialContext === "purchase" && purchaseSerials.length === 0) {
        serialContext = null;
    }
    if (serialContext === "purchase-cart") {
        serialContext = null;
    }
});

$('#serialModal').on('show.bs.modal', function () {

    if (serialContext === "purchase") {
        purchaseSerials = [];
        $("#serial-inputs").html("");
        $("#serialList").html("");
        $("#enteredCount").text(0);
    }
});

// add serial btn
$(document).on("click", "#saveSerialBtn", function () {

    if (!["purchase", "purchase-cart"].includes(serialContext)) return;

    const $input = $(".serial-input");
    const serial = $.trim($input.val());

    if (!serial) return;

    if (purchaseSerials.includes(serial)) {
        toastr.error("Serial already added");
        return;
    }

    // Determine product_id based on context
    let product_id;
    if (serialContext === "purchase-cart") {
        product_id = activeCartRow.data("product_id");
    } else {
        product_id = selectedProduct.product_id;
    }

    let url = $("#check-serial-exist").val();

    $.ajax({
        url: url,
        type: "POST",
        data: {
            product_id: product_id,
            serial: serial
        },
        success: function (res) {

            if (res.exists) {
                toastr.error("This serial already exists for this product");
                return;
            }

            purchaseSerials.push(serial);
            $input.val("");

            $("#serialList").append(`
                <div class="serial-item new">
                    <span>${serial}</span>
                    <span class="serial-remove-btn">&times;</span>
                </div>
            `);

            $("#serial-inputs").append(
                `<input type="hidden" value="${serial}">`
            );

            $("#enteredCount").text(purchaseSerials.length);

        }
    });
});

// Press Enter to add serial, keep cursor in input
$(document).on("keydown", ".serial-input", function (e) {

    if (e.key === "Enter") {
        e.preventDefault();
        e.stopPropagation();

        $("#saveSerialBtn").trigger("click");

        // keep cursor in input
        $(this).focus();
    }

});

// remove serial btn
$(document).on("click", ".serial-remove-btn", function () {

    const serial = $(this).siblings("span").text();

    purchaseSerials = purchaseSerials.filter(s => s !== serial);

    $(this).closest(".serial-item").remove();
    $("#enteredCount").text(purchaseSerials.length);

    // Also remove the hidden input when in first-time-add context
    if (serialContext === "purchase") {
        $("#serial-inputs input").each(function () {
            if ($(this).val() === serial) {
                $(this).remove();
                return false;
            }
        });
    }
});

function lockSerialCartRows() {
    $("#purchase_cart_list tr").each(function () {
        const $row = $(this);
        const hasSerial = parseInt($row.data("has_serial")) === 1;

        const $qtyInput = $row.find(".cart-qty");
        const $plusBtn = $row.find(".plus-btn");
        const $minusBtn = $row.find(".minus-btn");
        const $serialBtn = $row.find(".serial-cell-button");

        if (hasSerial) {
            $qtyInput.prop("disabled", true).addClass("readonly");
            $plusBtn.addClass("d-none");
            $minusBtn.addClass("d-none");
            $serialBtn.removeClass("d-none");
        } else {
            $qtyInput.prop("disabled", false).removeClass("readonly");
            $plusBtn.removeClass("d-none");
            $minusBtn.removeClass("d-none");
            $serialBtn.addClass("d-none");
        }
    });
}

// open serial modal from cart row (editable mode)
$(document).on("click", ".serial-cell-button", function () {
    if (!$(this).closest("#purchase_cart_list").length) {
        return;
    }

    $(".serial-cell-button").removeClass("active");
    $(this).addClass("active");

    const $row = $(this).closest("tr");

    serialContext = "purchase-cart";

    activeCartRow = $row;

    const existingSerials = $row.data("serials") || [];

    purchaseSerials = [...existingSerials];

    $("#serialList").html("");
    $("#serial-inputs").html("");

    existingSerials.forEach(serial => {
        $("#serialList").append(`
            <div class="serial-item">
                <span>${serial}</span>
                <span class="serial-remove-btn">&times;</span>
            </div>
        `);
    });

    $("#enteredCount").text(purchaseSerials.length);

    $("#serialModal").modal("show");
});

$(document).on("click", ".remove-all-serial", function () {
    purchaseSerials = [];
    $("#serialList").html("");
    $("#serial-inputs").html("");
    $("#enteredCount").text(0);

    if (serialContext === "purchase-cart" && activeCartRow) {
        activeCartRow.find(".remove-btn").trigger("click");
    }
});

$(document).ready(function () {
    lockSerialCartRows();
});

/** Serial Code End **/
