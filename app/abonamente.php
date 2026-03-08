<?php
require_once 'helpers.php';
require_login();
require_once 'config.php';

$title = "Abonamente";
require 'layout/header.php';

$userId   = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? '';
$isClient = ($userRole === 'client');

/**
 * Calculează DataEnd pe server (siguranță), după tip.
 * Returnează string Y-m-d.
 */
function calc_end_date(string $tip, string $startYmd): string {
    $d = DateTime::createFromFormat('Y-m-d', $startYmd);
    if (!$d) return $startYmd;

    $tipNorm = mb_strtolower(trim($tip));

    // Weekend: până duminică (aceeași săptămână). Dacă e deja duminică, păstrăm aceeași zi.
    if ($tipNorm === 'weekend') {
        // ISO: 1=Luni ... 7=Duminică
        $dow = (int)$d->format('N');
        $add = 7 - $dow; // 0 dacă e duminică
        if ($add > 0) $d->modify("+$add day");
        return $d->format('Y-m-d');
    }

    // Default: +30 zile
    $d->modify('+30 day');
    return $d->format('Y-m-d');
}

if ($isClient && ($_POST['action'] ?? '') === 'buy') {
    $idAb  = (int)($_POST['IdAbonament'] ?? 0);
    $start = $_POST['DataStart'] ?? now_date();

    // luăm tipul abonamentului din DB (nu din form)
    $st = $conn->prepare("SELECT Tip FROM Abonamente WHERE Id=?");
    $st->execute([$idAb]);
    $row = $st->fetch();

    if (!$row) {
        flash_set("Abonamentul selectat nu există.", "warning");
        header("Location: abonamente.php");
        exit;
    }

    $tip = (string)$row['Tip'];
    $end = calc_end_date($tip, $start);

    // asigurăm DataEnd > DataStart (condiția din DB)
    // dacă Weekend e aceeași zi (duminică), facem end = start + 1 zi ca să respecte CHECK (DataEnd > DataStart)
    if (strtotime($end) <= strtotime($start)) {
        $d = DateTime::createFromFormat('Y-m-d', $start);
        $d->modify('+1 day');
        $end = $d->format('Y-m-d');
    }

    // închidem abonamentele active care s-ar suprapune (DataEnd >= start)
    $close = $conn->prepare("
        UPDATE AbonamenteClient
        SET DataEnd = DATEADD(day, -1, ?)
        WHERE IdClient = ? AND DataEnd >= ?
    ");
    $close->execute([$start, $userId, $start]);

    try {
        $ins = $conn->prepare("
            INSERT INTO AbonamenteClient (IdClient, IdAbonament, DataStart, DataEnd)
            VALUES (?, ?, ?, ?)
        ");
        $ins->execute([$userId, $idAb, $start, $end]);

        flash_set("Abonament activat: $tip ($start → $end).", "success");
    } catch (PDOException $e) {
        flash_set("Nu pot activa abonamentul. Verifică DataStart.", "warning");
    }

    header("Location: abonamente.php");
    exit;
}

$abon = $conn->query("SELECT Id, Tip, Pret, Ore, Descriere FROM Abonamente ORDER BY Pret")->fetchAll();

// abonament activ (doar client)
$abonActiv = null;
if ($isClient) {
    $stmt = $conn->prepare("
        SELECT TOP 1 a.Tip, a.Pret, a.Ore, ac.DataStart, ac.DataEnd
        FROM AbonamenteClient ac
        JOIN Abonamente a ON a.Id = ac.IdAbonament
        WHERE ac.IdClient = ? AND ac.DataEnd >= ?
        ORDER BY ac.DataEnd DESC
    ");
    $stmt->execute([$userId, now_date()]);
    $abonActiv = $stmt->fetch();
}

// statistică anonimă
$stats = $conn->query("
SELECT a.Tip,
       COUNT(DISTINCT ac.IdClient) AS NrClienti
FROM Abonamente a
LEFT JOIN AbonamenteClient ac ON ac.IdAbonament = a.Id
GROUP BY a.Tip
ORDER BY NrClienti DESC, a.Tip;
")->fetchAll();
?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Abonamente disponibile</h5>

        <?php if ($isClient && $abonActiv): ?>
          <div class="alert alert-info">
            <strong>Abonament activ:</strong> <?= e($abonActiv['Tip']) ?> (<?= e((string)$abonActiv['Ore']) ?> ore)<br>
            <small><?= e((string)$abonActiv['DataStart']) ?> → <?= e((string)$abonActiv['DataEnd']) ?></small>
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Tip</th><th>Preț</th><th>Ore</th><th>Descriere</th></tr></thead>
            <tbody>
              <?php foreach ($abon as $a): ?>
                <tr>
                  <td><?= e($a['Tip']) ?></td>
                  <td><?= e((string)$a['Pret']) ?></td>
                  <td><?= e((string)$a['Ore']) ?></td>
                  <td class="text-muted"><?= e((string)$a['Descriere']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$abon): ?><tr><td colspan="4" class="text-muted">Nu există abonamente.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($isClient): ?>
          <hr>
          <h6>Activează abonament</h6>

          <form method="post" class="row g-2" id="buyForm">
            <input type="hidden" name="action" value="buy">

            <div class="col-12">
              <label class="form-label">Abonament</label>
              <select class="form-select" name="IdAbonament" id="IdAbonament" required>
                <?php foreach ($abon as $a): ?>
                  <option value="<?= (int)$a['Id'] ?>" data-tip="<?= e($a['Tip']) ?>">
                    <?= e($a['Tip']) ?> (<?= e((string)$a['Pret']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6">
              <label class="form-label">Data start</label>
              <input class="form-control" type="date" name="DataStart" id="DataStart" value="<?= e(now_date()) ?>" required>
            </div>

            <div class="col-6">
              <label class="form-label">Data end (auto)</label>
              <input class="form-control" type="date" name="DataEndDisplay" id="DataEndDisplay" disabled>
              <div class="form-text">Se calculează automat în funcție de tip.</div>
            </div>

            <div class="col-12">
              <button class="btn btn-primary w-100">Activează</button>
            </div>
          </form>

          <script>
            function addDays(dateObj, days) {
              const d = new Date(dateObj);
              d.setDate(d.getDate() + days);
              return d;
            }
            function formatYmd(d) {
              const y = d.getFullYear();
              const m = String(d.getMonth() + 1).padStart(2, '0');
              const day = String(d.getDate()).padStart(2, '0');
              return `${y}-${m}-${day}`;
            }
            function calcEnd(tip, startYmd) {
              if (!startYmd) return '';
              const start = new Date(startYmd + 'T00:00:00');

              const tipNorm = (tip || '').trim().toLowerCase();
              if (tipNorm === 'weekend') {
                // luni=1 ... duminica=7
                let jsDay = start.getDay(); // duminica=0, luni=1...
                let iso = jsDay === 0 ? 7 : jsDay;
                let add = 7 - iso; // 0 dacă e duminică
                let end = addDays(start, add);
                // dacă e aceeași zi (duminică), facem +1 zi ca să fie > start
                if (formatYmd(end) === formatYmd(start)) {
                  end = addDays(end, 1);
                }
                return formatYmd(end);
              }

              // default: +30 zile
              return formatYmd(addDays(start, 30));
            }

            function updateEnd() {
              const sel = document.getElementById('IdAbonament');
              const opt = sel.options[sel.selectedIndex];
              const tip = opt.getAttribute('data-tip') || '';
              const start = document.getElementById('DataStart').value;

              const end = calcEnd(tip, start);
              document.getElementById('DataEndDisplay').value = end;
            }

            document.getElementById('IdAbonament').addEventListener('change', updateEnd);
            document.getElementById('DataStart').addEventListener('change', updateEnd);
            updateEnd();
          </script>

          <p class="text-muted small mt-2 mb-0">
            Confidențialitate: nu afișăm abonamentele altor utilizatori.
          </p>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Popularitate abonamente (anonim)</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Tip</th><th>Nr clienți (distinct)</th></tr></thead>
            <tbody>
              <?php foreach ($stats as $s): ?>
                <tr>
                  <td><?= e($s['Tip']) ?></td>
                  <td><?= e((string)$s['NrClienti']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$stats): ?><tr><td colspan="2" class="text-muted">Nu există date.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="text-muted small mb-0">COUNT DISTINCT – fără nume/email.</p>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
