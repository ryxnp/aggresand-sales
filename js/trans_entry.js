// js/trans_entry.js

window.TransEntryPage = (() => {


    function select2FormatSite(item) {
    if (!item.id) {
        return item.text; // default for placeholder
    }

    const remarks = $(item.element).data('remarks') || '';

    const $container = $(`
        <div>
            <div><strong>${item.text}</strong></div>
            ${remarks ? `<small class="text-muted">${remarks}</small>` : ''}
        </div>
    `);

    return $container;
}

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
        }

        resetForm();

        // (If later you add customer edit buttons, hook them here)
        cancelBtn.on("click", resetForm);
    }

    function initDeliveryForm() {
        const form        = $("#delivery-form");
        const formTitle   = $("#delivery-form-title");
        const actionFld   = $("#delivery_action");
        const idFld       = $("#del_id");

        const customerSel = $("#delivery_customer_id");
        const deliveryDate= $("#delivery_date");
        const billingDate = $("#billing_date");
        const drNo        = $("#dr_no");
        const truckSel    = $("#truck_id");
        const materialSel = $("#material_id");
        const materialName= $("#material_name");
        const quantity    = $("#quantity");
        const unitPrice   = $("#unit_price");
        const statusSel   = $("#delivery_status");
        const totalAmount = $("#total_amount");

        const submitBtn   = $("#delivery-submit-btn");
        const cancelBtn   = $("#delivery-cancel-edit-btn");

        function resetForm() {
            actionFld.val("create");
            idFld.val("");

            form.attr("action", "pages/trans_entry.php");

            customerSel.val("").trigger("change");
            deliveryDate.val("");
            billingDate.val("");
            drNo.val("");
            truckSel.val("").trigger("change");
            materialSel.val("").trigger("change");
            materialName.val("");
            quantity.val("");
            unitPrice.val("");
            statusSel.val("pending");
            totalAmount.val("");

            formTitle.text("Delivery");
            submitBtn.text("Save Delivery");
            cancelBtn.addClass("d-none");
        }

        function updateTotal() {
            const q  = parseFloat(quantity.val()) || 0;
            const up = parseFloat(unitPrice.val()) || 0;
            const tot = q * up;
            if (!isNaN(tot)) {
                totalAmount.val(tot.toFixed(2));
            } else {
                totalAmount.val("");
            }
        }

        // When material changes, autofill unit_price and material_name
        materialSel.on("change", function () {
            const opt   = $(this).find("option:selected");
            const price = parseFloat(opt.data("unit-price")) || 0;
            const name  = opt.data("name") || opt.text().trim();

            if (!isNaN(price)) {
                unitPrice.val(price.toFixed(2));
            } else {
                unitPrice.val("");
            }
            materialName.val(name);

            updateTotal();
        });

        quantity.on("input", updateTotal);
        // unitPrice is read-only but we still react in case code changes it
        unitPrice.on("input", updateTotal);

        resetForm();

        // Edit delivery from row button
        $(".trans-btn-edit-delivery").on("click", function () {
            const row = $(this).closest(".delivery-row");

            const delId       = row.data("del-id");
            const cid         = row.data("customer-id");
            const delDate     = row.data("delivery-date");
            const billDate    = row.data("billing-date");
            const dr          = row.data("dr-no");
            const material    = row.data("material");
            const qty         = row.data("quantity");
            const price       = row.data("unit-price");
            const stat        = row.data("status");

            actionFld.val("update");
            idFld.val(delId);
            form.attr("action", "pages/trans_entry.php");

            // Set customer dropdown
            customerSel.val(String(cid)).trigger("change");

            deliveryDate.val(delDate || "");
            billingDate.val(billDate || "");
            drNo.val(dr || "");
            quantity.val(qty || "");
            unitPrice.val(price || "");
            statusSel.val(stat || "pending");

            // Try to select matching material option by text
            let found = false;
            materialSel.find("option").each(function () {
                if ($(this).text().trim() === (material || "").trim()) {
                    materialSel.val($(this).val()).trigger("change");
                    found = true;
                    return false;
                }
            });
            if (!found) {
                materialSel.val("").trigger("change");
                materialName.val(material || "");
            }

            updateTotal();

            submitBtn.text("Update Delivery");
            formTitle.text("Edit Delivery #" + delId);
            cancelBtn.removeClass("d-none");

            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        cancelBtn.on("click", resetForm);
    }

    function initFiltersAndPagination() {
        // Filters form → SPA reload
        $(".trans-filter-form").on("submit", function (e) {
            e.preventDefault();
            const query = $(this).serialize();
            if (typeof window.loadPage === "function") {
                window.loadPage("trans_entry.php", query);
            }
        });

        // Pagination links → SPA reload
        $(".pagination .page-link").on("click", function (e) {
            e.preventDefault();
            const href  = $(this).attr("href") || "";
            const parts = href.split("?");
            const query = parts[1] || "";
            if (typeof window.loadPage === "function") {
                window.loadPage("trans_entry.php", query);
            }
        });
    }

    function init() {
        initSelects();
        initCustomerForm();
        initDeliveryForm();
        initFiltersAndPagination();
        initCollapseToggles();
    }

    return { init };

})();

function initCollapseToggles() {
    // Any button with data-bs-toggle="collapse" and data-bs-target
    document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target]').forEach(btn => {
        const targetSelector = btn.getAttribute('data-bs-target');
        const target = document.querySelector(targetSelector);
        if (!target) return;

        const updateLabel = () => {
            const isShown = target.classList.contains('show');
            btn.textContent = isShown ? 'Hide form' : 'Show form';
        };

        // Initial label
        updateLabel();

        target.addEventListener('shown.bs.collapse', updateLabel);
        target.addEventListener('hidden.bs.collapse', updateLabel);
    });
}

