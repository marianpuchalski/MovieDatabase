<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

$pdo = get_db();
$errors = [];

$formData = [
    'id' => 0,
    'first_name' => '',
    'last_name' => '',
    'birth_date' => '',
    'country' => '',
    'biography' => '',
    'photo_url' => '',
];
$isEditMode = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $actorId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($actorId > 0) {
            $stmt = $pdo->prepare('DELETE FROM actors WHERE id = :id');
            $stmt->execute([':id' => $actorId]);
            set_flash('success', 'Aktor został usunięty.');
        }
        redirect('admin/actors.php');
    }

    if ($action === 'create' || $action === 'update') {
        $isEditMode = ($action === 'update');
        $actorId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $birthDateRaw = trim((string) ($_POST['birth_date'] ?? ''));
        $countryRaw = trim((string) ($_POST['country'] ?? ''));
        $biographyRaw = trim((string) ($_POST['biography'] ?? ''));
        $photoUrlRaw = (string) ($_POST['photo_url'] ?? '');

        $formData = [
            'id' => $actorId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $birthDateRaw,
            'country' => $countryRaw,
            'biography' => $biographyRaw,
            'photo_url' => trim($photoUrlRaw),
        ];

        if ($firstName === '' || mb_strlen($firstName) > 100) {
            $errors[] = 'Imię jest wymagane i nie może przekraczać 100 znaków.';
        }
        if ($lastName === '' || mb_strlen($lastName) > 100) {
            $errors[] = 'Nazwisko jest wymagane i nie może przekraczać 100 znaków.';
        }

        $birthDate = null;
        if ($birthDateRaw !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $birthDateRaw);
            $dtErrors = DateTime::getLastErrors();
            if (!$dt || ($dtErrors !== false && ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0))) {
                $errors[] = 'Podaj poprawną datę urodzenia w formacie RRRR-MM-DD.';
            } else {
                $birthDate = $dt->format('Y-m-d');
            }
        }

        if ($countryRaw !== '' && mb_strlen($countryRaw) > 100) {
            $errors[] = 'Nazwa kraju nie może przekraczać 100 znaków.';
        }

        $urlError = null;
        $photoUrl = validate_image_url($photoUrlRaw, $urlError);
        if ($urlError !== null) {
            $errors[] = $urlError;
        }

        $country = $countryRaw === '' ? null : $countryRaw;
        $biography = $biographyRaw === '' ? null : $biographyRaw;

        if ($errors === []) {
            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO actors (first_name, last_name, birth_date, country, biography, photo_url)
                     VALUES (:first_name, :last_name, :birth_date, :country, :biography, :photo_url)'
                );
                $stmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':birth_date' => $birthDate,
                    ':country' => $country,
                    ':biography' => $biography,
                    ':photo_url' => $photoUrl,
                ]);
                set_flash('success', 'Dodano nowego aktora.');
            } else {
                if ($actorId <= 0) {
                    set_flash('error', 'Nieprawidłowy identyfikator aktora.');
                    redirect('admin/actors.php');
                }
                $stmt = $pdo->prepare(
                    'UPDATE actors
                     SET first_name = :first_name, last_name = :last_name, birth_date = :birth_date,
                         country = :country, biography = :biography, photo_url = :photo_url
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':birth_date' => $birthDate,
                    ':country' => $country,
                    ':biography' => $biography,
                    ':photo_url' => $photoUrl,
                    ':id' => $actorId,
                ]);
                set_flash('success', 'Zaktualizowano dane aktora.');
            }
            redirect('admin/actors.php');
        }
    }
} elseif (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM actors WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $editId]);
        $row = $stmt->fetch();
        if ($row) {
            $isEditMode = true;
            $formData = [
                'id' => (int) $row['id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'birth_date' => (string) ($row['birth_date'] ?? ''),
                'country' => (string) ($row['country'] ?? ''),
                'biography' => (string) ($row['biography'] ?? ''),
                'photo_url' => (string) ($row['photo_url'] ?? ''),
            ];
        }
    }
}

$actors = $pdo->query(
    'SELECT id, first_name, last_name, birth_date, country, photo_url
     FROM actors
     ORDER BY last_name ASC, first_name ASC'
)->fetchAll();

$pageTitle = 'Zarządzanie aktorami — Panel Administratora';
$extraScripts = ['assets/js/preview.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Zarządzanie aktorami</h1>
    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Powrót do pulpitu</a></p>

    <?php if ($errors !== []): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('admin/actors.php')) ?>" class="admin-form js-validate-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $isEditMode ? 'update' : 'create' ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <?php endif; ?>

        <h2><?= $isEditMode ? 'Edytuj aktora' : 'Dodaj nowego aktora' ?></h2>

        <div class="form-row">
            <div class="form-group">
                <label for="actor-first-name">Imię *</label>
                <input type="text" id="actor-first-name" name="first_name" value="<?= e($formData['first_name']) ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="actor-last-name">Nazwisko *</label>
                <input type="text" id="actor-last-name" name="last_name" value="<?= e($formData['last_name']) ?>" required maxlength="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="actor-birth-date">Data urodzenia (opcjonalna)</label>
                <input type="date" id="actor-birth-date" name="birth_date" value="<?= e($formData['birth_date']) ?>">
            </div>

            <div class="form-group">
                <label for="actor-country">Kraj pochodzenia (opcjonalny)</label>
                <input type="text" id="actor-country" name="country" value="<?= e($formData['country']) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-group">
            <label for="actor-photo-url">Adres URL zdjęcia (opcjonalny, http/https)</label>
            <input
                type="url"
                id="actor-photo-url"
                name="photo_url"
                value="<?= e($formData['photo_url']) ?>"
                maxlength="500"
                placeholder="https://example.com/aktor.jpg"
                class="js-url-preview-input"
                data-preview-target="actor-preview"
                data-default-src="<?= e(base_url('assets/img/default-actor.png')) ?>"
            >
            <div class="url-preview-box">
                <img
                    id="actor-preview"
                    src="<?= e(actor_photo_url($formData['photo_url'])) ?>"
                    alt="Podgląd zdjęcia aktora"
                    class="preview-actor"
                >
            </div>
        </div>

        <div class="form-group">
            <label for="actor-biography">Biografia (opcjonalna)</label>
            <textarea id="actor-biography" name="biography" rows="4"><?= e($formData['biography']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEditMode ? 'Zapisz zmiany' : 'Dodaj aktora' ?></button>
        <?php if ($isEditMode): ?>
            <a href="<?= e(base_url('admin/actors.php')) ?>" class="btn btn-secondary">Anuluj</a>
        <?php endif; ?>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię i nazwisko</th>
                <th>Data urodzenia</th>
                <th>Kraj</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($actors as $a): ?>
                <tr>
                    <td><?= (int) $a['id'] ?></td>
                    <td>
                        <a href="<?= e(base_url('actor.php?id=' . (int) $a['id'])) ?>">
                            <?= e($a['first_name'] . ' ' . $a['last_name']) ?>
                        </a>
                    </td>
                    <td><?= !empty($a['birth_date']) ? e(date('d.m.Y', strtotime($a['birth_date']))) : '—' ?></td>
                    <td><?= !empty($a['country']) ? e($a['country']) : '—' ?></td>
                    <td class="table-actions">
                        <a href="<?= e(base_url('admin/actors.php?edit=' . (int) $a['id'])) ?>" class="btn btn-secondary">Edytuj</a>
                        <form method="post" action="<?= e(base_url('admin/actors.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz usunąć aktora <?= e($a['first_name'] . ' ' . $a['last_name']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button type="submit" class="btn btn-danger">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
