<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('profile.php');
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        $errors[] = 'Nazwa użytkownika musi mieć od 3 do 50 znaków.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $errors[] = 'Nazwa użytkownika może zawierać tylko litery, cyfry oraz znaki _ . -';
    }

    if ($email === '' || mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Podaj poprawny adres e-mail.';
    }

    if (mb_strlen($password) < 6) {
        $errors[] = 'Hasło musi mieć co najmniej 6 znaków.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Hasło i potwierdzenie hasła muszą być identyczne.';
    }

    if ($errors === []) {
        $pdo = get_db();

        $checkStmt = $pdo->prepare('SELECT id, username, email FROM users WHERE username = :username OR email = :email LIMIT 1');
        $checkStmt->execute([
            ':username' => $username,
            ':email' => $email,
        ]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if (strcasecmp($existing['username'], $username) === 0) {
                $errors[] = 'Podana nazwa użytkownika jest już zajęta.';
            }
            if (strcasecmp($existing['email'], $email) === 0) {
                $errors[] = 'Konto o podanym adresie e-mail już istnieje.';
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare(
                "INSERT INTO users (username, email, password_hash, role, is_blocked)
                 VALUES (:username, :email, :password_hash, 'user', 0)"
            );
            $insertStmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $hash,
            ]);

            set_flash('success', 'Rejestracja przebiegła pomyślnie. Możesz się teraz zalogować.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Rejestracja — Baza filmów';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <h1>Rejestracja</h1>

    <?php if ($errors !== []): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('register.php')) ?>" class="auth-form js-validate-form">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="reg-username">Nazwa użytkownika (login) *</label>
            <input type="text" id="reg-username" name="username" value="<?= e($username) ?>" required minlength="3" maxlength="50">
        </div>

        <div class="form-group">
            <label for="reg-email">Adres e-mail *</label>
            <input type="email" id="reg-email" name="email" value="<?= e($email) ?>" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="reg-password">Hasło (min. 6 znaków) *</label>
            <input type="password" id="reg-password" name="password" required minlength="6">
        </div>

        <div class="form-group">
            <label for="reg-password-confirm">Potwierdź hasło *</label>
            <input type="password" id="reg-password-confirm" name="password_confirm" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary">Utwórz konto</button>
    </form>

    <p class="auth-switch">Masz już konto? <a href="<?= e(base_url('login.php')) ?>">Zaloguj się</a>.</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
