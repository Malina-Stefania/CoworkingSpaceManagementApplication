<?php
require_once 'helpers.php';
require_login();

$title = "Acasă";
require 'layout/header.php';
?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-1">Bine ai venit!</h4>
        <p class="text-muted mb-3">Aplicație de gestionare a spațiilor de coworking.</p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-outline-primary" href="spatii.php">Caută spații</a>
          <a class="btn btn-outline-primary" href="birouri.php">Caută birouri</a>
          <a class="btn btn-outline-primary" href="abonamente.php">Abonamente</a>
          <a class="btn btn-outline-primary" href="rezervare_noua.php">Rezervare nouă</a>
          <a class="btn btn-outline-primary" href="rezervarile_mele.php">Rezervările mele</a>
          <?php if (($_SESSION['user_role'] ?? '') === 'administrator'): ?>
            <a class="btn btn-warning" href="admin/dashboard.php">Panou Admin</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="text-muted mb-2">Cont curent</h6>
        <div><strong>Email:</strong> <?= e($_SESSION['user_email']) ?></div>
        <div><strong>Rol:</strong> <?= e($_SESSION['user_role']) ?></div>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
