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
        const form        = $("#delivery-form");
        const formTitle   = $("#delivery-form-title");
        const actionFld   = $("#delivery_action");
        const idFld       = $("#del_id");

        const customerSel = $("#delivery_customer_id");
        const deliveryDate= $("#delivery_date");
        // const billingDate = $("#billing_date");
        const drNo        = $("#dr_no");
        const poNumber    = $("#po_number");
        const terms       = $("#terms");
        const truckSel    = $("#truck_id");
        const materialSel = $("#material_id");
        const materialName= $("#material_name");
        const quantity    = $("#quantity");
        const unitPrice   = $("#unit_price");
        const statusSel   = $("#delivery_status");
        const totalAmount = $("#total_amount");

        const submitBtn   = $("#delivery-submit-btn");
        const cancelBtn   = $("#delivery-cancel-edit-btn");

        function resetForm(force = false) {
            if ($("#insert_mode").val() === "1" && !force) {
                $("#insert_mode").val("0");
                return;
            }

            actionFld.val("create");
            idFld.val("");

            form.attr("action", "pages/trans_entry.php");

            customerSel.val("").trigger("change");
            deliveryDate.val("");
            // billingDate.val("");
            drNo.val("");
            poNumber.val("");
            terms.val("");
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
            $("#delivery-insert-btn").removeClass("d-none");
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

        // When material changes, only set hidden material name; price is manual
        materialSel.on("change", function () {
            const opt   = $(this).find("option:selected");
            const name  = opt.text().trim();

            materialName.val(name);
            // Do NOT auto-set unit price anymore (manual input)
            updateTotal();
        });

        quantity.on("input", updateTotal);
        unitPrice.on("input", updateTotal);

        resetForm();

        // Edit delivery from row button
        $(".trans-btn-edit-delivery").on("click", function () {
            const row = $(this).closest(".delivery-row");

            const delId       = row.data("del-id");
            const cid         = row.data("customer-id");
            const delDate     = row.data("delivery-date");
            // const billDate    = row.data("billing-date");
            const dr          = row.data("dr-no");
            const material    = row.data("material");
            const qty         = row.data("quantity");
            const price       = row.data("unit-price");
            const stat        = row.data("status");
            const po          = row.data("po") || "";
            const termsVal    = row.data("terms") || "";

            actionFld.val("update");
            idFld.val(delId);
            form.attr("action", "pages/trans_entry.php");

            customerSel.val(String(cid)).trigger("change");

            deliveryDate.val(delDate || "");
            // billingDate.val(billDate || "");
            drNo.val(dr || "");
            poNumber.val(po);
            terms.val(termsVal);
            quantity.val(qty || "");
            unitPrice.val(price || "");
            statusSel.val(stat || "pending");

            // Try to select matching material option by its text
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
            $("#delivery-insert-btn").addClass("d-none");

            $("#deliveryFormCollapse").collapse("show");
            window.scrollTo({ top: 0, behavior: "smooth" });
        });

        // INSERT MODE (do not clear form)
        $("#delivery-insert-btn").on("click", function () {
        if ($(this).prop("disabled")) return;

        // cache current form values
        const data = {};
        $("#delivery-form")
            .find("input, select, textarea")
            .each(function () {
                if (this.name) {
                    data[this.name] = $(this).val();
                }
            });

        sessionStorage.setItem("delivery_insert_cache", JSON.stringify(data));

        $("#delivery_action").val("create");
        $("#del_id").val("");
        $("#insert_mode").val("1");

        $("#delivery-form")[0].submit();
    });

        // RESTORE FORM AFTER INSERT (post-reload)
        const cached = sessionStorage.getItem("delivery_insert_cache");
        if (cached) {
            const data = JSON.parse(cached);

            Object.keys(data).forEach(name => {
                const $el = $(`[name="${name}"]`);
                if ($el.length) {
                    $el.val(data[name]).trigger("change");
                }
            });

            sessionStorage.removeItem("delivery_insert_cache");

            // force form open
            $("#deliveryFormCollapse").collapse("show");

            // ensure create mode
            $("#delivery_action").val("create");
            $("#del_id").val("");

            // recalc total
            const q = parseFloat($("#quantity").val()) || 0;
            const p = parseFloat($("#unit_price").val()) || 0;
            $("#total_amount").val((q * p).toFixed(2));
        }


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

    function init() {
        initSelects();
        initSOABar();
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

function initSOABar() {
        const $soaSelect = $('#soa_select');
        const $soaHidden = $('#soa_id');
        const $badge = $('#soa_status_badge');
        const $btnFinalize = $('#btn_finalize_soa');
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

        function setBadge(status) {
            $badge.removeClass();
            if (!status) {
                $badge.addClass('badge bg-secondary').text('NO SOA');
            } else if (status === 'finalized') {
                $badge.addClass('badge bg-success').text('FINALIZED');
            } else {
                $badge.addClass('badge bg-warning text-dark').text('DRAFT');
            }
        }

        function applySOAState(soaId) {
            const status = window.SOA_STATUS_MAP?.[soaId] || '';

            $soaHidden.val(soaId || '');
            setBadge(status);

            const isFinal = status === 'finalized';
            disableDelivery(!soaId || isFinal);

            $btnFinalize.prop('disabled', !soaId || isFinal);
            $btnCreate.prop('disabled', !!soaId);

            if (soaId && isFinal) {
                $btnPrint
                    .attr('href', 'pages/reports_print.php?soa_id=' + soaId)
                    .css({ pointerEvents: 'auto', opacity: 1 });
            } else {
                $btnPrint
                    .attr('href', '#')
                    .css({ pointerEvents: 'none', opacity: 0.6 });
            }
        }

        $soaSelect.on('change', function () {
            const soaId = this.value || '';
            const current = getSOAFromHash();
            if (soaId === current) return;

            history.replaceState(null, '', '#trans_entry.php' + (soaId ? '?soa_id=' + soaId : ''));
            window.loadPage?.('trans_entry.php', soaId ? 'soa_id=' + soaId : '');
        });

        // INITIAL LOAD
        const initialSOA = getSOAFromHash();
        if (initialSOA) {
            $soaSelect.val(initialSOA).trigger('change.select2');
        }
        applySOAState($soaSelect.val());

        $btnFinalize.on('click', function () {
            if (!confirm('Finalize this SOA?\nThis cannot be undone.')) return;
            this.closest('form').submit();
        });
    }

