<?php
// super_admin/advertisers.php - Advertiser Management
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Advertisers';
$success = '';
$error = '';

// Handle add/edit advertiser
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $company = $_POST['company'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        if (isset($_POST['edit_id']) && $_POST['edit_id']) {
            $stmt = $conn->prepare("UPDATE advertisers SET name = ?, email = ?, company = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $company, $phone, $_POST['edit_id']]);
            $success = "Advertiser updated successfully!";
        } else {
            $stmt = $conn->prepare("INSERT INTO advertisers (name, email, company, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $company, $phone]);
            $success = "Advertiser added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("DELETE FROM advertisers WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Advertiser deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting advertiser: " . $e->getMessage();
    }
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM advertisers ORDER BY created_at DESC");
    $stmt->execute();
    $advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading advertisers: " . $e->getMessage();
    $advertisers = [];
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="page-header mb-0">
                    <h2><i class="fas fa-users me-2"></i>Advertisers</h2>
                    <p>Manage advertisers</p>
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>Add Advertiser
                </button>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($advertisers)): ?>
                        <div class="p-5 text-center">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No advertisers yet</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Company</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($advertisers as $advertiser): ?>
                                    <tr>
                                        <td><?php echo $advertiser['id']; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($advertiser['name']); ?></td>
                                        <td><?php echo htmlspecialchars($advertiser['email']); ?></td>
                                        <td><?php echo htmlspecialchars($advertiser['company'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($advertiser['phone'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning text-white edit-btn" 
                                                    data-id="<?php echo $advertiser['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($advertiser['name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($advertiser['email']); ?>"
                                                    data-company="<?php echo htmlspecialchars($advertiser['company'] ?? ''); ?>"
                                                    data-phone="<?php echo htmlspecialchars($advertiser['phone'] ?? ''); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $advertiser['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this advertiser?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Advertiser</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="editId">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" id="inputName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="inputEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" id="inputCompany" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="inputPhone" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extra_js = "
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Edit Advertiser';
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('inputName').value = this.dataset.name;
            document.getElementById('inputEmail').value = this.dataset.email;
            document.getElementById('inputCompany').value = this.dataset.company;
            document.getElementById('inputPhone').value = this.dataset.phone;
            new bootstrap.Modal(document.getElementById('addModal')).show();
        });
    });
    
    document.getElementById('addModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Add Advertiser';
        document.getElementById('editId').value = '';
        this.querySelector('form').reset();
    });
";
require_once 'includes/footer.php';
?>