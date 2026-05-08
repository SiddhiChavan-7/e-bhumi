<?php 
session_start(); 
require_once 'config.php';

// Fetch real-time dynamic stats
try {
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM properties");
    $total_properties = $stmt1->fetchColumn();

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM property_listings WHERE status = 'Available'");
    $active_listings = $stmt2->fetchColumn();

    $stmt3 = $pdo->query("SELECT COUNT(*) FROM citizens");
    $total_citizens = $stmt3->fetchColumn();
} catch (Exception $e) {
    $total_properties = 0;
    $active_listings = 0;
    $total_citizens = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>e-BhumiAbhilekhan – Smart Land Verification</title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Marathi&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --saffron:#E8621A;--saffron-light:#F5894A;--saffron-pale:#FDF0E8;
  --green:#1A7A3C;--green-light:#2A9E52;--green-pale:#E8F5EE;
  --navy:#0F1F3D;--navy-mid:#1B3260;--cream:#FAF7F2;
  --warm-gray:#8C8278;--border:#E2DAD0;--white:#FFFFFF;
  --score-high:#1A7A3C;--score-mid:#D4820A;--score-low:#C0321A;
}
html{scroll-behavior:smooth;}
body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--navy);min-height:100vh;overflow-x:hidden;cursor:none;}

/* CUSTOM CURSOR */
#cursor{
  position:fixed;z-index:99999;pointer-events:none;
  width:12px;height:12px;background:var(--saffron);border-radius:50%;
  transform:translate(-50%,-50%);transition:transform .1s,background .2s;
  mix-blend-mode:normal;
}
#cursor-ring{
  position:fixed;z-index:99998;pointer-events:none;
  width:36px;height:36px;border:1.5px solid rgba(232,98,26,.5);
  border-radius:50%;transform:translate(-50%,-50%);
  transition:width .3s,height .3s,border-color .3s;
}
#cursor-glow{
  pointer-events:none;position:fixed;z-index:9990;
  width:600px;height:600px;border-radius:50%;
  background:radial-gradient(circle,rgba(232,98,26,.10) 0%,rgba(232,98,26,.03) 40%,transparent 70%);
  transform:translate(-50%,-50%);will-change:transform;
}
body:has(a:hover) #cursor,
body:has(button:hover) #cursor{transform:translate(-50%,-50%) scale(2.5);background:var(--saffron-light);}
body:has(a:hover) #cursor-ring,
body:has(button:hover) #cursor-ring{width:54px;height:54px;border-color:rgba(232,98,26,.8);}

