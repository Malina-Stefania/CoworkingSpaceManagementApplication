<?php
// DEBUG local (poți comenta după ce merge)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'helpers.php';
require_login();
require_once 'config.php';

$title = "Rezervare nouă";
require 'layout/header.php';

$userId   = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? '';
$isClient = ($userRole === 'client');

if (!$isClient) {
    echo "<div class='alert alert-warning'>Doar clienții pot crea rezervări.</div>";
    require 'layout/footer.php';
    exit;
}

$err = '';
$success = '';

function normalize_dt(?string $s): ?string {
    if (!$s) return null;
    $s = trim($s);
    // datetime-local -> SQL Server
    $s = str_replace('T', ' ', $s);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $s)) {
        $s .= ':00';
    }
    return $s;
}

// 1) Abonamente active ale clientului
$abonRows = [];
try {
    $stmt = $conn->prepare("
        SELECT ac.IdAbonament, a.Tip, ac.DataStart, ac.DataEnd, a.Ore
        FROM AbonamenteClient ac
        JOIN Abonamente a ON a.Id = ac.IdAbonament
        WHERE ac.IdClient = ? AND ac.DataEnd >= ?
        ORDER BY ac.DataEnd DESC
    ");
    $stmt->execute([$userId, now_date()]);
    $abonRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $err = "Eroare la încărcarea abonamentelor: " . htmlspecialchars($e->getMessage());
}

// 2) Birouri
$birouri = [];
try {
    $birouri = $conn->query("
        SELECT b.Id AS IdBirou, b.Cod, b.PretOra,
               sa.Denumire AS Sala, sa.Tip,
               sp.Denumire AS Spatiu, sp.Oras
        FROM Birouri b
        JOIN Sali sa ON sa.Id = b.IdSala
        JOIN Spatii sp ON sp.Id = sa.IdSpatiu
        ORDER BY sp.Oras, sp.Denumire, sa.Denumire, b.Cod
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $err = "Eroare la încărcarea birourilor: " . htmlspecialchars($e->getMessage());
}

$old = [
    'IdAbonament' => $_POST['IdAbonament'] ?? ($abonRows[0]['IdAbonament'] ?? ''),
    'IdBirou'     => $_POST['IdBirou'] ?? ($birouri[0]['IdBirou'] ?? ''),
    'DataStart'   => $_POST['DataStart'] ?? now_datetime_local(),
    'DataEnd'     => $_POST['DataEnd'] ?? now_datetime_local(),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$err) {
    $idBirou = (int)($_POST['IdBirou'] ?? 0);
    $idAbon  = (int)($_POST['IdAbonament'] ?? 0);
    $start   = normalize_dt($_POST['DataStart'] ?? '');
    $end     = normalize_dt($_POST['DataEnd'] ?? '');

    if (!$idBirou || !$idAbon || !$start || !$end) {
        $err = "Completează toate câmpurile.";
    } else {
        // Validare DateTime
        try {
            $dtStart = new DateTime($start);
            $dtEnd   = new DateTime($end);
        } catch (Exception $e) {
            $err = "Format dată/oră invalid.";
        }

        if (!$err && $dtEnd <= $dtStart) {
            $err = "DataEnd trebuie să fie după DataStart.";
        }

        // Abonamentul ales trebuie să fie al clientului și activ
        $abInfo = null;
        if (!$err) {
            $st = $conn->prepare("
                SELECT TOP 1 ac.DataStart, ac.DataEnd, a.Ore, a.Tip
                FROM AbonamenteClient ac
                JOIN Abonamente a ON a.Id = ac.IdAbonament
                WHERE ac.IdClient = ?
                  AND ac.IdAbonament = ?
                  AND ac.DataEnd >= ?
                ORDER BY ac.DataEnd DESC
            ");
            $st->execute([$userId, $idAbon, now_date()]);
            $abInfo = $st->fetch(PDO::FETCH_ASSOC);

            if (!$abInfo) {
                $err = "Abonamentul ales nu este activ sau nu îți aparține.";
            }
        }

        // Intervalul rezervării trebuie să fie în perioada abonamentului
        if (!$err && $abInfo) {
            $abStart = new DateTime($abInfo['DataStart'] . ' 00:00:00');
            $abEnd   = new DateTime($abInfo['DataEnd'] . ' 23:59:59');

            if ($dtStart < $abStart || $dtEnd > $abEnd) {
                $err = "Rezervarea trebuie să fie în perioada abonamentului (" .
                       htmlspecialchars($abInfo['DataStart']) . " → " . htmlspecialchars($abInfo['DataEnd']) . ").";
            }
        }

        // Verificare suprapunere pe același birou
        if (!$err) {
            $check = $conn->prepare("
                SELECT COUNT(*) AS Cnt
                FROM RezervareBirou rb
                JOIN Rezervari r ON r.Id = rb.IdRezervare
                WHERE rb.IdBirou = ?
                  AND r.Status <> 'anulata'
                  AND (? < r.DataEnd AND ? > r.DataStart)
            ");
            $check->execute([$idBirou, $start, $end]);
            $cnt = (int)($check->fetch(PDO::FETCH_ASSOC)['Cnt'] ?? 0);

            if ($cnt > 0) {
                $err = "Biroul este deja rezervat în intervalul ales. Alege alt interval.";
            }
        }

        // Verificare ore disponibile în abonament (folosite + noi <= Ore)
        if (!$err && $abInfo) {
            $durMinutes = (int)(($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 60);
            if ($durMinutes <= 0) {
                $err = "Durata rezervării trebuie să fie pozitivă.";
            } else {
                // total minute folosite pe acest abonament (neanulate) ÎN PERIOADA abonamentului
                $sum = $conn->prepare("
                    SELECT COALESCE(SUM(DATEDIFF(minute, r.DataStart, r.DataEnd)), 0) AS MinTotal
                    FROM Rezervari r
                    WHERE r.IdAbonament = ?
                      AND r.Status <> 'anulata'
                      AND r.DataStart >= ?
                      AND r.DataEnd <= ?
                ");
                $sum->execute([
                    $idAbon,
                    $abInfo['DataStart'] . " 00:00:00",
                    $abInfo['DataEnd'] . " 23:59:59"
                ]);
                $minTotal = (int)($sum->fetch(PDO::FETCH_ASSOC)['MinTotal'] ?? 0);

                $oreDisponibile = (int)$abInfo['Ore'];
                $oreFolosite    = $minTotal / 60.0;
                $oreNoi         = $durMinutes / 60.0;

                if (($oreFolosite + $oreNoi) - $oreDisponibile > 1e-9) {
                    $err = "Nu ai suficiente ore în abonamentul „" . htmlspecialchars($abInfo['Tip']) . "”. " .
                           "Disponibile: {$oreDisponibile} ore. " .
                           "Folosite (aprox.): " . number_format($oreFolosite, 2) . " ore. " .
                           "Această rezervare: " . number_format($oreNoi, 2) . " ore.";
                }
            }
        }

        // INSERT Rezervari + RezervareBirou (cu OUTPUT INSERTED.Id)
        if (!$err) {
            try {
                $conn->beginTransaction();

                $insR = $conn->prepare("
                    INSERT INTO Rezervari (IdAbonament, DataStart, DataEnd, Status)
                    OUTPUT INSERTED.Id
                    VALUES (?, ?, ?, 'asteptare')
                ");
                $insR->execute([$idAbon, $start, $end]);
                $rid = (int)$insR->fetchColumn();

                if ($rid <= 0) {
                    throw new Exception("Nu am putut obține ID-ul rezervării.");
                }

                $insRB = $conn->prepare("
                    INSERT INTO RezervareBirou (IdRezervare, IdBirou)
                    VALUES (?, ?)
                ");
                $insRB->execute([$rid, $idBirou]);

                $conn->commit();

                $success = "Rezervarea a fost creată (status: așteptare).";
                // opțional redirect:
                // header("Location: rezervarile_mele.php"); exit;
            } catch (Throwable $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $err = "Eroare la salvare rezervare: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Creează rezervare</h5>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($err): ?>
          <div class="alert alert-danger"><?= $err ?></div>
        <?php endif; ?>

        <?php if (!$abonRows): ?>
          <div class="alert alert-warning">
            Nu ai abonament activ. Activează unul din <a href="abonamente.php">Abonamente</a>.
          </div>
        <?php endif; ?>

        <form method="post" class="vstack gap-2">
          <div>
            <label class="form-label">Abonament (activ)</label>
            <select class="form-select" name="IdAbonament" required <?= !$abonRows ? 'disabled' : '' ?>>
              <?php foreach ($abonRows as $a): ?>
                <option value="<?= (int)$a['IdAbonament'] ?>"
                  <?= ((string)$old['IdAbonament'] === (string)$a['IdAbonament']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a['Tip']) ?> — <?= (int)$a['Ore'] ?> ore (<?= htmlspecialchars($a['DataStart']) ?> → <?= htmlspecialchars($a['DataEnd']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="form-label">Birou</label>
            <select class="form-select" name="IdBirou" required>
              <?php foreach ($birouri as $b): ?>
                <option value="<?= (int)$b['IdBirou'] ?>"
                  <?= ((string)$old['IdBirou'] === (string)$b['IdBirou']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($b['Spatiu'] . " (" . $b['Oras'] . ") / " . $b['Sala'] . " / " . $b['Cod'] . " - " . $b['PretOra'] . " lei/oră") ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2">
            <div class="col">
              <label class="form-label">Data start</label>
              <input class="form-control" type="datetime-local" name="DataStart" value="<?= htmlspecialchars($old['DataStart']) ?>" required>
            </div>
            <div class="col">
              <label class="form-label">Data end</label>
              <input class="form-control" type="datetime-local" name="DataEnd" value="<?= htmlspecialchars($old['DataEnd']) ?>" required>
            </div>
          </div>

          <button class="btn btn-primary" <?= !$abonRows ? 'disabled' : '' ?>>
            Trimite rezervarea
          </button>

          <p class="text-muted small mb-0">
            Validări: perioadă abonament + ore disponibile + fără suprapuneri pe birou.
          </p>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Birouri</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Spațiu</th><th>Oraș</th><th>Sală</th><th>Tip</th><th>Cod</th><th>Preț/oră</th></tr></thead>
            <tbody>
              <?php foreach ($birouri as $b): ?>
                <tr>
                  <td><?= htmlspecialchars($b['Spatiu']) ?></td>
                  <td><?= htmlspecialchars($b['Oras']) ?></td>
                  <td><?= htmlspecialchars($b['Sala']) ?></td>
                  <td><?= htmlspecialchars($b['Tip']) ?></td>
                  <td><?= htmlspecialchars($b['Cod']) ?></td>
                  <td><?= htmlspecialchars((string)$b['PretOra']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$birouri): ?><tr><td colspan="6" class="text-muted">Nu există birouri.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
