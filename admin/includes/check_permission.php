<?php
// admin/includes/check_permission.php - Check admin permissions

function hasPermission($conn, $admin_id, $permission) {
    try {
        // Check if admin_permissions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_permissions'");
        if ($tableCheck->rowCount() == 0) {
            return true; // If table doesn't exist, allow all (backward compatibility)
        }
        
        $stmt = $conn->prepare("SELECT id FROM admin_permissions WHERE admin_id = ? AND permission = ?");
        $stmt->execute([$admin_id, $permission]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return true; // On error, allow access
    }
}

function hasAnyPermission($conn, $admin_id, $permissions) {
    foreach ($permissions as $perm) {
        if (hasPermission($conn, $admin_id, $perm)) {
            return true;
        }
    }
    return false;
}

// Get all permissions for current admin
function getAdminPermissions($conn, $admin_id) {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_permissions'");
        if ($tableCheck->rowCount() == 0) {
            return []; // Return empty if table doesn't exist
        }
        
        $stmt = $conn->prepare("SELECT permission FROM admin_permissions WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}
