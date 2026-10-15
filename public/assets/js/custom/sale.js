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
    if (value === null || value === undefined) return 0;
    return parseFloat(String(value).replace(/[^0-9.-]+/g, "")) || 0;
}

function assetPath(path) {
    if (!path) return "";

    // If already a full URL, return as is
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    let baseUrl = $("#asset_base_url").val() || "";
    return baseUrl.replace(/\/$/, "") + "/" + path.replace(/^\/+/, "");
}

// Round option wise round amount
function RoundingTotal(amount) {
    let option = $("#rounding_amount_option").val();
    if (option == "round_up") {
        return Math.ceil(amount);
    } else if (option == "nearest_whole_number") {
        return Math.round(amount);
    } else if (option == "nearest_0.05") {
        return Math.round(amount * 20) / 20;
    } else if (option == "nearest_0.1") {
        return Math.round(amount * 10) / 10;
    } else if (option == "nearest_0.5") {
        return Math.round(amount * 2) / 2;
    } else {
        return amount;
    }
}

// Update the cart list and call the callback once complete
function fetchUpdatedCart(callback) {
    let url = $("#get-cart").val();
    $.ajax({
        url: url,
        type: "GET",
        success: function (response) {
            $("#sale_cart_list").html(response);
            if ($("#has-serial-code-addon").val() == 1) {
                lockSaleSerialCartRows();
            }
            if (typeof callback == "function") callback(); // Call the callback after updating the cart
        },
    });
}

// Update price
$(document).on("change", ".cart-price", function () {
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let newPrice = parseFloat($(this).val());

    if (newPrice < 0 || isNaN(newPrice)) {
        toastr.error("Price can not be negative.");
        return;
    }
    let currentQty = parseFloat($row.find(".cart-qty").val());
    let currentDiscount = parseFloat($row.find(".cart-discount").val()) || 0;

    updateCart(rowId, currentQty, updateRoute, newPrice, currentDiscount);
});

// Update discount
$(document).on("change", ".cart-discount", function () {
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");

    let qty = parseFloat($row.find(".cart-qty").val());
    let price = parseFloat($row.find(".cart-price").val());

    let discount = parseFloat($(this).val()) || 0;
    if (discount < 0) {
        toastr.error("Discount cannot be negative.");
        return;
    }

    if (discount > price) {
        toastr.error("Discount cannot be more than sale price!");
        $(this).val(price);
        discount = price;
    }

    updateCart(rowId, qty, updateRoute, price, discount);
});

// Increase quantity
$(document).on("click", ".plus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());
    let newQty = currentQty + 1;
    $qtyInput.val(newQty);

    // Get the current price
    let currentPrice = parseFloat($row.find(".cart-price").val());
    let currentDiscount = parseFloat($row.find(".cart-discount").val()) || 0;

    if (isNaN(currentPrice) || currentPrice < 0) {
        toastr.error("Price can not be negative.");
        return;
    }
    updateCart(rowId, newQty, updateRoute, currentPrice, currentDiscount);
});

// Decrease quantity
$(document).on("click", ".minus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());

    // Ensure quantity does not go below 1
    if (currentQty > 1) {
        let newQty = currentQty - 1;
        $qtyInput.val(newQty);

        // Get the current price
        let currentPrice = parseFloat($row.find(".cart-price").val());
        let currentDiscount =
            parseFloat($row.find(".cart-discount").val()) || 0;

        if (isNaN(currentPrice) || currentPrice < 0) {
            toastr.error("Price can not be negative.");
            return;
        }

        // Call updateCart with both qty and price
        updateCart(rowId, newQty, updateRoute, currentPrice, currentDiscount);
    }
});

// Cart quantity input field change event
$(document).on("change", ".cart-qty", function () {
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let newQty = parseFloat($(this).val());

    // Retrieve the cart price
    let currentPrice = parseFloat($row.find(".cart-price").val());
    let currentDiscount = parseFloat($row.find(".cart-discount").val()) || 0;

    if (isNaN(currentPrice) || currentPrice < 0) {
        toastr.error("Price can not be negative.");
        return;
    }

    // Ensure quantity does not go below 0
    if (newQty >= 0) {
        updateCart(rowId, newQty, updateRoute, currentPrice, currentDiscount);
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
                fetchUpdatedCart(calTotalAmount);
            } else {
                toastr.error(response.message || "Failed to remove item");
            }
        },
        error: function () {
            toastr.error("Error removing item from cart");
        },
    });
});

// Function to update cart item with the new quantity
function updateCart(
    rowId,
    qty,
    updateRoute,
    price,
    discount = 0,
    serials = null,
) {
    $.ajax({
        url: updateRoute,
        type: "PUT",
        data: {
            rowId: rowId,
            qty: qty,
            price: price,
            discount: discount,
            serial_numbers: serials,
        },
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(calTotalAmount); // Refresh the cart and recalculate totals
            } else {
                toastr.error(response.message || "Failed to update cart");
            }
        },
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
            fetchUpdatedCart(calTotalAmount); // Call calTotalAmount after cart fetch completes
        },
        error: function () {
            console.error("There was an issue clearing the cart.");
        },
    });
}

/** Handle customer selection change **/
$(".customer-select").on("change", function () {
    let customerType = $(this).find(":selected").data("type");
    let route = $("#get_stock_prices").val();

    let cartRows = []; // Collect cart stock_id + batch_no if cart is not empty
    $("#sale_cart_list tr").each(function () {
        let $row = $(this);
        let stockId = $row.data("stock_id");
        let batchNo = $row.data("batch_no") || null;

        if (stockId) {
            cartRows.push({ stock_id: stockId, batch_no: batchNo });
        }
    });

    // Fetch prices for selected customer type
    $.ajax({
        url: route,
        type: "GET",
        data: {
            type: customerType,
            stocks: cartRows.length ? cartRows : null, // Optional for cart list
        },
        success: function (response) {
            // Update product list
            $(".single-product").each(function () {
                let productId = $(this).data("product_id");
                if (response.products.hasOwnProperty(productId)) {
                    let numericPrice = getNumericValue(
                        String(response.products[productId]),
                    );
                    $(this)
                        .find(".product_price")
                        .text(currencyFormat(numericPrice));
                }
            });

            // Update cart cart list if cart not empty
            if (cartRows.length) {
                $("#sale_cart_list tr").each(function () {
                    let $row = $(this);
                    let stockId = $row.data("stock_id");
                    let batchNo = $row.data("batch_no") || "default";

                    if (stockId && response.stocks[stockId]) {
                        let rawPrice = null;

                        if (response.stocks[stockId].hasOwnProperty(batchNo)) {
                            rawPrice = response.stocks[stockId][batchNo];
                        } else if (
                            response.stocks[stockId].hasOwnProperty("default")
                        ) {
                            rawPrice = response.stocks[stockId]["default"];
                        }

                        if (rawPrice !== null) {
                            let numericPrice = getNumericValue(
                                String(rawPrice),
                            );
                            $row.find(".cart-price")
                                .val(numericPrice)
                                .trigger("change");
                        }
                    }
                });

                // Recalculate totals after cart update
                calTotalAmount();
            }
        },
    });
});

