<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Fetch basic stats for the dashboard
$total_properties = 0;
$total_encumbrances = 0;
$total_citizens = 0;
$total_marketplace = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM properties");
    $total_properties = $stmt->fetchColumn();

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM encumbrances WHERE status = 'Active'");
    $total_encumbrances = $stmt2->fetchColumn();
    
    $stmt3 = $pdo->query("SELECT COUNT(*) FROM citizens");
    $total_citizens = $stmt3->fetchColumn();
    
    $stmt4 = $pdo->query("SELECT COUNT(*) FROM property_listings");
    $total_marketplace = $stmt4->fetchColumn();
} catch (PDOException $e) {
    // Silently ignore or log error
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - e-BhumiAbhilekhan</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(79, 70, 229, 0.15);
            color: var(--primary);
        }
        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 4px;
        }
        .stat-info p {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
        }
        .danger-icon {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }
        .success-icon {
            background: rgba(16, 185, 129, 0.15);
            color: #10B981;
        }
        .warning-icon {
            background: rgba(245, 158, 11, 0.15);
            color: #F59E0B;
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
            <a href="dashboard.php" class="nav-link active">
                <i data-feather="home"></i> Dashboard
            </a>
            <a href="admin_marketplace.php" class="nav-link">
                <i data-feather="shopping-cart"></i> Marketplace Moderation
            </a>
            <a href="add_property.php" class="nav-link">
                <i data-feather="plus-square"></i> Add Property Database
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
            <h1>Dashboard Overview</h1>
            <div class="user-profile">
                <i data-feather="user"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card glass-panel">
                <div class="stat-icon">
                    <i data-feather="map-pin"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Properties</h3>
                    <p><?php echo $total_properties; ?></p>
                </div>
            </div>
            
            <div class="stat-card glass-panel">
                <div class="stat-icon danger-icon">
                    <i data-feather="alert-octagon"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Encumbrances</h3>
                    <p><?php echo $total_encumbrances; ?></p>
                </div>
            </div>

            <div class="stat-card glass-panel">
                <div class="stat-icon success-icon">
                    <i data-feather="users"></i>
                </div>
                <div class="stat-info">
                    <h3>Registered Citizens</h3>
                    <p><?php echo $total_citizens; ?></p>
                </div>
            </div>

            <div class="stat-card glass-panel">
                <div class="stat-icon warning-icon">
                    <i data-feather="shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>Marketplace Listings</h3>
                    <p><?php echo $total_marketplace; ?></p>
                </div>
            </div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem;">
            <h2 style="font-size: 1.2rem; margin-bottom: 1rem;">Recent Activity</h2>
            <p style="color: var(--text-muted);">Welcome to the new e-BhumiAbhilekhan smart land verification system admin portal.</p>
            <p style="color: var(--text-muted); margin-top: 1rem;">Use the sidebar to add new properties, upload 7/12 records, and manage encumbrances.</p>
        </div>
    </main>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
