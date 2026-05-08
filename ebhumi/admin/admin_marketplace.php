<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

$message = "";

// Handle Delete Listing action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_listing'])) {
    $delete_id = $_POST['listing_id'];
    
    // First, fetch file paths to delete from server
    $stmt = $pdo->prepare("SELECT layout_pdf_path, image_path FROM property_listings WHERE id = ?");
    $stmt->execute([$delete_id]);
    $files = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($files) {
        if ($files['layout_pdf_path'] && file_exists('../' . $files['layout_pdf_path'])) {
            unlink('../' . $files['layout_pdf_path']);
        }
        if ($files['image_path'] && file_exists('../' . $files['image_path'])) {
            unlink('../' . $files['image_path']);
        }
        
        // Delete from DB
        $stmt = $pdo->prepare("DELETE FROM property_listings WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        $message = "<div style='background: rgba(16, 185, 129, 0.15); color: #10B981; padding: 12px; border-radius: 8px; margin-bottom: 1.5rem;'>Listing forcefully removed from the platform.</div>";
    }
}

// Fetch all listings
$stmt = $pdo->query("SELECT pl.*, c.full_name as seller_name FROM property_listings pl JOIN citizens c ON pl.seller_id = c.id ORDER BY pl.created_at DESC");
$all_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all citizens
$stmt2 = $pdo->query("SELECT * FROM citizens ORDER BY created_at DESC");
$all_citizens = $stmt2->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Moderation - Admin</title>
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
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--surface-border); color: #fff; }
        th { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        
        .status { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status.Available { background: rgba(16, 185, 129, 0.15); color: #10B981; }
        .status.Sold { background: rgba(239, 68, 68, 0.15); color: #EF4444; }
        
        .btn-danger { background: #EF4444; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; }
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
            <a href="dashboard.php" class="nav-link">
                <i data-feather="home"></i> Dashboard
            </a>
            <a href="admin_marketplace.php" class="nav-link active">
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
            <a href="logout.php" class="nav-link"><i data-feather="log-out"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <h1>Marketplace Moderation</h1>
            <div class="user-profile">
                <i data-feather="user"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            </div>
        </header>

        <?php echo $message; ?>

        <div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2.5rem;">
            <h2>All Property Listings</h2>
            <div style="overflow-x: auto;">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Survey No.</th>
                        <th>Village</th>
                        <th>Seller Name</th>
                        <th>Asking Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($all_listings as $list): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($list['id']); ?></td>
                        <td><?php echo htmlspecialchars($list['survey_number']); ?></td>
                        <td><?php echo htmlspecialchars($list['village']); ?></td>
                        <td><?php echo htmlspecialchars($list['seller_name']); ?></td>
                        <td>₹<?php echo number_format($list['asking_price']); ?></td>
                        <td><span class="status <?php echo $list['status']; ?>"><?php echo $list['status']; ?></span></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to FORCE DELETE this listing? This action cannot be undone.');">
                                <input type="hidden" name="listing_id" value="<?php echo $list['id']; ?>">
                                <button type="submit" name="delete_listing" class="btn-danger"><i data-feather="trash-2" style="width:16px; height:16px;"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="glass-panel" style="padding: 1.5rem;">
            <h2>Registered Citizens Directory</h2>
            <div style="overflow-x: auto;">
                <table>
                    <tr>
                        <th>Citizen ID</th>
                        <th>Full Name</th>
                        <th>Mobile</th>
                        <th>Aadhaar</th>
                        <th>Joined On</th>
                    </tr>
                    <?php foreach ($all_citizens as $cit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cit['id']); ?></td>
                        <td><?php echo htmlspecialchars($cit['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($cit['mobile_number']); ?></td>
                        <td><?php echo htmlspecialchars($cit['aadhaar_number']); ?></td>
                        <td><?php echo date('d M Y', strtotime($cit['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

    </main>
</div>

<script>feather.replace();</script>
</body>
</html>
