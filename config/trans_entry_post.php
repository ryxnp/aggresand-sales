<?php
// config/trans_entry_post.php
// Handles ALL POST requests for trans_entry.php

if (!$admin) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Not authenticated'];
    header("Location: $redirectUrl");
    exit;
}

$formType = $_POST['form_type'] ?? '';

try {

    /* =========================================================
       SOA CREATE
       ========================================================= */
    if ($formType === 'soa') {

        $company_id = (int)($_POST['company_id'] ?? 0);
        $site_id    = (int)($_POST['site_id'] ?? 0);
        $billing_date = $_POST['billing_date'] ?? '';

        if (!$billing_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $billing_date)) {
            throw new Exception('Invalid billing date');
        }
        if ($company_id <= 0 || $site_id <= 0) {
            throw new Exception('Company and Site are required');
        }

        $terms_select = $_POST['terms_select'] ?? '*';
        if ($terms_select === 'custom') {
            $terms_custom = (int)($_POST['terms_custom'] ?? 0);
            if ($terms_custom <= 0) {
                throw new Exception('Custom terms must be greater than 0');
            }
            $terms = (string)$terms_custom;
        } else {
            $terms = $terms_select;
        }

        $soa_no = generate_soa_no($conn, $company_id);
        $audit  = audit_on_create($admin);

        $stmt = $conn->prepare("
            INSERT INTO statement_of_account (
                soa_no, company_id, site_id, billing_date, terms,
                is_deleted, date_created, date_edited, created_by, edited_by
            ) VALUES (
                :soa_no, :company_id, :site_id, :billing_date, :terms,
                0, :dc, :de, :cb, :eb
            )
        ");

        $stmt->execute([
            ':soa_no'       => $soa_no,
            ':company_id'   => $company_id,
            ':site_id'      => $site_id,
            ':billing_date' => $billing_date,
            ':terms'        => $terms,
            ':dc'           => $audit['date_created'],
            ':de'           => $audit['date_edited'],
            ':cb'           => $audit['created_by'],
            ':eb'           => $audit['edited_by'],
        ]);

        $newSoaId = (int)$conn->lastInsertId();
        audit_log('statement_of_account', $newSoaId, 'CREATE', null, $_POST, $admin);

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'SOA created'];
        header("Location: /main.php#trans_entry.php?soa_id={$newSoaId}");
        exit;
    }

    /* =========================================================
       DELIVERY (SINGLE + BULK)
       ========================================================= */
    if ($formType === 'delivery') {

        $action       = $_POST['action'] ?? 'create';
        $id           = (int)($_POST['del_id'] ?? 0);
        $soa_id_post  = (int)($_POST['soa_id'] ?? 0);

        if ($soa_id_post <= 0) {
            throw new Exception('Please select an SOA first');
        }

        // Validate SOA
        $soaStmt = $conn->prepare("
            SELECT soa_id, company_id
            FROM statement_of_account
            WHERE soa_id = :id AND is_deleted = 0
            LIMIT 1
        ");
        $soaStmt->execute([':id' => $soa_id_post]);
        $soaRow = $soaStmt->fetch(PDO::FETCH_ASSOC);

        if (!$soaRow) {
            throw new Exception('Invalid SOA');
        }

        $company_id = (int)$soaRow['company_id'];

        /* =====================================================
        DELETE (HARD DELETE)
        ===================================================== */
        if ($action === 'delete') {

            if ($id <= 0) {
                throw new Exception('Invalid delivery ID');
            }
            
            // Fetch old data for audit trail
            $oldStmt = $conn->prepare("
                SELECT * FROM delivery WHERE del_id = :id
            ");
            $oldStmt->execute([':id' => $id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Delivery not found');
            }

            // 🔥 HARD DELETE
            $stmt = $conn->prepare("
                DELETE FROM delivery
                WHERE del_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);

            // Audit log still works
            audit_log('delivery', $id, 'DELETE', $oldData, null, $admin);

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Delivery permanently deleted'
            ];

            header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}");
            exit;
        }



        /* =====================================================
           BULK VALIDATE (NO INSERT, JSON RESPONSE)
           ===================================================== */
        if ($action === 'bulk_validate') {

            header('Content-Type: application/json');

            $errors = [];

            $dates   = $_POST['bulk_delivery_date'] ?? [];
            $drs     = $_POST['bulk_dr_no'] ?? [];
            $mats    = $_POST['bulk_material_name'] ?? [];
            $qtys    = $_POST['bulk_quantity'] ?? [];
            $prices  = $_POST['bulk_unit_price'] ?? [];

            // check required fields + DR uniqueness per row
            foreach ($dates as $i => $delivery_date) {

                $delivery_date = trim((string)$delivery_date);
                $dr_no         = trim((string)($drs[$i] ?? ''));
                $material      = trim((string)($mats[$i] ?? ''));
                $quantity      = (float)($qtys[$i] ?? 0);
                $unit_price    = (float)($prices[$i] ?? 0);

                if ($delivery_date === '') {
                    $errors[] = ['row' => $i, 'field' => 'bulk_delivery_date'];
                }
                if ($material === '') {
                    $errors[] = ['row' => $i, 'field' => 'bulk_material_name'];
                }
                if ($quantity <= 0) {
                    $errors[] = ['row' => $i, 'field' => 'bulk_quantity'];
                }
                if ($unit_price <= 0) {
                    $errors[] = ['row' => $i, 'field' => 'bulk_unit_price'];
                }

                // DR uniqueness check (if provided)
                if ($dr_no !== '') {
                    $chk = $conn->prepare("
                        SELECT COUNT(*) FROM delivery
                        WHERE TRIM(dr_no) = :dr AND is_deleted = 0
                    ");
                    $chk->execute([':dr' => $dr_no]);
                    if ((int)$chk->fetchColumn() > 0) {
                        $errors[] = ['row' => $i, 'field' => 'bulk_dr_no'];
                    }
                }
            }

            if (!empty($errors)) {
                echo json_encode([
                    'status' => 'error',
                    'errors' => $errors
                ]);
                exit;
            }

            echo json_encode(['status' => 'ok']);
            exit;
        }

        /* =====================================================
           BULK CREATE (LOOP INSERT) + PO NUMBER (NULLABLE)
           ===================================================== */
        if ($action === 'bulk_create') {

    $dates   = $_POST['bulk_delivery_date'] ?? [];
    $drs     = $_POST['bulk_dr_no'] ?? [];
    $pos     = $_POST['bulk_po_number'] ?? [];
    $trucks  = $_POST['bulk_truck_id'] ?? [];
    $mats    = $_POST['bulk_material_name'] ?? [];
    $qtys    = $_POST['bulk_quantity'] ?? [];
    $prices  = $_POST['bulk_unit_price'] ?? [];
    $status  = $_POST['bulk_status'] ?? [];

    if (count($dates) === 0) {
        throw new Exception('No bulk rows submitted');
    }

    $audit = audit_on_create($admin);

    try {
        // 🔒 START TRANSACTION
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO delivery (
                soa_id, company_id, delivery_date, dr_no,
                po_number,
                truck_id, material, quantity, unit_price,
                status, is_deleted,
                date_created, date_edited, created_by, edited_by
            ) VALUES (
                :soa_id, :company_id, :delivery_date, :dr_no,
                :po_number,
                :truck_id, :material, :quantity, :unit_price,
                :status, 0,
                :dc, :de, :cb, :eb
            )
        ");

        foreach ($dates as $i => $delivery_date) {

            $delivery_date = trim((string)$delivery_date);
            $material      = trim((string)($mats[$i] ?? ''));
            $quantity      = (float)($qtys[$i] ?? 0);
            $unit_price    = (float)($prices[$i] ?? 0);

            // safety guard (frontend already validated)
            if (
                $delivery_date === '' ||
                $material === '' ||
                $quantity <= 0 ||
                $unit_price <= 0
            ) {
                throw new Exception("Invalid data in bulk row #" . ($i + 1));
            }

            $po_number = trim((string)($pos[$i] ?? ''));
            $po_number = ($po_number === '') ? null : $po_number;

            $stmt->execute([
                ':soa_id'        => $soa_id_post,
                ':company_id'    => $company_id,
                ':delivery_date' => $delivery_date,
                ':dr_no'         => trim((string)($drs[$i] ?? '')),
                ':po_number'     => $po_number,
                ':truck_id'      => !empty($trucks[$i]) ? (int)$trucks[$i] : null,
                ':material'      => $material,
                ':quantity'      => $quantity,
                ':unit_price'    => $unit_price,
                ':status'        => $status[$i] ?? 'pending',
                ':dc'            => $audit['date_created'],
                ':de'            => $audit['date_edited'],
                ':cb'            => $audit['created_by'],
                ':eb'            => $audit['edited_by'],
            ]);
        }

        // ✅ COMMIT if ALL rows succeed
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'Bulk deliveries saved successfully'
    ];

    header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}");
    exit;
}


        /* =====================================================
           SINGLE CREATE / UPDATE
           ===================================================== */

        if (in_array($action, ['create', 'update'], true)) {

            $delivery_date = trim($_POST['delivery_date'] ?? '');
            if ($delivery_date === '') {
                throw new Exception('Delivery date is required');
            }

            $dr_no      = trim($_POST['dr_no'] ?? '');
            $po_number  = trim($_POST['po_number'] ?? '') ?: null;
            $truck_id   = (int)($_POST['truck_id'] ?? 0) ?: null;
            $material   = trim($_POST['material_name'] ?? '');
            $quantity   = (float)($_POST['quantity'] ?? 0);
            $unit_price = (float)($_POST['unit_price'] ?? 0);
            $status     = $_POST['status'] ?? 'pending';
        }

        /* ---------- DR uniqueness ---------- */
        if ($action === 'create' && $dr_no !== '') {
            $chk = $conn->prepare("
                SELECT COUNT(*) FROM delivery
                WHERE TRIM(dr_no) = :dr AND is_deleted = 0
            ");
            $chk->execute([':dr' => $dr_no]);
            if ($chk->fetchColumn() > 0) {
                throw new Exception('DR Number already exists');
            }
        }

        if ($action === 'update') {
            $old = $conn->prepare("SELECT * FROM delivery WHERE del_id=:id AND is_deleted=0");
            $old->execute([':id' => $id]);
            $oldData = $old->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('Delivery not found');
            }

            if ($dr_no !== '' && trim($oldData['dr_no']) !== $dr_no) {
                $chk = $conn->prepare("
                    SELECT COUNT(*) FROM delivery
                    WHERE TRIM(dr_no) = :dr AND del_id != :id AND is_deleted = 0
                ");
                $chk->execute([':dr' => $dr_no, ':id' => $id]);
                if ($chk->fetchColumn() > 0) {
                    throw new Exception('DR Number already exists');
                }
            }
        }

        /* ---------- CREATE ---------- */
        if ($action === 'create') {

            $audit = audit_on_create($admin);

            $stmt = $conn->prepare("
                INSERT INTO delivery (
                    soa_id, company_id, delivery_date, dr_no,
                    truck_id, material, quantity, unit_price,
                    po_number, status, is_deleted,
                    date_created, date_edited, created_by, edited_by
                ) VALUES (
                    :soa, :co, :dd, :dr,
                    :truck, :mat, :qty, :price,
                    :po, :status, 0,
                    :dc, :de, :cb, :eb
                )
            ");

            $stmt->execute([
                ':soa'    => $soa_id_post,
                ':co'     => $company_id,
                ':dd'     => $delivery_date,
                ':dr'     => $dr_no,
                ':truck'  => $truck_id,
                ':mat'    => $material,
                ':qty'    => $quantity,
                ':price'  => $unit_price,
                ':po'     => $po_number,
                ':status' => $status,
                ':dc'     => $audit['date_created'],
                ':de'     => $audit['date_edited'],
                ':cb'     => $audit['created_by'],
                ':eb'     => $audit['edited_by'],
            ]);

            audit_log('delivery', $conn->lastInsertId(), 'CREATE', null, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery created'];
            header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}");
            exit;
        }

        /* ---------- UPDATE ---------- */
        if ($action === 'update') {

            $audit = audit_on_update($admin);

            $stmt = $conn->prepare("
                UPDATE delivery SET
                    delivery_date = :dd,
                    dr_no = :dr,
                    truck_id = :truck,
                    material = :mat,
                    quantity = :qty,
                    unit_price = :price,
                    po_number = :po,
                    status = :status,
                    date_edited = :de,
                    edited_by = :eb
                WHERE del_id = :id
            ");

            $stmt->execute([
                ':id'     => $id,
                ':dd'     => $delivery_date,
                ':dr'     => $dr_no,
                ':truck'  => $truck_id,
                ':mat'    => $material,
                ':qty'    => $quantity,
                ':price'  => $unit_price,
                ':po'     => $po_number,
                ':status' => $status,
                ':de'     => $audit['date_edited'],
                ':eb'     => $audit['edited_by'],
            ]);

            audit_log('delivery', $id, 'UPDATE', $oldData, $_POST, $admin);

            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Delivery updated'];
            header("Location: /main.php#trans_entry.php?soa_id={$soa_id_post}");
            exit;
        }
    }

} catch (Exception $e) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    header("Location: {$redirectUrl}?soa_id={$soa_id}");
    exit;
}
