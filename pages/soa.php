<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/soa.php'; // generate_soa_no()
require_once __DIR__ . '/../helpers/alerts.php';

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) {
    $_SESSION['alert'] = ['type'=>'danger','message'=>'Not authenticated'];
    header('Location: /login.php');
    exit;
}

/* ============================================================
   HANDLE POST (CREATE / UPDATE / FINALIZE)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $action = $_POST['action'] ?? '';

        /* ================= CREATE ================= */
        if ($action === 'create') {

            $company_id = (int)($_POST['company_id'] ?? 0);
            $site_id    = (int)($_POST['site_id'] ?? 0);
            $terms      = (int)($_POST['terms'] ?? 0);

            if ($company_id <= 0 || $site_id <= 0 || $terms <= 0) {
                throw new Exception('Company, Site and Terms are required');
            }

            $soa_no = generate_soa_no($conn, $company_id);
            $audit  = audit_on_create($admin);

            $stmt = $conn->prepare("
                INSERT INTO statement_of_account
                (soa_no, company_id, site_id, terms, status, is_deleted,
                 date_created, date_edited, created_by, edited_by)
                VALUES
                (:soa_no, :company, :site, :terms, 'draft', 0,
                 :dc, :de, :cb, :eb)
            ");

            $stmt->execute([
                ':soa_no' => $soa_no,
                ':company'=> $company_id,
                ':site'   => $site_id,
                ':terms'  => $terms,
                ':dc'     => $audit['date_created'],
                ':de'     => $audit['date_edited'],
                ':cb'     => $audit['created_by'],
                ':eb'     => $audit['edited_by'],
            ]);

            audit_log('statement_of_account', $conn->lastInsertId(), 'CREATE', null, $_POST, $admin);

            $_SESSION['alert'] = ['type'=>'success','message'=>'SOA created'];
        }

        /* ================= FINALIZE ================= */
        if ($action === 'finalize') {

            $soa_id = (int)$_POST['soa_id'];
            if ($soa_id <= 0) throw new Exception('Invalid SOA');

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE statement_of_account
                SET status='finalized',
                    date_edited=:de,
                    edited_by=:eb
                WHERE soa_id=:id AND is_deleted=0
            ");
            $stmt->execute([
                ':id'=>$soa_id,
                ':de'=>$audit['date_edited'],
                ':eb'=>$audit['edited_by'],
            ]);

            audit_log('statement_of_account', $soa_id, 'FINALIZE', null, ['status'=>'finalized'], $admin);
            $_SESSION['alert'] = ['type'=>'success','message'=>'SOA finalized'];
        }

        header('Location: /main.php#soa.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type'=>'danger','message'=>$e->getMessage()];
        header('Location: /main.php#soa.php');
        exit;
    }
}

/* ============================================================
   LOOKUPS
   ============================================================ */
$companies = $conn->query("
    SELECT company_id, company_name
    FROM company
    WHERE is_deleted=0
    ORDER BY company_name
")->fetchAll(PDO::FETCH_ASSOC);

$sites = $conn->query("
    SELECT site_id, site_name, remarks
    FROM site
    WHERE is_deleted=0
    ORDER BY site_name
")->fetchAll(PDO::FETCH_ASSOC);

$soas = $conn->query("
    SELECT s.*, c.company_name, si.site_name
    FROM statement_of_account s
    JOIN company c ON s.company_id=c.company_id
    JOIN site si ON s.site_id=si.site_id
    WHERE s.is_deleted=0
    ORDER BY s.date_created DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Statement of Account</h2>

    <!-- CREATE SOA -->
    <div class="card mb-4">
        <div class="card-header">Create New SOA</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="row">
                    <div class="col-md-4">
                        <label>Company</label>
                        <select name="company_id" class="form-select" required>
                            <option value="">-- Select Company --</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['company_id'] ?>">
                                    <?= htmlspecialchars($c['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Site</label>
                        <select name="site_id" class="form-select" required>
                            <option value="">-- Select Site --</option>
                            <?php foreach ($sites as $s):
                                $label = $s['site_name'];
                                if ($s['remarks']) $label .= ' - '.$s['remarks'];
                            ?>
                                <option value="<?= $s['site_id'] ?>">
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Terms (Days)</label>
                        <input type="number" name="terms" class="form-control" required>
                    </div>
                </div>

                <button class="btn btn-primary mt-3">Create SOA</button>
            </form>
        </div>
    </div>

    <!-- SOA LIST -->
    <div class="card">
        <div class="card-header">SOA List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SOA No</th>
                        <th>Company</th>
                        <th>Site</th>
                        <th>Terms</th>
                        <th>Status</th>
                        <th width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($soas as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['soa_no']) ?></td>
                        <td><?= htmlspecialchars($s['company_name']) ?></td>
                        <td><?= htmlspecialchars($s['site_name']) ?></td>
                        <td><?= (int)$s['terms'] ?></td>
                        <td>
                            <span class="badge bg-<?= $s['status']==='finalized'?'dark':'success' ?>">
                                <?= ucfirst($s['status']) ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-secondary"
                               href="/main.php#trans_entry.php?soa_id=<?= $s['soa_id'] ?>">
                                Open
                            </a>

                            <a class="btn btn-sm btn-success"
                               target="_blank"
                               href="pages/reports_print.php?soa_id=<?= $s['soa_id'] ?>">
                                Print
                            </a>

                            <?php if ($s['status'] !== 'finalized'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="finalize">
                                    <input type="hidden" name="soa_id" value="<?= $s['soa_id'] ?>">
                                    <button class="btn btn-sm btn-danger"
                                            onclick="return confirm('Finalize this SOA?')">
                                        Finalize
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