/* NAV */
nav{display:flex;align-items:center;justify-content:space-between;padding:1rem 2.5rem;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.nav-logo{display:flex;align-items:center;gap:10px;cursor:pointer;}
.nav-logo-icon{width:36px;height:36px;background:var(--saffron);border-radius:8px;display:flex;align-items:center;justify-content:center;transition:transform .4s cubic-bezier(.34,1.56,.64,1),box-shadow .3s;}
.nav-logo-icon:hover{transform:rotate(-12deg) scale(1.12);box-shadow:0 6px 20px rgba(232,98,26,.4);}
.nav-logo-icon svg{width:20px;height:20px;fill:white;}
.nav-brand{font-family:'Tiro Devanagari Marathi',serif;font-size:1.1rem;color:var(--navy);line-height:1.1;}
.nav-brand span{display:block;font-family:'DM Sans',sans-serif;font-size:.68rem;font-weight:400;color:var(--warm-gray);letter-spacing:.04em;}
.nav-links{display:flex;gap:1.5rem;align-items:center;}
.nav-links a{font-size:.875rem;color:var(--warm-gray);text-decoration:none;font-weight:400;transition:color .2s;position:relative;cursor:pointer;}
.nav-links a::after{content:'';position:absolute;bottom:-2px;left:0;width:0;height:1.5px;background:var(--saffron);transition:width .3s;}
.nav-links a:hover{color:var(--navy);}
.nav-links a:hover::after{width:100%;}
.nav-btn{background:var(--saffron);color:white;border:none;border-radius:8px;padding:.5rem 1.25rem;font-size:.875rem;font-family:'DM Sans',sans-serif;font-weight:500;cursor:pointer;transition:background .2s,transform .2s,box-shadow .2s;text-decoration:none;display:inline-block;}
.nav-btn:hover{background:var(--saffron-light);transform:translateY(-2px);box-shadow:0 6px 18px rgba(232,98,26,.35);}
.nav-logout{background:#FEF0EE;color:#C0321A;border:1px solid #C0321A;border-radius:8px;padding:.4rem 1rem;font-size:.8rem;font-family:'DM Sans',sans-serif;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:0.3s;}
.nav-logout:hover{background:#C0321A;color:white;}
.nav-user{font-size:.875rem;color:var(--navy);font-weight:600;}

/* HERO */
.hero{position:relative;padding:5rem 2.5rem 4rem;background:var(--navy);overflow:hidden;}
.hero-pattern{position:absolute;inset:0;background-image:repeating-linear-gradient(0deg,transparent,transparent 39px,rgba(255,255,255,.025) 39px,rgba(255,255,255,.025) 40px),repeating-linear-gradient(90deg,transparent,transparent 39px,rgba(255,255,255,.025) 39px,rgba(255,255,255,.025) 40px);pointer-events:none;}
.orb{position:absolute;border-radius:50%;pointer-events:none;animation:orbFloat 8s ease-in-out infinite;}
.orb1{width:380px;height:380px;top:-100px;right:-80px;background:radial-gradient(circle,rgba(232,98,26,.22) 0%,transparent 70%);animation-delay:0s;}
.orb2{width:260px;height:260px;bottom:-60px;left:-50px;background:radial-gradient(circle,rgba(26,122,60,.2) 0%,transparent 70%);animation-delay:-3s;}
.orb3{width:180px;height:180px;top:35%;left:18%;background:radial-gradient(circle,rgba(232,98,26,.1) 0%,transparent 70%);animation-delay:-5s;}
@keyframes orbFloat{0%,100%{transform:translateY(0) scale(1);}50%{transform:translateY(-22px) scale(1.06);}}
.hero-inner{max-width:760px;margin:0 auto;text-align:center;position:relative;z-index:1;}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(232,98,26,.15);border:1px solid rgba(232,98,26,.3);color:var(--saffron-light);padding:.35rem 1rem;border-radius:100px;font-size:.78rem;font-weight:500;letter-spacing:.05em;margin-bottom:1.5rem;animation:fadeDown .6s ease both;}
.hero-badge::before{content:'';width:6px;height:6px;background:var(--saffron-light);border-radius:50%;animation:pulse 2s ease infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(.7);}}
.hero h1{font-family:'Tiro Devanagari Marathi',serif;font-size:clamp(2rem,5vw,3.2rem);color:var(--white);line-height:1.15;margin-bottom:1rem;animation:fadeDown .6s .1s ease both;}
.hero h1 em{font-style:normal;color:var(--saffron-light);}
.hero p{font-size:1rem;color:rgba(255,255,255,.6);line-height:1.7;max-width:520px;margin:0 auto 2.5rem;font-weight:300;animation:fadeDown .6s .2s ease both;}

/* SEARCH CARD */
.search-card{background:var(--white);border-radius:16px;padding:2rem;max-width:680px;margin:0 auto;box-shadow:0 4px 40px rgba(0,0,0,.3);animation:fadeUp .6s .3s ease both;position:relative;overflow:hidden;}
.search-card::before{content:'';position:absolute;inset:0;background:radial-gradient(500px circle at var(--mx,50%) var(--my,50%),rgba(232,98,26,.07),transparent 60%);pointer-events:none;}
.search-tabs{display:flex;gap:4px;background:var(--cream);border-radius:10px;padding:4px;margin-bottom:1.5rem;}
.search-tab{flex:1;padding:.55rem;border-radius:8px;border:none;background:transparent;font-family:'DM Sans',sans-serif;font-size:.85rem;color:var(--warm-gray);cursor:pointer;transition:all .25s;font-weight:400;}
.search-tab.active{background:var(--white);color:var(--navy);font-weight:500;box-shadow:0 1px 4px rgba(0,0,0,.08);}
.search-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1.25rem;}
.search-fields.single{grid-template-columns:1fr;}
.field-group{display:flex;flex-direction:column;gap:5px;}
.field-group label{font-size:.78rem;font-weight:500;color:var(--warm-gray);letter-spacing:.03em;}
.field-group input,.field-group select{padding:.65rem .9rem;border:1.5px solid var(--border);border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--navy);background:var(--cream);outline:none;transition:border-color .2s,background .2s,box-shadow .2s;}
.field-group input:focus,.field-group select:focus{border-color:var(--saffron);background:var(--white);box-shadow:0 0 0 3px rgba(232,98,26,.1);}
.field-group input::placeholder{color:#BDB5AA;}
.search-btn{width:100%;padding:.85rem;background:var(--saffron);color:white;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;cursor:pointer;transition:background .2s,transform .15s,box-shadow .2s;display:flex;align-items:center;justify-content:center;gap:8px;position:relative;overflow:hidden;}
.search-btn::after{content:'';position:absolute;inset:0;background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.18) 50%,transparent 70%);transform:translateX(-100%);transition:transform .55s;}
.search-btn:hover{background:var(--saffron-light);box-shadow:0 8px 24px rgba(232,98,26,.4);}
.search-btn:hover::after{transform:translateX(100%);}
.search-btn:active{transform:scale(.99);}
.search-btn svg{width:18px;height:18px;fill:white;}

/* STATS */
.stats-bar{display:flex;justify-content:center;gap:3rem;padding:1.75rem 2.5rem;background:var(--white);border-bottom:1px solid var(--border);}
.stat-item{text-align:center;cursor:default;}
.stat-num{font-size:1.4rem;font-weight:600;color:var(--navy);line-height:1;transition:color .3s,transform .3s;display:inline-block;}
.stat-item:hover .stat-num{color:var(--saffron);transform:scale(1.1);}
.stat-label{font-size:.75rem;color:var(--warm-gray);margin-top:3px;letter-spacing:.03em;}

