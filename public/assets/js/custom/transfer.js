"use strict";

let allProducts = [];

// Asset path helper
function assetPath(path) {
    if (!path) return "";

    // If already a full URL, return as is
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    let baseUrl = $("#asset_base_url").val() || "";
    return baseUrl.replace(/\/$/, "") + "/" + path.replace(/^\/+/, "");
}

// renders product dropdown list
function populateProducts(products) {
    const $dropdownList = $("#dropdownList");
    $dropdownList.empty();

    if (products.length === 0) {
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
                data-has_serial="${product.has_serial}"
                data-route="${cartRoute}"
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
                html += `<div class="d-flex align-items-center justify-content-between w-100 position-relative multi-items add-batch-item"
                        data-product_stock_id="${stock.id}"
                        data-product_id="${product.id}"
                        data-has_serial="${product.has_serial}"
                        data-has_serial="${product.has_serial}"
                        data-product_expire_date="${stock.expire_date ?? ""}"
                        data-product_name="${product.productName}"
                        data-product_code="${product.productCode}"
                        data-default_price="${stock.productPurchasePrice}"
                        data-product_unit_id="${product.unit_id}"
                        data-product_unit_name="${product.unit?.unitName ?? ""}"
                        data-purchase_price="${stock.productPurchasePrice}"
                        data-product_image="${product.productPicture}"
                        data-route="${cartRoute}"
                        data-batch_no="${stock.batch_no ?? ""}"
                        data-serial_numbers='${JSON.stringify(stock.serial_numbers ?? [])}'
                        >

                        <div class="product-des">
                            Batch: ${stock.batch_no ?? "N/A"} ${
                                product.color ? ", Color: " + product.color : ""
                            }
                            <span class="product-in-stock">In Stock: ${
                                stock.productStock
                            }</span>
                        </div>
                        <div class="d-flex flex-column align-items-center position-absolute cost-container-3">
                            <p class="cost-amount">${currencyFormat(
                                stock.productPurchasePrice,
                            )}</p>
                        </div>
                        <div class="product-price product_price">${currencyFormat(
                            stock.productSalePrice,
                        )}</div>
                    </div>`;
            });

            html += `</div></div></div>`;
        }
        // Single stock
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
                data-has_serial="${product.has_serial ?? 0}"
                data-default_price="${singleStock.productPurchasePrice ?? 0}"
                data-product_name="${product.productName}"
                data-product_code="${product.productCode}"
                data-product_unit_id="${product.unit_id}"
                data-product_unit_name="${product.unit?.unitName ?? ""}"
                data-purchase_price="${singleStock.productPurchasePrice ?? 0}"
                data-product_image="${product.productPicture}"
                data-product_expire_date="${singleStock.expire_date ?? ""}"
                data-route="${cartRoute}"
                data-batch_no="${singleStock.batch_no ?? ""}"
                data-serial_numbers='${JSON.stringify(singleStock.serial_numbers ?? [])}'
                >
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
                                Batch: ${singleStock.batch_no ?? "N/A"} ${
                                    product.color
                                        ? ", Color: " + product.color
                                        : ""
                                }
                                <span class="product-in-stock">In Stock: ${
                                    Array.isArray(product.stocks)
                                        ? product.stocks.reduce(
                                              (sum, stock) =>
                                                  sum + stock.productStock,
                                              0,
                                          )
                                        : 0
                                }</span>
                            </div>
                        <div class="d-flex flex-column align-items-center position-absolute cost-container-2">
                        <p class="cost-amount">${currencyFormat(singleStock.productPurchasePrice ?? 0)}</p>
                        </div>
                            <div class="product-price product_price">
                            ${currencyFormat(singleStock.productSalePrice ?? 0)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        $dropdownList.append(html);
    });
}

// load all products initially
const allProductsUrl = $("#all-products").val();
if (allProductsUrl) {
    // $.get(allProductsUrl, function (products) {
    $.get(allProductsUrl, { is_transfer: 1 }, function (products) {
        allProducts = products; // store globally
        populateProducts(allProducts);
    });
}

// Filter products based on branch & warehouse
function filterProducts() {
    const branchId = $("#from_branch").val();
    const warehouseId = $("#from_warehouse").val();

    const filtered = allProducts
        .map((product) => {
            if (!product.stocks || product.stocks.length === 0) return null;

            // filter stocks by selected branch & warehouse
            const filteredStocks = product.stocks.filter((stock) => {
                const branchMatch = !branchId || stock.branch_id == branchId;
                const warehouseMatch =
                    !warehouseId || stock.warehouse_id == warehouseId;
                return branchMatch && warehouseMatch && stock.productStock > 0;
            });

            if (filteredStocks.length === 0) return null;

            return {
                ...product, // copy product data, keep only filtered stocks
                stocks: filteredStocks,
            };
        })
        .filter(Boolean); // remove nulls

    populateProducts(filtered);
}

