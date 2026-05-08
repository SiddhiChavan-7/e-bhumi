<?php
session_start();
require_once 'config.php';

$message = '';
// Handle Purchase Request Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_purchase'])) {
    if (!isset($_SESSION['citizen_logged_in'])) {
        $message = "<div style='color:red;'>You must be logged in to send a request.</div>";
    } else {
        $listing_id = $_POST['listing_id'];
        $buyer_id = $_SESSION['citizen_id'];
        
        // Check if already requested
        $chk = $pdo->prepare("SELECT id FROM purchase_requests WHERE listing_id = ? AND buyer_id = ?");
        $chk->execute([$listing_id, $buyer_id]);
        if ($chk->rowCount() > 0) {
            $message = "<div style='color:#E8621A; text-align:center; padding:10px;'>You have already sent a request for this property.</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO purchase_requests (listing_id, buyer_id) VALUES (?, ?)");
            $stmt->execute([$listing_id, $buyer_id]);
            $message = "<div style='color:#1A7A3C; text-align:center; padding:10px; background:#E8F5EE;'>Purchase Request sent successfully to the seller!</div>";
        }
    }
}

// Build the query dynamically based on filters
try {
$where_clauses = ["pl.status = 'Available'"];
$params = [];

if (isset($_GET['min_price']) && is_numeric($_GET['min_price']) && $_GET['min_price'] > 0) {
    $where_clauses[] = "pl.asking_price >= :min_price";
    $params[':min_price'] = $_GET['min_price'];
}
if (isset($_GET['max_price']) && is_numeric($_GET['max_price']) && $_GET['max_price'] > 0) {
    $where_clauses[] = "pl.asking_price <= :max_price";
    $params[':max_price'] = $_GET['max_price'];
}
if (isset($_GET['area_type']) && !empty($_GET['area_type'])) {
    $where_clauses[] = "pl.area_type = :area_type";
    $params[':area_type'] = $_GET['area_type'];
}

$where_sql = implode(' AND ', $where_clauses);

$stmt = $pdo->prepare("
    SELECT pl.*, c.full_name as seller_name, c.mobile_number as seller_mobile 
    FROM property_listings pl 
    JOIN citizens c ON pl.seller_id = c.id 
    WHERE $where_sql
    ORDER BY pl.created_at DESC
");
$stmt->execute($params);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading marketplace: " . $e->getMessage();
    $listings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>e-Bhumi Marketplace</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --saffron: #E8621A;
        --saffron-light: #F5894A;
        --navy: #0F1F3D;
        --cream: #FAF7F2;
        --border: #E2DAD0;
        --white: #FFFFFF;
        --green: #1A7A3C;
        --warm-gray: #8C8278;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--navy); }
    
    /* NAV */
    nav{display:flex;align-items:center;justify-content:space-between;padding:1rem 2.5rem;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
    .nav-brand{font-size:1.1rem;color:var(--navy);font-weight:700;text-decoration:none;}
    .nav-links{display:flex;gap:1.5rem;align-items:center;}
    .nav-links a{font-size:.875rem;color:var(--navy);text-decoration:none;font-weight:500;}
    
    .header { background: var(--navy); color: var(--white); padding: 4rem 2rem; text-align: center; }
    .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
    .header p { color: rgba(255,255,255,0.7); font-size: 1.1rem; }
    
    .container { max-width: 1200px; margin: 3rem auto; padding: 0 2rem; }
    
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem; }
    
    .card { background: var(--white); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.3s; display: flex; flex-direction: column; }
    .card:hover { transform: translateY(-5px); border-color: var(--saffron); }
    
    .card-img { height: 160px; background: #e0d8cd; position: relative; display: flex; align-items: center; justify-content: center; color: var(--warm-gray); font-size: 0.9rem; }
    .area-badge { position: absolute; top: 1rem; right: 1rem; background: var(--white); color: var(--navy); font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.8rem; border-radius: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .area-badge.highway { color: var(--saffron); border: 1px solid var(--saffron); }
    
    .card-body { padding: 1.5rem; flex: 1; }
    .price { font-size: 1.5rem; font-weight: 700; color: var(--green); margin-bottom: 0.5rem; }
    .title { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
    .desc { font-size: 0.85rem; color: var(--warm-gray); margin-bottom: 1rem; line-height: 1.5; }
    
    .meta { display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.85rem; margin-bottom: 1.5rem; border-top: 1px solid var(--border); padding-top: 1rem; }
    .meta div { display: flex; justify-content: space-between; }
    .meta span.label { color: var(--warm-gray); }
    .meta span.value { font-weight: 600; }
    
    .card-footer { padding: 1rem 1.5rem; background: var(--cream); border-top: 1px solid var(--border); display: flex; gap: 10px; }
    .btn { flex: 1; text-align: center; padding: 0.6rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .btn-primary { background: var(--navy); color: var(--white); }
    .btn-primary:hover { background: var(--saffron); }
    .btn-outline { border: 1px solid var(--saffron); color: var(--saffron); }
    .btn-outline:hover { background: var(--saffron-pale); }
</style>
</head>
<body>

<nav>
  <a href="index.php" class="nav-brand">e-Bhumi Marketplace</a>
  <div class="nav-links">
    <?php if (isset($_SESSION['citizen_logged_in'])): ?>
        <a href="sell_property.php" style="color: var(--saffron); font-weight: 700;">+ Sell Property</a>
    <?php endif; ?>
    <a href="index.php">Search Engine</a>
    <?php if (isset($_SESSION['citizen_logged_in'])): ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login / Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="header">
    <h1>Buy & Sell Verified Land</h1>
    <p>Browse properties listed by owners. View layout documents and trust scores.</p>
</div>

<div class="container">
    <div class="header-section">
        <h1>Verified Lands for Sale</h1>
        <p>Browse through authenticated properties listed directly by owners. All listings are subject to e-Bhumi 7/12 verification.</p>
    </div>

    <!-- Filter Section -->
    <div style="background: var(--white); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; border: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Area Type</label>
                <select name="area_type" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border);">
                    <option value="">All Area Types</option>
                    <option value="Highway Touch" <?php echo (isset($_GET['area_type']) && $_GET['area_type']=='Highway Touch') ? 'selected' : ''; ?>>Highway Touch</option>
                    <option value="Roadside" <?php echo (isset($_GET['area_type']) && $_GET['area_type']=='Roadside') ? 'selected' : ''; ?>>Roadside</option>
                    <option value="Village Interior" <?php echo (isset($_GET['area_type']) && $_GET['area_type']=='Village Interior') ? 'selected' : ''; ?>>Village Interior</option>
                    <option value="Agricultural" <?php echo (isset($_GET['area_type']) && $_GET['area_type']=='Agricultural') ? 'selected' : ''; ?>>Agricultural Zone</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Min Price (₹)</label>
                <input type="number" name="min_price" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>" placeholder="e.g. 100000" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border);">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px;">Max Price (₹)</label>
                <input type="number" name="max_price" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>" placeholder="e.g. 5000000" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border);">
            </div>
            <div>
                <button type="submit" style="padding: 10px 20px; background: var(--navy); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Apply Filters</button>
                <a href="marketplace.php" style="display: inline-block; padding: 10px 20px; background: #F5F5F5; color: var(--navy); text-decoration: none; border-radius: 6px; font-weight: 600; margin-left: 10px;">Clear</a>
            </div>
        </form>
    </div>

    <?php if (!empty($message)) echo $message; ?>
    <?php if (isset($error)): ?>
        <div style="background:#FEF0EE; color:#C0321A; padding:1rem; border-radius:8px; text-align:center;"><?php echo $error; ?></div>
    <?php elseif (empty($listings)): ?>
        <div style="text-align:center; padding: 4rem; color: var(--warm-gray);">
            <h2>No properties listed yet!</h2>
            <p style="margin-top:0.5rem;">Be the first to list a property on the e-Bhumi marketplace.</p>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($listings as $listing): ?>
                <?php 
                    $badgeClass = ($listing['area_type'] == 'Highway Touch') ? 'highway' : ''; 
                    $formattedPrice = "₹" . number_format($listing['asking_price']);
                ?>
                <div class="card">
                    <div class="card-img">
                        <?php if ($listing['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($listing['image_path']); ?>" alt="Property Image" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background: linear-gradient(135deg, #e0d8cd, #f5f0ea); display:flex; align-items:center; justify-content:center;">
                                <span>No Image Available</span>
                            </div>
                        <?php endif; ?>
                        <div class="area-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($listing['area_type']); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="price"><?php echo $formattedPrice; ?></div>
                        <div class="title">Land in <?php echo htmlspecialchars($listing['village']); ?></div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--navy); margin-bottom: 5px;">
                            <?php echo htmlspecialchars($listing['total_area']); ?>
                        </div>
                        <div class="desc"><?php echo htmlspecialchars(substr($listing['description'], 0, 100)) . '...'; ?></div>
                        
                        <?php if(!empty($listing['address_details'])): ?>
                            <div style="font-size: 0.75rem; color: #8C8278; margin-bottom: 10px; font-style: italic;">
                                📍 <?php echo htmlspecialchars($listing['address_details']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta">
                            <div><span class="label">Survey No:</span> <span class="value"><?php echo htmlspecialchars($listing['survey_number']); ?></span></div>
                            <div><span class="label">Seller:</span> <span class="value"><?php echo htmlspecialchars($listing['seller_name']); ?></span></div>
                            <div><span class="label">Contact:</span> <span class="value"><?php echo htmlspecialchars($listing['seller_mobile']); ?></span></div>
                        </div>
                    </div>
                    <div class="card-footer" style="flex-wrap: wrap; justify-content: center;">
                        <?php if ($listing['layout_pdf_path']): ?>
                            <a href="<?php echo htmlspecialchars($listing['layout_pdf_path']); ?>" target="_blank" class="btn btn-outline" style="width: 100%; margin-bottom: 5px;">View Layout (PDF)</a>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['citizen_logged_in']) && $_SESSION['citizen_id'] != $listing['seller_id']): ?>
                            <form action="marketplace.php" method="POST" style="width: 100%;">
                                <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                <button type="submit" name="request_purchase" class="btn btn-primary" style="width: 100%; cursor: pointer; border: none;">Send Purchase Request</button>
                            </form>
                        <?php elseif (!isset($_SESSION['citizen_logged_in'])): ?>
                            <a href="login.php" class="btn btn-primary" style="width: 100%;">Login to Buy</a>
                        <?php else: ?>
                            <button class="btn" style="width: 100%; background: #ccc; cursor: not-allowed;" disabled>Your Listing</button>
                        <?php endif; ?>
                        
                        <!-- EMI Button -->
                        <button class="btn btn-outline" style="width: 100%; margin-top: 5px; cursor: pointer;" onclick="openEMIModal(<?php echo $listing['asking_price']; ?>)">Calculate EMI</button>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- EMI Modal -->
<div id="emiModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--white); padding:2rem; border-radius:12px; max-width:400px; width:90%; position:relative;">
        <span onclick="document.getElementById('emiModal').style.display='none'" style="position:absolute; top:15px; right:20px; font-size:1.5rem; cursor:pointer; color:var(--warm-gray);">&times;</span>
        <h2 style="margin-bottom:1rem; color:var(--navy); font-size:1.5rem;">Smart EMI Calculator</h2>
        
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:5px;">Property Price (₹)</label>
            <input type="number" id="emiPrice" readonly style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; background:#F5F5F5;">
        </div>
        
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:5px;">Downpayment (₹)</label>
            <input type="number" id="emiDownpayment" value="0" oninput="calculateEMI()" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
        </div>
        
        <div style="display:flex; gap:1rem; margin-bottom:1.5rem;">
            <div style="flex:1;">
                <label style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:5px;">Interest Rate (%)</label>
                <input type="number" id="emiRate" value="8.5" step="0.1" oninput="calculateEMI()" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
            </div>
            <div style="flex:1;">
                <label style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:5px;">Tenure (Years)</label>
                <input type="number" id="emiYears" value="15" oninput="calculateEMI()" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px;">
            </div>
        </div>
        
        <div style="background:#E8F5EE; border:1px solid var(--green); padding:1.5rem; border-radius:8px; text-align:center;">
            <div style="color:var(--green); font-weight:600; font-size:0.9rem; margin-bottom:5px;">Estimated Monthly EMI</div>
            <div id="emiResult" style="font-size:2rem; font-weight:700; color:var(--navy);">₹0</div>
        </div>
    </div>
</div>

<script>
function openEMIModal(price) {
    document.getElementById('emiPrice').value = price;
    // Set default downpayment to 20%
    document.getElementById('emiDownpayment').value = Math.round(price * 0.2);
    document.getElementById('emiModal').style.display = 'flex';
    calculateEMI();
}

function calculateEMI() {
    let p = parseFloat(document.getElementById('emiPrice').value) - parseFloat(document.getElementById('emiDownpayment').value);
    let r = parseFloat(document.getElementById('emiRate').value) / 12 / 100;
    let n = parseFloat(document.getElementById('emiYears').value) * 12;
    
    if (p <= 0 || isNaN(p)) {
        document.getElementById('emiResult').textContent = "₹0";
        return;
    }
    
    // EMI = P * r * (1 + r)^n / ((1 + r)^n - 1)
    let emi = p * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
    
    if (isNaN(emi) || !isFinite(emi)) {
        document.getElementById('emiResult').textContent = "₹0";
    } else {
        document.getElementById('emiResult').textContent = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(emi);
    }
}
</script>

</body>
</html>
