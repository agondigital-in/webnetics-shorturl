<?php
// super_admin/publishers.php - Publisher Management
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$page_title = 'Publishers';
$success = '';
$error = '';

// Handle add/edit publisher
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $website = $_POST['website'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (isset($_POST['edit_id']) && $_POST['edit_id']) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE publishers SET name = ?, email = ?, website = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $website, $phone, $hashed, $_POST['edit_id']]);
            } else {
                $stmt = $conn->prepare("UPDATE publishers SET name = ?, email = ?, website = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $email, $website, $phone, $_POST['edit_id']]);
            }
            $success = "Publisher updated successfully!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO publishers (name, email, website, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $website, $phone, $hashed]);
            $success = "Publisher added successfully!";
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
        $stmt = $conn->prepare("DELETE FROM publishers WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Publisher deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting publisher: " . $e->getMessage();
    }
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM publishers ORDER BY created_at DESC");
    $stmt->execute();
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading publishers: " . $e->getMessage();
    $publishers = [];
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
                    <h2><i class="fas fa-share-alt me-2"></i>Publishers</h2>
                    <p>Manage publishers</p>
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>Add Publisher
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
                    <?php if (empty($publishers)): ?>
                        <div class="p-5 text-center">
                            <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No publishers yet</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Website</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): ?>
                                    <tr>
                                        <td><?php echo $publisher['id']; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($publisher['name']); ?></td>
                                        <td><?php echo htmlspecialchars($publisher['email']); ?></td>
                                        <td><?php echo htmlspecialchars($publisher['website'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($publisher['phone'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning text-white edit-btn" 
                                                    data-id="<?php echo $publisher['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($publisher['name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($publisher['email']); ?>"
                                                    data-website="<?php echo htmlspecialchars($publisher['website'] ?? ''); ?>"
                                                    data-phone="<?php echo htmlspecialchars($publisher['phone'] ?? ''); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $publisher['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this publisher?')">
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
                    <h5 class="modal-title" id="modalTitle">Add Publisher</h5>
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
                        <label class="form-label">Website</label>
                        <input type="url" name="website" id="inputWebsite" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="inputPhone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span id="pwdNote">(required)</span></label>
                        <input type="password" name="password" id="inputPassword" class="form-control">
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
            document.getElementById('modalTitle').textContent = 'Edit Publisher';
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('inputName').value = this.dataset.name;
            document.getElementById('inputEmail').value = this.dataset.email;
            document.getElementById('inputWebsite').value = this.dataset.website;
            document.getElementById('inputPhone').value = this.dataset.phone;
            document.getElementById('inputPassword').required = false;
            document.getElementById('pwdNote').textContent = '(leave blank to keep current)';
            new bootstrap.Modal(document.getElementById('addModal')).show();
        });
    });
    
    document.getElementById('addModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Add Publisher';
        document.getElementById('editId').value = '';
        document.getElementById('inputPassword').required = true;
        document.getElementById('pwdNote').textContent = '(required)';
        this.querySelector('form').reset();
    });
";
require_once 'includes/footer.php';
?>