// toggle product dropdown visibility
const $dropdown = $("#dropdownList");
const $searchContainer = $("#searchContainer");
const $productSearch = $("#productSearch");
const $arrow = $("#arrow");

$("#productDropdown .product-selected").on("click", function (e) {
    e.stopPropagation();
    $dropdown.toggle();
    $searchContainer.toggleClass("hidden");
    $arrow.toggleClass("product-rotate");
});

// Close dropdown if clicked outside
$(document).on("click", function (e) {
    if (!$(e.target).closest("#productDropdown").length) {
        $dropdown.hide();
        $searchContainer.addClass("hidden");
        $arrow.removeClass("product-rotate");
    }
});

// product search also respects filter
$productSearch.on("keyup", function () {
    const searchTerm = $(this).val().toLowerCase();
    $("#dropdownList .product-option-item").each(function () {
        const name = String($(this).data("product_name") || "").toLowerCase();
        const code = String($(this).data("product_code") || "").toLowerCase();
        $(this).toggle(name.includes(searchTerm) || code.includes(searchTerm));
    });
});

let selectedSerialNumbers = [];
let serialContext = null;
let currentSerialStockId = null;
let pendingProductData = null;

// Add to cart on batch click
$("#dropdownList").on("click", ".add-batch-item", function (e) {
    e.stopPropagation();

    const $item = $(this);
    $dropdown.hide();
    $searchContainer.addClass("hidden");
    $arrow.removeClass("product-rotate");

    let stockId = $item.data("product_stock_id");
    let productId = $item.data("product_id");
    let hasSerial = $item.data("has_serial") == 1 ? true : false;

    let name = $item.data("product_name");
    let code = $item.data("product_code");
    let price = parseFloat($item.data("default_price") || 0);
    let batchNo = $item.data("batch_no") || "";
    let image =
        $item.data("product_image") ||
        assetPath("assets/images/products/box.svg");
    let serialAddonEnabled = $("#has-serial-code-addon").val() == "1";

    if (hasSerial && !serialAddonEnabled) {
        toastr.error(
            "This product has serial numbers. Please enable Serial Code Addon to add this product.",
        );
        return;
    }

    let existingRow = $("#product-row-" + stockId);

    // Serial Product
    if (hasSerial) {
        pendingProductData = {
            stockId,
            productId,
            name,
            code,
            price,
            batchNo,
            image,
        };

        serialContext = "sale";
        currentSerialStockId = stockId;
        selectedSerialNumbers = [];

        let stockSerials = $item.data("serial_numbers") || [];

        const $availableContainer = $("#availableSerialList");
        const $selectedContainer = $("#selectedSerialList");

        $availableContainer.empty();
        $selectedContainer.empty();
        $(".serial-input").val("");

        stockSerials.forEach((serial) => {
            $availableContainer.append(`
                <div class="serial-item available-serial" data-serial="${serial}">
                    <span>${serial}</span>
                </div>
            `);
        });

        $("#serial-selection-modal").modal("show");
        return;
    }
    // Except Serial Product
    if (existingRow.length) {
        let qtyInput = existingRow.find(".dynamic-qty");
        qtyInput.val((parseInt(qtyInput.val()) || 1) + 1).trigger("input");
        return;
    }

    addProductRow({
        stockId,
        productId,
        name,
        code,
        price,
        batchNo,
        image,
        serials: [],
    });
});
function addProductRow(data) {
    let { stockId, productId, name, code, price, batchNo, image, serials } =
        data;
    let qty = serials.length > 0 ? serials.length : 1;

    let serialButtonHtml = "-";

    //  Build Serial Button
    if (serials.length > 0) {
        serialButtonHtml = `
           <button type="button" class="serial-cell-button serial-return-btn">
               <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                   <path d="M4 7H18" stroke="var(--clr-primary)" stroke-width="2" stroke-linecap="round"/>
                   <path d="M23 7H23.0105" stroke="var(--clr-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                   <path d="M23 14H23.0105" stroke="var(--clr-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                   <path d="M23 21H23.0105" stroke="var(--clr-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                   <path d="M4 14H18" stroke="var(--clr-primary)" stroke-width="2" stroke-linecap="round"/>
                   <path d="M4 21H18" stroke="var(--clr-primary)" stroke-width="2" stroke-linecap="round"/>
               </svg>
           </button>
            <input type="hidden" name="products[${stockId}][serial_numbers]" class="serial-hidden-input" value='${JSON.stringify(serials)}'>
        `;
    }

    let rowHtml = `
        <tr id="product-row-${stockId}">
            <td><img src="${assetPath(image)}" width="40"></td>
            <td>${name}</td>
            <td>${code}</td>
            <td>${batchNo}</td>
            <td>
                <div class="d-flex align-items-center justify-content-center">
                    <button type="button" class="incre-decre subtract-btn btn btn-sm btn-outline-secondary ${serials.length > 0 ? "d-none" : ""}">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" name="products[${stockId}][quantity]"
                           class="custom-number-input dynamic-qty form-control form-control-sm mx-1 text-center"
                           value="${qty}" min="1" step="1"
                           ${serials.length > 0 ? "readonly" : ""}>
                    <button type="button" class="incre-decre adding-btn btn btn-sm btn-outline-secondary ${serials.length > 0 ? "d-none" : ""}">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </td>
            <td>
                <input type="number" name="products[${stockId}][unit_price]" class="form-control unit-price text-center" value="${price}" min="0" readonly>
            </td>
            <td class="text-center">
                ${serialButtonHtml}
            </td>
            <td>
                <input type="number" name="products[${stockId}][tax]" class="form-control tax text-center" value="0" min="0">
            </td>
            <td>
                <input type="number" name="products[${stockId}][discount]" class="form-control discount text-center" value="0" min="0">
            </td>
            <td class="sub-total text-center">
                ${(price * qty).toFixed(2)}
            </td>
            <td>
               <button type="button" class="x-btn remove-btn" data-id="${stockId}">
                  <svg width="25" height="24" viewBox="0 0 25 24" fill="none">
                      <path d="M18.5 6L6.5 18" stroke="#E13F3D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M6.5 6L18.5 18" stroke="#E13F3D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
            </td>

            <input type="hidden" name="products[${stockId}][product_id]" value="${productId}">
        </tr>
    `;

    $("#product-list").append(rowHtml);

    updateTotals();
}

