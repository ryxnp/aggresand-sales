<?php
require_once __DIR__ . '/../config/db.php';

/* =========================
   BILLING DATE (FROM HASH / GET)
========================= */
$billing_date = $_GET['billing_date'] ?? date('Y-m-d');

/* =========================
   FETCH FINALIZED SOAs
========================= */
$stmt = $conn->prepare("
    SELECT
        s.soa_id,
        s.soa_no,
        s.billing_date,
        s.terms,
        c.company_name,
        si.site_name
    FROM statement_of_account s
    JOIN company c ON s.company_id = c.company_id
    JOIN site si ON s.site_id = si.site_id
      AND s.is_deleted = 0
      AND s.billing_date = :billing_date
    ORDER BY s.soa_no ASC
");
$stmt->execute([':billing_date' => $billing_date]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">SOA Reports</h2>

    <div class="row g-3">
        <!-- ================= LEFT (30%) FILTER ================= -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header fw-bold">
                    Filters
                </div>
                <div class="card-body">

                    <label class="form-label fw-bold mb-1">
                        Billing Date
                    </label>

                    <input
                        type="date"
                        id="billing_date_picker"
                        class="form-control mb-2"
                        value="<?= htmlspecialchars($billing_date) ?>"
                    >

                    <small class="text-muted d-block mb-3">
                        SOAs update automatically
                    </small>

                    <?php if ($rows): ?>
                        <a
                            href="pages/reports_batch_print.php?billing_date=<?= urlencode($billing_date) ?>"
                            target="_blank"
                            class="btn btn-success w-100"
                        >
                            Print All (<?= count($rows) ?> SOAs)
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success w-100" disabled>
                            Print All
                        </button>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- ================= RIGHT (70%) TABLE ================= -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header fw-bold">
                    Available SOAs — <?= htmlspecialchars($billing_date) ?>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SOA No</th>
                                <th>Company</th>
                                <th>Site</th>
                                <th>Billing Date</th>
                                <th>Terms</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No finalized SOAs found for this date.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['soa_no']) ?></td>
                                    <td><?= htmlspecialchars($r['company_name']) ?></td>
                                    <td><?= htmlspecialchars($r['site_name']) ?></td>
                                    <td><?= htmlspecialchars($r['billing_date']) ?></td>
                                    <td>
                                        <?= $r['terms'] === '*'
                                            ? '<strong>No Cash Payment</strong>'
                                            : htmlspecialchars($r['terms']) . ' days' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* =========================================================
   SPA DATE CHANGE HANDLER (NO PAGE RELOAD)
========================================================= */
(function () {
    const picker = document.getElementById('billing_date_picker');
    if (!picker) return;

    picker.addEventListener('change', function () {
        const date = this.value;
        if (!date) return;

        // Update hash (SPA-safe)
        const hash = '#reports.php?billing_date=' + encodeURIComponent(date);
        history.replaceState(null, '', hash);

        // Reload reports via loader
        if (typeof window.loadPage === 'function') {
            window.loadPage('reports.php', 'billing_date=' + date);
        }
    });
})();
</script>
