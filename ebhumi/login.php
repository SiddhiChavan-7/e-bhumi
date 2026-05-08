<?php
session_start();
// If admin is already logged in, they can go to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin/dashboard.php");
    exit;
}
// If citizen is already logged in, redirect to portal
if (isset($_SESSION['citizen_logged_in']) && $_SESSION['citizen_logged_in'] === true) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - e-BhumiAbhilekhan</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --saffron: #E8621A;
            --saffron-light: #F5894A;
            --green: #1A7A3C;
            --navy: #0F1F3D;
            --cream: #FAF7F2;
            --white: #FFFFFF;
            --border: #E2DAD0;
            --warm-gray: #8C8278;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'DM Sans', sans-serif; 
            background: url('https://images.unsplash.com/photo-1590458909403-11116c478a87?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(15, 31, 61, 0.9) 0%, rgba(15, 31, 61, 0.7) 100%);
            backdrop-filter: blur(8px);
            z-index: 1;
        }

        .login-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding: 2rem;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--white);
            animation: fadeDown 0.6s ease;
        }
        .brand-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .brand-header p {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.7);
        }

        .login-box {
            background: var(--white);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeUp 0.6s ease;
            position: relative;
            overflow: hidden;
        }

        /* Toggle Switch */
        .role-toggle {
            display: flex;
            background: var(--cream);
            border-radius: 12px;
            padding: 6px;
            margin-bottom: 2rem;
            position: relative;
        }
        .toggle-btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--warm-gray);
            cursor: pointer;
            z-index: 2;
            transition: color 0.3s ease;
            border: none;
            background: transparent;
        }
        .toggle-btn.active {
            color: var(--navy);
        }
        .toggle-slider {
            position: absolute;
            top: 6px; left: 6px;
            width: calc(50% - 6px);
            height: calc(100% - 12px);
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            z-index: 1;
        }
        .role-toggle[data-active="admin"] .toggle-slider {
            transform: translateX(100%);
        }

        /* Forms */
        .form-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .form-section.active {
            display: block;
        }

        .form-group { margin-bottom: 1.5rem; position: relative; }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 0.5rem;
            letter-spacing: 0.02em;
        }
        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--warm-gray);
            width: 18px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 14px 14px 40px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--navy);
            transition: all 0.2s ease;
            outline: none;
            background: var(--white);
        }
        .form-group input:focus {
            border-color: var(--saffron);
            box-shadow: 0 0 0 4px rgba(232, 98, 26, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-user {
            background: var(--navy);
            color: var(--white);
        }
        .btn-user:hover {
            background: var(--saffron);
        }
        
        .btn-admin {
            background: var(--green);
            color: var(--white);
        }
        .btn-admin:hover {
            background: #156230;
        }

        /* Error Message */
        .error-msg {
            background: #FEF0EE;
            color: #C0321A;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.5rem;
        }

        @keyframes fadeDown { from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:none;} }
        @keyframes fadeUp { from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:none;} }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }

        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--warm-gray);
        }
        .form-footer a {
            color: var(--saffron);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="overlay"></div>

<div class="login-wrapper">
    <div class="brand-header">
        <h1><i data-feather="map" style="width:28px;height:28px;"></i> e-Bhumi</h1>
        <p>Maharashtra Smart Land Verification</p>
    </div>

    <div class="login-box">
        
        <!-- Toggle Switch -->
        <div class="role-toggle" id="roleToggle" data-active="user">
            <div class="toggle-slider"></div>
            <button class="toggle-btn active" onclick="switchRole('user')" id="btn-user">Citizen (User)</button>
            <button class="toggle-btn" onclick="switchRole('admin')" id="btn-admin">Department (Admin)</button>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                <i data-feather="alert-circle" style="width:16px;"></i>
                <?php 
                if ($_GET['error'] == 'invalid_credentials') echo "Invalid mobile number or password.";
                elseif ($_GET['error'] == 'empty_fields') echo "Please fill all required fields.";
                elseif ($_GET['error'] == 'exists') echo "Mobile number or Aadhaar already registered.";
                elseif ($_GET['error'] == 'db') echo "Database error occurred. Try again.";
                else echo "Invalid admin credentials.";
                ?>
            </div>
        <?php endif; ?>

        <!-- USER LOGIN FORM -->
        <div class="form-section <?php echo (isset($_GET['form']) && $_GET['form'] === 'register') ? '' : 'active'; ?>" id="form-user">
            <form action="citizen_auth.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Mobile Number</label>
                    <div class="input-wrapper">
                        <i data-feather="smartphone"></i>
                        <input type="text" name="mobile_number" required placeholder="Enter 10-digit mobile number" pattern="[0-9]{10}" title="Must be a 10 digit number">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i data-feather="key"></i>
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>
                </div>
                <button type="submit" class="submit-btn btn-user">
                    Verify & Proceed <i data-feather="arrow-right" style="width:18px;"></i>
                </button>
            </form>
            <div class="form-footer">
                New citizen? <a href="#" onclick="toggleRegister(event)">Register here</a>
            </div>
        </div>

        <!-- USER REGISTER FORM -->
        <div class="form-section <?php echo (isset($_GET['form']) && $_GET['form'] === 'register') ? 'active' : ''; ?>" id="form-register">
            <form action="citizen_auth.php" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <i data-feather="user"></i>
                        <input type="text" name="full_name" required placeholder="Enter your full name">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <div class="input-wrapper">
                        <i data-feather="smartphone"></i>
                        <input type="text" name="mobile_number" required placeholder="Enter 10-digit mobile number" pattern="[0-9]{10}" title="Must be a 10 digit number">
                    </div>
                </div>
                <div class="form-group">
                    <label>Aadhaar Number</label>
                    <div class="input-wrapper">
                        <i data-feather="credit-card"></i>
                        <input type="text" name="aadhaar_number" required placeholder="Enter 12-digit Aadhaar number" pattern="[0-9]{12}" title="Must be a 12 digit number">
                    </div>
                </div>
                <div class="form-group">
                    <label>Create Password</label>
                    <div class="input-wrapper">
                        <i data-feather="lock"></i>
                        <input type="password" name="password" required placeholder="Create a secure password">
                    </div>
                </div>
                <button type="submit" class="submit-btn btn-user" style="background:var(--saffron);">
                    Create Account <i data-feather="user-plus" style="width:18px;"></i>
                </button>
            </form>
            <div class="form-footer">
                Already registered? <a href="#" onclick="toggleRegister(event)">Login here</a>
            </div>
        </div>

        <!-- ADMIN LOGIN FORM -->
        <div class="form-section" id="form-admin">
            <form action="admin/auth.php" method="POST">
                <div class="form-group">
                    <label>Admin Username</label>
                    <div class="input-wrapper">
                        <i data-feather="user"></i>
                        <input type="text" name="username" required placeholder="Enter assigned username">
                    </div>
                </div>
                <div class="form-group">
                    <label>Secure Password</label>
                    <div class="input-wrapper">
                        <i data-feather="lock"></i>
                        <input type="password" name="password" required placeholder="Enter administrator password">
                    </div>
                </div>
                <button type="submit" class="submit-btn btn-admin">
                    Access Portal <i data-feather="shield" style="width:18px;"></i>
                </button>
            </form>
            <div class="form-footer">
                <i data-feather="info" style="width:14px;vertical-align:middle;margin-right:4px;"></i> Restricted to Revenue Dept Officials
            </div>
        </div>

    </div>
</div>

<script>
    feather.replace();

    function switchRole(role) {
        // UI Toggle
        const toggle = document.getElementById('roleToggle');
        toggle.setAttribute('data-active', role);
        
        document.getElementById('btn-user').classList.toggle('active', role === 'user');
        document.getElementById('btn-admin').classList.toggle('active', role === 'admin');

        // Form Switching
        document.getElementById('form-user').classList.toggle('active', role === 'user');
        document.getElementById('form-admin').classList.toggle('active', role === 'admin');
        if (document.getElementById('form-register')) {
            document.getElementById('form-register').classList.remove('active');
        }
    }

    function toggleRegister(e) {
        if(e) e.preventDefault();
        const loginForm = document.getElementById('form-user');
        const regForm = document.getElementById('form-register');
        if (loginForm.classList.contains('active')) {
            loginForm.classList.remove('active');
            regForm.classList.add('active');
            feather.replace(); // Refresh icons for new form
        } else {
            regForm.classList.remove('active');
            loginForm.classList.add('active');
        }
    }

    // If there's an error in URL, switch to admin automatically
    if (window.location.search.includes('error=1')) {
        switchRole('admin');
    }
</script>

</body>
</html>