// Cart qty, price, discount, tax change
$("#product-list").on(
    "input",
    ".dynamic-qty, .unit-price, .discount, .tax",
    function () {
        let $row = $(this).closest("tr");
        let qty = parseFloat($row.find(".dynamic-qty").val()) || 0;
        let price = parseFloat($row.find(".unit-price").val()) || 0;
        let discount = parseFloat($row.find(".discount").val()) || 0;
        let tax = parseFloat($row.find(".tax").val()) || 0;

        let subtotal = qty * price + tax - discount;
        $row.find(".sub-total").text(subtotal.toFixed(2));
        updateTotals();
    },
);

// increment / decrement buttons
$("#product-list").on("click", ".adding-btn", function () {
    let $row = $(this).closest("tr");
    let $qty = $row.find(".dynamic-qty");
    $qty.val((parseInt($qty.val()) || 0) + 1).trigger("input");
});
$("#product-list").on("click", ".subtract-btn", function () {
    let $row = $(this).closest("tr");
    let $qty = $row.find(".dynamic-qty");
    let current = parseInt($qty.val()) || 1;
    if (current > 1) $qty.val(current - 1).trigger("input");
});

// remove row
$("#product-list").on("click", ".remove-btn", function () {
    $(this).closest("tr").remove();
    updateTotals();
});

// update totals function
function updateTotals() {
    let subTotal = 0,
        totalDiscount = 0,
        totalTax = 0,
        grandTotal = 0;

    $("#product-list tr").each(function () {
        let qty = parseFloat($(this).find(".dynamic-qty").val()) || 0;
        let price = parseFloat($(this).find(".unit-price").val()) || 0;
        let discount = parseFloat($(this).find(".discount").val()) || 0;
        let tax = parseFloat($(this).find(".tax").val()) || 0;

        let lineBase = qty * price;
        let lineWithTax = lineBase + tax;
        let lineSubtotal = lineWithTax - discount;

        $(this).find(".sub-total").text(lineSubtotal.toFixed(2));

        subTotal += lineBase;
        totalTax += tax;
        totalDiscount += discount;
        grandTotal += lineSubtotal;
    });

    let shipping = parseFloat($("#shipping_amount").val()) || 0;
    grandTotal += shipping;

    $("#total_amount").text(subTotal.toFixed(2));
    $("#tax_amount").text(totalTax.toFixed(2));
    $("#discount_amount").text(totalDiscount.toFixed(2));
    $("#grand_total_amount").text(grandTotal.toFixed(2));
}

