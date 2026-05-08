<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['citizen_logged_in'])) {
    header("Location: login.php");
    exit;
}

$citizen_id = $_SESSION['citizen_id'];
$message = '';

// Handle Accept/Reject action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_request'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action_type']; // 'Accepted' or 'Rejected'
    $listing_id = $_POST['listing_id'];
    
    if ($action == 'Accepted') {
        // Update request status
        $stmt = $pdo->prepare("UPDATE purchase_requests SET status = 'Accepted' WHERE id = ?");
        $stmt->execute([$request_id]);
        
        // Reject all other requests for this listing
        $stmt = $pdo->prepare("UPDATE purchase_requests SET status = 'Rejected' WHERE listing_id = ? AND id != ?");
        $stmt->execute([$listing_id, $request_id]);
        
        // Mark listing as Sold
        $stmt = $pdo->prepare("UPDATE property_listings SET status = 'Sold' WHERE id = ?");
        $stmt->execute([$listing_id]);
        
        $message = "<div class='success-msg'>Offer Accepted! Property is now marked as Sold.</div>";
    } elseif ($action == 'Rejected') {
        $stmt = $pdo->prepare("UPDATE purchase_requests SET status = 'Rejected' WHERE id = ?");
        $stmt->execute([$request_id]);
        $message = "<div class='success-msg'>Offer Rejected.</div>";
    }
}

