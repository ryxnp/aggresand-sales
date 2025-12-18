<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/audit_fields.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/soa.php'; // <-- 4C helper (generate_soa_no)

$admin       = $_SESSION['admin_id'] ?? null;
$redirectUrl = '/main.php#trans_entry.php';

/* ============================================================
   SOA CONTEXT (single source of truth)
   ============================================================ */
$soa_id = (isset($_GET['soa_id']) && ctype_digit((string)$_GET['soa_id'])) ? (int)$_GET['soa_id'] : 0;
$soa = null;

if ($soa_id > 0) {
    $stmt = $conn->prepare("
        SELECT soa_id, soa_no, company_id, site_id, terms, status
        FROM statement_of_account
        WHERE soa_id = :id AND is_deleted = 0
        LIMIT 1
    ");
    $stmt->execute([':id' => $soa_id]);
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$soa) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid SOA'];
        header('Location: /main.php#reports.php');
        exit;
    }
}

/* ============================================================
   HANDLE POST (SOA + CUSTOMER + DELIVERY)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$admin) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
        header("Location: $redirectUrl");
        exit;
    }

    $formType = $_POST['form_type'] ?? '';

    try {

        /* ===================== SOA (CREATE / FINALIZE) ===================== */
        if ($formType === 'soa') {
            $action = $_POST['action'] ?? 'create';

            if ($action === 'create') {
                $company_id = (int)($_POST['company_id'] ?? 0);
                $site_id    = (int)($_POST['site_id'] ?? 0);
                $terms = $_POST['terms'] ?? '*';
                $allowedTerms = ['*', '15', '30', '45'];

                if ($company_id <= 0) throw new Exception('Company is required for SOA');
                if ($site_id <= 0) throw new Exception('Site is required for SOA');
                if (!in_array($terms, $allowedTerms, true)) {
                    throw new Exception('Invalid Terms of Payment');
                }

                $audit  = audit_on_create($admin);
                $soa_no = generate_soa_no($conn);

                $stmt = $conn->prepare("
                    INSERT INTO statement_of_account
                        (soa_no, company_id, site_id, terms, status, is_deleted,
                         date_created, date_edited, created_by, edited_by)
                    VALUES
                        (:soa_no, :company_id, :site_id, :terms, 'draft', 0,
                         :date_created, :date_edited, :created_by, :edited_by)
                ");
                $stmt->execute([
                    ':soa_no'       => $soa_no,
                    ':company_id'   => $company_id,
                    ':site_id'      => $site_id,
                    ':terms'        => $terms,
                    ':date_created' => $audit['date_created'],
                    ':date_edited'  => $audit['date_edited'],
                    ':created_by'   => $audit['created_by'],
                    ':edited_by'    => $audit['edited_by'],
                ]);

                $newSOAId = (int)$conn->lastInsertId();
                audit_log('statement_of_account', $newSOAId, 'CREATE', null, [
                    'soa_no'     => $soa_no,
                    'company_id' => $company_id,
                    'site_id'    => $site_id,
                    'terms'      => $terms,
                    'status'     => 'draft',
                ], $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => "SOA created ($soa_no)"];
                header("Location: /main.php#trans_entry.php?soa_id=".$newSOAId);
                exit;
            }

            if ($action === 'finalize') {
                $soa_id_post = (int)($_POST['soa_id'] ?? 0);
                if ($soa_id_post <= 0) throw new Exception('Invalid SOA');

                $stmt = $conn->prepare("
                    SELECT * FROM statement_of_account
                    WHERE soa_id = :id AND is_deleted = 0
                    LIMIT 1
                ");
                $stmt->execute([':id' => $soa_id_post]);
                $oldSOA = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$oldSOA) throw new Exception('SOA not found');

                if (($oldSOA['status'] ?? '') === 'finalized') {
                    throw new Exception('SOA already finalized');
                }

                $audit = audit_on_update($admin);

                $upd = $conn->prepare("
                    UPDATE statement_of_account
                    SET status = 'finalized',
                        date_edited = :date_edited,
                        edited_by = :edited_by
                    WHERE soa_id = :id
                ");
                $upd->execute([
                    ':id'         => $soa_id_post,
                    ':date_edited'=> $audit['date_edited'],
                    ':edited_by'  => $audit['edited_by'],
                ]);

                audit_log('statement_of_account', $soa_id_post, 'FINALIZE', $oldSOA, ['status' => 'finalized'], $admin);

                $_SESSION['alert'] = ['type' => 'success', 'message' => 'SOA finalized (Deliveries locked)'];
                header("Location: /main.php#trans_entry.php?soa_id=".$soa_id_post);
                exit;
            }

            throw new Exception('Invalid SOA action');
        }

        /* ===================== CUSTOMER ===================== */
        if ($formType === 'customer') {

            $action = $_POST['action'] ?? 'create';
            $id     = (int)($_POST['customer_id'] ?? 0);

            // If SOA exists, company/site are locked to that SOA
            if (!empty($_POST['soa_id'])) {
                $soa_id_post = (int)$_POST['soa_id'];
                if ($soa_id_post > 0) {
                    $s = $conn->prepare("SELECT soa_id, company_id, site_id, status FROM statement_of_account WHERE soa_id=:id AND is_deleted=0");
                    $s->execute([':id' => $soa_id_post]);
                    $soaRow = $s->fetch(PDO::FETCH_ASSOC);
                    if ($soaRow) {
                        $company_id = (int)$soaRow['company_id'];
                        $site_id    = (int)$soaRow['site_id'];
                    } else {
                        $company_id = (int)($_POST['company_id'] ?? 0) ?: null;
                        $site_id    = (int)($_POST['site_id'] ?? 0) ?: null;
                    }
                } else {
                    $company_id = (int)($_POST['company_id'] ?? 0) ?: null;
                    $site_id    = (int)($_POST['site_id'] ?? 0) ?: null;
                }
            } else {
                $company_id = (int)($_POST['company_id'] ?? 0) ?: null;
                $site_id    = (int)($_POST['site_id'] ?? 0) ?: null;
            }

            $contractor_id = (int)($_POST['contractor_id'] ?? 0) ?: null;
            $customer_name = trim($_POST['customer_name'] ?? '');
            $contact_no    = trim($_POST['contact_no'] ?? '');
            $email         = trim($_POST['email'] ?? '');
            $address       = trim($_POST['address'] ?? '');
            $status        = $_POST['status'] ?? 'active';

            if ($customer_name === '') throw new Exception('Customer name is required');

            if ($action === 'create') {
                $audit = audit_on_create($admin);

                $stmt = $conn->prepare("
                    INSERT INTO customer
                        (company_id, contractor_id, site_id, customer_name,
                         contact_no, email, address, status, is_deleted,
                         date_created, date_edited, created_by, edited_by)
                    VALUES
                        (:company_id, :contractor_id, :site_id, :customer_name,
                         :contact_no, :email, :address, :status, 0,
                         :date_created, :date_edited, :created_by, :edited_by)
                ");
                $stmt->execute([
                    ':company_id'    => $company_id,
                    ':contractor_id' => $contractor_id,
                    ':site_id'       => $site_id,
                    ':customer_name' => $customer_name,
                    ':contact_no'    => $contact_no,
                    ':email'         => $email,
                    ':address'       => $address,
                    ':status'        => $status,
                    ':date_created'  => $audit['date_created'],
                    ':date_edited'   => $audit['date_edited'],
                    ':created_by'    => $audit['created_by'],
                    ':edited_by'     => $audit['edited_by'],
                ]);

                $newId = (int)$conn->lastInsertId();
                audit_log('customer', $newId, 'CREATE', null, $_POST, $admin);

                $_SESSION['alert'] = ['type'=>'success','message'=>'Customer created'];

                // keep SOA context if present
                $redir = $redirectUrl . ($soa_id > 0 ? '?soa_id='.$soa_id : '');
                header("Location: $redir");
                exit;
            }

            if ($action === 'update') {
                if ($id <= 0) throw new Exception('Invalid customer ID');

                $old = $conn->prepare("SELECT * FROM customer WHERE customer_id=:id AND is_deleted=0");
                $old->execute([':id'=>$id]);
                $oldData = $old->fetch(PDO::FETCH_ASSOC);
                if (!$oldData) throw new Exception('Customer not found');

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
                    UPDATE customer SET
                        company_id    = :company_id,
                        contractor_id = :contractor_id,
                        site_id       = :site_id,
                        customer_name = :customer_name,
                        contact_no    = :contact_no,
                        email         = :email,
                        address       = :address,
                        status        = :status,
                        date_edited   = :date_edited,
                        edited_by     = :edited_by
                    WHERE customer_id = :id
                ");
                $stmt->execute([
                    ':id'            => $id,
                    ':company_id'    => $company_id,
                    ':contractor_id' => $contractor_id,
                    ':site_id'       => $site_id,
                    ':customer_name' => $customer_name,
                    ':contact_no'    => $contact_no,
                    ':email'         => $email,
                    ':address'       => $address,
                    ':status'        => $status,
                    ':date_edited'   => $audit['date_edited'],
                    ':edited_by'     => $audit['edited_by'],
                ]);

                audit_log('customer', $id, 'UPDATE', $oldData, $_POST, $admin);
                $_SESSION['alert'] = ['type'=>'success','message'=>'Customer updated'];

                $redir = $redirectUrl . ($soa_id > 0 ? '?soa_id='.$soa_id : '');
                header("Location: $redir");
                exit;
            }
        }

        /* ===================== DELIVERY (4C AUTO-CREATE SOA) ===================== */
        if ($formType === 'delivery') {

            $action = $_POST['action'] ?? 'create';
            $id     = (int)($_POST['del_id'] ?? 0);

            $soa_id_post   = (int)($_POST['soa_id'] ?? 0); // may be 0 (4C)
            $customer_id   = (int)($_POST['delivery_customer_id'] ?? 0);
            $delivery_date = trim($_POST['delivery_date'] ?? '');
            if ($delivery_date === '') {
                throw new Exception('Delivery date is required');
            }

            $dr_no         = trim($_POST['dr_no'] ?? '');
            $po_number     = trim($_POST['po_number'] ?? '') ?: null;
            $truck_id      = (int)($_POST['truck_id'] ?? 0) ?: null;
            $material      = trim($_POST['material_name'] ?? '');
            $quantity      = (float)($_POST['quantity'] ?? 0);
            $unit_price    = (float)($_POST['unit_price'] ?? 0);
            $status        = $_POST['status'] ?? 'pending';

            if ($customer_id <= 0) throw new Exception('Customer is required');
            if ($delivery_date === '') throw new Exception('Delivery date is required');

            // 4C: if no SOA selected, auto-create SOA based on customer's company + site
            if ($soa_id_post <= 0) {
                $cu = $conn->prepare("SELECT customer_id, company_id, site_id FROM customer WHERE customer_id=:id AND is_deleted=0 LIMIT 1");
                $cu->execute([':id' => $customer_id]);
                $cuRow = $cu->fetch(PDO::FETCH_ASSOC);
                if (!$cuRow) throw new Exception('Customer not found');

                $company_id_for_soa = (int)($cuRow['company_id'] ?? 0);
                $site_id_for_soa    = (int)($cuRow['site_id'] ?? 0);

                if ($company_id_for_soa <= 0 || $site_id_for_soa <= 0) {
                    throw new Exception('Customer must have Company and Site to auto-create SOA');
                }
                if ($terms === null || $terms < 0) {
                    throw new Exception('Terms is required to auto-create SOA');
                }

                $audit  = audit_on_create($admin);
                $soa_no = generate_soa_no($conn);

                $stmt = $conn->prepare("
                    INSERT INTO statement_of_account
                        (soa_no, company_id, site_id, terms, status, is_deleted,
                         date_created, date_edited, created_by, edited_by)
                    VALUES
                        (:soa_no, :company_id, :site_id, :terms, 'draft', 0,
                         :date_created, :date_edited, :created_by, :edited_by)
                ");
                $stmt->execute([
                    ':soa_no'       => $soa_no,
                    ':company_id'   => $company_id_for_soa,
                    ':site_id'      => $site_id_for_soa,
                    ':terms'        => $terms,
                    ':date_created' => $audit['date_created'],
                    ':date_edited'  => $audit['date_edited'],
                    ':created_by'   => $audit['created_by'],
                    ':edited_by'    => $audit['edited_by'],
                ]);

                $soa_id_post = (int)$conn->lastInsertId();

                audit_log('statement_of_account', $soa_id_post, 'CREATE', null, [
                    'soa_no'     => $soa_no,
                    'company_id' => $company_id_for_soa,
                    'site_id'    => $site_id_for_soa,
                    'terms'      => $terms,
                    'status'     => 'draft',
                ], $admin);
            }

            // SOA validation (must exist)
            $soaStmt = $conn->prepare("
                SELECT soa_id, status
                FROM statement_of_account
                WHERE soa_id = :id AND is_deleted = 0
                LIMIT 1
            ");
            $soaStmt->execute([':id' => $soa_id_post]);
            $soaRow = $soaStmt->fetch(PDO::FETCH_ASSOC);
            if (!$soaRow) throw new Exception('Invalid SOA selected');
            if (($soaRow['status'] ?? '') === 'finalized') throw new Exception('SOA is finalized. Deliveries are locked.');
            
            // normalize once
            $dr_no = trim((string)$dr_no);

            // ================= DR NUMBER UNIQUENESS CHECK =================
            if ($action === 'create') {

                if ($dr_no !== '') {
                    $chk = $conn->prepare("
                        SELECT COUNT(*)
                        FROM delivery
                        WHERE TRIM(dr_no) = :dr_no
                        AND is_deleted = 0
                    ");
                    $chk->execute([':dr_no' => $dr_no]);

                    if ((int)$chk->fetchColumn() > 0) {
                        throw new Exception('DR Number already exists. Please use a unique DR Number.');
                    }
                }

            } elseif ($action === 'update') {

                if ($id <= 0) throw new Exception('Invalid delivery ID');

                // fetch old row early (needed for comparison)
                $old = $conn->prepare("SELECT * FROM delivery WHERE del_id=:id AND is_deleted=0");
                $old->execute([':id'=>$id]);
                $oldData = $old->fetch(PDO::FETCH_ASSOC);
                if (!$oldData) throw new Exception('Delivery not found');

                $old_dr = trim((string)($oldData['dr_no'] ?? ''));

                // only check if DR was changed
                if ($dr_no !== '' && $dr_no !== $old_dr) {
                    $chk = $conn->prepare("
                        SELECT COUNT(*)
                        FROM delivery
                        WHERE TRIM(dr_no) = :dr_no
                        AND del_id != :del_id
                        AND is_deleted = 0
                    ");
                    $chk->execute([
                        ':dr_no'  => $dr_no,
                        ':del_id' => $id
                    ]);

                    if ((int)$chk->fetchColumn() > 0) {
                        throw new Exception('DR Number already exists. Please use a unique DR Number.');
                    }
                }

                // ✅ IMPORTANT: reuse $oldData later in update block
            }

            if ($action === 'create') {
                $audit = audit_on_create($admin);

                $stmt = $conn->prepare("
                    INSERT INTO delivery (
                        soa_id, customer_id, delivery_date, dr_no,
                        truck_id, material,
                        quantity, unit_price, po_number,
                        status, is_deleted,
                        date_created, date_edited, created_by, edited_by
                    )
                    VALUES
                        (:soa_id, :customer_id, :delivery_date, :dr_no,
                         :truck_id, :material,
                         :quantity, :unit_price, :po_number,
                         :status, 0,
                         :date_created, :date_edited, :created_by, :edited_by)
                ");
                $stmt->execute([
                    ':soa_id'       => $soa_id_post,
                    ':customer_id'  => $customer_id,
                    ':delivery_date'=> $delivery_date,
                    ':dr_no'        => $dr_no,
                    ':truck_id'     => $truck_id,
                    ':material'     => $material,
                    ':quantity'     => $quantity,
                    ':unit_price'   => $unit_price,
                    ':po_number'    => $po_number,
                    ':status'       => $status,
                    ':date_created' => $audit['date_created'],
                    ':date_edited'  => $audit['date_edited'],
                    ':created_by'   => $audit['created_by'],
                    ':edited_by'    => $audit['edited_by'],
                ]);

                $newDelId = (int)$conn->lastInsertId();
                audit_log('delivery', $newDelId, 'CREATE', null, $_POST, $admin);

                // $_SESSION['alert'] = ['type'=>'success','message'=>'Delivery created'];
                // header("Location: /main.php#trans_entry.php?soa_id=".$soa_id_post);
                // exit;
                $_SESSION['alert'] = ['type'=>'success','message'=>'Delivery created'];

                $insertMode = (int)($_POST['insert_mode'] ?? 0);

                if ($insertMode === 1) {
                    // stay on page, keep form values
                    header("Location: /main.php#trans_entry.php?soa_id=".$soa_id_post."&keep=1");
                } else {
                    // normal save behavior
                    header("Location: /main.php#trans_entry.php?soa_id=".$soa_id_post);
                }
                exit;
            }

            if ($action === 'update') {
                if ($id <= 0) throw new Exception('Invalid delivery ID');

                // lock if old SOA finalized
                if (!empty($oldData['soa_id'])) {
                    $chk = $conn->prepare("SELECT status FROM statement_of_account WHERE soa_id=:sid AND is_deleted=0");
                    $chk->execute([':sid'=>$oldData['soa_id']]);
                    if ($chk->fetchColumn() === 'finalized') {
                        throw new Exception('Delivery belongs to a finalized SOA');
                    }
                }

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
                    UPDATE delivery SET
                        soa_id       = :soa_id,
                        customer_id  = :customer_id,
                        delivery_date= :delivery_date,
                        dr_no        = :dr_no,
                        truck_id     = :truck_id,
                        material     = :material,
                        quantity     = :quantity,
                        unit_price   = :unit_price,
                        po_number    = :po_number,
                        status       = :status,
                        date_edited  = :date_edited,
                        edited_by    = :edited_by
                    WHERE del_id = :id
                ");
                $stmt->execute([
                    ':id'           => $id,
                    ':soa_id'       => $soa_id_post,
                    ':customer_id'  => $customer_id,
                    ':delivery_date'=> $delivery_date,
                    ':dr_no'        => $dr_no,
                    ':truck_id'     => $truck_id,
                    ':material'     => $material,
                    ':quantity'     => $quantity,
                    ':unit_price'   => $unit_price,
                    ':po_number'    => $po_number,
                    ':status'       => $status,
                    ':date_edited'  => $audit['date_edited'],
                    ':edited_by'    => $audit['edited_by'],
                ]);

                audit_log('delivery', $id, 'UPDATE', $oldData, $_POST, $admin);
                $_SESSION['alert'] = ['type'=>'success','message'=>'Delivery updated'];
                header("Location: /main.php#trans_entry.php?soa_id=".$soa_id_post);
                exit;
            }

            if ($action === 'delete') {
                if ($id <= 0) throw new Exception('Invalid delivery ID');

                $old = $conn->prepare("SELECT * FROM delivery WHERE del_id=:id AND is_deleted=0");
                $old->execute([':id'=>$id]);
                $oldData = $old->fetch(PDO::FETCH_ASSOC);
                if (!$oldData) throw new Exception('Delivery not found');

                // lock if SOA finalized
                if (!empty($oldData['soa_id'])) {
                    $chk = $conn->prepare("SELECT status FROM statement_of_account WHERE soa_id=:sid AND is_deleted=0");
                    $chk->execute([':sid'=>$oldData['soa_id']]);
                    if ($chk->fetchColumn() === 'finalized') {
                        throw new Exception('Delivery belongs to a finalized SOA');
                    }
                }

                $audit = audit_on_update($admin);

                $stmt = $conn->prepare("
                    UPDATE delivery SET
                        is_deleted  = 1,
                        date_edited = :date_edited,
                        edited_by   = :edited_by
                    WHERE del_id = :id
                ");
                $stmt->execute([
                    ':id'         => $id,
                    ':date_edited'=> $audit['date_edited'],
                    ':edited_by'  => $audit['edited_by'],
                ]);

                audit_log('delivery', $id, 'DELETE', $oldData, ['is_deleted'=>1], $admin);
                $_SESSION['alert'] = ['type'=>'success','message'=>'Delivery deleted'];
                header("Location: /main.php#trans_entry.php?soa_id=".$soa_id_post);
                exit;
            }
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type'=>'danger','message'=>$e->getMessage()];

        $query = [];
        if ($soa_id > 0) $query[] = 'soa_id=' . $soa_id;
        if (!empty($_POST['insert_mode'])) $query[] = 'keep=1';

        $qs = $query ? '?' . implode('&', $query) : '';

        header("Location: {$redirectUrl}{$qs}");
        exit;

    }

    // fallback
    header("Location: $redirectUrl");
    exit;
}

/* ============================================================
   LOOKUP DATA
   ============================================================ */

// SOA list
$soas = $conn->query("
    SELECT
        s.soa_id,
        s.soa_no,
        s.status,
        co.company_name,
        si.site_name
    FROM statement_of_account s
    JOIN company co ON s.company_id = co.company_id
    JOIN site si ON s.site_id = si.site_id
    WHERE s.is_deleted = 0
      AND s.status IN ('draft','finalized')
    ORDER BY s.date_created DESC, s.soa_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// companies
$companies = $conn->query("
    SELECT company_id, company_name
    FROM company
    WHERE is_deleted = 0
    ORDER BY company_name
")->fetchAll(PDO::FETCH_ASSOC);

// contractors
$contractors = $conn->query("
    SELECT contractor_id, contractor_name
    FROM contractor
    WHERE is_deleted = 0
    ORDER BY contractor_name
")->fetchAll(PDO::FETCH_ASSOC);

// sites
$sites = $conn->query("
    SELECT site_id, site_name, remarks
    FROM site
    WHERE is_deleted = 0
    ORDER BY site_name
")->fetchAll(PDO::FETCH_ASSOC);

// trucks
$trucks = $conn->query("
    SELECT truck_id, plate_no
    FROM truck
    WHERE is_deleted = 0
    ORDER BY plate_no
")->fetchAll(PDO::FETCH_ASSOC);

// customers (filtered if SOA exists)
if ($soa) {
    $stmt = $conn->prepare("
        SELECT customer_id, customer_name
        FROM customer
        WHERE is_deleted = 0
          AND company_id = :company_id
          AND site_id = :site_id
        ORDER BY customer_name
    ");
    $stmt->execute([
        ':company_id' => $soa['company_id'],
        ':site_id'    => $soa['site_id']
    ]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $customers = $conn->query("
        SELECT customer_id, customer_name
        FROM customer
        WHERE is_deleted = 0
        ORDER BY customer_name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// materials
$materials = $conn->query("
    SELECT material_id, material_name, unit_price
    FROM materials
    WHERE is_deleted = 0
      AND status = 'active'
    ORDER BY material_name
")->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FILTERS + DELIVERY LIST
   ============================================================ */

require_once __DIR__ . '/../helpers/alerts.php';

$q              = trim($_GET['q'] ?? '');
$statusFilter   = $_GET['status_filter'] ?? '';
$companyFilter  = isset($_GET['company_filter']) ? (int)$_GET['company_filter'] : 0;
$siteFilter     = isset($_GET['site_filter']) ? (int)$_GET['site_filter'] : 0;
$materialFilter = $_GET['material_filter'] ?? '';
$dateFrom       = $_GET['del_date_from'] ?? '';
$dateTo         = $_GET['del_date_to'] ?? '';

$where  = "d.is_deleted = 0";
$params = [];

// SOA scoped table: if no SOA, show none
if ($soa_id > 0) {
    $where .= " AND d.soa_id = :soa_id";
    $params[':soa_id'] = $soa_id;
} else {
    $where .= " AND 1 = 0";
}

// date range
if ($dateFrom !== '') {
    $where .= " AND d.delivery_date >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where .= " AND d.delivery_date <= :date_to";
    $params[':date_to'] = $dateTo;
}

// status
if ($statusFilter !== '') {
    $where .= " AND d.status = :status";
    $params[':status'] = $statusFilter;
}

// company/site filter via customer
if ($companyFilter > 0) {
    $where .= " AND c.company_id = :company_id";
    $params[':company_id'] = $companyFilter;
}
if ($siteFilter > 0) {
    $where .= " AND c.site_id = :site_id";
    $params[':site_id'] = $siteFilter;
}

// material filter
if ($materialFilter !== '') {
    $where .= " AND d.material = :material_filter";
    $params[':material_filter'] = $materialFilter;
}

// free text
if ($q !== '') {
    $where .= " AND (
        d.dr_no LIKE :q
        OR c.customer_name LIKE :q
        OR co.company_name LIKE :q
        OR s.site_name LIKE :q
        OR t.plate_no LIKE :q
        OR d.material LIKE :q
        OR d.po_number LIKE :q
    )";
    $params[':q'] = '%'.$q.'%';
}

// pagination
$per_page     = 5;
$current_page = (isset($_GET['p']) && ctype_digit((string)$_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// count
$countSql = "
    SELECT COUNT(*)
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    LEFT JOIN company co ON c.company_id = co.company_id
    LEFT JOIN site s ON c.site_id = s.site_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE $where
";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_records = (int)$countStmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

$listSql = "
    SELECT
        d.del_id,
        d.delivery_date,
        d.dr_no,
        d.billing_date,
        d.material,
        d.quantity,
        d.unit_price,
        d.status,
        d.po_number,
        d.terms,
        c.customer_id,
        c.customer_name,
        co.company_name,
        s.site_name,
        t.plate_no
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    LEFT JOIN company co ON c.company_id = co.company_id
    LEFT JOIN site s ON c.site_id = s.site_id
    LEFT JOIN truck t ON d.truck_id = t.truck_id
    WHERE $where
    ORDER BY d.delivery_date DESC, d.del_id DESC
    LIMIT :limit OFFSET :offset
";

$listStmt = $conn->prepare($listSql);
foreach ($params as $k => $v) $listStmt->bindValue($k, $v);
$listStmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$queryBase = http_build_query([
    'soa_id'         => $soa_id,
    'q'              => $q,
    'status_filter'  => $statusFilter,
    'company_filter' => $companyFilter,
    'site_filter'    => $siteFilter,
    'material_filter'=> $materialFilter,
    'del_date_from'  => $dateFrom,
    'del_date_to'    => $dateTo,
]);
?>

<div class="container-fluid">
    <h2 class="mb-4">Transaction Entry</h2>

    <?php if (!empty($_SESSION['alert'])) { ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php } ?>

    <!-- ===================== SOA BAR ===================== -->
    <div class="card mb-3" id="soa-bar">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">

            <div style="min-width:320px;">
                <label class="form-label mb-1">Statement of Account</label>
                <select id="soa_select" class="form-select">
                    <option value="">-- Select SOA --</option>
                    <?php foreach ($soas as $s): ?>
                        <option value="<?= (int)$s['soa_id'] ?>"
                            <?= ($soa_id == $s['soa_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['soa_no']) ?>
                            - <?= htmlspecialchars($s['company_name']) ?>
                            - <?= htmlspecialchars($s['site_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Select SOA first, or create one.
                </div>
            </div>

            <div style="min-width:220px;">
                <label class="form-label mb-1">SOA Status</label><br>
                <?php
                $badge = 'secondary';
                $label = 'No SOA Selected';
                if ($soa) {
                    if ($soa['status'] === 'finalized') { $badge='success'; $label='Finalized'; }
                    else { $badge='danger'; $label=ucfirst($soa['status']); }
                }
                ?>
                <span class="badge bg-<?= $badge ?>" id="soa_status_badge"><?= htmlspecialchars($label) ?></span>
            </div>

            <div class="ms-auto d-flex gap-2">
                <button type="button"
                        class="btn btn-outline-primary"
                        id="btn_open_create_soa"
                        data-bs-toggle="modal"
                        data-bs-target="#soaCreateModal"
                        <?= $soa ? 'disabled' : '' ?>>
                    Create New SOA
                </button>

                <form method="POST" action="pages/trans_entry.php" class="m-0 p-0">
                    <input type="hidden" name="form_type" value="soa">
                    <input type="hidden" name="action" value="finalize">
                    <input type="hidden" name="soa_id" value="<?= (int)$soa_id ?>">
                    <button type="button"
                            class="btn btn-danger"
                            id="btn_finalize_soa"
                            <?= (!$soa || $soa['status'] === 'finalized') ? 'disabled' : '' ?>>
                        Finalize SOA
                    </button>

                </form>

                <a class="btn btn-success"
                id="btn_print_soa"
                target="_blank"
                href="<?= ($soa && $soa['status'] !== 'draft')
                        ? 'pages/reports_print.php?soa_id='.(int)$soa_id
                        : '#' ?>"
                style="<?= ($soa && $soa['status'] !== 'draft')
                        ? ''
                        : 'pointer-events:none; opacity:.6;' ?>">
                    Print SOA
                </a>
            </div>

        </div>
    </div>

    <div class="row">
        <!-- CUSTOMER FORM -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span id="customer-form-title">Customer</span>
                    <button class="btn btn-sm btn-outline-secondary collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#customerFormCollapse"
                            aria-expanded="false"
                            aria-controls="customerFormCollapse">
                        Show form
                    </button>
                </div>

                <div id="customerFormCollapse" class="collapse">
                    <div class="card-body">
                        <form id="customer-form" method="POST" action="pages/trans_entry.php">
                            <input type="hidden" name="form_type" value="customer">
                            <input type="hidden" name="action" id="customer_action" value="create">
                            <input type="hidden" name="customer_id" id="customer_id">
                            <input type="hidden" name="soa_id" value="<?= (int)$soa_id ?>">

                            <?php if ($soa): ?>
                                <input type="hidden" name="company_id" value="<?= (int)$soa['company_id'] ?>">
                                <input type="hidden" name="site_id" value="<?= (int)$soa['site_id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Company</label>
                                <select name="company_id" id="company_id" class="form-select select2-field" <?= $soa ? 'disabled' : '' ?>>
                                    <option value="">-- Select Company --</option>
                                    <?php foreach ($companies as $co): ?>
                                        <option value="<?= (int)$co['company_id'] ?>"
                                            <?= ($soa && (int)$soa['company_id'] === (int)$co['company_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($co['company_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contractor</label>
                                <select name="contractor_id" id="contractor_id" class="form-select select2-field">
                                    <option value="">-- Select Contractor --</option>
                                    <?php foreach ($contractors as $ct): ?>
                                        <option value="<?= (int)$ct['contractor_id'] ?>"><?= htmlspecialchars($ct['contractor_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Site</label>
                                <select name="site_id" id="site_id" class="form-select select2-field" <?= $soa ? 'disabled' : '' ?>>
                                    <option value="">-- Select Site --</option>
                                    <?php foreach ($sites as $st):
                                        $label = $st['site_name'];
                                        if (!empty($st['remarks'])) $label .= ' - '.$st['remarks'];
                                    ?>
                                        <option value="<?= (int)$st['site_id'] ?>"
                                            <?= ($soa && (int)$soa['site_id'] === (int)$st['site_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contact No</label>
                                <input type="text" name="contact_no" id="customer_contact_no" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="customer_email" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" id="customer_address" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="customer_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary" id="customer-submit-btn">Save Customer</button>
                            <button type="button" class="btn btn-secondary d-none" id="customer-cancel-edit-btn">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- DELIVERY + TOTAL -->
        <div class="col-lg-6 mb-4">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span id="delivery-form-title">Delivery</span>
                    <button class="btn btn-sm btn-outline-secondary collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#deliveryFormCollapse"
                            aria-expanded="false"
                            aria-controls="deliveryFormCollapse">
                        Show form
                    </button>
                </div>

                <div id="deliveryFormCollapse" class="collapse">
                    <?php $soaLocked = (!$soa || ($soa['status'] ?? '') === 'finalized'); ?>

                    <?php if (!$soa): ?>
                        <div class="alert alert-warning m-3">
                            No SOA selected. You can still encode delivery (4C will auto-create SOA on save),
                            but it’s recommended to select/create SOA first.
                        </div>
                    <?php elseif (($soa['status'] ?? '') === 'finalized'): ?>
                        <div class="alert alert-info m-3">
                            This SOA is finalized. Deliveries are locked.
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <form id="delivery-form" method="POST" action="pages/trans_entry.php" <?= $soaLocked ? 'class="opacity-50"' : '' ?>>
                            <fieldset id="delivery-fieldset">
                            <input type="hidden" name="form_type" value="delivery">
                            <input type="hidden" name="action" id="delivery_action" value="create">
                            <input type="hidden" name="del_id" id="del_id">
                            <input type="hidden" name="soa_id" id="soa_id" value="<?= (int)$soa_id ?>">
                            <input type="hidden" name="insert_mode" id="insert_mode" value="0">
                            <input type="hidden" name="keep" id="delivery_keep" value="0">

                            <div class="mb-3">
                                <label class="form-label">Customer</label>
                                <select name="delivery_customer_id" id="delivery_customer_id" class="form-select select2-field">
                                    <option value="">-- Select Customer --</option>
                                    <?php foreach ($customers as $cu): ?>
                                        <option value="<?= (int)$cu['customer_id'] ?>"><?= htmlspecialchars($cu['customer_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Delivery Date</label>
                                    <input type="date" name="delivery_date" id="delivery_date" class="form-control" required>
                                </div>
                                <!-- <div class="col">
                                    <label class="form-label">Billing Date</label>
                                    <input type="date" name="billing_date" id="billing_date" class="form-control">
                                </div> -->
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">DR No</label>
                                    <input type="text" name="dr_no" id="dr_no" class="form-control">
                                </div>
                                <div class="col">
                                    <label class="form-label">PO Number</label>
                                    <input type="text" name="po_number" id="po_number" class="form-control">
                                </div>
                                <!-- <div class="col">
                                    <label class="form-label">Terms of Payment</label>
                                    <select name="terms" id="terms" class="form-select">
                                        <option value="*">*</option>
                                        <option value="15">15 Days</option>
                                        <option value="30">30 Days</option>
                                        <option value="45">45 Days</option>
                                    </select>
                                </div> -->
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Truck</label>
                                <select name="truck_id" id="truck_id" class="form-select select2-field">
                                    <option value="">-- Select Truck --</option>
                                    <?php foreach ($trucks as $tr): ?>
                                        <option value="<?= (int)$tr['truck_id'] ?>"><?= htmlspecialchars($tr['plate_no']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Material</label>
                                <select name="material_id" id="material_id" class="form-select select2-field">
                                    <option value="">-- Select Material --</option>
                                    <?php foreach ($materials as $m): ?>
                                        <option value="<?= (int)$m['material_id'] ?>"><?= htmlspecialchars($m['material_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="material_name" id="material_name">
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" step="0.01" name="quantity" id="quantity" class="form-control">
                                </div>
                                <div class="col">
                                    <label class="form-label">Unit Price</label>
                                    <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="delivery_status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success" <?= ($soa && ($soa['status'] ?? '') === 'finalized') ? 'disabled' : '' ?> id="delivery-submit-btn">
                                Save Delivery
                            </button>
                            <button type="button" class="btn btn-outline-primary ms-2" id="delivery-insert-btn">
                                Insert
                            </button>
                            <button type="button" class="btn btn-secondary d-none" id="delivery-cancel-edit-btn">Cancel</button>
                            </fieldset>
                        </form>
                    </div>

                    <div class="card border-0 border-top">
                        <div class="card-header">Total</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Total Amount</label>
                                <input type="text" id="total_amount" class="form-control" readonly>
                            </div>
                            <div class="form-text">Total = Quantity × Unit Price (auto-calculated).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTERS + TABLE -->
    <div class="card">
        <div class="card-header">
            <form class="row g-2 align-items-end trans-filter-form flex-nowrap" method="GET" action="">
    <input type="hidden" name="soa_id" value="<?= (int)$soa_id ?>">

    <div class="col-lg-2 col-md-3 col-sm-6">
        <label class="form-label">Delivery From</label>
        <input type="date" name="del_date_from" class="form-control"
               value="<?= htmlspecialchars($dateFrom) ?>">
    </div>

    <div class="col-lg-2 col-md-3 col-sm-6">
        <label class="form-label">Delivery To</label>
        <input type="date" name="del_date_to" class="form-control"
               value="<?= htmlspecialchars($dateTo) ?>">
    </div>

    <div class="col-lg-2 col-md-3 col-sm-6">
        <label class="form-label">Material</label>
        <select name="material_filter" class="form-select">
            <option value="">All</option>
            <?php foreach ($materials as $m): ?>
                <option value="<?= htmlspecialchars($m['material_name']) ?>"
                    <?= $materialFilter === $m['material_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['material_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-lg-2 col-md-3 col-sm-6">
        <label class="form-label">Status</label>
        <select name="status_filter" class="form-select">
            <option value="">All</option>
            <option value="pending"   <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-12">
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control"
               value="<?= htmlspecialchars($q) ?>"
               placeholder="DR / Customer / Company / Truck / Material / PO">
    </div>

    <div class="col-lg-1 col-md-3 col-sm-6">
        <button class="btn btn-primary w-100">Apply</button>
    </div>
</form>

        </div>

        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered mb-0" id="delivery-table">
                <thead class="table-light">
                    <tr>
                        <th>DR No</th>
                        <th>Delivery Date</th>
                        <th>Customer</th>
                        <th>Company</th>
                        <th>Site</th>
                        <th>Truck</th>
                        <th>Material</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="12" class="text-center">No records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $total = (float)$r['quantity'] * (float)$r['unit_price'];
                    ?>
                        <tr class="delivery-row"
                            data-del-id="<?= (int)$r['del_id'] ?>"
                            data-customer-id="<?= (int)$r['customer_id'] ?>"
                            data-delivery-date="<?= htmlspecialchars($r['delivery_date']) ?>"
                            data-dr-no="<?= htmlspecialchars($r['dr_no'], ENT_QUOTES) ?>"
                            data-material="<?= htmlspecialchars($r['material'], ENT_QUOTES) ?>"
                            data-quantity="<?= htmlspecialchars($r['quantity']) ?>"
                            data-unit-price="<?= htmlspecialchars($r['unit_price']) ?>"
                            data-status="<?= htmlspecialchars($r['status']) ?>"
                            data-po="<?= htmlspecialchars($r['po_number'] ?? '', ENT_QUOTES) ?>"
                            data-terms="<?= htmlspecialchars($r['terms'] ?? '', ENT_QUOTES) ?>"
                        >
                            <td><?= htmlspecialchars($r['dr_no']) ?></td>
                            <td><?= htmlspecialchars($r['delivery_date']) ?></td>
                            <td><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td><?= htmlspecialchars($r['company_name']) ?></td>
                            <td><?= htmlspecialchars($r['site_name']) ?></td>
                            <td><?= htmlspecialchars($r['plate_no']) ?></td>
                            <td><?= htmlspecialchars($r['material']) ?></td>
                            <td><?= htmlspecialchars($r['quantity']) ?></td>
                            <td><?= number_format((float)$r['unit_price'], 2) ?></td>
                            <td><?= number_format($total, 2) ?></td>
                            <td><?= htmlspecialchars($r['status']) ?></td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-secondary trans-btn-edit-delivery" <?= ($soa && ($soa['status'] ?? '') === 'finalized') ? 'disabled' : '' ?>>
                                    Edit
                                </button>
                                <form method="POST" action="pages/trans_entry.php" class="d-inline"
                                      onsubmit="return confirm('Delete this delivery?');">
                                    <input type="hidden" name="form_type" value="delivery">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="del_id" value="<?= (int)$r['del_id'] ?>">
                                    <input type="hidden" name="soa_id" value="<?= (int)$soa_id ?>">
                                    <button class="btn btn-sm btn-danger" <?= ($soa && ($soa['status'] ?? '') === 'finalized') ? 'disabled' : '' ?>>Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $current_page - 1 ?>">&laquo;</a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= htmlspecialchars($queryBase) ?>&p=<?= $current_page + 1 ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

        <!-- SOA CREATE MODAL (BACKEND ENABLED) -->
        <div class="modal fade" id="soaCreateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" id="soa-create-form" method="POST" action="pages/trans_entry.php">
                    <input type="hidden" name="form_type" value="soa">
                    <input type="hidden" name="action" value="create">

                    <div class="modal-header">
                        <h5 class="modal-title">Create New SOA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select" required>
                                <option value="">-- Select Company --</option>
                                <?php foreach ($companies as $co): ?>
                                    <option value="<?= (int)$co['company_id'] ?>"><?= htmlspecialchars($co['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Site</label>
                            <select name="site_id" class="form-select" required>
                                <option value="">-- Select Site --</option>
                                <?php foreach ($sites as $st):
                                    $label = $st['site_name'];
                                    if (!empty($st['remarks'])) $label .= ' - '.$st['remarks'];
                                ?>
                                    <option value="<?= (int)$st['site_id'] ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Terms of Payment</label>
                            <select name="terms" class="form-select">
                                <option value="*" selected>*</option>
                                <option value="15">15 Days</option>
                                <option value="30">30 Days</option>
                                <option value="45">45 Days</option>
                            </select>
                            <div class="form-text">
                                * = No cash payment will be accepted
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0">
                            SOA number is auto-generated.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary" type="submit">Create SOA</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
window.currentSOAStatus = <?= json_encode($soa['status'] ?? null) ?>;
window.currentSOAId     = <?= json_encode($soa_id) ?>;

window.SOA_STATUS_MAP = <?= json_encode(
  array_column($soas, 'status', 'soa_id')
) ?>;

</script>


