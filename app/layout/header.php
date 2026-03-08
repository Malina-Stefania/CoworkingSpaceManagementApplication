<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? "Coworking";

$isAdminPage = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$base = $isAdminPage ? "../" : "";

$userLogged = isset($_SESSION['user_id']);
$userRole   = $_SESSION['user_role'] ?? '';
$isAdmin    = ($userRole === 'administrator');

$current = $_SERVER['SCRIPT_NAME']; // pt highlight simplu
function active_if_contains(string $needle, string $current): string {
  return (strpos($current, $needle) !== false) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= $base ?>index.php">Coworking</a>

    <!-- buton meniul "hamburger" (mobil) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">

      <!-- STÂNGA: taburi principale -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <?php if ($userLogged && !$isAdminPage): ?>
          <!-- Client tabs -->
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/abonamente.php', $current) ?>" href="abonamente.php">Abonamente</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/spatii.php', $current) ?>" href="spatii.php">Spații</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/rezervare_noua.php', $current) ?>" href="rezervare_noua.php">Rezervare nouă</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/rezervarile_mele.php', $current) ?>" href="rezervarile_mele.php">Rezervările mele</a>
          </li>
        <?php endif; ?>

        <?php if ($userLogged && $isAdmin): ?>
          <!-- Admin tabs (și în root, și în /admin) -->
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/admin/dashboard.php', $current) ?>" href="<?= $base ?>admin/dashboard.php">Admin Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/admin/rezervari.php', $current) ?>" href="<?= $base ?>admin/rezervari.php">Admin Rezervări</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/admin/abonamente_crud.php', $current) ?>" href="<?= $base ?>admin/abonamente_crud.php">Admin Abonamente</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= active_if_contains('/admin/spatii_crud.php', $current) ?>" href="<?= $base ?>admin/spatii_crud.php">Admin Spații</a>
          </li>

          <!-- AM SCOS DOAR:
               admin/rapoarte.php
               admin/plati.php
          -->
        <?php endif; ?>

      </ul>

      <!-- DREAPTA: meniu user -->
      <ul class="navbar-nav ms-auto">
        <?php if ($userLogged): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= htmlspecialchars($_SESSION['user_email'] ?? $_SESSION['user'] ?? 'Cont') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (!$isAdminPage): ?>
                <li><a class="dropdown-item" href="index.php">Acasă</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="<?= $base ?>index.php">Acasă</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>

              <li><a class="dropdown-item" href="<?= $base ?>logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>register.php">Înregistrare</a>
          </li>
        <?php endif; ?>
      </ul>

    </div>
  </div>
</nav>

<div class="container my-4">
<?php
// Flash messages (dacă există în helpers.php)
if (function_exists('flash_get')) {
  $f = flash_get();
  if ($f && !empty($f['msg'])) {
    $type = htmlspecialchars($f['type'] ?? 'info');
    $msg  = htmlspecialchars($f['msg']);
    echo "<div class='alert alert-$type'>$msg</div>";
  }
}
?>
