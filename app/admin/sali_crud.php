<?php
$in_admin = true;
require_once '../helpers.php';
require_admin();
require_once '../config.php';

$title = "Admin - Săli";
require '../layout/header.php';

$spatii = $conn->query("SELECT Id, Denumire, Oras FROM Spatii ORDER BY Denumire")->fetchAll();

if (($_POST['action'] ?? '') === 'create') {
    $stmt = $conn->prepare("INSERT INTO Sali (IdSpatiu, Denumire, Tip, Capacitate, PretOra) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        (int)($_POST['IdSpatiu'] ?? 0),
        trim($_POST['Denumire'] ?? ''),
        trim($_POST['Tip'] ?? ''),
        (int)($_POST['Capacitate'] ?? 1),
        (float)($_POST['PretOra'] ?? 0),
    ]);
    flash_set("Sală adăugată.");
    header('Location: sali_crud.php'); exit;
}

if (($_POST['action'] ?? '') === 'update') {
    $stmt = $conn->prepare("UPDATE Sali SET Capacitate=?, PretOra=? WHERE Id=?");
    $stmt->execute([
        (int)($_POST['Capacitate'] ?? 1),
        (float)($_POST['PretOra'] ?? 0),
        (int)($_POST['Id'] ?? 0),
    ]);
    flash_set("Sală actualizată.");
    header('Location: sali_crud.php'); exit;
}

if (($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['Id'] ?? 0);
    try {
        $stmt = $conn->prepare("DELETE FROM Sali WHERE Id=?");
        $stmt->execute([$id]);
        flash_set("Sală ștearsă.");
    } catch (PDOException $e) {
        flash_set("Nu pot șterge sala (are dependențe).", "warning");
    }
    header('Location: sali_crud.php'); exit;
}

$rows = $conn->query("
SELECT sa.Id, sa.Denumire, sa.Tip, sa.Capacitate, sa.PretOra,
       sp.Denumire AS Spatiu, sp.Oras
FROM Sali sa
JOIN Spatii sp ON sp.Id = sa.IdSpatiu
ORDER BY sa.Id DESC;
")->fetchAll();
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Adaugă sală</h5>
        <form method="post" class="vstack gap-2">
          <input type="hidden" name="action" value="create">
          <div>
            <label class="form-label">Spațiu</label>
            <select class="form-select" name="IdSpatiu" required>
              <?php foreach ($spatii as $sp): ?>
                <option value="<?= (int)$sp['Id'] ?>"><?= e($sp['Denumire'].' ('.$sp['Oras'].')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label class="form-label">Denumire</label><input class="form-control" name="Denumire" required></div>
          <div>
            <label class="form-label">Tip</label>
            <select class="form-select" name="Tip" required>
              <option>open space</option>
              <option>sala meeting</option>
              <option>birou privat</option>
            </select>
          </div>
          <div class="row g-2">
            <div class="col"><label class="form-label">Capacitate</label><input class="form-control" type="number" min="1" name="Capacitate" required></div>
            <div class="col"><label class="form-label">Preț/oră</label><input class="form-control" type="number" step="0.01" min="0" name="PretOra" required></div>
          </div>
          <button class="btn btn-primary">Insert</button>
          <div class="text-muted small">CRUD: INSERT (Sali)</div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Lista săli</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Spațiu</th><th>Oraș</th><th>Sală</th><th>Tip</th><th>Cap</th><th>Preț</th><th style="width:230px"></th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e($r['Spatiu']) ?></td>
                  <td><?= e($r['Oras']) ?></td>
                  <td><?= e($r['Denumire']) ?></td>
                  <td><?= e($r['Tip']) ?></td>
                  <td><?= e((string)$r['Capacitate']) ?></td>
                  <td><?= e((string)$r['PretOra']) ?></td>
                  <td>
                    <form method="post" class="d-flex gap-1 align-items-end">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="Id" value="<?= (int)$r['Id'] ?>">
                      <div><label class="form-label small mb-0">Cap</label><input class="form-control form-control-sm" type="number" min="1" name="Capacitate" value="<?= e((string)$r['Capacitate']) ?>"></div>
                      <div><label class="form-label small mb-0">Preț</label><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="PretOra" value="<?= e((string)$r['PretOra']) ?>"></div>
                      <button class="btn btn-success btn-sm">Update</button>
                    </form>
                    <form method="post" class="mt-1" onsubmit="return confirm('Ștergi sala?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="Id" value="<?= (int)$r['Id'] ?>">
                      <button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?><tr><td colspan="7" class="text-muted">Nu există săli.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="text-muted small">CRUD: UPDATE/DELETE (Sali) + JOIN: Sali ↔ Spatii</div>
      </div>
    </div>
  </div>
</div>

<?php require '../layout/footer.php'; ?>
