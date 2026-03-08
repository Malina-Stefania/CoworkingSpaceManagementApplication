<?php
require_once 'helpers.php';
require_login();
require_once 'config.php';

$title = "Spații";
require 'layout/header.php';

$oras = trim($_GET['oras'] ?? '');
$orasLike = $oras . '%';

$sql = "
SELECT sp.Id, sp.Denumire, sp.Oras, sp.Judet,
       u.Nume + ' ' + u.Prenume AS Administrator
FROM Spatii sp
JOIN Utilizatori u ON u.Id = sp.IdAdmin
WHERE (? = 1 OR sp.Oras LIKE ?)
ORDER BY sp.Denumire;
";

$stmt = $conn->prepare($sql);
$stmt->execute([($oras === '' ? 1 : 0), $orasLike]);
$rows = $stmt->fetchAll();
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Caută după oraș</h5>
        <form method="get" class="d-flex gap-2">
          <input class="form-control" name="oras" placeholder="ex: Bucuresti" value="<?= e($oras) ?>">
          <button class="btn btn-primary">Caută</button>
        </form>
        <p class="text-muted small mt-2 mb-0">Parametru variabil: oraș</p>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Rezultate</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Denumire</th><th>Oraș</th><th>Județ</th><th>Administrator</th><th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e($r['Denumire']) ?></td>
                  <td><?= e($r['Oras']) ?></td>
                  <td><?= e($r['Judet']) ?></td>
                  <td><?= e($r['Administrator']) ?></td>
                  <td><a class="btn btn-outline-primary btn-sm" href="spatiu_detalii.php?id=<?= (int)$r['Id'] ?>">Detalii</a></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?>
                <tr><td colspan="5" class="text-muted">Nu există rezultate.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="text-muted small mb-0">JOIN: Spatii ↔ Utilizatori</p>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
