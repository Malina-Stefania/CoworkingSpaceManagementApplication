<?php
require_once 'helpers.php';
require_login();
require_once 'config.php';

$title = "Birouri";
require 'layout/header.php';

$tip = trim($_GET['tip'] ?? 'open space');
$capMin = (int)($_GET['capMin'] ?? 1);

$sql = "
SELECT sp.Denumire AS Spatiu, sp.Oras,
       sa.Denumire AS Sala, sa.Tip, sa.Capacitate,
       b.Cod, b.PretOra
FROM Birouri b
JOIN Sali sa ON sa.Id = b.IdSala
JOIN Spatii sp ON sp.Id = sa.IdSpatiu
WHERE sa.Tip = ? AND sa.Capacitate >= ?
ORDER BY sp.Oras, sp.Denumire, b.Cod;
";

$stmt = $conn->prepare($sql);
$stmt->execute([$tip, $capMin]);
$rows = $stmt->fetchAll();
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Filtre</h5>
        <form method="get" class="vstack gap-2">
          <div>
            <label class="form-label">Tip sală</label>
            <select class="form-select" name="tip">
              <?php foreach (['open space','sala meeting','birou privat'] as $t): ?>
                <option value="<?= e($t) ?>" <?= $t===$tip?'selected':'' ?>><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Capacitate minimă</label>
            <input class="form-control" type="number" name="capMin" min="1" value="<?= e((string)$capMin) ?>">
          </div>
          <button class="btn btn-primary">Caută</button>
          <p class="text-muted small mb-0">Parametri: tip, capacitate</p>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Birouri</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Spațiu</th><th>Oraș</th><th>Sala</th><th>Tip</th><th>Cod</th><th>Preț/oră</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e($r['Spatiu']) ?></td>
                  <td><?= e($r['Oras']) ?></td>
                  <td><?= e($r['Sala']) ?></td>
                  <td><?= e($r['Tip']) ?></td>
                  <td><?= e($r['Cod']) ?></td>
                  <td><?= e((string)$r['PretOra']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?><tr><td colspan="6" class="text-muted">Nu există rezultate.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="text-muted small mb-0">JOIN: Birouri ↔ Sali ↔ Spatii</p>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
