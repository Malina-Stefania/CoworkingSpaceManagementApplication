<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

if (isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nume    = trim($_POST['Nume'] ?? '');
    $prenume = trim($_POST['Prenume'] ?? '');
    $email   = trim($_POST['Email'] ?? '');
    $telefon = trim($_POST['Telefon'] ?? '');
    $parola  = trim($_POST['Parola'] ?? '');
    $parola2 = trim($_POST['Parola2'] ?? '');

    if (!$nume || !$prenume || !$email || !$parola) {
        $err = "Completează câmpurile obligatorii.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Email invalid.";
    } elseif ($parola !== $parola2) {
        $err = "Parolele nu coincid.";
    } else {
        try {
            $sql = "INSERT INTO Utilizatori (Nume, Prenume, Email, Telefon, Rol, Parola, DataReg)
                    VALUES (?, ?, ?, ?, 'client', ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nume, $prenume, $email, $telefon ?: null, $parola, now_date()]);

            flash_set("Cont creat. Te poți autentifica.", "success");
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $err = "Nu pot crea contul. Verifică dacă email-ul există deja.";
        }
    }
}

$title = "Creează cont";
require 'layout/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-7 col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="mb-3">Creează cont (client)</h4>
        <?php if ($err): ?>
          <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endif; ?>

        <form method="post" class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nume *</label>
            <input class="form-control" name="Nume" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Prenume *</label>
            <input class="form-control" name="Prenume" required>
          </div>
          <div class="col-md-7">
            <label class="form-label">Email *</label>
            <input class="form-control" type="email" name="Email" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Telefon</label>
            <input class="form-control" name="Telefon">
          </div>
          <div class="col-md-6">
            <label class="form-label">Parola *</label>
            <input class="form-control" type="password" name="Parola" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Repetă parola *</label>
            <input class="form-control" type="password" name="Parola2" required>
          </div>

          <div class="col-12 mt-2">
            <button class="btn btn-primary">Creează cont</button>
            <a class="btn btn-outline-secondary ms-2" href="login.php">Înapoi la login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