/* MAP */
.map-section{padding:4rem 2.5rem;background:var(--white);}
.map-section .section-header{text-align:center;margin-bottom:2.5rem;}
.section-eyebrow{font-size:.75rem;font-weight:600;letter-spacing:.1em;color:var(--saffron);text-transform:uppercase;margin-bottom:.5rem;}
.section-title{font-family:'Tiro Devanagari Marathi',serif;font-size:clamp(1.5rem,3vw,2rem);color:var(--navy);}
.map-wrapper{max-width:960px;margin:0 auto;display:grid;grid-template-columns:1fr 300px;gap:2rem;align-items:start;}
.map-container{background:var(--cream);border-radius:16px;border:1px solid var(--border);padding:1.5rem;position:relative;overflow:hidden;}
.map-container svg{width:100%;height:auto;display:block;}
.district-path{fill:#C8E8D8;stroke:#fff;stroke-width:1.2;cursor:pointer;transition:fill .2s,filter .25s;}
.district-path:hover{fill:#F5894A;filter:drop-shadow(0 2px 10px rgba(232,98,26,.4));}
.district-path.active{fill:var(--saffron);}
.map-tooltip{position:absolute;background:var(--navy);color:white;padding:.35rem .75rem;border-radius:8px;font-size:.75rem;font-weight:500;pointer-events:none;opacity:0;transition:opacity .15s;white-space:nowrap;transform:translate(-50%,-130%);z-index:20;}
.map-tooltip.show{opacity:1;}
.map-tooltip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:var(--navy);}
.map-info{display:flex;flex-direction:column;gap:1rem;}
.map-info-card{background:var(--cream);border:1px solid var(--border);border-radius:12px;padding:1.25rem;transition:border-color .3s,transform .3s;}
.map-info-card:hover{border-color:var(--saffron);transform:translateX(3px);}
.map-info-card h4{font-size:.88rem;font-weight:600;color:var(--navy);margin-bottom:.5rem;}
.map-info-card p{font-size:.8rem;color:var(--warm-gray);line-height:1.6;}
.district-detail{background:var(--white);border:1.5px solid var(--saffron);border-radius:12px;padding:1.25rem;display:none;animation:fadeUp .3s ease;}
.district-detail.visible{display:block;}
.district-detail h4{font-size:.95rem;font-weight:600;color:var(--navy);margin-bottom:.75rem;display:flex;align-items:center;gap:6px;}
.district-detail h4::before{content:'';width:8px;height:8px;background:var(--saffron);border-radius:50%;display:inline-block;}
.d-stat{display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--border);font-size:.82rem;}
.d-stat:last-child{border:none;}
.d-stat label{color:var(--warm-gray);}
.d-stat span{font-weight:600;color:var(--navy);}
.d-search-btn{width:100%;margin-top:1rem;padding:.6rem;background:var(--saffron);color:white;border:none;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;transition:background .2s;}
.d-search-btn:hover{background:var(--saffron-light);}

/* HOW IT WORKS */
.how-section{padding:4rem 2.5rem;background:var(--cream);}
.how-section .section-header{text-align:center;margin-bottom:3rem;}
.steps-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;max-width:900px;margin:0 auto;}
.step-card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:1.5rem;transition:border-color .3s,transform .3s,box-shadow .3s;}
.step-card:hover{border-color:var(--saffron);transform:translateY(-6px);box-shadow:0 14px 32px rgba(232,98,26,.13);}
.step-num{width:36px;height:36px;border-radius:10px;background:var(--saffron-pale);color:var(--saffron);font-weight:700;font-size:.9rem;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;transition:background .3s,color .3s,transform .3s;}
.step-card:hover .step-num{background:var(--saffron);color:white;transform:rotate(8deg) scale(1.1);}
.step-card h3{font-size:.95rem;font-weight:600;margin-bottom:.4rem;color:var(--navy);}
.step-card p{font-size:.82rem;color:var(--warm-gray);line-height:1.6;}

