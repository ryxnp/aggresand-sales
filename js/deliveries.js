// js/deliveries.js

window.DeliveriesPage = (() => {

    function initFilters() {
        $('.deliveries-filter-form').on('submit', function (e) {
            e.preventDefault();
            window.loadPage('deliveries.php', $(this).serialize());
        });
    }

    function initAmountPaidValidation() {

        $(document).on('input', 'input[name="amount_paid"]', function () {

            const input = $(this);
            let value = parseFloat(input.val());

            if (isNaN(value) || value < 0) {
                input.val('');
                return;
            }

            // get selected delivery
            const d = $('.delivery-row.table-active').data();
            if (!d) return;

            const balance = parseFloat(
                String(d.balance).replace(/,/g, '')
            );

            if (!isNaN(balance) && value > balance) {
                input.val(balance.toFixed(2));
            }
        });
    }


    function renderPaymentForm(d, p = null) {
        const isEdit = !!p;

        return `
            <form method="POST" action="pages/deliveries.php">
                <input type="hidden" name="action" value="${isEdit ? 'update_payment' : 'add_payment'}">
                <input type="hidden" name="dr_no" value="${d.dr}">
                ${isEdit ? `<input type="hidden" name="payment_id" value="${p.payment_id}">` : ''}

                <div class="mb-2">
                    <label class="form-label">Date Paid</label>
                    <input type="date"
                           name="date_paid"
                           class="form-control"
                           value="${p ? p.date_paid : ''}"
                           required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Amount Paid</label>
                    <input type="number"
                           step="0.01"
                           name="amount_paid"
                           class="form-control"
                           value="${p ? p.amount_paid : ''}"
                           required>
                </div>

                <div class="mb-2">
                    <label class="form-label">SI No</label>
                    <input name="si_no"
                           class="form-control"
                           value="${p ? (p.si_no || '') : ''}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Check No</label>
                    <input name="check_no"
                           class="form-control"
                           value="${p ? (p.check_no || '') : ''}">
                </div>

                <button class="btn btn-primary w-100">
                    ${isEdit ? 'Update Payment' : 'Save Payment'}
                </button>

                ${isEdit ? `
                    <button type="button"
                            class="btn btn-secondary w-100 mt-2"
                            id="cancel-payment-edit">
                        Cancel Edit
                    </button>
                ` : ''}
            </form>
        `;
    }

    function renderPaymentHistory(payments) {
    if (!payments || !payments.length) {
        return '<p class="text-muted mb-0">No payments recorded.</p>';
    }

    return `
        <table class="table table-bordered table-sm mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>SI No</th>
                    <th>Check No</th>
                    <th width="160">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${payments.map(p => `
                    <tr class="payment-row"
                        data-si="${(p.si_no || '').toLowerCase()}"
                        data-status="${p.status}">
                        <td>${p.date_paid}</td>
                        <td>${parseFloat(p.amount_paid).toFixed(2)}</td>
                        <td>${p.si_no || ''}</td>
                        <td>${p.check_no || ''}</td>
                        <td class="text-nowrap">
                            <button type="button"
                                    class="btn btn-sm btn-secondary payment-edit-btn"
                                    data-payment='${JSON.stringify(p)}'>
                                Edit
                            </button>

                            <form method="POST"
                                  action="pages/deliveries.php"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete this payment?');">
                                <input type="hidden" name="action" value="delete_payment">
                                <input type="hidden" name="payment_id" value="${p.payment_id}">
                                <input type="hidden" name="dr_no" value="${p.dr_no}">
                                <button class="btn btn-sm btn-danger">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

    function initRowSelection() {
        $(document).on('click', '.delivery-row', function () {

            $('.delivery-row').removeClass('table-active');
            $(this).addClass('table-active');

            const d = $(this).data();

            const paymentsByDr = JSON.parse(
                $('#payments-data').attr('data-payments') || '{}'
            );
            const payments = paymentsByDr[d.dr] || [];

            $('#delivery-info').html(`
                <div><strong>DR No:</strong> ${d.dr}</div>
                <div><strong>Company:</strong> ${d.company}</div>
                <hr class="my-2">
                <div><strong>Billing Date:</strong> ${d.billing || '-'}</div>
                <div><strong>Plate No:</strong> ${d.plate || '-'}</div>
            `);

            $('#dr-summary').html(`
                <div><strong>Total:</strong> ₱${d.drTotal}</div>
                <div class="text-success"><strong>Paid:</strong> ₱${d.paid}</div>
                <hr class="my-2">
                <div class="text-danger fs-5">
                    <strong>Balance:</strong> ₱${d.balance}
                </div>
            `);

            $('#payment-form-container').html(renderPaymentForm(d));
            $('#payment-history').html(renderPaymentHistory(payments));
        });
    }

    function initPaymentEdit() {

        // Edit payment
        $(document).on('click', '.payment-edit-btn', function () {
            const p = JSON.parse($(this).attr('data-payment'));
            const d = $('.delivery-row.table-active').data();

            if (!d) {
                alert('Please select a delivery row first.');
                return;
            }

            $('#payment-form-container').html(
                renderPaymentForm(d, p)
            );
        });

        // Cancel edit
        $(document).on('click', '#cancel-payment-edit', function () {
            const d = $('.delivery-row.table-active').data();
            if (!d) return;

            $('#payment-form-container').html(
                renderPaymentForm(d)
            );
        });
    }

    function init() {
        initFilters();
        initRowSelection();
        initPaymentEdit();
        initAmountPaidValidation();
        // no auto-select
    }

    return { init };

})();
