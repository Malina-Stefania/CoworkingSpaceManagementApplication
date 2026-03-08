<?php
require_once 'helpers.php';
require_login();
require_once 'config.php';

$title = "Rezervările mele";
require 'layout/header.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$isClient = (($_SESSION['user_role'] ?? '') === 'client');

if (!$isClient) {
    echo "<div class='alert alert-warning'>Doar clienții au rezervări personale.</div>";
    require 'layout/footer.php';
    exit;
}

if (($_POST['action'] ?? '') === 'cancel') {
    $idRez = (int)($_POST['IdRezervare'] ?? 0);

    $stmt = $conn->prepare("
        UPDATE Rezervari
        SET Status='anulata'
        WHERE Id = ?
          AND IdAbonament IN (SELECT IdAbonament FROM AbonamenteClient WHERE IdClient = ?)
    ");
    $stmt->execute([$idRez, $userId]);

    flash_set("Rezervare anulată.", "success");
    header("Location: rezervarile_mele.php");
    exit;
}

$sql = "
SELECT r.Id, r.DataStart, r.DataEnd, r.Status,
       sp.Denumire AS Spatiu, sp.Oras,
       sa.Denumire AS Sala,
       b.Cod, b.PretOra,
       a.Tip AS Abonament
FROM Rezervari r
JOIN RezervareBirou rb ON rb.IdRezervare = r.Id
JOIN Birouri b ON b.Id = rb.IdBirou
JOIN Sali sa ON sa.Id = b.IdSala
JOIN Spatii sp ON sp.Id = sa.IdSpatiu
JOIN Abonamente a ON a.Id = r.IdAbonament
WHERE r.IdAbonament IN (SELECT IdAbonament FROM AbonamenteClient WHERE IdClient = ?)
ORDER BY r.DataStart DESC;
";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();
?>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="card-title mb-0">Rezervările mele</h5>
      <a class="btn btn-primary btn-sm" href="rezervare_noua.php">Rezervare nouă</a>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>ID</th><th>Spațiu</th><th>Birou</th><th>Abonament</th><th>Interval</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['Id'] ?></td>
              <td><?= e($r['Spatiu'].' ('.$r['Oras'].')') ?></td>
              <td><?= e($r['Sala'].' / '.$r['Cod'].' ('.$r['PretOra'].'/h)') ?></td>
              <td><?= e($r['Abonament']) ?></td>
              <td><?= e((string)$r['DataStart']) ?> → <?= e((string)$r['DataEnd']) ?></td>
              <td><?= e($r['Status']) ?></td>
              <td>
                <?php if ($r['Status'] !== 'anulata'): ?>
                  <form method="post" onsubmit="return confirm('Sigur anulezi?');">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="IdRezervare" value="<?= (int)$r['Id'] ?>">
                    <button class="btn btn-outline-danger btn-sm">Anulează</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="7" class="text-muted">Nu ai rezervări.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