/* RESULT PREVIEW */
.result-section{background:var(--navy);padding:4rem 2.5rem;}
.result-section .section-header{text-align:center;margin-bottom:2.5rem;}
.result-section .section-title{color:var(--white);}
.result-section .section-eyebrow{color:var(--saffron-light);}
.result-card{max-width:700px;margin:0 auto;background:var(--white);border-radius:16px;overflow:hidden;border:1px solid var(--border);}
.result-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);background:var(--cream);}
.result-survey{font-size:.78rem;color:var(--warm-gray);font-weight:500;letter-spacing:.05em;}
.result-name{font-size:1.05rem;font-weight:600;color:var(--navy);}
.trust-badge{text-align:center;background:var(--green-pale);border:1.5px solid var(--green);border-radius:12px;padding:.6rem 1.1rem;min-width:90px;}
.trust-score{font-size:1.6rem;font-weight:700;color:var(--green);line-height:1;}
.trust-label{font-size:.68rem;color:var(--green);font-weight:500;letter-spacing:.05em;}
.result-body{padding:1.25rem 1.5rem;}
.result-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;}
.result-field label{font-size:.72rem;font-weight:500;color:var(--warm-gray);letter-spacing:.04em;display:block;margin-bottom:2px;}
.result-field span{font-size:.9rem;color:var(--navy);font-weight:500;}
.doc-badge{display:inline-flex;align-items:center;gap:5px;background:var(--green-pale);color:var(--green);border:1px solid var(--green);border-radius:6px;padding:.25rem .7rem;font-size:.75rem;font-weight:600;}
.risk-bar{background:var(--cream);border-radius:10px;padding:1rem 1.25rem;border:1px solid var(--border);}
.risk-bar-label{display:flex;justify-content:space-between;font-size:.78rem;font-weight:500;color:var(--warm-gray);margin-bottom:.5rem;}
.risk-track{height:8px;background:#E2DAD0;border-radius:100px;overflow:hidden;}
.risk-fill{height:100%;border-radius:100px;background:linear-gradient(90deg,var(--score-low),var(--score-mid) 50%,var(--score-high));transition:width 1s ease;}

/* LIVE RESULT */
.live-result{max-width:680px;margin:1.5rem auto 0;display:none;animation:fadeUp .4s ease both;}
.live-result.visible{display:block;}
.live-result-inner{background:var(--white);border-radius:14px;border:1px solid var(--border);overflow:hidden;}
.lr-top{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:var(--cream);border-bottom:1px solid var(--border);}
.lr-title{font-weight:600;color:var(--navy);font-size:.95rem;}
.lr-sub{font-size:.78rem;color:var(--warm-gray);}
.lr-body{padding:1.25rem;}
.lr-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;margin-bottom:1rem;}
.lr-field label{font-size:.7rem;color:var(--warm-gray);font-weight:500;letter-spacing:.03em;display:block;}
.lr-field span{font-size:.88rem;color:var(--navy);font-weight:500;}
.lr-score{display:flex;align-items:center;gap:1rem;padding:.875rem 1rem;border-radius:10px;border:1.5px solid;}
.lr-score.high{background:var(--green-pale);border-color:var(--green);}
.lr-score.mid{background:#FFF8EC;border-color:#D4820A;}
.lr-score.low{background:#FEF0EE;border-color:var(--score-low);}
.lr-score-num{font-size:2rem;font-weight:700;line-height:1;}
.lr-score.high .lr-score-num{color:var(--green);}
.lr-score.mid .lr-score-num{color:#D4820A;}
.lr-score.low .lr-score-num{color:var(--score-low);}
.lr-score-info strong{font-size:.88rem;font-weight:600;display:block;}
.lr-score-info p{font-size:.78rem;color:var(--warm-gray);margin-top:2px;}
.lr-score.high .lr-score-info strong{color:var(--green);}
.lr-score.mid .lr-score-info strong{color:#D4820A;}
.lr-score.low .lr-score-info strong{color:var(--score-low);}

footer{background:var(--navy);padding:2rem 2.5rem;text-align:center;border-top:1px solid rgba(255,255,255,.06);}
footer p{font-size:.8rem;color:rgba(255,255,255,.35);}
footer span{color:var(--saffron-light);}

@keyframes fadeDown{from{opacity:0;transform:translateY(-18px);}to{opacity:1;transform:none;}}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:none;}}
.reveal{opacity:0;transform:translateY(28px);transition:opacity .65s ease,transform .65s ease;}
.reveal.visible{opacity:1;transform:none;}

@media print {
  body * { visibility: hidden; }
  .live-result, .live-result * { visibility: visible; }
  .live-result { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; margin:0; padding:0; }
  .live-result-inner { border: none !important; }
  .print-btn { display: none !important; }
}

@media(max-width:700px){
  body{cursor:auto;}#cursor,#cursor-ring{display:none;}
  nav{padding:1rem 1.25rem;}.nav-links{display:none;}
  .hero{padding:3rem 1.25rem 3rem;}.search-fields{grid-template-columns:1fr;}
  .stats-bar{gap:1.5rem;padding:1.25rem;flex-wrap:wrap;}
  .map-wrapper{grid-template-columns:1fr;}
  .lr-grid{grid-template-columns:1fr 1fr;}
  .result-grid{grid-template-columns:1fr 1fr;}
}
</style>
</head>
<body>

<!-- CUSTOM CURSOR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>
<div id="cursor-glow"></div>

<!-- NAV -->
<nav>
  <div class="nav-logo">
    <div class="nav-logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
    </div>
    <div class="nav-brand">e-BhumiAbhilekhan<span>Smart Land Verification System</span></div>
  </div>
  <div class="nav-links">
    <a href="marketplace.php" style="color: var(--saffron); font-weight: 600;">Marketplace</a>
    <?php if (isset($_SESSION['citizen_logged_in']) && $_SESSION['citizen_logged_in'] === true): ?>
        <a href="user_dashboard.php">My Dashboard</a>
        <a href="sell_property.php">Sell Property</a>
    <?php endif; ?>
    <a href="#">How it works</a>
    <a href="#map-section">Districts</a>
    <a href="#">About</a>
    <?php if (isset($_SESSION['citizen_logged_in']) && $_SESSION['citizen_logged_in'] === true): ?>
        <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['citizen_name']); ?></span>
        <a href="logout.php" class="nav-logout">Logout</a>
    <?php else: ?>
        <a href="login.php" class="nav-btn">Portal Login</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-pattern"></div>
  <div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div>
  <div class="hero-inner">
    <div class="hero-badge">Maharashtra Land Records Portal</div>
    <h1>Verify Land Ownership<br/>with <em>Confidence</em></h1>
    <p>Search any property by survey number, village, or owner name. Get instant ownership history, encumbrance details, and a trust score backed by 7/12 records.</p>

    <div class="search-card" id="search-card">
      <div class="search-tabs">
        <button class="search-tab active" onclick="switchTab('survey',this)">Survey Number</button>
        <button class="search-tab" onclick="switchTab('village',this)">Village / Taluka</button>
        <button class="search-tab" onclick="switchTab('owner',this)">Owner Name</button>
      </div>
      <div id="tab-survey">
        <div class="search-fields">
          <div class="field-group"><label>SURVEY NUMBER</label><input type="text" id="inp-survey" placeholder="e.g. SRV-001" autocomplete="off"/></div>
          <div class="field-group"><label>DISTRICT</label>
            <select id="inp-district">
              <option value="">Select district</option>
              <option>Kolhapur</option><option>Sangli</option><option>Pune</option>
              <option>Nashik</option><option>Aurangabad</option><option>Nagpur</option>
              <option>Satara</option><option>Solapur</option><option>Latur</option>
              <option>Nanded</option><option>Amravati</option><option>Jalgaon</option>
            </select>
          </div>
        </div>
      </div>
      <div id="tab-village" style="display:none">
        <div class="search-fields">
          <div class="field-group"><label>VILLAGE NAME</label><input type="text" id="inp-village" placeholder="e.g. Shirol"/></div>
          <div class="field-group"><label>TALUKA</label><input type="text" id="inp-taluka" placeholder="e.g. Hatkanangle"/></div>
        </div>
      </div>
      <div id="tab-owner" style="display:none">
        <div class="search-fields single">
          <div class="field-group"><label>OWNER FULL NAME</label><input type="text" id="inp-owner" placeholder="e.g. Ramesh Patil"/></div>
        </div>
      </div>
      <button class="search-btn" onclick="doSearch()">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        Search Property
      </button>
      <div class="live-result" id="live-result"><div class="live-result-inner" id="result-content"></div></div>

      <!-- RECENT SEARCHES -->
      <div id="recent-searches-container" style="display:none; margin-top:2rem; text-align:left;">
        <h4 style="font-size:0.8rem; color:var(--warm-gray); letter-spacing:0.05em; margin-bottom:1rem; text-transform:uppercase;">Recent Searches</h4>
        <div id="recent-searches-list" style="display:flex; gap:10px; flex-wrap:wrap;"></div>
      </div>
    </div>
  </div>
</section>

<!-- DYNAMIC LIVE STATS -->
<div class="stats-bar">
  <a href="marketplace.php" class="stat-item" style="text-decoration:none;">
      <div class="stat-num" style="color: var(--saffron);"><?php echo $active_listings; ?></div>
      <div class="stat-label">Lands for Sale</div>
  </a>
  <div class="stat-item">
      <div class="stat-num"><?php echo $total_properties; ?></div>
      <div class="stat-label">Verified Records</div>
  </div>
  <div class="stat-item">
      <div class="stat-num"><?php echo $total_citizens; ?></div>
      <div class="stat-label">Registered Citizens</div>
  </div>
  <a href="#map-section" class="stat-item" style="text-decoration:none;">
      <div class="stat-num">36</div>
      <div class="stat-label">Districts Supported</div>
  </a>
</div>

<!-- MAP -->
<section class="map-section reveal" id="map-section">
  <div class="section-header">
    <div class="section-eyebrow">Real-Time Location</div>
    <div class="section-title">Property Geolocation Map</div>
  </div>
  <div class="map-wrapper" style="grid-template-columns: 1fr; max-width: 1000px;">
    <div class="map-container" style="padding: 0; border-radius: 16px; overflow: hidden; height: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
      <div id="real-map" style="width: 100%; height: 100%; z-index: 1;"></div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section reveal">
  <div class="section-header">
    <div class="section-eyebrow">Simple Process</div>
    <div class="section-title">How e-BhumiAbhilekhan Works</div>
  </div>
  <div class="steps-grid">
    <div class="step-card"><div class="step-num">1</div><h3>Enter Property Details</h3><p>Search by survey number, village name, taluka, or current owner's name.</p></div>
    <div class="step-card"><div class="step-num">2</div><h3>View Ownership History</h3><p>See the complete title chain — every past owner, transfer date, and type.</p></div>
    <div class="step-card"><div class="step-num">3</div><h3>Check 7/12 Document</h3><p>Download the official Satbara Utara or verify directly on MahaBhumi portal.</p></div>
    <div class="step-card"><div class="step-num">4</div><h3>Get Trust Score</h3><p>Our risk engine checks loans, disputes, and documents to give a 0–10 score.</p></div>
  </div>
</section>

<!-- RESULT PREVIEW -->
<section class="result-section reveal">
  <div class="section-header">
    <div class="section-eyebrow">Sample Result</div>
    <div class="section-title">What a Property Report Looks Like</div>
  </div>
  <div class="result-card">
    <div class="result-header">
      <div><div class="result-survey">SURVEY NO. SRV-001 · SHIROL, KOLHAPUR</div><div class="result-name">Agricultural Plot — 2.50 Acres</div></div>
      <div class="trust-badge"><div class="trust-score">9/10</div><div class="trust-label">SAFE</div></div>
    </div>
    <div class="result-body">
      <div class="result-grid">
        <div class="result-field"><label>CURRENT OWNER</label><span>Ramesh Dattatray Patil</span></div>
        <div class="result-field"><label>LAND TYPE</label><span>Agricultural</span></div>
        <div class="result-field"><label>VILLAGE / TALUKA</label><span>Shirol / Shirol</span></div>
        <div class="result-field"><label>ENCUMBRANCES</label><span style="color:var(--green);font-weight:600;">None found</span></div>
        <div class="result-field"><label>7/12 DOCUMENT</label><span><span class="doc-badge">✓ Verified</span></span></div>
        <div class="result-field"><label>LAST TRANSFER</label><span>March 2019 (Sale)</span></div>
      </div>
      <div class="risk-bar">
        <div class="risk-bar-label"><span>Trust Score</span><span style="color:var(--green);font-weight:600;">9 / 10 — Safe to proceed</span></div>
        <div class="risk-track"><div class="risk-fill" style="width:90%"></div></div>
      </div>
    </div>
  </div>
</section>

<footer>
  <p>e-BhumiAbhilekhan &nbsp;·&nbsp; Built with <span>PHP + MySQL</span> &nbsp;·&nbsp; Maharashtra Land Records Verification System</p>
</footer>

<script>
/* CURSOR */
const cur = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
const glow = document.getElementById('cursor-glow');
let mx=innerWidth/2,my=innerHeight/2,cx=mx,cy=my,rx=mx,ry=my,gx=mx,gy=my;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;});
(function loop(){
  cx+=(mx-cx)*.25; cy+=(my-cy)*.25;
  rx+=(mx-rx)*.12; ry+=(my-ry)*.12;
  gx+=(mx-gx)*.06; gy+=(my-gy)*.06;
  cur.style.left=mx+'px'; cur.style.top=my+'px';
  ring.style.left=rx+'px'; ring.style.top=ry+'px';
  glow.style.left=gx+'px'; glow.style.top=gy+'px';
  requestAnimationFrame(loop);
})();