// Trigger calculation whenever Discount, or Receive Amount fields change
$("#discount_amount, #receive_amount, #shipping_charge").on(
    "input change",
    function () {
        calTotalAmount();
    },
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

    // Calculate subtotal from cart list using qty * price
    $("#sale_cart_list tr").each(function () {
        let qty = getNumericValue($(this).find(".cart-qty").val()) || 0;
        let price = getNumericValue($(this).find(".cart-price").val()) || 0;
        let discountField = $(this).find(".cart-discount");
        let discount = discountField.length
            ? getNumericValue(discountField.val())
            : 0;
        let row_subtotal = qty * (price - discount);
        subtotal += row_subtotal;
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

    // Rounding total
    let rounding_total = RoundingTotal(total_amount);

    // Rounding off
    let rounding_amount = Math.abs(rounding_total - total_amount);
    $("#rounding_amount").text(currencyFormat(rounding_amount));

    // Payable Amount
    let payable_amount = rounding_total;
    $("#payable_amount").text(currencyFormat(payable_amount));

    // Receive Amount
    let receive_amount = getNumericValue($("#receive_amount").val()) || 0;
    if (receive_amount < 0) {
        toastr.error("Receive amount cannot be less than 0!");
        receive_amount = 0;
        $("#receive_amount").val(receive_amount);
    }

    // Change Amount
    let change_amount =
        receive_amount > payable_amount ? receive_amount - payable_amount : 0;
    $("#change_amount").val(formattedAmount(change_amount, 2));

    // Due Amount
    let due_amount =
        payable_amount > receive_amount ? payable_amount - receive_amount : 0;
    $("#due_amount").val(formattedAmount(due_amount, 2));
}

calTotalAmount();

// Function to check if any payment type selected is cash
function hasCashPaymentSelected($form) {
    if (!$form) $form = $(".ajaxform");
    // Check main payment type
    var mainType = $form.find(".main-select").val();
    if (mainType && mainType.toLowerCase() === 'cash') return true;
    // Check additional payment rows
    var hasCash = false;
    $form.find(".payment-type-select").each(function () {
        var val = $(this).val();
        if (val && val.toLowerCase() === 'cash') {
            hasCash = true;
            return false;
        }
    });
    return hasCash;
}

// Validate payment type on receive_amount change
$("#receive_amount").on("input change", function () {
    var changeAmount = parseFloat($("#change_amount").val()) || 0;
    if (changeAmount > 0 && !hasCashPaymentSelected()) {
        $(".overpayment-warning").remove();
        $(".payment-section").first().before(
            '<div class="alert alert-warning overpayment-warning">' +
            window.trans?.overpayment_cash_only ??
            'Overpayment/change is only allowed for cash payments.' +
            '</div>'
        );
    } else {
        $(".overpayment-warning").remove();
    }
});

// Intercept form submit to validate change/payment type
$(".ajaxform").on("submit", function (e) {
    var changeAmount = parseFloat($("#change_amount").val()) || 0;
    if (changeAmount > 0 && !hasCashPaymentSelected($(this))) {
        e.preventDefault();
        e.stopImmediatePropagation();
        toastr.error(window.trans?.overpayment_cash_only ??
            "Overpayment/change is only allowed for cash payments.");
        return false;
    }
});

// Cancel btn action
$(".cancel-sale-btn").on("click", function (e) {
    e.preventDefault();
    clearCart("sale");
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

// Warehouse Filter
$(document).on("change", ".warehouse_id", function () {
    const warehouseId = $(this).val();
    const searchTerm = $("#sale_product_search").val();
    const route = $(".product-filter-form").attr("action");

    $.ajax({
        type: "POST",
        url: route,
        data: {
            search: searchTerm,
            warehouse_id: warehouseId,
        },
        success: function (response) {
            $("#products-list").html(response.data);
            $("#category-list").html(response.categories);
            $("#brand-list").html(response.brands);
        },
    });
});

$(document).on("submit", "#categorySearchForm, #brandSearchForm", function (e) {
    e.preventDefault();
});

// select brand or product action
$(document).on("click", ".category-list, .brand-list", function () {
    const isCategory = $(this).hasClass("category-list");
    const filterType = isCategory ? "category_id" : "brand_id";
    const filterId = $(this).data("id");
    const route = $(this).data("route"); // product filter route

    const searchTerm = $("#sale_product_search").val();

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

/** Add to cart functionality start **/

// Debounce function to limit the frequency of API calls
function debounce(func, delay) {
    let timer;
    return function (...args) {
        const context = this;
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(context, args), delay);
    };
}

// Scanner detection variables
let isScannerInput = false;
let scannerInputTimeout;
const SCANNER_LOCK_TIME = 300; // Time to wait before allowing another scan

// Handle scanner input when Enter key is pressed
$(".product-filter").on("keydown", ".search-input", function (e) {
    if (e.key == "Enter") {
        if (isScannerInput) {
            e.preventDefault();
            return; // Skip duplicate scanner calls
        }

        e.preventDefault(); // Prevent form submission

        handleScannerInput(this);
    }
});

$(".product-filter").on("submit", function (e) {
    e.preventDefault();
});

// Trigger input handler on user typing (debounced)
$(".product-filter").on(
    "input",
    ".search-input",
    debounce(function () {
        if (isScannerInput) {
            return; // Skip input events triggered by scanner
        }

        handleUserInput();
    }, 400),
);

// Function to handle scanner input
function handleScannerInput(inputElement) {
    isScannerInput = true; // Lock scanner input handling
    clearTimeout(scannerInputTimeout); // Reset scanner lock timer

    const form = $(inputElement).closest("form")[0];

    $.ajax({
        type: "POST",
        url: $(form).attr("action"),
        data: new FormData(form),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            $("#products-list").html(res.data);
            const searchTerm = $("#sale_product_search").val()?.trim() || '';

            if (res.total_products == 1 && res.product_id && (res.total_products_count > 1 || searchTerm !== "")) {
                if (res.has_serial) {
                    let element = $(".single-product." + res.product_id);
                    manageAddToCart(element);
                } else {
                    autoAddItemToCart(res.product_id);
                }
            }
            // change price according customer-type
            customerWisePrice();
            if (res.total_products) {
                $("#sale_product_search").val("");
            }
        },
        complete: function () {
            resetScannerLock();
        },
    });
}

// Function to handle user input
function handleUserInput() {
    fetchProducts();
}

// Reset scanner lock after processing
function resetScannerLock() {
    scannerInputTimeout = setTimeout(() => {
        isScannerInput = false;
    }, SCANNER_LOCK_TIME);
}

// Fetch products function
function fetchProducts() {
    const form = $(".product-filter-form")[0];

    $.ajax({
        type: "POST",
        url: $(form).attr("action"),
        data: new FormData(form),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            $("#products-list").html(res.data); // Update the table with new data
            const searchTerm = $("#sale_product_search").val()?.trim() || '';

            if (
                res.total_products == 1 &&
                res.product_id &&
                (res.total_products_count > 1 || searchTerm !== "")
            ) {
                if (res.has_serial) {
                    let element = $(".single-product." + res.product_id);
                    manageAddToCart(element);

                    $('#sale_product_search').val('');
                    handleUserInput();
                } else {
                    autoAddItemToCart(res.product_id);
                }
            }
            // change price according customer-type
            customerWisePrice();
        },
    });
}

// Quick product create from sale page
$(document).on("submit", "#quick-product-form", function (e) {
    e.preventDefault();
    let $form = $(this);
    let $btn = $form.closest(".modal-content").find(".modal-footer .submit-btn");
    let originalText = $btn.html();
    let savingLoader = `<div class="spinner-border spinner-border-sm custom-text-primary" role="status"><span class="visually-hidden">${window.trans?.loading ?? 'Loading...'}</span></div>`;

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
            refreshInventoryProducts();
            customerWisePrice();
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

// Customer Wise Product Price Change
function customerWisePrice() {
    let customer_type =
        $(".customer-select option:selected").data("type") || "Retailer";
    if (customer_type && customer_type !== "Retailer") {
        let url = $("#get_product").val();

        $.ajax({
            url: url,
            type: "GET",
            data: { type: customer_type },
            success: function (data) {
                $(".single-product").each(function () {
                    let productId = $(this).data("product_id");
                    if (data[productId]) {
                        $(this).find(".product_price").text(data[productId]);
                    }
                });
            },
        });
    }
}

// ------------------------
// Utility Functions
// ------------------------
function getCustomerType() {
    return $(".customer-select option:selected").data("type") || "Retailer";
}

function getAvailableStocks(stocks) {
    return Array.isArray(stocks)
        ? stocks.filter((stock) => parseFloat(stock.productStock) >= 1)
        : [];
}
function getAdjustedPrice(batch, customerType) {
    let price = 0;
    if (
        customerType == "Dealer" &&
        batch.productDealerPrice !== null &&
        batch.productDealerPrice !== undefined
    ) {
        price = batch.productDealerPrice;
    } else if (
        customerType == "Wholesaler" &&
        batch.productWholeSalePrice !== null &&
        batch.productWholeSalePrice !== undefined
    ) {
        price = batch.productWholeSalePrice;
    } else {
        price = batch.productSalePrice;
    }

    return Math.round(price * 100) / 100;
}

function prepareSingleBatchItem(item, batch, customerType) {
    item.data("product_stock_id", batch.id);
    item.data("product_expire_date", batch.expire_date);
    item.data("default_price", getAdjustedPrice(batch, customerType));
    return item;
}

// Handle variant selection
$(document).on("click", ".variant-item", function () {
    const radio = $(this).find(".variant-radio");
    const isActive = $(this).hasClass("cart-active");

    // Toggle active state and radio checked status
    $(this).toggleClass("cart-active", !isActive);
    radio.prop("checked", !isActive);
});

// show variant modal
function showBatchSelectionModal(element, availableStocks, customerType) {
    // Clear old variant list
    const $modalBody = $("#stock-list-modal .modal-body");
    $modalBody.empty();

    // Build variant items
    const variantHtml = availableStocks
        .map((batch, index) => {
            const adjustedPrice = getAdjustedPrice(batch, customerType);

            return `
            <div class="cart-variant-box variant-item ${index > 0 ? "mt-2" : ""}"
                data-product_id="${element.data("product_id")}"
                data-product_type="${element.data("product_type")}"
                data-product_stock_id="${batch.id}"
                data-product_expire_date="${batch.expire_date ?? ""}"
                data-product_type="${element.data("product_type")}"
                data-product_name="${element.data("product_name")}"
                data-product_code="${element.data("product_code")}"
                data-default_price="${adjustedPrice}"
                data-product_unit_id="${element.data("product_unit_id")}"
                data-product_unit_name="${element.data("product_unit_name")}"
                data-purchase_price="${batch.productPurchasePrice ?? 0}"
                data-price_without_tax="${batch.exclusive_price ?? 0}"
                data-product_image="${element.data("product_image")}"
                data-route="${element.data("route")}">

                <input type="radio" class="form-check-input me-2 variant-radio">
                <div class="cart-variant-info">
                    <div>
                        ${batch.variant_name ? `<strong>${batch.variant_name.replace(/-/g, ", ")}</strong>,` : ""}
                        <span class="cart-stock">Stock: ${batch.productStock ?? 0}</span>
                    </div>
                    <small>Batch: ${batch.batch_no ?? "N/A"}</small>
                    </div>
                <div class="cart-variant-price">${currencyFormat(adjustedPrice)}</div>
            </div>
        `;
        })
        .join("");

    // Append generated items + Add button
    $modalBody.append(variantHtml);
    $modalBody.append(`<button class="cart-add-btn mt-3">Add to Cart</button>`);

    // Show modal
    $("#stock-list-modal").modal("show");
}

// ------------------------
// Core Add-to-Cart Logic
// ------------------------
function handleAddToCart(element) {
    const batchCount = parseInt(element.data("batch_count")) || 0;
    const stocks = Array.isArray(element.data("stocks"))
        ? element.data("stocks")
        : [];
    const customerType = getCustomerType();
    const availableStocks = getAvailableStocks(stocks);

    if (batchCount > 1 && availableStocks.length > 0) {
        showBatchSelectionModal(element, availableStocks, customerType);
        return;
    }

    // Only one batch or no modal needed
    const singleBatch = stocks[0] ?? {};
    
    // If no stocks array but element has direct stock_id, use element itself
    if (stocks.length === 0 && element.data("product_stock_id")) {
        addItemToCart(element);
        return;
    }

    const item = prepareSingleBatchItem(element, singleBatch, customerType);

    addItemToCart(item);
}

// ------------------------
// Auto Add From Scanner
// ------------------------
function autoAddItemToCart(id) {
    const element = $("#products-list").find(".single-product." + id);
    handleAddToCart(element);
}

// ------------------------
// Click Event Binding
// ------------------------

let selectedSerialNumbers = [];
let serialContext = null;
let selectedProduct = null;

$(document).on("click", ".single-product", function () {
    const element = $(this);
    manageAddToCart(element);
});

function manageAddToCart(element) {
    const hasSerial = parseInt(element.data("has_serial")) === 1;
    const serialAddonEnabled = parseInt($("#has-serial-code-addon").val()) === 1;
    const productType = element.data("product_type") || "";

    // Has serial but addon disabled
    if (hasSerial && !serialAddonEnabled) {
        toastr.error(
            "This product has serial numbers. Please enable Serial Code Addon to add this product.",
        );
        return;
    }

    // Serial product
    if (hasSerial && serialAddonEnabled && productType !== "combo") {
        serialContext = "sale";

        selectedProduct = {
            product_id: element.data("product_id"),
            product_name: element.data("product_name"),
            product_code: element.data("product_code"),
            product_unit_id: element.data("product_unit_id"),
            product_unit_name: element.data("product_unit_name"),
            product_image: element.data("product_image"),
            product_type: element.data("product_type"),
            sales_price: element.data("default_price"),
            route: element.data("route"),
        };

        $.ajax({
            url: $("#get-product-serials").val(),
            type: "GET",
            data: {
                product_id: selectedProduct.product_id,
                warehouse_id: $(".warehouse_id").val(),
            },
            success: function (res) {
                if (!res.serials || res.serials.length === 0) {
                    toastr.error(
                        "No available serial numbers found for this product.",
                    );
                    return;
                }

                selectedSerialNumbers = [];

                const $availableContainer = $("#availableSerialList");
                const $selectedContainer = $("#selectedSerialList");

                $availableContainer.empty();
                $selectedContainer.empty();
                $(".serial-input").val("");

                // Append available serials
                res.serials.forEach((serial) => {
                    $availableContainer.append(`
                        <div class="serial-item available-serial" data-serial="${serial}">
                            <span>${serial}</span>
                        </div>
                    `);
                });

                $("#serial-selection-modal").modal("show");
            },
        });

        return;
    }

    // Normal product
    handleAddToCart(element);
}

// Handle Add to Cart button click for multiple selections
$(document).on("click", "#stock-list-modal .cart-add-btn", function () {
    const $modal = $("#stock-list-modal");
    const selectedItems = $modal.find(".variant-item.cart-active");

    if (selectedItems.length == 0) {
        toastr.warning("Please select at least one variant.");
        return;
    }

    // Serialize AJAX calls to prevent session race conditions
    const items = selectedItems.toArray();
    let index = 0;

    function addNextItem() {
        if (index >= items.length) {
            $modal.modal("hide");
            fetchUpdatedCart(calTotalAmount);
            return;
        }

        const $item = $(items[index]);
        index++;

        const product_price = Math.round(parseFloat($item.data("default_price")) * 100) / 100 || 0;

        $.ajax({
            url: $item.data("route"),
            type: "POST",
            data: {
                type: "sale",
                id: $item.data("product_id"),
                name: $item.data("product_name"),
                price: product_price,
                quantity: 1,
                product_type: $item.data("product_type"),
                product_code: $item.data("product_code"),
                product_unit_id: $item.data("product_unit_id"),
                product_unit_name: $item.data("product_unit_name"),
                stock_id: $item.data("product_stock_id"),
                expire_date: $item.data("product_expire_date"),
                purchase_price: $item.data("purchase_price"),
                product_image: $item.data("product_image"),
                price_without_tax: $item.data("price_without_tax"),
                has_serial: $item.data("has_serial") ?? 0,
            },
            success: function (response) {
                if (!response.success) {
                    toastr.error(response.message);
                }
                addNextItem();
            },
            error: function (xhr) {
                console.error("Error:", xhr.responseText);
                addNextItem();
            },
        });
    }

    addNextItem();
});

// search filter in modal
$(document).on("keyup", ".stock-search", function () {
    let value = $(this).val().toLowerCase();
    $(".stock-table tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

// Final cart addition logic
function addItemToCart(element) {
    let url = element.data("route");
    let product_id = element.data("product_id");
    let product_type = element.data("product_type");
    let product_name = element.data("product_name");
    let extractedPrice = getNumericValue(element.find(".product_price").text());
    let product_price =
        !isNaN(extractedPrice) && extractedPrice > 0
            ? Math.round(extractedPrice * 100) / 100
            : Math.round(parseFloat(element.data("default_price")) * 100) / 100 || 0;
    let product_code = element.data("product_code");
    let product_unit_id = element.data("product_unit_id");
    let product_unit_name = element.data("product_unit_name");
    let product_stock_id = element.data("product_stock_id");
    let product_expire_date = element.data("product_expire_date");
    let purchase_price = element.data("purchase_price");
    let product_image = element.data("product_image");
    let price_without_tax = element.data("price_without_tax");
    let has_serial = element.data("has_serial") ?? 0;

    $.ajax({
        url: url,
        type: "POST",
        data: {
            type: "sale",
            id: product_id,
            name: product_name,
            price: product_price,
            quantity: 1,
            product_type: product_type,
            product_code: product_code,
            product_unit_id: product_unit_id,
            product_unit_name: product_unit_name,
            stock_id: product_stock_id,
            expire_date: product_expire_date,
            purchase_price: purchase_price,
            product_image: product_image,
            price_without_tax: price_without_tax,
            has_serial: has_serial,
        },
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(calTotalAmount); // Update totals after cart fetch completes
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr) {
            console.error("Error:", xhr.responseText);
        },
    });
}

/** Add to Cart Functionality End **/

/** Serial Code Start **/

// Availabe Serial list
$(document).on("click", ".available-serial", function () {
    const serial = $(this).data("serial");

    if (selectedSerialNumbers.includes(serial)) {
        return;
    }

    selectedSerialNumbers.push(serial);

    const $selectedContainer = $("#serial-selection-modal .serial-container")
        .eq(1)
        .find(".serial-list");

    $selectedContainer.append(`
        <div class="serial-item selected-serial" data-serial="${serial}">
            <span>${serial}</span>
            <span class="serial-remove-btn">×</span>
        </div>
    `);

    $(this).addClass("d-none");
});

// Remove single selected serial
$(document).on("click", ".serial-remove-btn", function () {
    const $item = $(this).closest(".selected-serial");
    const serial = $item.data("serial");

    selectedSerialNumbers = selectedSerialNumbers.filter(
        (s) => s.toString() !== serial.toString(),
    );

    const $availableContainer = $("#availableSerialList");

    const $newItem = $(`
        <div class="serial-item available-serial" data-serial="${serial}">
            <span>${serial}</span>
        </div>
    `);

    $availableContainer.append($newItem);
    $newItem.show().removeClass('d-none');

    $item.remove();
});

// remove all selected serial
$(document).on("click", ".remove-all-serial", function () {
    const $selectedContainer = $("#selectedSerialList");
    const $availableContainer = $("#availableSerialList");

    // Add all selected serials back to available container
    $selectedContainer.find(".selected-serial").each(function () {
        const serial = $(this).data("serial");

        // Only add if not already in available list
        if (
            $availableContainer.find(`[data-serial="${serial}"]`).length === 0
        ) {
            $availableContainer.append(`
                <div class="serial-item available-serial" data-serial="${serial}">
                    <span>${serial}</span>
                </div>
            `);
        }
    });

    selectedSerialNumbers = [];
    $selectedContainer.empty();
});

// search serial
$(document).on("keyup", ".serial-input", function () {
    let value = $(this).val().toLowerCase();

    $("#serial-selection-modal .available-serial").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

// Confirm serial selection button
$(document).on("click", ".confirm-serial-btn", function () {
    if (!serialContext) return;

    if (selectedSerialNumbers.length === 0) {
        toastr.error("Please select at least one serial number.");
        return;
    }

    // Update existing cart row (sale-cart)
    if (serialContext === "sale-cart" && selectedProduct?.row_id) {
        const $row = $(`tr[data-row_id="${selectedProduct.row_id}"]`);
        const updateRoute = $row.data("update_route");
        const stockId = $row.data("stock_id");

        // Get current price from input
        const price = parseFloat($row.find(".cart-price").val()) || 0;
        const discount = parseFloat($row.find(".cart-discount").val()) || 0;

        $.ajax({
            url: updateRoute,
            type: "PUT",
            data: {
                rowId: selectedProduct.row_id,
                qty: selectedSerialNumbers.length,
                price: price,
                discount: discount,
                serial_numbers: selectedSerialNumbers,
            },
            success: function (response) {
                if (response.success) {
                    fetchUpdatedCart(calTotalAmount);
                    $("#serial-selection-modal").modal("hide");
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                toastr.error(
                    xhr.responseJSON?.message ||
                        "An error occurred while updating cart.",
                );
            },
        });

        return;
    }
    // Add new product from product list
    else if (serialContext === "sale") {
        $.ajax({
            url: selectedProduct.route,
            type: "POST",
            data: {
                type: "sale",
                id: selectedProduct.product_id,
                name: selectedProduct.product_name,
                quantity: selectedSerialNumbers.length,
                price: selectedProduct.sales_price,
                product_code: selectedProduct.product_code,
                product_unit_id: selectedProduct.product_unit_id,
                product_unit_name: selectedProduct.product_unit_name,
                product_image: selectedProduct.product_image,
                product_type: selectedProduct.product_type,
                serial_numbers: selectedSerialNumbers,
                customer_type: getCustomerType(),
            },
            success: function (response) {
                if (response.success) {
                    fetchUpdatedCart(calTotalAmount);
                    $("#serial-selection-modal").modal("hide");
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                toastr.error(
                    xhr.responseJSON?.message ||
                        "An error occurred while adding product.",
                );
            },
        });
    }

    // Reset
    selectedSerialNumbers = [];
    serialContext = null;
    selectedProduct = null;
});

// Serial cell button (view/edit serials)
$(document).on("click", ".serial-cell-button", function () {
    if (!$(this).closest("#sale_cart_list").length) {
        return;
    }
    const $row = $(this).closest("tr");
    const rowId = $row.data("row_id");

    // Get currently selected serials from cart row
    const selectedSerials = $row.data("serials") || [];

    // Setup selectedProduct info
    selectedProduct = {
        row_id: rowId,
        product_id: $row.data("product_id"),
        product_name: $row.data("product_name"),
        product_code: $row.data("product_code"),
        product_unit_id: $row.data("product_unit_id"),
        product_unit_name: $row.data("product_unit_name"),
        product_image: $row.data("product_image"),
        product_type: $row.data("product_type"),
        sales_price: $row.data("product_price"),
        route: $row.data("update_route"),
    };

    // Modal containers
    const $availableContainer = $("#availableSerialList");
    const $selectedContainer = $("#selectedSerialList");

    $availableContainer.empty();
    $selectedContainer.empty();
    $(".serial-input").val("");

    // Fetch all serials for this product via AJAX
    $.ajax({
        url: $("#get-product-serials").val(),
        type: "GET",
        data: {
            product_id: selectedProduct.product_id,
            warehouse_id: $(".warehouse_id").val(),
            batch_no: $row.data("batch_no") ?? null,
        },
        success: function (res) {
            const allSerials = res.serials || [];

            // Available serials = allSerials minus selectedSerials
            const remainingSerials = allSerials.filter(
                (s) => !selectedSerials.includes(s),
            );

            // Append remaining serials (original add-to-cart modal design)
            remainingSerials.forEach((serial) => {
                $availableContainer.append(`
                    <div class="serial-item available-serial" data-serial="${serial}">
                        <span>${serial}</span>
                    </div>
                `);
            });

            // Append selected serials
            selectedSerials.forEach((serial) => {
                $selectedContainer.append(`
                    <div class="serial-item selected-serial" data-serial="${serial}">
                        <span>${serial}</span>
                        <span class="serial-remove-btn">×</span>
                    </div>
                `);
            });

            // Pre-fill global selectedSerialNumbers
            selectedSerialNumbers = [...selectedSerials];

            // Set context for cart row
            serialContext = "sale-cart";

            $("#serial-selection-modal").modal("show");
        },
        error: function () {
            toastr.error("Failed to fetch serial numbers for this product.");
        },
    });
});
function lockSaleSerialCartRows() {
    $("#sale_cart_list tr").each(function (index) {
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

$(document).ready(function () {
    if ($("#has-serial-code-addon").val() == 1) {
        lockSaleSerialCartRows();
    }
});

/** Serial Code End **/
// ---------------------------------------------------------------------------------------------------------
/** INVENTORY SALE START **/

// Function to reset prices to default (retailer) prices
function resetToDefaultPrices() {
    // Reset product list prices
    $(".single-product, .add-batch-item").each(function () {
        let $element = $(this);
        let defaultPrice = $element.data("default_price");

        if (defaultPrice !== undefined) {
            let numericPrice = getNumericValue(String(defaultPrice));
            $element.find(".product_price").text(currencyFormat(numericPrice));
        }
    });

    // Reset cart prices
    $("#sale_cart_list tr").each(function () {
        let $row = $(this);
        let defaultPrice = $row.data("default_price");

        if (defaultPrice !== undefined) {
            let numericPrice = getNumericValue(String(defaultPrice));
            $row.find(".cart-price").val(numericPrice).trigger("change");
        }
    });

    calTotalAmount();
}

// Function to update product prices for inventory sale based on customer type
function updateInventoryProductPrices(customerType, customerId) {
    if (!customerType) {
        return;
    }

    // For Retailer, reset to default prices
    if (customerType === "Retailer") {
        resetToDefaultPrices();
        return;
    }

    let url = $("#get_stock_prices").val();

    // Collect cart rows if any
    let cartRows = [];
    $("#sale_cart_list tr").each(function () {
        let $row = $(this);
        let stockId = $row.data("stock_id");
        let batchNo = $row.data("batch_no") || null;

        if (stockId) {
            cartRows.push({ stock_id: stockId, batch_no: batchNo });
        }
    });

    $.ajax({
        url: url,
        type: "GET",
        data: {
            type: customerType,
            inventory: true,
            stocks: cartRows.length ? cartRows : null,
        },
        success: function (response) {
            // Update product list prices
            $(".single-product, .add-batch-item").each(function () {
                let $element = $(this);
                let stockId = $element.data("product_stock_id") || $element.data("stock_id");

                if (!stockId) return;

                let batchNo = $element.data("batch_no") || "default";
                let rawPrice = null;

                if (response.stocks && response.stocks[stockId]) {
                    if (response.stocks[stockId].hasOwnProperty(batchNo)) {
                        rawPrice = response.stocks[stockId][batchNo];
                    } else if (response.stocks[stockId].hasOwnProperty("default")) {
                        rawPrice = response.stocks[stockId]["default"];
                    }
                } else if (response.products && response.products[stockId]) {
                    rawPrice = response.products[stockId];
                }

                if (rawPrice !== null) {
                    let numericPrice = getNumericValue(String(rawPrice));
                    $element.find(".product_price").text(currencyFormat(numericPrice));
                }
            });

            // Update cart prices if cart not empty
            if (cartRows.length) {
                $("#sale_cart_list tr").each(function () {
                    let $row = $(this);
                    let stockId = $row.data("stock_id");
                    let batchNo = $row.data("batch_no") || "default";

                    if (stockId && response.stocks && response.stocks[stockId]) {
                        let rawPrice = null;

                        if (response.stocks[stockId].hasOwnProperty(batchNo)) {
                            rawPrice = response.stocks[stockId][batchNo];
                        } else if (response.stocks[stockId].hasOwnProperty("default")) {
                            rawPrice = response.stocks[stockId]["default"];
                        }

                        if (rawPrice !== null) {
                            let numericPrice = getNumericValue(String(rawPrice));
                            $row.find(".cart-price")
                                .val(numericPrice)
                                .trigger("change");
                        }
                    }
                });

                calTotalAmount();
            }
        },
    });
}

// customer selection triggers product price update based on type
$(".inventory-customer-select").on("change", function () {
    let customer_type = $(this).find(":selected").data("type");
    let $customer_id = $(this).val();

    // slight delay to prevent race condition
    setTimeout(() => {
        clearCart("sale");
        $(".payment-section input").val("");
    }, 100);

    if ($customer_id == "guest") {
        $(".guest_phone").removeClass("d-none");

        // show default (retail) price from batch data
        $(".single-product").each(function () {
            $(this)
                .find(".product_price")
                .each(function () {
                    let defaultPrice = $(this)
                        .closest(".add-batch-item, .single-product")
                        .data("default_price");
                    $(this).text(currencyFormat(defaultPrice));
                });
        });

        // Reset cart prices too
        $("#sale_cart_list tr").each(function () {
            let $row = $(this);
            let defaultPrice = $row.data("default_price");
            if (defaultPrice) {
                $row.find(".cart-price").val(defaultPrice).trigger("change");
            }
        });
        calTotalAmount();
    } else if ($customer_id) {
        $(".guest_phone").addClass("d-none");
        $(".guest_phone input").val("");

        if (customer_type) {
            updateInventoryProductPrices(customer_type, $customer_id);
        }
    }
});

// renders product dropdown list with batch-level stock/pricing info
function populateProducts(products) {
    const $dropdownList = $("#inventoryDropdownList");
    $dropdownList.empty();

    if (products.length == 0) {
        $dropdownList.append(
            '<div class="product-option-item">No products available</div>',
        );
        return;
    }

    const cartRoute = $("#cart-store-url").val();

    $.each(products, function (index, product) {
        const imageUrl = assetPath(
            product.productPicture ?? "assets/images/products/box.svg",
        );
        let html = "";

        // if multiple stocks
        if (product.stocks && product.stocks.length > 1) {
            html += `<div class="product-option-item single-product"
                data-product_id="${product.id}"
                data-route="${cartRoute}"
                data-product_type="${product.product_type}"
                data-product_name="${product.productName}"
                data-product_code="${product.productCode}"
                data-product_unit_id="${product.unit_id}"
                data-product_unit_name="${product.unit?.unitName ?? ""}"
                data-product_image="${product.productPicture}">
                <div class="product-left">
                    <img src="${imageUrl}" alt="">
                    <div class="product-text">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="product-title">${
                                product.productName
                            }</div>
                            <div class="d-flex flex-column align-items-center position-absolute cost-container">
                                <p class="cost-price">Cost Price</p>
                            </div>
                            <p>Code : ${product.productCode}</p>
                        </div>`;

            product.stocks.forEach((stock) => {
                html += `<div class="d-flex align-items-center justify-content-between position-relative w-100 multi-items add-batch-item"
                        data-product_stock_id="${stock.id}"
                        data-product_id="${product.id}"
                        data-product_expire_date="${stock.expire_date ?? ""}"
                        data-product_name="${product.productName}"
                        data-product_code="${product.productCode}"
                        data-default_price="${stock.productSalePrice}"
                        data-product_unit_id="${product.unit_id}"
                        data-product_unit_name="${product.unit?.unitName ?? ""}"
                        data-purchase_price="${stock.productPurchasePrice}"
                        data-price_without_tax="${stock.exclusive_price}"
                        data-has_serial="${product.has_serial ?? 0}"
                        data-product_image="${product.productPicture}"
                        data-batch_no="${stock.batch_no ?? 'default'}"
                        data-route="${cartRoute}">
                        <div class="product-des">
                            Batch: ${stock.batch_no ?? "N/A"}${
                                product.color ? ", Color: " + product.color : ""
                            }${
                                stock.warehouse?.name
                                    ? ", Warehouse: " +
                                      stock.warehouse.name +
                                      ","
                                    : ""
                            }
                            <span class="product-in-stock"> In Stock: ${stock.productStock ?? 0} </span>
                        </div>
                        <div class="d-flex flex-column align-items-center position-absolute cost-container-3">
                            <p class="cost-amount">${currencyFormat(stock.productPurchasePrice)}</p>
                        </div>
                        <div class="product-price product_price">${currencyFormat(
                            stock.productSalePrice,
                        )}</div>
                    </div>`;
            });

            html += `</div></div></div>`;
        }
        // single stock
        else {
            const singleStock =
                Array.isArray(product.stocks) && product.stocks.length > 0
                    ? product.stocks[0]
                    : {};

            html += `<div class="product-option-item single-product ${
                product.id
            } add-batch-item"
                data-product_stock_id="${singleStock.id ?? ""}"
                data-product_id="${product.id}"
                data-batch_no="${singleStock.batch_no ?? 'default'}"
                data-default_price="${singleStock.productSalePrice ?? 0}"
                data-product_type="${product.product_type}"
                data-product_name="${product.productName}"
                data-product_code="${product.productCode}"
                data-product_unit_id="${product.unit_id}"
                data-product_unit_name="${product.unit?.unitName ?? ""}"
                data-purchase_price="${singleStock.productPurchasePrice ?? 0}"
                data-price_without_tax="${singleStock.exclusive_price ?? 0}"
                data-has_serial="${product.has_serial ?? 0}"
                data-product_image="${product.productPicture}"
                data-product_expire_date="${singleStock.expire_date ?? ""}"
                data-route="${cartRoute}">
                <div class="product-left">
                    <img src="${imageUrl}" alt="">
                    <div class="product-text">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="product-title">${
                                product.productName
                            }</div>
                            <div class="d-flex flex-column align-items-center position-absolute cost-container">
                                <p class="cost-price">Cost Price</p>
                            </div>
                            <p>Code : ${product.productCode}</p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between w-100">
                           <div class="product-des">
                                 Batch: ${singleStock.batch_no ?? "N/A"},
                                 ${product.color ? ", Color: " + product.color : ""}
                                 ${
                                     singleStock.warehouse?.name
                                         ? ", Warehouse: " +
                                           singleStock.warehouse.name
                                         : ""
                                 }
                                 <span class="product-in-stock">
                                     In Stock: ${singleStock.productStock ?? 0}
                                 </span>
                            </div>
                            <div class="d-flex flex-column align-items-center position-absolute cost-container-2">
                            <p class="cost-amount">${currencyFormat(singleStock.productPurchasePrice ?? 0)}</p>
                            </div>
                            <div class="product-price product_price">${currencyFormat(
                                singleStock.productSalePrice ?? 0,
                            )}</div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        $dropdownList.append(html);
    });
}

function refreshInventoryProducts() {
    const allProductsUrl = $("#all-products").val();
    if (!allProductsUrl || !$("#inventoryDropdownList").length) return;

    $.get(allProductsUrl, { is_transfer: 1 }, function (products) {
        populateProducts(products);

        const preselectedCustomer = $(".inventory-customer-select option:selected");
        const preselectedCustomerId = preselectedCustomer.val();
        const preselectedCustomerType = preselectedCustomer.data("type");

        if (
            preselectedCustomerId &&
            preselectedCustomerType &&
            preselectedCustomerType !== "Retailer"
        ) {
            updateInventoryProductPrices(preselectedCustomerType, preselectedCustomerId);
        }
    });
}

// load all products initially
const allProductsUrl = $("#all-products").val();
if (allProductsUrl) {
    $.get(allProductsUrl, { is_transfer: 1 }, function (products) {
        populateProducts(products);

        // If a customer is pre-selected on page load (and not Guest/empty), update prices
        const preselectedCustomer = $(".inventory-customer-select option:selected");
        const preselectedCustomerId = preselectedCustomer.val();
        const preselectedCustomerType = preselectedCustomer.data("type");

        if (preselectedCustomerId && preselectedCustomerId !== "" && preselectedCustomerType && preselectedCustomerType !== "Retailer") {
            // Update prices for pre-selected customer type
            updateInventoryProductPrices(preselectedCustomerType, preselectedCustomerId);
        }
    });
}

// filter by category selection
$("#categorySelect").on("change", function () {
    const categoryId = $(this).val();
    const categoryUrlBase = $("#get-by-category").val();
    const allProductsUrl = $("#all-products").val();

    if (categoryId && categoryUrlBase) {
        const url = categoryUrlBase + "/" + categoryId;
        $.get(url, function (products) {
            populateProducts(products);
        });
    } else if (allProductsUrl) {
        $.get(allProductsUrl, function (products) {
            populateProducts(products);
        });
    }
});

// toggle product dropdown visibility
const $dropdown = $("#inventoryDropdownList");
const $searchContainer = $("#inventorySearchContainer");
const $productSearch = $("#inventoryProductSearch");
const $selectedText = $("#inventorySelectedValue");
const $selectedValueInput = $("#selectedProductValue");
const $arrow = $("#inventoryArrow");
let inventorySearchAutoAddTimer = null;

$("#inventoryProductDropdown .product-selected").on("click", function (e) {
    e.stopPropagation();
    $dropdown.toggle();
    $searchContainer.toggleClass("hidden");
    $arrow.toggleClass("product-rotate");
});

// Add to cart on batch click
$("#inventoryDropdownList").on("click", ".add-batch-item", function (e) {
    e.stopPropagation();

    const $element = $(this);
    const value = $element.data("value") ?? "";
    const text =
        $element.find(".product-title").text()?.trim() ||
        $element.data("product_name");

    $selectedText.text(text);
    $selectedValueInput.val(value);
    $dropdown.hide();
    $searchContainer.addClass("hidden");
    $arrow.removeClass("product-rotate");

    const hasSerial = parseInt($element.data("has_serial")) === 1;
    const serialAddonEnabled =
        parseInt($("#has-serial-code-addon").val()) === 1;
    const productType = $element.data("product_type") || "";

    // Has serial but addon disabled
    if (hasSerial && !serialAddonEnabled) {
        toastr.error(
            "This product has serial numbers. Please enable Serial Code Addon to add this product.",
        );
        return;
    }

    // Serial product
    if (hasSerial && serialAddonEnabled && productType !== "combo") {
        serialContext = "sale";

        selectedProduct = {
            product_id: $element.data("product_id"),
            product_name: $element.data("product_name"),
            product_code: $element.data("product_code"),
            product_unit_id: $element.data("product_unit_id"),
            product_unit_name: $element.data("product_unit_name"),
            product_image: $element.data("product_image"),
            product_type: $element.data("product_type"),
            sales_price: $element.data("default_price"),
            route: $element.data("route"),
        };

        $.ajax({
            url: $("#get-product-serials").val(),
            type: "GET",
            data: {
                product_id: selectedProduct.product_id,
                warehouse_id: $(".warehouse_id").val(),
            },
            success: function (res) {
                if (!res.serials || res.serials.length === 0) {
                    toastr.error(
                        "No available serial numbers found for this product.",
                    );
                    return;
                }

                selectedSerialNumbers = [];

                const $availableContainer = $("#availableSerialList");
                const $selectedContainer = $("#selectedSerialList");

                $availableContainer.empty();
                $selectedContainer.empty();
                $(".serial-input").val("");

                // Render serial list
                res.serials.forEach((serial) => {
                    $availableContainer.append(`
                        <div class="serial-item available-serial" data-serial="${serial}">
                            <span>${serial}</span>
                        </div>
                    `);
                });

                $("#serial-selection-modal").modal("show");
            },
        });

        return;
    }

    // Normal product
    addItemToCart($element);
});

// Close dropdown and search if clicked outside
$(document).on("click", function (e) {
    if (!$(e.target).closest("#inventoryProductDropdown").length) {
        $dropdown.hide();
        $searchContainer.addClass("hidden");
        $arrow.removeClass("product-rotate");
    }
});

// product search
$productSearch.on("keyup", function (e) {
    const searchTerm = this.value.toLowerCase();

    if (e.key == "Enter") return;

    clearTimeout(inventorySearchAutoAddTimer);

    const $items = $("#inventoryDropdownList .product-option-item");

    $items.each(function () {
        const $el = $(this);
        const name = String($el.data("product_name") || "").toLowerCase();
        const code = String($el.data("product_code") || "").toLowerCase();

        $el.toggle(name.includes(searchTerm) || code.includes(searchTerm));
    });

    // count visible items only
    const $visibleItems = $items.filter(":visible");
    const visibleCount = $visibleItems.length;

    if (searchTerm && visibleCount === 1) {
        const $visibleItem = $visibleItems.first();
        const $addableItems = $visibleItem.hasClass("add-batch-item")
            ? $visibleItem
            : $visibleItem.find(".add-batch-item");

        if ($addableItems.length === 1) {
            inventorySearchAutoAddTimer = setTimeout(function () {
                $addableItems.first().trigger("click");

                $productSearch.val("").focus();
                $items.show();
                $dropdown.show();
                $searchContainer.removeClass("hidden");
                $arrow.addClass("product-rotate");
            }, 150);
        }
    }
});

$(document).on("keydown", ".ajaxform", function (e) {
    if (e.key === "Enter") {
        e.preventDefault();
    }
});

/** ADDRESS HANDLING START **/

$(document).on("change", ".inventory-customer-select, .customer-select", function () {
    let customerId = $(this).val();
    let url = $("#getQuotaionCustomerRoute").val();
    let bladeUrl = $("#addressUpdateRoute").val();

    if (customerId) {
        url = url.replace(":id", customerId);
        let updateUrl = bladeUrl.replace(":id", customerId);

        $.ajax({
            url: url,
            type: "GET",
            dataType: "json",
            success: function (res) {
                $("#billing_address").html(res.billing_address);
                $("#shipping_address").html(res.shipping_address);

                $('.edit-address-btn[data-address-type="billing"]')
                    .data("url", updateUrl)
                    .attr("data-billing-address", res.billing_address);
                $('.edit-address-btn[data-address-type="shipping"]')
                    .data("url", updateUrl)
                    .attr("data-shipping-address", res.shipping_address);
            },
        });
    } else {
        $("#billing_address").html("No Address");
        $("#shipping_address").html("No Address");
    }
});

$(document).on("click", ".edit-address-btn", function () {
    let url = $(this).data("url");
    let addressType = $(this).data("address-type");
    let billingAddress = $(this).attr("data-billing-address");
    let shippingAddress = $(this).attr("data-shipping-address");
    let customerId = $(".inventory-customer-select, .customer-select").val();

    if (!customerId) {
        toastr.error(`${window.trans.please_select_customer_first}`);
        return;
    }

    $("#addressModal").modal("show");

    if (addressType === "billing") {
        $("#addressTitle").text(`${window.trans.update_billing_ddress}`);
        $("#billing_input input").val(billingAddress);
        $("#billing_input").show();
        $("#shipping_input").hide();
    } else {
        $("#addressTitle").text(`${window.trans.update_shipping_ddress}`);
        $("#shipping_input input").val(shippingAddress);
        $("#billing_input").hide();
        $("#shipping_input").show();
    }

    $("#addressUpdateForm").attr("action", url);
});

/** ADDRESS HANDLING END **/

/** INVENTORY SALE END **/

if (document.querySelector(".choices-select")) {
    const choice = new Choices(".choices-select", {
        placeholder: false,
        placeholderValue: "",
        searchPlaceholderValue: "Search...",
        removeItemButton: false,
        allowHTML: true,
        shouldSort: false,
        itemSelectText: "",
        duplicateItemsAllowed: false,
    });
}
