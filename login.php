<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$loginInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $loginInput = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($loginInput === '' || $password === '') {
        $errors[] = 'Podaj login (lub adres e-mail) oraz hasło.';
    } else {
        $stmt = get_db()->prepare(
            'SELECT id, username, email, password_hash, role, is_blocked
             FROM users
             WHERE username = :u OR email = :e
             LIMIT 1'
        );
        $stmt->execute([
            ':u' => $loginInput,
            ':e' => $loginInput,
        ]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Nieprawidłowy login lub hasło.';
        } elseif ((int) $user['is_blocked'] === 1) {
            $errors[] = 'Twoje konto zostało zablokowane i nie może się zalogować.';
        } else {
            login_user($user);
            set_flash('success', 'Zalogowano pomyślnie. Witaj, ' . $user['username'] . '!');
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'index.php');
        }
    }
}

$pageTitle = 'Logowanie — Baza filmów';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <h1>Logowanie</h1>

    <?php if ($errors !== []): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('login.php')) ?>" class="auth-form js-validate-form">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="login-input">Nazwa użytkownika lub e-mail *</label>
            <input type="text" id="login-input" name="login" value="<?= e($loginInput) ?>" required>
        </div>

        <div class="form-group">
            <label for="login-password">Hasło *</label>
            <input type="password" id="login-password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary">Zaloguj się</button>
    </form>

    <p class="auth-switch">Nie masz konta? <a href="<?= e(base_url('register.php')) ?>">Zarejestruj się</a>.</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