/* SEARCH CARD inner glow */
const sc=document.getElementById('search-card');
sc.addEventListener('mousemove',e=>{
  const r=sc.getBoundingClientRect();
  sc.style.setProperty('--mx',(e.clientX-r.left)+'px');
  sc.style.setProperty('--my',(e.clientY-r.top)+'px');
});

/* SCROLL REVEAL */
const obs=new IntersectionObserver(en=>en.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible');}),{threshold:.1});
document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));

/* LEAFLET MAP */
const map = L.map('real-map').setView([19.7515, 75.7139], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
}).addTo(map);
let currentMarker = null;
let allMarkers = L.layerGroup().addTo(map);

// Fetch all marketplace properties to plot on the map
async function loadMarketplacePins() {
    try {
        const res = await fetch('api_map_listings.php');
        const json = await res.json();
        if (json.status === 'success') {
            json.data.forEach(item => {
                const lat = parseFloat(item.latitude);
                const lng = parseFloat(item.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    // Create a custom icon or standard marker
                    const marker = L.marker([lat, lng]);
                    
                    // Format price
                    const price = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(item.asking_price);
                    
                    const imgHtml = item.image_path 
                        ? `<img src="${item.image_path}" style="width:100%; height:100px; object-fit:cover; border-radius:6px; margin-bottom:8px;">` 
                        : `<div style="width:100%; height:100px; background:#e0d8cd; border-radius:6px; margin-bottom:8px; display:flex; align-items:center; justify-content:center; font-size:10px;">No Image</div>`;

                    const areaHtml = item.total_area ? `<span style="color:#0F1F3D; font-size:0.8rem; font-weight:600; display:block; margin-bottom:5px;">${item.total_area}</span>` : '';

                    const popupHtml = `
                        <div style="font-family:'DM Sans',sans-serif; text-align:center; min-width:180px;">
                            ${imgHtml}
                            <b style="color:#0F1F3D; font-size:1.1rem; display:block;">${price}</b>
                            ${areaHtml}
                            <span style="color:#8C8278; font-size:0.8rem; display:block; margin-bottom:5px;">Land in ${item.village}</span>
                            <span style="display:inline-block; font-size:0.7rem; background:#E8F5EE; color:#1A7A3C; padding:2px 6px; border-radius:4px; margin-bottom:8px;">${item.area_type}</span>
                            <br>
                            <a href="marketplace.php" style="display:block; width:100%; padding:6px; background:#E8621A; color:white; text-decoration:none; border-radius:6px; font-weight:600; font-size:0.85rem;">View Listing</a>
                        </div>
                    `;
                    marker.bindPopup(popupHtml);
                    allMarkers.addLayer(marker);
                }
            });
        }
    } catch (e) {
        console.error("Failed to load map pins");
    }
}

