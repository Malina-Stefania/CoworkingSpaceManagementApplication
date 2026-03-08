<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

if (isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user && $pass) {
        $sql = "SELECT Id, Email, Rol
                FROM Utilizatori
                WHERE Email = ? AND Parola = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user, $pass]);
        $row = $stmt->fetch();

        if ($row) {
            $_SESSION['user_id']    = (int)$row['Id'];
            $_SESSION['user_email'] = $row['Email'];
            $_SESSION['user_role']  = $row['Rol'];
            flash_set("Autentificare reușită!", "success");
            header('Location: index.php');
            exit;
        } else {
            $error = "Utilizator sau parola incorecte!";
        }
    } else {
        $error = "Completează toate câmpurile!";
    }
}

$title = "Login";
require 'layout/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="mb-3 text-center">Autentificare</h4>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" class="vstack gap-2">
          <div>
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="username" required>
          </div>
          <div>
            <label class="form-label">Parola</label>
            <input class="form-control" type="password" name="password" required>
          </div>
          <button class="btn btn-primary w-100 mt-2">Login</button>
        </form>
        <div class="text-center mt-3">
          <a href="register.php">Creează cont nou</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>
