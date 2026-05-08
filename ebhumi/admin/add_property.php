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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $survey_number = trim($_POST['survey_number']);
    $village = trim($_POST['village']);
    $taluka = trim($_POST['taluka']);
    $district = trim($_POST['district']);
    $area = floatval($_POST['area_sq_meters']);
    $map_coords = trim($_POST['map_coordinates']);

    if (empty($survey_number) || empty($village) || empty($taluka) || empty($district) || $area <= 0) {
        $message = "Please fill all required fields correctly.";
        $msg_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO properties (survey_number, village, taluka, district, area_sq_meters, map_coordinates) VALUES (:survey, :village, :taluka, :district, :area, :coords)");
            $stmt->execute([
                ':survey' => $survey_number,
                ':village' => $village,
                ':taluka' => $taluka,
                ':district' => $district,
                ':area' => $area,
                ':coords' => empty($map_coords) ? null : $map_coords
            ]);
            $message = "Property added successfully!";
            $msg_type = "success";
        } catch (PDOException $e) {
            // Error 23000 is for unique constraint violation
            if ($e->getCode() == 23000) {
                $message = "Error: A property with this Survey Number already exists in the given village and taluka.";
            } else {
                $message = "Database error: " . $e->getMessage();
            }
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
    <title>Add Property - Admin e-BhumiAbhilekhan</title>
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        .sidebar {
            width: 260px;
            background: var(--surface);
            backdrop-filter: blur(12px);
            border-right: 1px solid var(--surface-border);
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2.5rem;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.875rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(79, 70, 229, 0.15);
            color: var(--primary);
        }
        .main-content {
            flex: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--surface);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            border: 1px solid var(--surface-border);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i data-feather="map"></i>
            e-Bhumi
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link">
                <i data-feather="home"></i> Dashboard
            </a>
            <a href="add_property.php" class="nav-link active">
                <i data-feather="plus-square"></i> Add Property
            </a>
            <a href="upload_712.php" class="nav-link">
                <i data-feather="file-text"></i> Upload 7/12
            </a>
            <a href="manage_encumbrances.php" class="nav-link">
                <i data-feather="alert-triangle"></i> Encumbrances
            </a>
        </nav>
        
        <div style="margin-top: auto;">
            <a href="logout.php" class="nav-link">
                <i data-feather="log-out"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <h1>Add New Property Record</h1>
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

            <form action="add_property.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="survey_number" class="form-label">Survey Number / Gut Number *</label>
                        <input type="text" id="survey_number" name="survey_number" class="form-control" required placeholder="e.g. 154/2">
                    </div>
                    
                    <div class="form-group">
                        <label for="area_sq_meters" class="form-label">Total Area (in sq. meters) *</label>
                        <input type="number" step="0.01" id="area_sq_meters" name="area_sq_meters" class="form-control" required placeholder="e.g. 5000.50">
                    </div>

                    <div class="form-group">
                        <label for="village" class="form-label">Village / City *</label>
                        <input type="text" id="village" name="village" class="form-control" required placeholder="Enter village name">
                    </div>

                    <div class="form-group">
                        <label for="taluka" class="form-label">Taluka *</label>
                        <input type="text" id="taluka" name="taluka" class="form-control" required placeholder="Enter taluka name">
                    </div>

                    <div class="form-group">
                        <label for="district" class="form-label">District *</label>
                        <input type="text" id="district" name="district" class="form-control" required placeholder="Enter district name">
                    </div>

                    <div class="form-group">
                        <label for="map_coordinates" class="form-label">Map Coordinates (Lat, Lng)</label>
                        <input type="text" id="map_coordinates" name="map_coordinates" class="form-control" placeholder="e.g. 19.0760, 72.8777 (Optional)">
                        <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px; display: block;">For Google Maps integration on the frontend.</small>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" style="margin-right: 8px; width: 18px;"></i> Save Property Record
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