// Trigger totals update on shipping change
$("#shipping_amount").on("input", function () {
    updateTotals();
});

const allWarehousesUrl = $("#branch_wise_warehouses").val();

function loadWarehouses(branchId, warehouseSelect) {
    if (!allWarehousesUrl) return;

    $.ajax({
        url: allWarehousesUrl,
        type: "GET",
        data: { branch_id: branchId },
        success: function (warehouses) {
            let options = `<option value="">${window.trans.select_one}</option>`;

            if (warehouses.length > 0) {
                $.each(warehouses, function (i, wh) {
                    options += `<option value="${wh.id}">${wh.name}</option>`;
                });
            } else {
                options += `<option value="">${window.trans.no_warehouse_available}</option>`;
            }

            $(warehouseSelect).html(options);
        },
        error: function () {
            $(warehouseSelect).html(
                `<option value="">${window.trans.error_loading_warehouses}</option>`,
            );
        },
    });
}

// Load all warehouses initially
loadWarehouses("", "#from_warehouse");
loadWarehouses("", "#to_warehouse");

// When "from_branch" changes
$("#from_branch").on("change", function () {
    let branchId = $(this).val();

    // Clear cart and update totals
    $("#product-list").empty();
    updateTotals();

    // Reload "from_warehouse" options based on selected branch
    loadWarehouses(branchId, "#from_warehouse");

    // Filter products based on new branch & warehouse
    filterProducts();
});

// When "to_branch" changes
$("#to_branch").on("change", function () {
    let branchId = $(this).val();
    let hasActiveBranch = $("#hasActiveBranch").val() == "1";

    // Only reload "to_warehouse" if no active branch
    if (!hasActiveBranch) {
        loadWarehouses(branchId, "#to_warehouse");
    }
});

// When "from_warehouse" changes
$("#from_warehouse").on("change", function () {
    // Clear cart and update totals
    $("#product-list").empty();
    updateTotals();

    // Filter products based on new warehouse
    filterProducts();
});

/** Serial Code Start **/

// Select available serial
$(document).on("click", ".available-serial", function () {
    let serial = $(this).data("serial").toString();

    if (!selectedSerialNumbers.includes(serial)) {
        selectedSerialNumbers.push(serial);
    }

    $(this).remove();

    $("#selectedSerialList").append(`
        <div class="serial-item selected-serial" data-serial="${serial}">
            <span>${serial}</span>
            <span class="serial-remove-btn">×</span>
        </div>
    `);
});

// Remove single selected serial
$(document).on("click", ".serial-remove-btn", function () {
    let serial = $(this).closest(".serial-item").data("serial").toString();

    // Remove from array
    selectedSerialNumbers = selectedSerialNumbers.filter((s) => s !== serial);

    // Remove from selected UI
    $(this).closest(".serial-item").remove();

    // Move back to available
    $("#availableSerialList").append(`
        <div class="serial-item available-serial" data-serial="${serial}">
            <span>${serial}</span>
        </div>
    `);

    if (currentSerialStockId) {
        let row = $("#product-row-" + currentSerialStockId);
        if (row.length) {
            // Update quantity input
            row.find(".dynamic-qty")
                .val(selectedSerialNumbers.length)
                .trigger("input");

            // Update hidden serial input
            let hiddenInput = row.find(".serial-hidden-input");
            if (hiddenInput.length) {
                hiddenInput.val(JSON.stringify(selectedSerialNumbers));
            }
            updateTotals();
        }
    }
});

// Remove all selected serials
$(document).on("click", ".remove-all-serial", function () {
    if (selectedSerialNumbers.length === 0) return;

    // Move all back to available
    selectedSerialNumbers.forEach((serial) => {
        $("#availableSerialList").append(`
            <div class="serial-item available-serial" data-serial="${serial}">
                <span>${serial}</span>
            </div>
        `);
    });

    // Clear array and selected UI
    selectedSerialNumbers = [];
    $("#selectedSerialList").empty();

    if (currentSerialStockId) {
        let row = $("#product-row-" + currentSerialStockId);
        if (row.length) {
            row.find(".dynamic-qty").val(0).trigger("input");
            let hiddenInput = row.find(".serial-hidden-input");
            if (hiddenInput.length)
                hiddenInput.val(JSON.stringify(selectedSerialNumbers));
            updateTotals();
        }
    }
});

