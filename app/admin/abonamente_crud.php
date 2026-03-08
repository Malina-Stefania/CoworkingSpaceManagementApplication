<?php
$in_admin = true;
require_once '../helpers.php';
require_admin();
require_once '../config.php';

$title = "Admin - Abonamente";
require '../layout/header.php';

if (($_POST['action'] ?? '') === 'create') {
    $stmt = $conn->prepare("INSERT INTO Abonamente (Tip, Pret, Ore, Descriere) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        trim($_POST['Tip'] ?? ''),
        (float)($_POST['Pret'] ?? 0),
        (int)($_POST['Ore'] ?? 0),
        trim($_POST['Descriere'] ?? '')
    ]);
    flash_set("Abonament adăugat.");
    header('Location: abonamente_crud.php'); exit;
}

if (($_POST['action'] ?? '') === 'update') {
    $stmt = $conn->prepare("UPDATE Abonamente SET Pret=?, Ore=?, Descriere=? WHERE Id=?");
    $stmt->execute([
        (float)($_POST['Pret'] ?? 0),
        (int)($_POST['Ore'] ?? 0),
        trim($_POST['Descriere'] ?? ''),
        (int)($_POST['Id'] ?? 0)
    ]);
    flash_set("Abonament actualizat.");
    header('Location: abonamente_crud.php'); exit;
}

if (($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['Id'] ?? 0);
    try {
        $stmt = $conn->prepare("DELETE FROM Abonamente WHERE Id=?");
        $stmt->execute([$id]);
        flash_set("Abonament șters.");
    } catch (PDOException $e) {
        flash_set("Nu pot șterge abonamentul (este folosit în alte tabele).", "warning");
    }
    header('Location: abonamente_crud.php'); exit;
}

$rows = $conn->query("SELECT Id, Tip, Pret, Ore, Descriere FROM Abonamente ORDER BY Id DESC")->fetchAll();
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Adaugă abonament</h5>
        <form method="post" class="vstack gap-2">
          <input type="hidden" name="action" value="create">
          <div><label class="form-label">Tip</label><input class="form-control" name="Tip" required></div>
          <div class="row g-2">
            <div class="col"><label class="form-label">Preț</label><input class="form-control" name="Pret" type="number" step="0.01" min="0" required></div>
            <div class="col"><label class="form-label">Ore</label><input class="form-control" name="Ore" type="number" min="0" required></div>
          </div>
          <div><label class="form-label">Descriere</label><textarea class="form-control" name="Descriere" rows="3"></textarea></div>
          <button class="btn btn-primary">Insert</button>
        </form>
        <div class="text-muted small mt-2">CRUD: INSERT (Abonamente)</div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Lista abonamente</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Tip</th><th>Preț</th><th>Ore</th><th>Descriere</th><th style="width:260px"></th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e($r['Tip']) ?></td>
                  <td><?= e((string)$r['Pret']) ?></td>
                  <td><?= e((string)$r['Ore']) ?></td>
                  <td class="text-muted"><?= e((string)$r['Descriere']) ?></td>
                  <td>
                    <form method="post" class="row g-1">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="Id" value="<?= (int)$r['Id'] ?>">
                      <div class="col-4"><input class="form-control form-control-sm" name="Pret" type="number" step="0.01" min="0" value="<?= e((string)$r['Pret']) ?>"></div>
                      <div class="col-3"><input class="form-control form-control-sm" name="Ore" type="number" min="0" value="<?= e((string)$r['Ore']) ?>"></div>
                      <div class="col-5"><input class="form-control form-control-sm" name="Descriere" value="<?= e((string)$r['Descriere']) ?>"></div>
                      <div class="col-12 d-flex gap-1 mt-1">
                        <button class="btn btn-success btn-sm">Update</button>
                      </div>
                    </form>
                    <form method="post" class="mt-1" onsubmit="return confirm('Ștergi abonamentul?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="Id" value="<?= (int)$r['Id'] ?>">
                      <button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?><tr><td colspan="5" class="text-muted">Nu există abonamente.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="text-muted small">CRUD: UPDATE/DELETE (Abonamente)</div>
      </div>
    </div>
  </div>
</div>

<?php require '../layout/footer.php'; ?>
