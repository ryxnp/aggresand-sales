<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* =========================
   DEFAULT BILLING DATE = TODAY
========================= */
$billing_date = $_GET['billing_date'] ?? date('Y-m-d');

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
    WHERE s.status = 'finalized'
      AND s.is_deleted = 0
      AND s.billing_date = :billing_date
    ORDER BY s.soa_no
");
$stmt->execute([':billing_date' => $billing_date]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">SOA Reports</h2>

    <!-- FILTER -->
    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="form-label">Billing Date</label>
                    <input type="date"
                           name="billing_date"
                           class="form-control"
                           value="<?= htmlspecialchars($billing_date) ?>"
                           required>
                </div>

                <div class="col-md-4">
                    <button class="btn btn-primary w-100">
                        Load SOAs
                    </button>
                </div>

                <?php if ($rows): ?>
        <div class="col-md-4">
            <a href="pages/reports_batch_print.php?billing_date=<?= urlencode($billing_date) ?>"
               target="_blank"
               class="btn btn-success">
                Print All (<?= count($rows) ?> SOA<?= count($rows) > 1 ? 's' : '' ?>)
            </a>
        </div>
    <?php endif; ?>

            </form>
        </div>
    </div>

    <!-- ACTION -->


    <!-- TABLE -->
    <div class="card">
        <div class="card-header">
            Finalized SOAs — <?= htmlspecialchars($billing_date) ?>
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
                        <td colspan="5" class="text-center">No SOAs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['soa_no']) ?></td>
                            <td><?= htmlspecialchars($r['company_name']) ?></td>
                            <td><?= htmlspecialchars($r['site_name']) ?></td>
                            <td><?= htmlspecialchars($r['billing_date']) ?></td>
                            <td>
                                <?php if ($r['terms'] === '*'): ?>
                                    <strong>No Cash Payment</strong>
                                <?php else: ?>
                                    <?= htmlspecialchars($r['terms']) ?> days
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
