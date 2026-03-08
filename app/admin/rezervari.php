<?php
$in_admin = true;
require_once '../helpers.php';
require_admin();
require_once '../config.php';

$title = "Admin - Rezervări (spațiile mele)";
require '../layout/header.php';

$adminId = (int)($_SESSION['user_id'] ?? 0);

// update status (doar rezervări care aparțin spațiilor lui)
if (($_POST['action'] ?? '') === 'set_status') {
    $idRez = (int)($_POST['IdRezervare'] ?? 0);
    $newStatus = $_POST['Status'] ?? 'asteptare';

    $stmt = $conn->prepare("
        UPDATE Rezervari
        SET Status = ?
        WHERE Id = ?
          AND EXISTS (
            SELECT 1
            FROM RezervareBirou rb
            JOIN Birouri b ON b.Id = rb.IdBirou
            JOIN Sali sa ON sa.Id = b.IdSala
            JOIN Spatii sp ON sp.Id = sa.IdSpatiu
            WHERE rb.IdRezervare = Rezervari.Id
              AND sp.IdAdmin = ?
          )
    ");
    $stmt->execute([$newStatus, $idRez, $adminId]);

    // la confirmare: creează o plată în asteptare dacă nu există
    if ($newStatus === 'confirmata') {
        $exists = $conn->prepare("SELECT COUNT(*) AS Cnt FROM Plati WHERE IdRezervare=?");
        $exists->execute([$idRez]);
        $cnt = (int)($exists->fetch()['Cnt'] ?? 0);

        if ($cnt === 0) {
            $calc = $conn->prepare("
                SELECT TOP 1
                    (b.PretOra * (DATEDIFF(minute, r.DataStart, r.DataEnd) / 60.0)) AS SumaCalc
                FROM Rezervari r
                JOIN RezervareBirou rb ON rb.IdRezervare = r.Id
                JOIN Birouri b ON b.Id = rb.IdBirou
                JOIN Sali sa ON sa.Id = b.IdSala
                JOIN Spatii sp ON sp.Id = sa.IdSpatiu
                WHERE r.Id = ? AND sp.IdAdmin = ?
            ");
            $calc->execute([$idRez, $adminId]);
            $suma = (float)($calc->fetch()['SumaCalc'] ?? 0);

            $ins = $conn->prepare("
                INSERT INTO Plati (IdRezervare, Suma, Data, Metoda, Status)
                VALUES (?, ?, ?, 'card', 'asteptare')
            ");
            $ins->execute([$idRez, $suma, date('Y-m-d')]);
        }
    }

    flash_set("Status actualizat.", "success");
    header("Location: rezervari.php");
    exit;
}

// filtre simple (parametru variabil) – fără să complicăm
$qSpatiu = trim($_GET['spatiu'] ?? '');
$qStatus = trim($_GET['status'] ?? '');

$spatiuLike = $qSpatiu . '%';

$sql = "
SELECT r.Id, r.DataStart, r.DataEnd, r.Status,
       sp.Denumire AS Spatiu, sp.Oras,
       sa.Denumire AS Sala, b.Cod,
       u.Email, (u.Nume + ' ' + u.Prenume) AS Client
FROM Rezervari r
JOIN RezervareBirou rb ON rb.IdRezervare = r.Id
JOIN Birouri b ON b.Id = rb.IdBirou
JOIN Sali sa ON sa.Id = b.IdSala
JOIN Spatii sp ON sp.Id = sa.IdSpatiu

-- deducem clientul din abonamente (schema ta)
JOIN AbonamenteClient ac ON ac.IdAbonament = r.IdAbonament
JOIN Utilizatori u ON u.Id = ac.IdClient

WHERE sp.IdAdmin = ?
  AND (? = '' OR sp.Denumire LIKE ?)
  AND (? = '' OR r.Status = ?)
ORDER BY r.DataStart DESC;
";
$stmt = $conn->prepare($sql);
$stmt->execute([$adminId, $qSpatiu, $spatiuLike, $qStatus, $qStatus]);
$rows = $stmt->fetchAll();
?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h5 class="card-title">Filtre</h5>
    <form method="get" class="row g-2">
      <div class="col-md-6">
        <label class="form-label">Spațiu (începe cu)</label>
        <input class="form-control" name="spatiu" value="<?= e($qSpatiu) ?>" placeholder="ex: Spatiu Central">
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="" <?= $qStatus===''?'selected':'' ?>>Toate</option>
          <?php foreach (['asteptare','confirmata','anulata'] as $s): ?>
            <option value="<?= e($s) ?>" <?= $qStatus===$s?'selected':'' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100">Aplică</button>
      </div>
      <div class="text-muted small">Parametri: spațiu, status. Admin vede doar spațiile lui.</div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title">Rezervări (spațiile mele)</h5>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>ID</th><th>Spațiu</th><th>Birou</th><th>Client</th><th>Interval</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['Id'] ?></td>
              <td><?= e($r['Spatiu'].' ('.$r['Oras'].')') ?></td>
              <td><?= e($r['Sala'].' / '.$r['Cod']) ?></td>
              <td><?= e($r['Client'].' ('.$r['Email'].')') ?></td>
              <td><?= e((string)$r['DataStart']) ?> → <?= e((string)$r['DataEnd']) ?></td>
              <td><?= e($r['Status']) ?></td>
              <td style="min-width:210px">
                <form method="post" class="d-flex gap-1">
                  <input type="hidden" name="action" value="set_status">
                  <input type="hidden" name="IdRezervare" value="<?= (int)$r['Id'] ?>">
                  <select class="form-select form-select-sm" name="Status">
                    <?php foreach (['asteptare','confirmata','anulata'] as $s): ?>
                      <option value="<?= e($s) ?>" <?= $r['Status']===$s?'selected':'' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-success btn-sm">Salvează</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="7" class="text-muted">Nu există rezervări pentru spațiile tale.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <p class="text-muted small mb-0">
      Date personale (client) sunt afișate doar pentru cei care au rezervat spațiile administrate de tine.
    </p>
  </div>
</div>

<?php require '../layout/footer.php'; ?>
