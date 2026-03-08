<?php
$in_admin = true;
require_once '../helpers.php';
require_admin();
require_once '../config.php';

$title = "Admin - Spații";
require '../layout/header.php';

$admins = $conn->query("SELECT Id, Nume, Prenume, Email FROM Utilizatori WHERE Rol='administrator' ORDER BY Nume")->fetchAll();

if (($_POST['action'] ?? '') === 'create') {
    $stmt = $conn->prepare("
      INSERT INTO Spatii (Denumire, IdAdmin, Strada, Numar, Oras, Judet, Descriere)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($_POST['Denumire'] ?? ''),
        (int)($_POST['IdAdmin'] ?? 0),
        trim($_POST['Strada'] ?? ''),
        trim($_POST['Numar'] ?? ''),
        trim($_POST['Oras'] ?? ''),
        trim($_POST['Judet'] ?? ''),
        trim($_POST['Descriere'] ?? ''),
    ]);
    flash_set("Spațiu adăugat.");
    header('Location: spatii_crud.php'); exit;
}

if (($_POST['action'] ?? '') === 'update') {
    $stmt = $conn->prepare("
      UPDATE Spatii
      SET Denumire=?, IdAdmin=?, Oras=?, Judet=?
      WHERE Id=?
    ");
    $stmt->execute([
        trim($_POST['Denumire'] ?? ''),
        (int)($_POST['IdAdmin'] ?? 0),
        trim($_POST['Oras'] ?? ''),
        trim($_POST['Judet'] ?? ''),
        (int)($_POST['Id'] ?? 0),
    ]);
    flash_set("Spațiu actualizat.");
    header('Location: spatii_crud.php'); exit;
}

if (($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['Id'] ?? 0);
    try {
        $stmt = $conn->prepare("DELETE FROM Spatii WHERE Id=?");
        $stmt->execute([$id]);
        flash_set("Spațiu șters.");
    } catch (PDOException $e) {
        flash_set("Nu pot șterge spațiul (are dependențe).", "warning");
    }
    header('Location: spatii_crud.php'); exit;
}

$rows = $conn->query("
SELECT sp.Id, sp.Denumire, sp.Oras, sp.Judet, sp.IdAdmin,
       u.Nume + ' ' + u.Prenume AS Administrator
FROM Spatii sp
JOIN Utilizatori u ON u.Id = sp.IdAdmin
ORDER BY sp.Id DESC;
")->fetchAll();
?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h5 class="card-title">Adaugă spațiu</h5>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="create">
      <div class="col-md-4"><label class="form-label">Denumire</label><input class="form-control" name="Denumire" required></div>
      <div class="col-md-4">
        <label class="form-label">Administrator</label>
        <select class="form-select" name="IdAdmin" required>
          <?php foreach ($admins as $a): ?>
            <option value="<?= (int)$a['Id'] ?>"><?= e($a['Nume'].' '.$a['Prenume'].' ('.$a['Email'].')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">Oraș</label><input class="form-control" name="Oras" required></div>
      <div class="col-md-2"><label class="form-label">Județ</label><input class="form-control" name="Judet" required></div>
      <div class="col-12"><button class="btn btn-primary">Insert</button></div>
      <div class="text-muted small">CRUD: INSERT (Spatii)</div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title">Lista spații</h5>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Denumire</th><th>Oraș</th><th>Județ</th><th>Admin</th><th style="width:420px"></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e($r['Denumire']) ?></td>
              <td><?= e($r['Oras']) ?></td>
              <td><?= e($r['Judet']) ?></td>
              <td><?= e($r['Administrator']) ?></td>
              <td>
                <form method="post" class="row g-1">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="Id" value="<?= (int)$r['Id'] ?>">
                  <div class="col-4"><input class="form-control form-control-sm" name="Denumire" value="<?= e($r['Denumire']) ?>" required></div>
                  <div class="col-4">
                    <select class="form-select form-select-sm" name="IdAdmin" required>
                      <?php foreach ($admins as $a): ?>
                        <option value="<?= (int)$a['Id'] ?>" <?= ((int)$a['Id']===(int)$r['IdAdmin'])?'selected':'' ?>>
                          <?= e($a['Nume'].' '.$a['Prenume']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-2"><input class="form-control form-control-sm" name="Oras" value="<?= e($r['Oras']) ?>" required></div>
                  <div class="col-2"><input class="form-control form-control-sm" name="Judet" value="<?= e($r['Judet']) ?>" required></div>
                  <div class="col-12 d-flex gap-1 mt-1">
                    <button class="btn btn-success btn-sm">Update</button>
                  </div>
                </form>

                <form method="post" class="mt-1" onsubmit="return confirm('Ștergi spațiul?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="Id" value="<?= (int)$r['Id'] ?>">
                  <button class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="5" class="text-muted">Nu există spații.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="text-muted small">CRUD: UPDATE/DELETE (Spatii) + JOIN: Spatii ↔ Utilizatori</div>
  </div>
</div>

<?php require '../layout/footer.php'; ?>
