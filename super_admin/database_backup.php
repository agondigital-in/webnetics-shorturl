<?php
// super_admin/database_backup.php - Database Backup Management
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Database Backup';
$db = Database::getInstance();
$conn = $db->getConnection();

$message = '';
$error = '';

// Get all tables
$tables = [];
$stmt = $conn->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

// Handle backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $backup_type = $_POST['backup_type'] ?? 'full';
    $selected_tables = $_POST['tables'] ?? [];
    
    try {
        $backup_content = "-- Database Backup\n";
        $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Type: " . ($backup_type === 'full' ? 'Full Backup' : 'Selected Tables') . "\n\n";
        $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        $tables_to_backup = ($backup_type === 'full') ? $tables : $selected_tables;
        
        foreach ($tables_to_backup as $table) {
            // Get table structure
            $stmt = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            
            $backup_content .= "-- Table: $table\n";
            $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup_content .= $row[1] . ";\n\n";
            
            // Get table data
            $stmt = $conn->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = array_map(function($val) use ($conn) {
                        if ($val === null) return 'NULL';
                        return $conn->quote($val);
                    }, array_values($row));
                    
                    $backup_content .= "INSERT INTO `$table` ($column_list) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Generate filename
        $filename = 'backup_' . ($backup_type === 'full' ? 'full' : 'partial') . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Send file for download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup_content));
        echo $backup_content;
        exit();
        
    } catch (Exception $e) {
        $error = "Backup failed: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="page-header">
                <h2><i class="fas fa-database me-2"></i>Database Backup</h2>
                <p>Download full or table-wise database backup</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Full Backup -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-download me-2"></i>Full Database Backup
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Download complete database backup including all tables and data.</p>
                            <ul class="small text-muted">
                                <li>All <?php echo count($tables); ?> tables included</li>
                                <li>Structure + Data</li>
                                <li>SQL format</li>
                            </ul>
                            <form method="POST">
                                <input type="hidden" name="backup_type" value="full">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-download me-2"></i>Download Full Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Table-wise Backup -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-table me-2"></i>Table-wise Backup
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Select specific tables to backup.</p>
                            <form method="POST">
                                <input type="hidden" name="backup_type" value="selected">
                                <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($tables as $table): ?>
                                    <div class="form-check">
                                        <input class="form-check-input table-checkbox" type="checkbox" name="tables[]" value="<?php echo $table; ?>" id="table_<?php echo $table; ?>">
                                        <label class="form-check-label" for="table_<?php echo $table; ?>">
                                            <?php echo $table; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllTables()">Select All</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllTables()">Deselect All</button>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-download me-2"></i>Download Selected Tables
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Database Info -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Database Information
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th class="text-end">Rows</th>
                                    <th class="text-end">Size</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_rows = 0;
                                $total_size = 0;
                                foreach ($tables as $table): 
                                    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
                                    $row_count = $stmt->fetch()['cnt'];
                                    $total_rows += $row_count;
                                    
                                    $stmt = $conn->query("SHOW TABLE STATUS LIKE '$table'");
                                    $status = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $size = ($status['Data_length'] ?? 0) + ($status['Index_length'] ?? 0);
                                    $total_size += $size;
                                ?>
                                <tr>
                                    <td><code><?php echo $table; ?></code></td>
                                    <td class="text-end"><?php echo number_format($row_count); ?></td>
                                    <td class="text-end"><?php echo formatBytes($size); ?></td>
                                    <td class="text-center">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="backup_type" value="selected">
                                            <input type="hidden" name="tables[]" value="<?php echo $table; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Download this table">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td class="fw-bold">Total (<?php echo count($tables); ?> tables)</td>
                                    <td class="text-end fw-bold"><?php echo number_format($total_rows); ?></td>
                                    <td class="text-end fw-bold"><?php echo formatBytes($total_size); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<script>
function selectAllTables() {
    document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = true);
}

function deselectAllTables() {
    document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = false);
}
</script>

<?php require_once 'includes/footer.php'; ?>
