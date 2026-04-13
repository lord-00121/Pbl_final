<?php
// login.php — Unified login page
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/User.php';

if (isLoggedIn()) { redirect(BASE_URL . '/index.php'); }

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'unauthorized': $error = 'Please login to access this area.'; break;
        case 'logout': $error = 'Logged out successfully.'; break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';

    if ($email && $password) {
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== $role) {
                $error = "This account is registered as a " . ucfirst($user['role']) . ". Please select the correct role.";
            } elseif ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended. Please contact support.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                redirect(BASE_URL . '/index.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Login · Sportify</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1A6B3C 0%, #0c3d22 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: rgba(255,255,255,0.95); border-radius: 16px; width: 100%; max-width: 400px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .btn-primary { background-color: #1A6B3C !important; box-shadow: 0 4px 12px rgba(26,107,60,0.3); }
        .nav-tabs .nav-link { font-weight: 500; color: #666; }
        .nav-tabs .nav-link.active { color: #1A6B3C; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <h4 class="fw-700" style="color:#1A6B3C">Sporti<span style="color:#F5A623">fy</span></h4>
        <p class="text-muted small">Real-time Venue Booking</p>
    </div>

    <ul class="nav nav-tabs nav-justified mb-4" id="roleTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-mdb-tab-init href="#loginPane">Login</a></li>
        <li class="nav-item"><a class="nav-link" data-mdb-tab-init href="#registerPane">Register</a></li>
    </ul>

    <?php if ($error): ?><div class="alert alert-danger py-2 small"><?php echo h($error); ?></div><?php endif; ?>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="loginPane">
            <form method="POST" action="login.php">
                <?php echo csrfInput(); ?>
                <div class="mb-4">
                    <p class="small text-muted mb-2 text-center">I am signing in as:</p>
                    <div class="role-selector d-flex justify-content-center gap-2">
                        <input type="radio" name="role" id="role_customer" value="customer" checked style="display:none;">
                        <label for="role_customer" class="role-btn" id="label_customer">
                            <span class="d-block mb-2"><span class="material-icons" style="font-size:1.8rem;">directions_run</span></span>
                            <span class="small fw-600">Customer</span>
                        </label>

                        <input type="radio" name="role" id="role_seller" value="seller" style="display:none;">
                        <label for="role_seller" class="role-btn" id="label_seller">
                            <span class="d-block mb-2"><span class="material-icons" style="font-size:1.8rem;">stadium</span></span>
                            <span class="small fw-600">Seller</span>
                        </label>

                        <input type="radio" name="role" id="role_admin" value="admin" style="display:none;">
                        <label for="role_admin" class="role-btn" id="label_admin">
                            <span class="d-block mb-2"><span class="material-icons" style="font-size:1.8rem;">admin_panel_settings</span></span>
                            <span class="small fw-600">Admin</span>
                        </label>
                    </div>
                </div>
                <style>
                    .role-selector .role-btn {
                        flex: 1; border: 2px solid #eee; border-radius: 12px; padding: 12px 10px;
                        text-align: center; cursor: pointer; transition: all 0.2s ease; background: white;
                    }
                    .role-selector input:checked + .role-btn {
                        border-color: #1A6B3C !important; background-color: #f0fdf4 !important; color: #1A6B3C !important;
                        box-shadow: 0 4px 12px rgba(26,107,60,0.15); transform: translateY(-2px);
                    }
                    .role-selector .role-btn:hover { border-color: #ccc; background: #fafafa; }
                </style>
                <div class="form-outline mb-3" data-mdb-input-init>
                    <input type="email" name="email" id="emailInput" class="form-control" required />
                    <label class="form-label" for="emailInput">Email address</label>
                </div>
                <div class="form-outline mb-4" data-mdb-input-init>
                    <input type="password" name="password" id="passInput" class="form-control" required />
                    <label class="form-label" for="passInput">Password</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-700 shadow-0">Sign In</button>
            </form>
        </div>

        <div class="tab-pane fade" id="registerPane">
            <p class="text-center small text-muted mb-4">Join our sports community today!</p>
            <form method="POST" action="register.php">
                <?php echo csrfInput(); ?>
                <div class="mb-4">
                    <p class="small text-muted mb-2 text-center">I want to join as a:</p>
                    <div class="role-selector d-flex justify-content-center gap-2">
                        <input type="radio" name="reg_role" id="reg_customer" value="customer" checked style="display:none;">
                        <label for="reg_customer" class="role-btn">
                            <span class="d-block mb-1"><span class="material-icons" style="font-size:1.5rem;">directions_run</span></span>
                            <span class="small fw-600">Customer</span>
                        </label>

                        <input type="radio" name="reg_role" id="reg_seller" value="seller" style="display:none;">
                        <label for="reg_seller" class="role-btn">
                            <span class="d-block mb-1"><span class="material-icons" style="font-size:1.5rem;">stadium</span></span>
                            <span class="small fw-600">Seller</span>
                        </label>
                    </div>
                </div>
                <div class="form-outline mb-3" data-mdb-input-init>
                    <input type="text" name="name" id="regName" class="form-control" required />
                    <label class="form-label" for="regName">Full Name</label>
                </div>
                <div class="form-outline mb-3" data-mdb-input-init>
                    <input type="email" name="email" id="regEmail" class="form-control" required />
                    <label class="form-label" for="regEmail">Email</label>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="form-outline" data-mdb-input-init>
                            <input type="tel" name="phone" id="regPhone" class="form-control" required />
                            <label class="form-label" for="regPhone">Phone Number</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-outline" data-mdb-input-init>
                            <input type="text" name="city" id="regCity" class="form-control" required />
                            <label class="form-label" for="regCity">City</label>
                        </div>
                    </div>
                </div>
                <div class="form-outline mb-3" data-mdb-input-init>
                    <input type="text" name="address" id="regAddress" class="form-control" required />
                    <label class="form-label" for="regAddress">Full Address</label>
                </div>
                <div class="form-outline mb-4" data-mdb-input-init>
                    <input type="password" name="password" id="regPass" class="form-control" required />
                    <label class="form-label" for="regPass">Password (Min 8 characters)</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-700 shadow-0">Create Account</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
</body>
</html>
