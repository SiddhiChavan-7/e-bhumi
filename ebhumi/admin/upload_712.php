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

// Fetch all properties for the dropdown
$properties = [];
try {
    $stmt = $pdo->query("SELECT id, survey_number, village FROM properties ORDER BY id DESC");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching properties: " . $e->getMessage();
    $msg_type = "danger";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $property_id = intval($_POST['property_id']);
    $owner_name = trim($_POST['owner_name']);
    $khata_number = trim($_POST['khata_number']);
    $cultivator_name = trim($_POST['cultivator_name']);
    $mutation_notes = trim($_POST['mutation_notes']);
    $record_date = trim($_POST['record_date']);

    if (empty($property_id) || empty($owner_name) || empty($khata_number) || empty($record_date)) {
        $message = "Please fill all required fields correctly.";
        $msg_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO seven_twelve_records (property_id, owner_name, khata_number, cultivator_name, mutation_notes, record_date) VALUES (:prop_id, :owner, :khata, :cultivator, :notes, :rdate)");
            $stmt->execute([
                ':prop_id' => $property_id,
                ':owner' => $owner_name,
                ':khata' => $khata_number,
                ':cultivator' => empty($cultivator_name) ? null : $cultivator_name,
                ':notes' => empty($mutation_notes) ? null : $mutation_notes,
                ':rdate' => $record_date
            ]);
            $message = "7/12 Extract data securely logged!";
            $msg_type = "success";
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload 7/12 - Admin e-BhumiAbhilekhan</title>
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
        textarea.form-control { resize: vertical; min-height: 100px; }
        select.form-control { background-color: rgba(15, 23, 42, 0.8); cursor: pointer; }
        select.form-control option { background-color: var(--bg-color); }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i data-feather="map"></i> e-Bhumi
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link"><i data-feather="home"></i> Dashboard</a>
            <a href="add_property.php" class="nav-link"><i data-feather="plus-square"></i> Add Property</a>
            <a href="upload_712.php" class="nav-link active"><i data-feather="file-text"></i> Upload 7/12</a>
            <a href="manage_encumbrances.php" class="nav-link"><i data-feather="alert-triangle"></i> Encumbrances</a>
        </nav>
        <div style="margin-top: auto;">
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <h1>Upload Structured 7/12 Data</h1>
            <div class="user-profile">
                <i data-feather="user"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            </div>
        </header>

        <div class="glass-panel" style="padding: 2rem; max-width: 900px;">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $msg_type; ?>">
                    <i data-feather="<?php echo $msg_type === 'success' ? 'check-circle' : 'alert-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($properties)): ?>
                <div class="alert alert-warning" style="background: rgba(212, 130, 10, 0.1); border: 1px solid rgba(212, 130, 10, 0.2); color: #FBBF24;">
                    <i data-feather="alert-circle"></i>
                    <span>Please add a Property Record first before uploading a 7/12 extract!</span>
                </div>
            <?php else: ?>
                <form action="upload_712.php" method="POST">
                    <div class="form-group">
                        <label for="property_id" class="form-label">Link to Property (Survey Number) *</label>
                        <select id="property_id" name="property_id" class="form-control" required>
                            <option value="">-- Select Property --</option>
                            <?php foreach ($properties as $prop): ?>
                                <option value="<?php echo $prop['id']; ?>">
                                    Survey: <?php echo htmlspecialchars($prop['survey_number']); ?> (<?php echo htmlspecialchars($prop['village']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="owner_name" class="form-label">Current Owner Name(s) *</label>
                            <input type="text" id="owner_name" name="owner_name" class="form-control" required placeholder="Name on 7/12">
                        </div>
                        
                        <div class="form-group">
                            <label for="khata_number" class="form-label">Khata Number *</label>
                            <input type="text" id="khata_number" name="khata_number" class="form-control" required placeholder="e.g. 1024">
                        </div>

                        <div class="form-group">
                            <label for="cultivator_name" class="form-label">Cultivator Name (If any)</label>
                            <input type="text" id="cultivator_name" name="cultivator_name" class="form-control" placeholder="Name of actual cultivator">
                        </div>

                        <div class="form-group">
                            <label for="record_date" class="form-label">Date of 7/12 Extract *</label>
                            <input type="date" id="record_date" name="record_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="mutation_notes" class="form-label">Mutation Notes / Other Rights (इतर हक्क)</label>
                        <textarea id="mutation_notes" name="mutation_notes" class="form-control" placeholder="Enter any specific notes, loans, or mutations mentioned in the extract..."></textarea>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="upload-cloud" style="margin-right: 8px; width: 18px;"></i> Upload 7/12 Data
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