// Load pins initially
loadMarketplacePins();

function plotPropertyOnMap(coordsStr, popupHtml) {
  if (!coordsStr) return;
  const parts = coordsStr.split(',');
  if (parts.length === 2) {
    const lat = parseFloat(parts[0].trim());
    const lng = parseFloat(parts[1].trim());
    if (!isNaN(lat) && !isNaN(lng)) {
      if (currentMarker) map.removeLayer(currentMarker);
      
      // We change the icon color for search results to distinguish from marketplace pins
      const searchIcon = L.icon({
          iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
      });

      currentMarker = L.marker([lat, lng], {icon: searchIcon}).addTo(map)
        .bindPopup(popupHtml).openPopup();
      map.setView([lat, lng], 14, {animate: true, duration: 1.5});
      setTimeout(() => {
        document.getElementById('map-section').scrollIntoView({behavior: 'smooth', block: 'center'});
      }, 500);
    }
  }
}

/* RECENT SEARCHES */
function loadRecentSearches() {
  const container = document.getElementById('recent-searches-container');
  const list = document.getElementById('recent-searches-list');
  const history = JSON.parse(localStorage.getItem('ebhumi_recent') || '[]');
  if (history.length === 0) {
    container.style.display = 'none';
    return;
  }
  container.style.display = 'block';
  list.innerHTML = history.map(item => `
    <button onclick="document.getElementById('inp-survey').value='${item.survey}'; switchTab('survey', document.querySelector('.search-tab')); doSearch();" style="background:var(--white); border:1px solid var(--border); padding:6px 12px; border-radius:30px; font-size:0.8rem; font-weight:500; cursor:pointer; color:var(--navy); transition:all 0.2s; box-shadow:0 2px 5px rgba(0,0,0,0.02);">
      ${item.survey} - ${item.village}
    </button>
  `).join('');
}

