<?php
// helpers/audit_fields.php

function audit_on_create($admin_id) {
    return [
        'date_created' => date('Y-m-d H:i:s'),
        'created_by'   => $admin_id,
        'date_edited'  => date('Y-m-d H:i:s'),
        'edited_by'    => $admin_id
    ];
}

function audit_on_update($admin_id) {
    return [
        'date_edited' => date('Y-m-d H:i:s'),
        'edited_by'   => $admin_id
    ];
}