// Handle Delete Listing action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_listing'])) {
    $delete_id = $_POST['listing_id'];
    
    // First, fetch file paths to delete from server
    $stmt = $pdo->prepare("SELECT layout_pdf_path, image_path FROM property_listings WHERE id = ? AND seller_id = ?");
    $stmt->execute([$delete_id, $citizen_id]);
    $files = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($files) {
        if ($files['layout_pdf_path'] && file_exists($files['layout_pdf_path'])) {
            unlink($files['layout_pdf_path']);
        }
        if ($files['image_path'] && file_exists($files['image_path'])) {
            unlink($files['image_path']);
        }
        
        // Delete from DB (ON DELETE CASCADE will handle purchase_requests)
        $stmt = $pdo->prepare("DELETE FROM property_listings WHERE id = ? AND seller_id = ?");
        $stmt->execute([$delete_id, $citizen_id]);
        
        $message = "<div class='success-msg'>Property listing has been completely removed.</div>";
    }
}
$stmt = $pdo->prepare("SELECT * FROM property_listings WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$citizen_id]);
$my_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch incoming requests for user's listings
$stmt = $pdo->prepare("
    SELECT pr.*, pl.survey_number, pl.village, c.full_name as buyer_name, c.mobile_number as buyer_mobile 
    FROM purchase_requests pr 
    JOIN property_listings pl ON pr.listing_id = pl.id 
    JOIN citizens c ON pr.buyer_id = c.id 
    WHERE pl.seller_id = ? 
    ORDER BY pr.created_at DESC
");
$stmt->execute([$citizen_id]);
$incoming_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard - e-Bhumi</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --saffron: #E8621A;
        --navy: #0F1F3D;
        --cream: #FAF7F2;
        --border: #E2DAD0;
        --white: #FFFFFF;
        --green: #1A7A3C;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--navy); }
    
    nav{display:flex;align-items:center;justify-content:space-between;padding:1rem 2.5rem;background:rgba(255,255,255,.92);border-bottom:1px solid var(--border);}
    .nav-brand{font-weight:700;text-decoration:none; color: var(--navy);}
    .nav-links{display:flex;gap:1.5rem;align-items:center;}
    .nav-links a{font-size:.875rem;color:var(--navy);text-decoration:none;font-weight:500;}
    
    .container { max-width: 1000px; margin: 2rem auto; padding: 0 2rem; }
    h1 { font-size: 2rem; margin-bottom: 2rem; }
    
    .card { background: var(--white); border-radius: 10px; padding: 1.5rem; border: 1px solid var(--border); margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .card h2 { font-size: 1.25rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; }
    
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: #8C8278; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
    
    .status { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .status.Available { background: #E8F5EE; color: var(--green); }
    .status.Sold { background: #FEF0EE; color: #C0321A; }
    .status.Pending { background: #FFF4E5; color: var(--saffron); }
    .status.Accepted { background: #E8F5EE; color: var(--green); }
    .status.Rejected { background: #F5F5F5; color: #8C8278; }
    
    .btn { padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; }
    .btn-accept { background: var(--green); color: white; }
    .btn-reject { background: #C0321A; color: white; }
    
    .success-msg { background: #E8F5EE; color: var(--green); padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--green); }
</style>
</head>
<body>

<?php
$pending_count = 0;
foreach ($incoming_requests as $req) {
    if ($req['status'] == 'Pending') $pending_count++;
}
?>
<nav>
  <a href="index.php" class="nav-brand">e-Bhumi Dashboard</a>
  <div class="nav-links">
    <a href="marketplace.php">Marketplace</a>
    <a href="sell_property.php" style="color: var(--saffron); font-weight: 600;">+ Sell Property</a>
    
    <div style="position: relative; display: inline-block; cursor: pointer;" title="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--navy); vertical-align: middle;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <?php if ($pending_count > 0): ?>
            <span style="position: absolute; top: -5px; right: -8px; background: #C0321A; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.65rem; font-weight: 700;"><?php echo $pending_count; ?></span>
        <?php endif; ?>
    </div>

    <a href="logout.php" style="color: #C0321A; margin-left: 10px;">Logout</a>
  </div>
</nav>

<div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['citizen_name']); ?></h1>
    
    <?php echo $message; ?>

    <div class="card">
        <h2>Incoming Purchase Requests</h2>
        <?php if (empty($incoming_requests)): ?>
            <p style="color: #8C8278;">No one has made an offer on your properties yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Buyer Name</th>
                    <th>Buyer Mobile</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($incoming_requests as $req): ?>
                <tr>
                    <td><?php echo htmlspecialchars($req['survey_number'] . ' (' . $req['village'] . ')'); ?></td>
                    <td><?php echo htmlspecialchars($req['buyer_name']); ?></td>
                    <td><?php echo htmlspecialchars($req['buyer_mobile']); ?></td>
                    <td><span class="status <?php echo $req['status']; ?>"><?php echo $req['status']; ?></span></td>
                    <td>
                        <?php if ($req['status'] == 'Pending'): ?>
                        <form method="POST" style="display:inline-flex; gap:5px;">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <input type="hidden" name="listing_id" value="<?php echo $req['listing_id']; ?>">
                            <button type="submit" name="action_type" value="Accepted" class="btn btn-accept">Accept</button>
                            <button type="submit" name="action_type" value="Rejected" class="btn btn-reject">Reject</button>
                            <input type="hidden" name="action_request" value="1">
                        </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>My Properties for Sale</h2>
        <?php if (empty($my_listings)): ?>
            <p style="color: #8C8278;">You have not listed any properties.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Survey Number</th>
                    <th>Village</th>
                    <th>Asking Price</th>
                    <th>Status</th>
                    <th>Listed On</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($my_listings as $list): ?>
                <tr>
                    <td><?php echo htmlspecialchars($list['survey_number']); ?></td>
                    <td><?php echo htmlspecialchars($list['village']); ?></td>
                    <td>₹<?php echo number_format($list['asking_price']); ?></td>
                    <td><span class="status <?php echo $list['status']; ?>"><?php echo $list['status']; ?></span></td>
                    <td><?php echo date('d M Y', strtotime($list['created_at'])); ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this property listing?');">
                            <input type="hidden" name="listing_id" value="<?php echo $list['id']; ?>">
                            <button type="submit" name="delete_listing" class="btn btn-reject">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
