<?php
require_once 'helpers.php';
require_login();
require_once 'config.php';

$idSpatiu = (int)($_GET['id'] ?? 0);

$title = "Detalii spațiu";
require 'layout/header.php';

$stmt = $conn->prepare("SELECT * FROM Spatii WHERE Id=?");
$stmt->execute([$idSpatiu]);
$sp = $stmt->fetch();

if (!$sp) {
    echo "<div class='alert alert-danger'>Spațiul nu există.</div>";
    require 'layout/footer.php';
    exit;
}

// sali
$stmt = $conn->prepare("
  SELECT Id, Denumire, Tip, Capacitate, PretOra
  FROM Sali
  WHERE IdSpatiu=?
  ORDER BY Denumire
");
$stmt->execute([$idSpatiu]);
$sali = $stmt->fetchAll();

// facilitati
$stmt = $conn->prepare("
  SELECT f.Denumire, f.Descriere
  FROM SpatiuFacilitate sf
  JOIN Facilitati f ON f.Id = sf.IdFacilitate
  WHERE sf.IdSpatiu=?
  ORDER BY f.Denumire
");
$stmt->execute([$idSpatiu]);
$fac = $stmt->fetchAll();
?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-1"><?= e($sp['Denumire']) ?></h4>
        <div class="text-muted"><?= e($sp['Oras'] ?? '') ?>, <?= e($sp['Judet'] ?? '') ?></div>
        <div class="mt-2"><strong>Adresă:</strong> <?= e($sp['Strada'] ?? '') ?> <?= e($sp['Numar'] ?? '') ?></div>
        <p class="mt-2 mb-0"><?= e($sp['Descriere'] ?? '') ?></p>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h5 class="card-title">Facilități</h5>
        <?php if ($fac): ?>
          <ul class="mb-0">
            <?php foreach ($fac as $f): ?>
              <li><strong><?= e($f['Denumire']) ?></strong> — <?= e($f['Descriere'] ?? '') ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-muted">Nu sunt facilități asociate.</div>
        <?php endif; ?>
        <p class="text-muted small mt-2 mb-0">JOIN: SpatiuFacilitate ↔ Facilitati</p>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Săli</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Denumire</th><th>Tip</th><th>Capacitate</th><th>Preț/oră</th></tr></thead>
            <tbody>
              <?php foreach ($sali as $s): ?>
                <tr>
                  <td><?= e($s['Denumire']) ?></td>
                  <td><?= e($s['Tip']) ?></td>
                  <td><?= e((string)$s['Capacitate']) ?></td>
                  <td><?= e((string)$s['PretOra']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$sali): ?><tr><td colspan="4" class="text-muted">Nu există săli.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="text-muted small mb-0">JOIN: Spatii ↔ Sali</p>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