function saveRecentSearch(p) {
  let history = JSON.parse(localStorage.getItem('ebhumi_recent') || '[]');
  history = history.filter(item => item.survey !== p.survey_number);
  history.unshift({ survey: p.survey_number, village: p.village });
  if (history.length > 5) history.pop();
  localStorage.setItem('ebhumi_recent', JSON.stringify(history));
  loadRecentSearches();
}

function downloadTxtReport(data) {
  const p = data.property;
  const r = data.risk;
  const ownerName = r.seven_twelve ? r.seven_twelve.owner_name : 'Owner not documented (No 7/12)';
  
  let txt = `=================================================\n`;
  txt += `        E-BHUMI ABHILEKHAN - TRUST REPORT        \n`;
  txt += `=================================================\n\n`;
  txt += `PROPERTY DETAILS\n`;
  txt += `----------------\n`;
  txt += `Survey Number: ${p.survey_number}\n`;
  txt += `Village:       ${p.village}\n`;
  txt += `Taluka:        ${p.taluka}\n`;
  txt += `District:      ${p.district}\n`;
  txt += `Area (Sq.M):   ${p.area_sq_meters}\n`;
  txt += `Owner Name:    ${ownerName}\n\n`;
  
  txt += `RISK ASSESSMENT\n`;
  txt += `---------------\n`;
  txt += `Trust Score:   ${r.score} / 10\n`;
  txt += `Status:        ${r.status}\n\n`;
  
  if (r.reasons && r.reasons.length > 0) {
      txt += `RISK FACTORS:\n`;
      r.reasons.forEach(reason => {
          txt += `- ${reason}\n`;
      });
      txt += `\n`;
  } else {
      txt += `RISK FACTORS: None Found.\n\n`;
  }
  
  txt += `=================================================\n`;
  txt += `Generated via e-BhumiAbhilekhan System\n`;
  txt += `Date: ${new Date().toLocaleString()}\n`;

  const blob = new Blob([txt], { type: 'text/plain' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `E-Bhumi_Report_${p.survey_number}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

document.addEventListener('DOMContentLoaded', loadRecentSearches);

/* SEARCH */
function switchTab(tab,btn){
  document.querySelectorAll('.search-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  ['survey','village','owner'].forEach(t=>document.getElementById('tab-'+t).style.display=t===tab?'':'none');
  document.getElementById('live-result').classList.remove('visible');
}

async function doSearch(){
  const box=document.getElementById('live-result');
  const content=document.getElementById('result-content');
  
  function showError(msg) {
    content.innerHTML=`<div style="padding:1.25rem;text-align:center;color:#8C8278;font-size:.88rem;">${msg}</div>`;
    box.classList.add('visible');
  }

  let query = '';
  const activeTab = document.querySelector('.search-tab.active').textContent.trim();
  
  if (activeTab === 'Survey Number') {
    const val=(document.getElementById('inp-survey')?.value||'').trim();
    const dist=(document.getElementById('inp-district')?.value||'').trim();
    if(!val) return showError("Please enter a survey number to search.");
    query = `survey_number=${encodeURIComponent(val)}`;
    if(dist) query += `&district=${encodeURIComponent(dist)}`;
  } else if (activeTab === 'Village / Taluka') {
    const v=(document.getElementById('inp-village')?.value||'').trim();
    const t=(document.getElementById('inp-taluka')?.value||'').trim();
    if(!v && !t) return showError("Please enter village or taluka to search.");
    if(v) query += `village=${encodeURIComponent(v)}&`;
    if(t) query += `taluka=${encodeURIComponent(t)}`;
  } else if (activeTab === 'Owner Name') {
    const o=(document.getElementById('inp-owner')?.value||'').trim();
    if(!o) return showError("Please enter owner name to search.");
    query = `owner_name=${encodeURIComponent(o)}`;
  }
  
  // Loading state
  content.innerHTML=`<div style="padding:2rem;text-align:center;"><div style="width:30px;height:30px;border:3px solid #f3f3f3;border-top:3px solid #E8621A;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 1rem;"></div><div style="color:#8C8278;font-size:.9rem;">Analyzing real-time land records...</div></div><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>`;
  box.classList.add('visible');

  try {
    const res = await fetch(`search_api.php?${query}`);
    const data = await res.json();
    
    if(data.error) {
        content.innerHTML=`<div style="padding:1.5rem;text-align:center;"><div style="font-size:1.1rem;font-weight:600;color:#0F1F3D;margin-bottom:.4rem;">Record Not Found</div><div style="font-size:.85rem;color:#8C8278;">${data.error}</div></div>`;
        return;
    }

    const p = data.property;
    const r = data.risk;
    
    // Format Display Values
    const ownerName = r.seven_twelve ? r.seven_twelve.owner_name : 'Owner not documented (No 7/12)';
    const docBadge = r.seven_twelve 
        ? `<span style="display:inline-flex;align-items:center;gap:5px;font-size:.78rem;font-weight:500;color:#1A7A3C;background:#E8F5EE;padding:.3rem .8rem;border-radius:6px;border:1px solid #1A7A3C;">✓ 7/12 Verified</span>`
        : `<span style="display:inline-flex;align-items:center;gap:5px;font-size:.78rem;font-weight:500;color:#C0321A;background:#FEF0EE;padding:.3rem .8rem;border-radius:6px;border:1px solid #C0321A;">✗ No document</span>`;
    
    let encText = "None Found";
    let encColor = "#1A7A3C";
    if (r.encumbrances && r.encumbrances.length > 0) {
        const severities = r.encumbrances.map(e => e.severity_level);
        encText = `${r.encumbrances.length} Active`;
        if(severities.includes('High')) { encText += ' (High Risk)'; encColor = "#C0321A"; }
        else if(severities.includes('Medium')) { encColor = "#D4820A"; }
    }

    const reasonsHtml = r.reasons.length > 0 
        ? `<div style="margin-top:1rem;padding-top:1rem;border-top:1px dashed #E2DAD0;"><label style="font-size:.7rem;color:#8C8278;font-weight:600;display:block;margin-bottom:6px;letter-spacing:0.05em;">RISK FACTORS IDENTIFIED:</label><ul style="font-size:.8rem;color:#0F1F3D;padding-left:1.2rem;line-height:1.6;margin:0;">${r.reasons.map(reason=>`<li>${reason}</li>`).join('')}</ul></div>` 
        : '';

    content.innerHTML=`
      <div class="lr-top">
        <div>
          <div class="lr-title">${ownerName}</div>
          <div class="lr-sub">Survey ${p.survey_number} · ${p.village}, ${p.taluka}, ${p.district}</div>
        </div>
      </div>
      <div class="lr-body">
        <div class="lr-grid">
          <div class="lr-field"><label>AREA (SQ.M)</label><span>${p.area_sq_meters}</span></div>
          <div class="lr-field"><label>ENCUMBRANCES</label><span style="color:${encColor};font-weight:600;">${encText}</span></div>
          <div class="lr-field"><label>7/12 STATUS</label><span>${docBadge}</span></div>
        </div>
        <div class="lr-score ${r.css_class}">
          <div class="lr-score-num">${r.score}/10</div>
          <div class="lr-score-info">
            <strong>${r.status}</strong>
            <p>Computed by Live Risk Engine based on records.</p>
          </div>
        </div>
        ${reasonsHtml}
        <div style="display:flex; gap:10px; margin-top:1.5rem;">
          <button class="print-btn" onclick="window.print()" style="flex:1;padding:12px;background:var(--navy);color:var(--white);border:none;border-radius:8px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:0.3s;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print PDF
          </button>
          <button class="print-btn" onclick='downloadTxtReport(${JSON.stringify(data).replace(/'/g, "&#39;")})' style="flex:1;padding:12px;background:var(--saffron);color:var(--white);border:none;border-radius:8px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:0.3s;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Download TXT
          </button>
        </div>
      </div>`;

      saveRecentSearch(p);

      // Plot on map if coordinates exist
      if (p.map_coordinates) {
          const popup = `<div style="font-family:'DM Sans',sans-serif;text-align:center;">
              <b style="color:#0F1F3D;">Survey: ${p.survey_number}</b><br>
              <span style="color:#8C8278;font-size:0.8rem;">${p.village}, ${p.taluka}</span><br>
              <b style="color:#1A7A3C;font-size:1.1rem;">Score: ${r.score}/10</b>
          </div>`;
          plotPropertyOnMap(p.map_coordinates, popup);
      }
  } catch(e) {
      content.innerHTML=`<div style="padding:1.5rem;text-align:center;"><div style="font-size:1.1rem;font-weight:600;color:#C0321A;margin-bottom:.4rem;">System Error</div><div style="font-size:.85rem;color:#8C8278;">Could not connect to the Trust Engine API.</div></div>`;
  }
}
}
document.addEventListener('keydown',e=>{if(e.key==='Enter')doSearch();});
</script>
</body>
</html>
