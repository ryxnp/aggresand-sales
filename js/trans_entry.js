// js/trans_entry.js

window.TransEntryPage = (() => {

    function initSelects() {
        // Make dropdowns searchable using Select2 if available
        if ($.fn.select2) {
            $('.select2-field').select2({
                width: '100%'
            });
        }
    }

    function initCustomerForm() {
        const form       = $("#customer-form");
        const formTitle  = $("#customer-form-title");
        const actionFld  = $("#customer_action");
        const idFld      = $("#customer_id");

        const company    = $("#company_id");
        const contractor = $("#contractor_id");
        const site       = $("#site_id");
        const name       = $("#customer_name");
        const contact    = $("#customer_contact_no");
        const email      = $("#customer_email");
        const address    = $("#customer_address");
        const status     = $("#customer_status");

        const submitBtn  = $("#customer-submit-btn");
        const cancelBtn  = $("#customer-cancel-edit-btn");

        function resetForm() {
            actionFld.val("create");
            idFld.val("");

            form.attr("action", "pages/trans_entry.php");

            company.val("").trigger("change");
            contractor.val("").trigger("change");
            site.val("").trigger("change");
            name.val("");
            contact.val("");
            email.val("");
            address.val("");
            status.val("active");

            formTitle.text("Customer");
            submitBtn.text("Save Customer");
            cancelBtn.addClass("d-none");
            $("#insert_mode").val("0");
        }

        resetForm();

        // (If later you add customer edit buttons, hook them here)
        cancelBtn.on("click", resetForm);
    }

    function initDeliveryForm() {
    const form         = $("#delivery-form");
    const actionFld    = $("#delivery_action");
    const idFld        = $("#del_id");

    const deliveryDate = $("#delivery_date");
    const drNo         = $("#dr_no");
    const poNumber     = $("#po_number");
    const truckSel     = $("#truck_id");
    const materialSel  = $("#material_id");
    const materialName = $("#material_name");
    const quantity     = $("#quantity");
    const unitPrice    = $("#unit_price");
    const statusSel    = $("#delivery_status");
    const totalAmount  = $("#total_amount");

    const submitBtn    = $("#delivery-submit-btn");
    const cancelBtn    = $("#delivery-cancel-edit-btn");

    /* ================= TOTAL ================= */
    function updateTotal() {
        const q = parseFloat(quantity.val()) || 0;
        const p = parseFloat(unitPrice.val()) || 0;
        totalAmount.val(q && p ? (q * p).toFixed(2) : "");
    }

    quantity.on("input", updateTotal);
    unitPrice.on("input", updateTotal);

    materialSel.on("change", function () {
        materialName.val($(this).find("option:selected").text().trim());
        updateTotal();
    });

    /* ================= PHP REHYDRATE FIX ================= */
    // Re-select material after PHP session restore
    const cachedMaterial = materialName.val();
    if (cachedMaterial) {
        materialSel.find("option").each(function () {
            if ($(this).text().trim() === cachedMaterial.trim()) {
                materialSel.val($(this).val()).trigger("change.select2");
                return false;
            }
        });
    }

    // Recalculate total on load (after PHP filled values)
    updateTotal();

    /* ================= CLEAR (EXPLICIT ONLY) ================= */
    function clearForm() {
        actionFld.val("create");
        idFld.val("");
        $("#insert_mode").val("0");

        deliveryDate.val("");
        drNo.val("");
        poNumber.val("");
        truckSel.val("").trigger("change.select2");
        materialSel.val("").trigger("change.select2");
        materialName.val("");
        quantity.val("");
        unitPrice.val("");
        statusSel.val("pending");
        totalAmount.val("");

        submitBtn.text("Save Delivery");
        cancelBtn.addClass("d-none");
        $("#delivery-insert-btn").removeClass("d-none");
    }

    /* ================= EDIT ================= */
    $(".trans-btn-edit-delivery").on("click", function () {
        const row = $(this).closest(".delivery-row");

        actionFld.val("update");
        idFld.val(row.data("del-id"));

        deliveryDate.val(row.data("delivery-date") || "");
        drNo.val(row.data("dr-no") || "");
        poNumber.val(row.data("po") || "");
        quantity.val(row.data("quantity") || "");
        unitPrice.val(row.data("unit-price") || "");
        statusSel.val((row.data("status") || "pending").toLowerCase());

        const truckId = row.data("truck-id");
        truckSel.val(truckId ? String(truckId) : "").trigger("change.select2");

        const material = row.data("material") || "";
        let found = false;
        materialSel.find("option").each(function () {
            if ($(this).text().trim() === material.trim()) {
                materialSel.val($(this).val()).trigger("change.select2");
                found = true;
                return false;
            }
        });
        if (!found) materialName.val(material);

        updateTotal();

        submitBtn.text("Update Delivery");
        cancelBtn.removeClass("d-none");
        $("#delivery-insert-btn").addClass("d-none");

        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    /* ================= INSERT ================= */
    $("#delivery-insert-btn").on("click", function () {
        if ($(this).prop("disabled")) return;

        // always return to CREATE mode
        actionFld.val("create");
        idFld.val("");
        $("#insert_mode").val("1");

        form[0].submit();
    });

    /* ================= CANCEL ================= */
    cancelBtn.on("click", clearForm);
}

    function init() {
        loadTransEntryOnce();  
        initSelects();
        initSOABar();
        initCustomerForm();
        initDeliveryForm();
        initEntryModeToggle();
        initBulkRecordForm();
    }

    return { init };

})();

function initSOABar() {
        const $soaSelect = $('#soa_select');
        const $soaHidden = $('#soa_id');
        const $btnPrint = $('#btn_print_soa');
        const $btnCreate = $('#btn_open_create_soa');

        function getSOAFromHash() {
            const q = window.location.hash.split('?')[1];
            return q ? new URLSearchParams(q).get('soa_id') : '';
        }

        function disableDelivery(disabled) {
            $('#delivery-fieldset').prop('disabled', disabled);
            $('#delivery-form').toggleClass('opacity-50', disabled);
        }

        function applySOAState(soaId) {

    $soaHidden.val(soaId || '');

    // Disable delivery ONLY if no SOA
    disableDelivery(!soaId);

    // Create SOA button
    $btnCreate.prop('disabled', !!soaId);

    // ✅ PRINT: enabled whenever SOA exists
    if (soaId) {
        $btnPrint
            .attr('href', 'pages/reports_print.php?soa_id=' + soaId)
            .css({ pointerEvents: 'auto', opacity: 1 });
    } else {
        $btnPrint
            .attr('href', '#')
            .css({ pointerEvents: 'none', opacity: 0.6 });
    }
}

        $soaSelect.on('change', function (e, meta) {
            if (meta?.silent) return;

            const soaId = this.value || '';
            history.replaceState(null, '', '#trans_entry.php' + (soaId ? '?soa_id=' + soaId : ''));
            window.loadPage?.('trans_entry.php', soaId ? 'soa_id=' + soaId : '');
        });

        // INITIAL LOAD
        const initialSOA = getSOAFromHash();
        if (initialSOA) {
            $soaSelect.val(initialSOA).trigger('change.select2', { silent: true });
        }
        applySOAState($soaSelect.val());
    }

function reloadTransEntry() {
    if (typeof window.loadPage === 'function') {
        const params = new URLSearchParams(window.location.hash.split('?')[1] || '');
        window.loadPage('trans_entry.php', params.toString());
    } else {
        location.reload();
    }
}

function loadTransEntryOnce() {
    if (window.__transEntryLoaded) return;

    const hash = window.location.hash;
    if (!hash.includes('trans_entry.php')) return;

    const clean = hash.replace('#', '');
    const [page, query] = clean.split('?');

    window.__transEntryLoaded = true;

    if (typeof window.loadPage === 'function') {
        window.loadPage(page, query || '');
    }
}

$(document).on("click", ".pagination a.page-link", function (e) {
    const href = $(this).attr("href");
    if (!href || href === "#") return;

    e.preventDefault();

    // Extract query string
    const query = href.startsWith("?") ? href.substring(1) : "";

    if (typeof window.loadPage === "function") {
        window.loadPage("trans_entry.php", query);
    } else {
        // fallback: normal navigation
        window.location.href = href;
    }
});


// Toggle between Single and Bulk Entry Modes
function initEntryModeToggle() {
    const STORAGE_KEY = "trans_entry_mode"; // single | bulk

    function hasSOA() {
        return !!window.currentSOAId;
    }

    function updateBulkControls() {
        const enabled = hasSOA();

        $("#bulk-add-row")
            .prop("disabled", !enabled)
            .toggleClass("disabled", !enabled);

        if (!enabled) {
            $("#bulk-add-row").attr("title", "Please select an SOA first");
        } else {
            $("#bulk-add-row").removeAttr("title");
        }
    }

    function showSingle(save = true) {
        $("#single-entry-card").removeClass("d-none");
        $("#bulk-entry-card").addClass("d-none");

        $("#btn-single-entry")
            .addClass("btn-primary")
            .removeClass("btn-outline-primary");

        $("#btn-bulk-entry")
            .addClass("btn-outline-primary")
            .removeClass("btn-primary");

        if (save) localStorage.setItem(STORAGE_KEY, "single");
    }

    function showBulk(save = true) {
        $("#bulk-entry-card").removeClass("d-none");
        $("#single-entry-card").addClass("d-none");

        $("#btn-bulk-entry")
            .addClass("btn-primary")
            .removeClass("btn-outline-primary");

        $("#btn-single-entry")
            .addClass("btn-outline-primary")
            .removeClass("btn-primary");

        updateBulkControls();

        if (save) localStorage.setItem(STORAGE_KEY, "bulk");
    }

    // Prevent duplicate bindings (AJAX safe)
    $(document).off("click", "#btn-single-entry");
    $(document).off("click", "#btn-bulk-entry");

    $(document).on("click", "#btn-single-entry", function () {
        showSingle(true);
    });

    $(document).on("click", "#btn-bulk-entry", function () {
        showBulk(true);
    });

    // Restore last selected mode
    const savedMode = localStorage.getItem(STORAGE_KEY);
    if (savedMode === "bulk") {
        showBulk(false);
    } else {
        showSingle(false);
    }
}

// Bulk Entry: Add Row
function initBulkRecordForm() {
    const MAX_ROWS = 10;

    /* ================= OPTIONS ================= */

    function truckOptionsHtml() {
        const $src = $("#truck_id option");
        if (!$src.length) return '<option value="">-- Select Truck --</option>';
        return $src.map((_, o) =>
            `<option value="${$(o).attr("value") ?? ""}">${$(o).text()}</option>`
        ).get().join("");
    }

    function materialOptionsHtml() {
        const $src = $("#material_id option");
        if (!$src.length) return '<option value="">-- Select Material --</option>';
        return $src.map((_, o) =>
            `<option value="${$(o).attr("value") ?? ""}">${$(o).text()}</option>`
        ).get().join("");
    }

    function hasSOA() {
        return !!window.currentSOAId;
    }

    function rowCount() {
        return $("#bulk-record-table tbody tr").length;
    }

    function updateBulkButtons() {
        const canAdd = hasSOA() && rowCount() < MAX_ROWS;
        $("#bulk-add-row").prop("disabled", !canAdd);

        const canSave = hasSOA() && rowCount() > 0;
        $("#bulk-save").prop("disabled", !canSave);
    }

    /* ================= CALCULATIONS ================= */

    function recalcRowTotal($tr) {
        const qty = parseFloat($tr.find(".bulk-qty").val()) || 0;
        const price = parseFloat($tr.find(".bulk-unit-price").val()) || 0;
        const total = qty * price;
        $tr.find(".bulk-total").val(total ? total.toFixed(2) : "");
    }

    /* ================= ADD ROW ================= */

    function addRow() {
        if (!hasSOA() || rowCount() >= MAX_ROWS) {
            updateBulkButtons();
            return;
        }

        const tr = `
        <tr>
            <td>
                <input type="date" class="form-control bulk-date" name="bulk_delivery_date[]">
            </td>
            <td>
                <input type="text" class="form-control bulk-dr" name="bulk_dr_no[]">
            </td>
            <td>
                <input type="text" class="form-control bulk-po" name="bulk_po_number[]">
            </td>
            <td>
                <select class="form-select bulk-truck" name="bulk_truck_id[]">
                    ${truckOptionsHtml()}
                </select>
            </td>
            <td>
                <select class="form-select bulk-material" name="bulk_material_id[]">
                    ${materialOptionsHtml()}
                </select>
                <input type="hidden" class="bulk-material-name" name="bulk_material_name[]">
            </td>
            <td>
                <input type="number" step="0.01" class="form-control bulk-qty" name="bulk_quantity[]">
            </td>
            <td>
                <input type="number" step="0.01" class="form-control bulk-unit-price" name="bulk_unit_price[]">
            </td>
            <td>
                <input type="text" class="form-control bulk-total" readonly>
            </td>
            <td>
                <select class="form-select bulk-status" name="bulk_status[]">
                    <option value="pending" selected>Pending</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger bulk-remove-row">&times;</button>
            </td>
        </tr>
        `;

        $("#bulk-record-table tbody").append(tr);
        updateBulkButtons();
    }

    /* ================= VALIDATION ================= */

    function validateBulkRows() {
        if (!hasSOA()) {
            alert("Please select an SOA first.");
            return false;
        }

        let error = "";

        $("#bulk-record-table tbody tr").each(function (i) {
            const row = i + 1;
            const date = $(this).find(".bulk-date").val();
            const material = $(this).find(".bulk-material").val();
            const qty = parseFloat($(this).find(".bulk-qty").val()) || 0;
            const price = parseFloat($(this).find(".bulk-unit-price").val()) || 0;

            if (!date) error = `Row ${row}: Delivery date is required`;
            else if (!material) error = `Row ${row}: Material is required`;
            else if (qty <= 0) error = `Row ${row}: Quantity must be greater than 0`;
            else if (price <= 0) error = `Row ${row}: Unit price must be greater than 0`;

            if (error) return false;
        });

        if (error) {
            alert(error);
            return false;
        }

        return true;
    }

    /* ================= EVENTS (AJAX SAFE) ================= */

    $(document).off("click.bulk", "#bulk-add-row");
    $(document).off("click.bulk", ".bulk-remove-row");
    $(document).off("input.bulk", ".bulk-qty, .bulk-unit-price");
    $(document).off("change.bulk", ".bulk-material");
    $(document).off("click.bulk", "#bulk-save");

    $(document).on("click.bulk", "#bulk-add-row", function () {
        addRow();
    });

    $(document).on("click.bulk", ".bulk-remove-row", function () {
        $(this).closest("tr").remove();
        updateBulkButtons();
    });

    $(document).on("input.bulk", ".bulk-qty, .bulk-unit-price", function () {
        recalcRowTotal($(this).closest("tr"));
    });

    $(document).on("change.bulk", ".bulk-material", function () {
        const $tr = $(this).closest("tr");
        const name = $(this).find("option:selected").text().trim();
        $tr.find(".bulk-material-name").val(name);
        recalcRowTotal($tr);
    });

    /* ================= BULK SAVE ================= */

    $(document).on("click.bulk", "#bulk-save", function () {

    const $btn = $(this);

    // If already confirmed → final save
    if ($btn.data("confirmed")) {
        $("#bulk-record-form")
            .append('<input type="hidden" name="action" value="bulk_create">')
            .append('<input type="hidden" name="form_type" value="delivery">')
            .submit();
        return;
    }

    // First click = VALIDATE
    clearBulkErrors();

    if (!frontendBulkValidate()) {
        return;
    }

    // send validation request
    const $form = $("#bulk-record-form");

    $form.find('input[name="action"]').remove();
    $form.append('<input type="hidden" name="action" value="bulk_validate">');
    $form.append('<input type="hidden" name="form_type" value="delivery">');

    $.post("pages/trans_entry.php", $form.serialize(), function (res) {

        if (res.status === "error") {
            applyBulkErrors(res.errors);
            return;
        }

        // no errors → lock form + confirm mode
        lockBulkForm();
        $btn
            .text("Confirm Save")
            .addClass("btn-danger")
            .data("confirmed", true);

    }, "json");
});


    /* ================= INIT ================= */

    updateBulkButtons();
}

function frontendBulkValidate() {
    let valid = true;

    $("#bulk-record-table tbody tr").each(function (i) {
        const $tr = $(this);

        const date = $tr.find(".bulk-date").val();
        const qty  = parseFloat($tr.find(".bulk-qty").val());
        const prc  = parseFloat($tr.find(".bulk-unit-price").val());

        if (!date) {
            markError($tr.find(".bulk-date"));
            valid = false;
        }
        if (!qty || qty <= 0) {
            markError($tr.find(".bulk-qty"));
            valid = false;
        }
        if (!prc || prc <= 0) {
            markError($tr.find(".bulk-unit-price"));
            valid = false;
        }
    });

    return valid;
}

function markError($el) {
    $el.addClass("is-invalid");
}

function clearBulkErrors() {
    $("#bulk-record-table .is-invalid").removeClass("is-invalid");
}

function applyBulkErrors(errors) {
    errors.forEach(err => {
        const $row = $("#bulk-record-table tbody tr").eq(err.row);
        const $field = $row.find(`[name="${err.field}[]"]`);
        $field.addClass("is-invalid");
    });
}

function lockBulkForm() {

    // Inputs → readonly (still submitted)
    $("#bulk-record-table input")
        .prop("readonly", true)
        .addClass("bg-light");

    // Selects → visually locked but still submitted
    $("#bulk-record-table select")
        .addClass("bulk-select-locked");

    // Prevent interaction
    $("#bulk-record-table select").on("mousedown.bulklock", function (e) {
        e.preventDefault();
    });

    // Buttons disabled
    $("#bulk-add-row, .bulk-remove-row").prop("disabled", true);
}

