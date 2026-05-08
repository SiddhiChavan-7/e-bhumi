<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

$message = '';
$msg_type = '';

// Fetch properties
$properties = [];
try {
    $stmt = $pdo->query("SELECT id, survey_number, village FROM properties ORDER BY id DESC");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $property_id = intval($_POST['property_id']);
    $type = $_POST['encumbrance_type'];
    $description = trim($_POST['description']);
    $severity = $_POST['severity_level'];
    $status = $_POST['status'];
    $date_recorded = trim($_POST['date_recorded']);

    if (empty($property_id) || empty($type) || empty($description) || empty($severity) || empty($date_recorded)) {
        $message = "Please fill all required fields.";
        $msg_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO encumbrances (property_id, encumbrance_type, description, severity_level, status, date_recorded) VALUES (:prop, :type, :desc, :sev, :status, :drec)");
            $stmt->execute([
                ':prop' => $property_id,
                ':type' => $type,
                ':desc' => $description,
                ':sev' => $severity,
                ':status' => $status,
                ':drec' => $date_recorded
            ]);
            $message = "Encumbrance added successfully!";
            $msg_type = "success";
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// Fetch all encumbrances to show in a table below
$encumbrances = [];
try {
    $stmt = $pdo->query("
        SELECT e.*, p.survey_number, p.village 
        FROM encumbrances e 
        JOIN properties p ON e.property_id = p.id 
        ORDER BY e.id DESC LIMIT 10
    ");
    $encumbrances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Encumbrances - Admin e-BhumiAbhilekhan</title>
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .layout { display: flex; min-height: 100vh; width: 100%; }
        .sidebar { width: 260px; background: var(--surface); backdrop-filter: blur(12px); border-right: 1px solid var(--surface-border); padding: 2rem 1rem; display: flex; flex-direction: column; }
        .sidebar-brand { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 2.5rem; padding: 0 1rem; display: flex; align-items: center; gap: 10px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 0.875rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: 8px; margin-bottom: 0.5rem; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background: rgba(79, 70, 229, 0.15); color: var(--primary); }
        .main-content { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
        .header h1 { font-size: 1.8rem; font-weight: 600; }
        .user-profile { display: flex; align-items: center; gap: 12px; background: var(--surface); padding: 0.5rem 1rem; border-radius: 30px; border: 1px solid var(--surface-border); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); }
        textarea.form-control { resize: vertical; min-height: 80px; }
        select.form-control { background-color: rgba(15, 23, 42, 0.8); cursor: pointer; }
        select.form-control option { background-color: var(--bg-color); }
        
        /* Table Styles */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .data-table th, .data-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--surface-border); }
        .data-table th { color: var(--text-muted); font-weight: 500; font-size: 0.9rem; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
        .badge-high { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        .badge-medium { background: rgba(245, 158, 11, 0.2); color: #F59E0B; }
        .badge-low { background: rgba(16, 185, 129, 0.2); color: var(--success); }
    </style>
</head>
<body>

<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><i data-feather="map"></i> e-Bhumi</div>
        <nav>
            <a href="dashboard.php" class="nav-link"><i data-feather="home"></i> Dashboard</a>
            <a href="add_property.php" class="nav-link"><i data-feather="plus-square"></i> Add Property</a>
            <a href="upload_712.php" class="nav-link"><i data-feather="file-text"></i> Upload 7/12</a>
            <a href="manage_encumbrances.php" class="nav-link active"><i data-feather="alert-triangle"></i> Encumbrances</a>
        </nav>
        <div style="margin-top: auto;">
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1>Manage Encumbrances (Bojha)</h1>
            <div class="user-profile">
                <i data-feather="user"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            </div>
        </header>

        <div class="form-grid" style="align-items: start;">
            <!-- Add Encumbrance Form -->
            <div class="glass-panel" style="padding: 2rem;">
                <h2 style="font-size: 1.2rem; margin-bottom: 1.5rem;">Add New Record</h2>
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $msg_type; ?>">
                        <i data-feather="<?php echo $msg_type === 'success' ? 'check-circle' : 'alert-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <form action="manage_encumbrances.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Property *</label>
                        <select name="property_id" class="form-control" required>
                            <option value="">-- Select Property --</option>
                            <?php foreach ($properties as $prop): ?>
                                <option value="<?php echo $prop['id']; ?>">Survey: <?php echo htmlspecialchars($prop['survey_number']); ?> (<?php echo htmlspecialchars($prop['village']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Encumbrance Type *</label>
                        <select name="encumbrance_type" class="form-control" required>
                            <option value="Bank Loan">Bank Loan (Bojha)</option>
                            <option value="Private Mortgage">Private Mortgage</option>
                            <option value="Legal Dispute">Legal Dispute / Court Case</option>
                            <option value="Government Lien">Government Lien / Tax Dues</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" required placeholder="e.g. Loan of ₹5,00,000 from SBI"></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Severity *</label>
                            <select name="severity_level" class="form-control" required>
                                <option value="Low">Low Risk</option>
                                <option value="Medium">Medium Risk</option>
                                <option value="High">High Risk</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="Active">Active</option>
                                <option value="Resolved">Resolved</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date Recorded *</label>
                        <input type="date" name="date_recorded" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i data-feather="plus" style="margin-right: 8px;"></i> Add Encumbrance
                    </button>
                </form>
            </div>

            <!-- Recent Encumbrances List -->
            <div class="glass-panel" style="padding: 2rem;">
                <h2 style="font-size: 1.2rem; margin-bottom: 1.5rem;">Recent Records</h2>
                
                <?php if (empty($encumbrances)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No encumbrances found.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($encumbrances as $enc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($enc['survey_number']); ?></td>
                                    <td><?php echo htmlspecialchars($enc['encumbrance_type']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($enc['severity_level']); ?>">
                                            <?php echo htmlspecialchars($enc['severity_level']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($enc['status'] == 'Active'): ?>
                                            <span style="color: var(--danger);"><i data-feather="x-circle" style="width:16px;"></i></span>
                                        <?php else: ?>
                                            <span style="color: var(--success);"><i data-feather="check-circle" style="width:16px;"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
