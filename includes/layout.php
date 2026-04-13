<?php
// includes/layout.php — Shared UI components
require_once __DIR__ . '/../config/app.php';

/**
 * Returns the correct URL for an image, handling both local and remote (Cloudinary) paths.
 */
function imgUrl($path) {
    if (!$path) return '';
    if (strpos($path, 'http') === 0) return $path;
    return BASE_URL . '/' . ltrim($path, '/');
}

function layoutHead(string $title = 'Sportify'): void {
    $url = BASE_URL;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title · Sportify</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- MDBootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.min.css" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="$url/assets/css/style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        :root { --primary: #1A6B3C; --accent: #F5A623; }
        .bg-primary { background-color: var(--primary) !important; }
        .text-primary { color: var(--primary) !important; }
        .btn-primary { background-color: var(--primary) !important; border: none; }
        .btn-accent { background-color: var(--accent) !important; color: white !important; border: none; }
        .card { border-radius: 8px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
HTML;
}

function layoutNavbar(string $role = 'customer', string $userName = ''): void {
    $url = BASE_URL;
    $roleLabel = ucfirst($role);
    echo <<<HTML
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-0">
  <div class="container-fluid">
    <a class="navbar-brand fw-700" href="$url/index.php">Sporti<span style="color:var(--accent)">fy</span></a>
    <div class="d-flex align-items-center">
      <span class="badge rounded-pill bg-light text-primary me-3 px-3 py-2" style="font-size: 0.75rem;">$roleLabel</span>
      <div class="dropdown">
        <a class="text-white dropdown-toggle d-flex align-items-center hidden-arrow" href="#" id="navbarDropdownMenuLink" role="button" data-mdb-dropdown-init aria-expanded="false">
          <span class="material-icons me-1" style="font-size: 1.2rem;">account_circle</span>
          <span class="d-none d-md-inline">$userName</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
          <li><a class="dropdown-item" href="$url/logout.php"><span class="material-icons align-middle me-1" style="font-size:1.1rem">logout</span> Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<div class="container-fluid mt-4">
    <div class="row">
HTML;
}

function layoutSidebar(string $role = 'customer', string $active = ''): void {
    $url = BASE_URL;
    if ($role === 'customer') {
        $items = [
            ['l' => 'Dashboard', 'h' => "$url/dashboard/customer/index.php", 'i' => 'dashboard'],
            ['l' => 'Browse Venues', 'h' => "$url/dashboard/customer/browse.php", 'i' => 'search'],
            ['l' => 'Browse Tournaments', 'h' => "$url/dashboard/customer/browse_tournaments.php", 'i' => 'emoji_events'],
            ['l' => 'My Bookings', 'h' => "$url/dashboard/customer/past_bookings.php", 'i' => 'history'],
        ];
    } elseif ($role === 'seller') {
        $items = [
            ['l' => 'Dashboard', 'h' => "$url/dashboard/seller/index.php", 'i' => 'dashboard'],
            ['l' => 'Manage Venues', 'h' => "$url/dashboard/seller/venues.php", 'i' => 'stadium'],
            ['l' => 'Manage Bookings', 'h' => "$url/dashboard/seller/bookings.php", 'i' => 'event_available'],
            ['l' => 'Tournaments', 'h' => "$url/dashboard/seller/tournaments.php", 'i' => 'emoji_events'],
            ['l' => 'Registrations', 'h' => "$url/dashboard/seller/registrations.php", 'i' => 'people'],
            ['l' => 'Revenue', 'h' => "$url/dashboard/seller/revenue.php", 'i' => 'bar_chart'],
        ];
    } else {
        $items = [
            ['l' => 'Dashboard', 'h' => "$url/dashboard/admin/index.php", 'i' => 'dashboard'],
            ['l' => 'Users', 'h' => "$url/dashboard/admin/users.php", 'i' => 'people'],
            ['l' => 'Venues', 'h' => "$url/dashboard/admin/venues.php", 'i' => 'location_city'],
            ['l' => 'Tournaments', 'h' => "$url/dashboard/admin/tournaments.php", 'i' => 'emoji_events'],
            ['l' => 'Bookings', 'h' => "$url/dashboard/admin/bookings.php", 'i' => 'event_available'],
        ];
    }

    echo '<div class="col-lg-2 mb-4"><div class="list-group list-group-flush shadow-sm rounded-3 border-0 bg-white">';
    foreach ($items as $item) {
        $isAct = ($active === $item['l']) ? 'active' : '';
        $color = ($active === $item['l']) ? 'white' : 'var(--primary)';
        echo '<a href="' . $item['h'] . '" class="list-group-item list-group-item-action py-3 border-0 ' . $isAct . '" style="border-radius:0;">';
        echo '<span class="material-icons align-middle me-2" style="font-size:1.2rem; color:' . $color . '">' . $item['i'] . '</span> ' . $item['l'] . '</a>';
    }
    echo '</div></div><main class="col-lg-10 mb-5">';
}

function layoutFooter(): void {
    $url = BASE_URL;
    echo <<<HTML
    </main>
    </div>
</div>
<!-- MDBootstrap 5 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
<script src="$url/assets/js/main.js"></script>
</body>
</html>
HTML;
}
