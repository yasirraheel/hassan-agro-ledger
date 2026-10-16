// Get numeric value from string
function getNumericValue(value) {
    if (value === null || value === undefined) return 0;
    return parseFloat(String(value).replace(/[^0-9.-]+/g, "")) || 0;
}

updateTotalAmount();

// Update quantity on manual change
$(document).on('change', '.return-qty', function() {
    var row = $(this).closest('tr');
    var currentQtyInput = $(this);
    var currentQty = getNumericValue(currentQtyInput.val());
    var maxQty = parseFloat(row.data('max_qty'));

    // Validate the entered quantity
    if (currentQty > maxQty) {
        toastr.error('You cannot return more than the ordered quantity.');
        currentQtyInput.val(0);
        currentQty = 0;
    } else if (currentQty < 0) {
        toastr.error('Return quantity cannot be less than 0.');
        currentQtyInput.val(0);
        currentQty = 0;
    }
    row.find(".return-qty-hidden").val(currentQty);

    updateSubTotal(row);
});


// Increase quantity
$(document).on('click', '.add-btn', function(e) {
    e.preventDefault();

    // Find the closest row and get current quantity and max quantity
    var row = $(this).closest('tr');
    var currentQtyInput = row.find('.return-qty');
    var currentQty = getNumericValue(currentQtyInput.val());
    var maxQty = parseFloat(row.data('max_qty'));

    // Check if increment will exceed maxQty
    if ((currentQty + 1) <= maxQty) {
        currentQtyInput.val(currentQty + 1);
        updateSubTotal(row);
        row.find(".return-qty-hidden").val(currentQty + 1);
    } else {
        toastr.error('You cannot return more than the ordered quantity.');
    }
});

// Decrease quantity
$(document).on('click', '.sub-btn', function(e) {
    e.preventDefault();

    // Find the closest row and get current quantity
    var row = $(this).closest('tr');
    var currentQtyInput = row.find('.return-qty');
    var currentQty = getNumericValue(currentQtyInput.val());

    // return quantity cannot negative
    if ((currentQty - 1) >= 0) {
        currentQtyInput.val(currentQty - 1);
        row.find(".return-qty-hidden").val(currentQty - 1)
        updateSubTotal(row);
    } else {
        toastr.error('Return quantity cannot be negative.');
    }
});

// Update the subtotal for each row based on the return quantity
function updateSubTotal(row, overrideQty = null) {
    // Get price from row
    let priceText = row.find(".price").text();
    let price = getNumericValue(String(priceText || ""))
        || getNumericValue(String(row.data("discounted_price_per_unit") || "0"));

    // Use overrideQty if provided (serial product), otherwise normal input
    let quantity = overrideQty !== null ? overrideQty : getNumericValue(String(row.find(".return-qty").val() || "0"));
    let subTotal = price * quantity;

    row.find(".subtotal").text(currencyFormat(subTotal));
    updateTotalAmount();
}

// Update total return amount
function updateTotalAmount() {
    var totalReturn = 0;
    $('.subtotal').each(function() {
        totalReturn += getNumericValue($(this).text());
    });

    var returnText = $('.return_amount').data('return-text');

    $('.return_amount').text(returnText + ' ' + currencyFormat(totalReturn));
}

/**  Serial Code Start **/

let selectedSerialNumbers = [];
let serialContext = null;

// Available Serial list
$(document).on("click", ".available-serial", function () {

    if (serialContext !== "sale-return") return;

    const $this = $(this);
    const serial = $this.data("serial").toString();

    if (!selectedSerialNumbers.includes(serial)) {
        selectedSerialNumbers.push(serial);
    }

    const $modal = $this.closest("#serial-selection-modal");

    $modal.find(".selected-serials .serial-list").append(`
        <div class="serial-item selected-serial" data-serial="${serial}">
            <span>${serial}</span>
            <span class="serial-remove-btn">×</span>
        </div>
    `);

    $this.remove();
});

// Serial cell button
$(document).on("click", ".serial-return-btn", function () {

    serialContext = "sale-return";

    const $row = $(this).closest("tr");
    const $modal = $("#serial-selection-modal");

    $modal.find(".modal-title").text("Return Serial Product");
    $modal.data("current-row", $row);

    const originalSerials = $row.data("serials") || [];

    let alreadySelected = [];
    if ($row.find(".selected-serials-input").val()) {
        const val = $row.find(".selected-serials-input").val();
        try {
            alreadySelected = JSON.parse(val).map(s => s.toString());
        } catch (e) {
            alreadySelected = [];
        }
    }

    const $availableContainer = $modal.find(".available-serials .serial-list");
    const $selectedContainer = $modal.find(".selected-serials .serial-list");

    $availableContainer.empty();
    $selectedContainer.empty();
    $modal.find(".serial-input").val("");

    const remainingSerials = originalSerials
        .map(s => s.toString())
        .filter(s => !alreadySelected.includes(s));

    // Append available
    remainingSerials.forEach((serial) => {
        $availableContainer.append(`
            <div class="serial-item available-serial" data-serial="${serial}">
                <span>${serial}</span>
            </div>
        `);
    });

    // Append selected
    alreadySelected.forEach((serial) => {
        $selectedContainer.append(`
            <div class="serial-item selected-serial" data-serial="${serial}">
                <span>${serial}</span>
                <span class="serial-remove-btn">×</span>
            </div>
        `);
    });

    selectedSerialNumbers = [...alreadySelected];

    $modal.modal("show");
});

// Confirm serial selection
$(document).on("click", ".confirm-serial-btn", function () {

    if (serialContext !== "sale-return") return;

    const $modal = $("#serial-selection-modal");
    const $row = $modal.data("current-row");

    // Update qty
    const serialCount = selectedSerialNumbers.length;
    $row.find(".return-qty").val(selectedSerialNumbers.length);
    $row.find(".return-qty-hidden").val(selectedSerialNumbers.length);

    // store as all strings
    $row.find(".selected-serials-input")
        .val(JSON.stringify(selectedSerialNumbers.map(s => s.toString())));

    updateSubTotal($row, serialCount);
    $modal.modal("hide");

    // Reset
    selectedSerialNumbers = [];
    serialContext = null;
});

// Remove single selected serial
$(document).on("click", ".serial-remove-btn", function () {

    if (serialContext !== "sale-return") return;

    const $item = $(this).closest(".selected-serial");
    const serial = $item.data("serial").toString();

    selectedSerialNumbers = selectedSerialNumbers.filter(s => s.toString() !== serial);

    const $modal = $item.closest("#serial-selection-modal");
    const $availableContainer = $modal.find(".available-serials .serial-list");

    if ($availableContainer.find(`[data-serial="${serial}"]`).length === 0) {
        $availableContainer.append(`
            <div class="serial-item available-serial" data-serial="${serial}">
                <span>${serial}</span>
            </div>
        `);
    }

    $item.remove();
});

// Remove all selected serials
$(document).on("click", ".remove-all-serial", function () {

    if (serialContext !== "sale-return") return;

    const $modal = $(this).closest("#serial-selection-modal");
    const $selectedContainer = $modal.find(".selected-serials .serial-list");
    const $availableContainer = $modal.find(".available-serials .serial-list");

    $selectedContainer.find(".selected-serial").each(function () {
        const serial = $(this).data("serial").toString();
        if ($availableContainer.find(`[data-serial="${serial}"]`).length === 0) {
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
        $(this).toggle(
            $(this).text().toLowerCase().indexOf(value) > -1
        );
    });
});

/** Serial Code End **/