// Search serial
$(document).on("keyup", ".serial-input", function () {
    let value = $(this).val().toLowerCase();

    $("#serial-selection-modal .available-serial").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

//  Confirm serial selection button
$(document).on("click", ".confirm-serial-btn", function () {
    if (!pendingProductData || selectedSerialNumbers.length === 0) {
        toastr.error("Please select at least one serial.");
        return;
    }

    let stockId = pendingProductData.stockId;
    let existingRow = $("#product-row-" + stockId);

    // duplicates serial check when serialContext = transfer
    if (serialContext === "transfer") {
        let existingSerials = [];
        if (existingRow.length) {
            existingSerials = JSON.parse(
                existingRow.find(".serial-hidden-input").val() || "[]",
            );
        }

        let duplicateSerial = selectedSerialNumbers.find((s) =>
            existingSerials.includes(s),
        );

        if (duplicateSerial) {
            toastr.error("This serial already exists in this product row.");
            return;
        }
    }

    // If Stock exist update row
    if (existingRow.length) {
        let hiddenInput = existingRow.find(".serial-hidden-input");

        // Merge new serials
        let oldSerials = JSON.parse(hiddenInput.val() || "[]");
        let mergedSerials = [...oldSerials, ...selectedSerialNumbers];
        mergedSerials = [...new Set(mergedSerials)];

        hiddenInput.val(JSON.stringify(mergedSerials));

        // Update quantity for serial products
        existingRow.find(".dynamic-qty").val(mergedSerials.length);

        let rowPrice =
            parseFloat(existingRow.find(".unit-price").val()) ||
            pendingProductData.price ||
            0; // ✅ Changed
        existingRow.find(".unit-price").val(rowPrice);

        updateTotals();
    } else {
        // Create new row for serial product with proper qty
        addProductRow({
            stockId: pendingProductData.stockId,
            productId: pendingProductData.productId,
            name: pendingProductData.name,
            code: pendingProductData.code,
            price: pendingProductData.price,
            batchNo: pendingProductData.batchNo,
            image: pendingProductData.image,
            serials: selectedSerialNumbers,
        });
    }

    // RESET
    pendingProductData = null;
    selectedSerialNumbers = [];
    currentSerialStockId = null;
    serialContext = null;

    $("#serial-selection-modal").modal("hide");
});

// Serial cell button (view/edit serials)
$(document).on("click", ".serial-cell-button", function () {
    const $row = $(this).closest("tr");

    // Current stock ID
    currentSerialStockId = $row.attr("id").replace("product-row-", "");
    serialContext = "transfer-cart";

    // Fetch existing serials from this row
    let existingSerials = [];
    const hiddenInput = $row.find(".serial-hidden-input");
    if (hiddenInput.length) {
        existingSerials = JSON.parse(hiddenInput.val() || "[]");
    }

    // Store pending product data temporarily
    pendingProductData = {
        stockId: currentSerialStockId,
        productId: $row.find('input[name$="[product_id]"]').val(),
        name: $row.find("td:nth-child(2)").text(),
        code: $row.find("td:nth-child(3)").text(),
        price: parseFloat($row.find(".unit-price").val() || 0),
        batchNo: $row.find("td:nth-child(4)").text(),
        image: $row.find("td:nth-child(1) img").attr("src"),
    };

    // Original serials from stock (all available serials)
    let stockSerials =
        $(
            `#dropdownList .add-batch-item[data-product_stock_id="${currentSerialStockId}"]`,
        ).data("serial_numbers") || [];

    // Clear selected array before filling
    selectedSerialNumbers = [];

    // Filter out already selected serials in this row
    let remainingSerials = stockSerials.filter(
        (serial) => !existingSerials.includes(serial),
    );

    // Reset modal containers
    const $availableContainer = $("#availableSerialList");
    const $selectedContainer = $("#selectedSerialList");
    $availableContainer.empty();
    $selectedContainer.empty();
    $(".serial-input").val("");

    // Pre-fill selected serials in modal
    existingSerials.forEach((serial) => {
        selectedSerialNumbers.push(serial);
        $selectedContainer.append(`
            <div class="serial-item selected-serial" data-serial="${serial}">
                <span>${serial}</span>
                <span class="serial-remove-btn">×</span>
            </div>
        `);
    });

    // Append remaining serials to available container
    remainingSerials.forEach((serial) => {
        $availableContainer.append(`
            <div class="serial-item available-serial" data-serial="${serial}">
                <span>${serial}</span>
            </div>
        `);
    });

    $("#serial-selection-modal").modal("show");
});
