<?php
$in_admin = true;
require_once '../helpers.php';
require_admin();
require_once '../config.php';

$title = "Admin - Dashboard";
require '../layout/header.php';

$adminId = (int)($_SESSION['user_id'] ?? 0);

// KPI-uri pentru spațiile adminului
// (1) câte spații administrează
$stmt = $conn->prepare("SELECT COUNT(*) AS Cnt FROM Spatii WHERE IdAdmin=?");
$stmt->execute([$adminId]);
$nrSpatii = (int)($stmt->fetch()['Cnt'] ?? 0);

// (2) câte rezervări există pe spațiile lui (toate statusurile)
$stmt = $conn->prepare("
SELECT COUNT(*) AS Cnt
FROM Rezervari r
JOIN RezervareBirou rb ON rb.IdRezervare = r.Id
JOIN Birouri b ON b.Id = rb.IdBirou
JOIN Sali sa ON sa.Id = b.IdSala
JOIN Spatii sp ON sp.Id = sa.IdSpatiu
WHERE sp.IdAdmin = ?
");
$stmt->execute([$adminId]);
$nrRez = (int)($stmt->fetch()['Cnt'] ?? 0);

// (3) venit efectuat pe spațiile lui (agregat, anonim)
$stmt = $conn->prepare("
SELECT COALESCE(SUM(p.Suma),0) AS Venit
FROM Plati p
JOIN Rezervari r ON r.Id = p.IdRezervare
JOIN RezervareBirou rb ON rb.IdRezervare = r.Id
JOIN Birouri b ON b.Id = rb.IdBirou
JOIN Sali sa ON sa.Id = b.IdSala
JOIN Spatii sp ON sp.Id = sa.IdSpatiu
WHERE sp.IdAdmin = ? AND p.Status='efectuata'
");
$stmt->execute([$adminId]);
$venit = $stmt->fetch()['Venit'] ?? 0;

// Agregat anonim: rezervări pe status pentru spațiile lui
$byStatus = $conn->prepare("
SELECT r.Status, COUNT(*) AS Cnt
FROM Rezervari r
JOIN RezervareBirou rb ON rb.IdRezervare = r.Id
JOIN Birouri b ON b.Id = rb.IdBirou
JOIN Sali sa ON sa.Id = b.IdSala
JOIN Spatii sp ON sp.Id = sa.IdSpatiu
WHERE sp.IdAdmin = ?
GROUP BY r.Status
ORDER BY Cnt DESC
");
$byStatus->execute([$adminId]);
$statusRows = $byStatus->fetchAll();

// Agregat anonim “piață”: top spații după nr rezervări (fără nume/email)
$topSpatii = $conn->query("
SELECT TOP 5 sp.Denumire, sp.Oras, COUNT(*) AS NrRezervari
FROM Rezervari r
JOIN RezervareBirou rb ON rb.IdRezervare = r.Id
JOIN Birouri b ON b.Id = rb.IdBirou
JOIN Sali sa ON sa.Id = b.IdSala
JOIN Spatii sp ON sp.Id = sa.IdSpatiu
GROUP BY sp.Denumire, sp.Oras
ORDER BY NrRezervari DESC
")->fetchAll();
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Indicatori (spațiile mele)</h5>
        <div class="mb-2"><strong>Spații administrate:</strong> <?= e((string)$nrSpatii) ?></div>
        <div class="mb-2"><strong>Rezervări totale:</strong> <?= e((string)$nrRez) ?></div>
        <div class="mb-2"><strong>Venit (plăți efectuate):</strong> <?= e((string)$venit) ?></div>

        <a class="btn btn-primary btn-sm w-100 mt-2" href="rezervari.php">Gestionează rezervări</a>

        <p class="text-muted small mt-2 mb-0">
          Totul este filtrat pe spațiile tale (Spatii.IdAdmin = admin curent).
        </p>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h6 class="mb-2">Rezervări pe status (spațiile mele)</h6>
        <table class="table table-sm mb-0">
          <thead><tr><th>Status</th><th>Nr</th></tr></thead>
          <tbody>
            <?php foreach ($statusRows as $r): ?>
              <tr><td><?= e($r['Status']) ?></td><td><?= e((string)$r['Cnt']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$statusRows): ?><tr><td colspan="2" class="text-muted">Nu există date.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Top spații după rezervări (anonim)</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Spațiu</th><th>Oraș</th><th>Nr rezervări</th></tr></thead>
            <tbody>
              <?php foreach ($topSpatii as $r): ?>
                <tr>
                  <td><?= e($r['Denumire']) ?></td>
                  <td><?= e($r['Oras']) ?></td>
                  <td><?= e((string)$r['NrRezervari']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$topSpatii): ?><tr><td colspan="3" class="text-muted">Nu există date.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="text-muted small mb-0">Agregare pe piață: fără utilizatori, fără date personale.</p>
      </div>
    </div>
  </div>
</div>

<?php require '../layout/footer.php'; ?>
