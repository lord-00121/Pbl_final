<?php
// register.php — Handle registration POST
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/User.php';

if (isLoggedIn()) { redirect(BASE_URL . '/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['reg_role'] ?? '', ['customer','seller']) ? $_POST['reg_role'] : 'customer';

    if (!$name || !$email || strlen($password) < 8) {
        redirect(BASE_URL . '/login.php?error=invalid_reg');
    }

    $userModel = new User();
    if ($userModel->findByEmail($email)) {
        redirect(BASE_URL . '/login.php?error=exists');
    }

    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $id = $userModel->create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => $role,
        'phone' => $phone,
        'city' => $city,
        'address' => $address
    ]);

    $_SESSION['user_id'] = $id;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();

    redirect(BASE_URL . '/index.php');
} else {
    redirect(BASE_URL . '/login.php');
}
