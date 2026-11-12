<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('error', 'Nieprawidłowa metoda żądania.');
    redirect('index.php');
}

require_valid_csrf();
$user = require_login();

$action = (string) ($_POST['action'] ?? '');
$movieId = filter_input(INPUT_POST, 'movie_id', FILTER_VALIDATE_INT) ?: 0;
$reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT) ?: 0;
$redirectTo = (string) ($_POST['redirect_to'] ?? 'movie');

$targetUrl = ($redirectTo === 'profile')
    ? 'profile.php'
    : ($movieId > 0 ? 'movie.php?id=' . $movieId . '#reviews' : 'index.php');

$pdo = get_db();

if ($action === 'add') {
    if ($movieId <= 0) {
        set_flash('error', 'Nieprawidłowy identyfikator filmu.');
        redirect('index.php');
    }

    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    if ($rating === false || $rating === null || $rating < 1 || $rating > 5) {
        set_flash('error', 'Ocena musi być liczbą całkowitą w skali od 1 do 5.');
        redirect($targetUrl);
    }

    $rawComment = trim((string) ($_POST['comment'] ?? ''));
    if (mb_strlen($rawComment) > 2000) {
        set_flash('error', 'Treść recenzji nie może przekraczać 2000 znaków.');
        redirect($targetUrl);
    }
    $comment = $rawComment === '' ? null : $rawComment;

    // Sprawdzenie czy film istnieje
    $movieCheck = $pdo->prepare('SELECT id FROM movies WHERE id = :id LIMIT 1');
    $movieCheck->execute([':id' => $movieId]);
    if (!$movieCheck->fetch()) {
        set_flash('error', 'Wybrany film nie istnieje.');
        redirect('index.php');
    }

    // Sprawdzenie limitu 1 recenzji na film od użytkownika
    $dupCheck = $pdo->prepare('SELECT id FROM reviews WHERE movie_id = :movie_id AND user_id = :user_id LIMIT 1');
    $dupCheck->execute([
        ':movie_id' => $movieId,
        ':user_id' => (int) $user['id'],
    ]);
    if ($dupCheck->fetch()) {
        set_flash('error', 'Możesz dodać tylko jedną recenzję do danego filmu. Edytuj istniejącą ocenę.');
        redirect($targetUrl);
    }

    try {
        $insertStmt = $pdo->prepare(
            'INSERT INTO reviews (movie_id, user_id, rating, comment)
             VALUES (:movie_id, :user_id, :rating, :comment)'
        );
        $insertStmt->execute([
            ':movie_id' => $movieId,
            ':user_id' => (int) $user['id'],
            ':rating' => $rating,
            ':comment' => $comment,
        ]);
        set_flash('success', 'Twoja ocena została dodana.');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            set_flash('error', 'Masz już wystawioną ocenę dla tego filmu.');
        } else {
            error_log('Błąd dodawania recenzji: ' . $e->getMessage());
            set_flash('error', 'Nie udało się zapisać recenzji.');
        }
    }

    redirect($targetUrl);
}

if ($action === 'edit') {
    if ($reviewId <= 0 || $movieId <= 0) {
        set_flash('error', 'Nieprawidłowe dane recenzji.');
        redirect($targetUrl);
    }

    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    if ($rating === false || $rating === null || $rating < 1 || $rating > 5) {
        set_flash('error', 'Ocena musi być liczbą całkowitą w skali od 1 do 5.');
        redirect($targetUrl);
    }

    $rawComment = trim((string) ($_POST['comment'] ?? ''));
    if (mb_strlen($rawComment) > 2000) {
        set_flash('error', 'Treść recenzji nie może przekraczać 2000 znaków.');
        redirect($targetUrl);
    }
    $comment = $rawComment === '' ? null : $rawComment;

    $updateStmt = $pdo->prepare(
        'UPDATE reviews
         SET rating = :rating, comment = :comment
         WHERE id = :id AND user_id = :user_id'
    );
    $updateStmt->execute([
        ':rating' => $rating,
        ':comment' => $comment,
        ':id' => $reviewId,
        ':user_id' => (int) $user['id'],
    ]);

    if ($updateStmt->rowCount() === 0) {
        // Weryfikacja czy recenzja istnieje, czy nie dokonano zmian w treści
        $ownerStmt = $pdo->prepare('SELECT user_id FROM reviews WHERE id = :id LIMIT 1');
        $ownerStmt->execute([':id' => $reviewId]);
        $row = $ownerStmt->fetch();
        if (!$row || (int) $row['user_id'] !== (int) $user['id']) {
            set_flash('error', 'Nie masz uprawnień do edycji tej recenzji.');
            redirect($targetUrl);
        }
    }

    set_flash('success', 'Recenzja została zaktualizowana.');
    redirect($targetUrl);
}

if ($action === 'delete') {
    if ($reviewId <= 0) {
        set_flash('error', 'Nieprawidłowy identyfikator recenzji.');
        redirect($targetUrl);
    }

    $deleteStmt = $pdo->prepare('DELETE FROM reviews WHERE id = :id AND user_id = :user_id');
    $deleteStmt->execute([
        ':id' => $reviewId,
        ':user_id' => (int) $user['id'],
    ]);

    if ($deleteStmt->rowCount() > 0) {
        set_flash('success', 'Twoja recenzja została usunięta.');
    } else {
        set_flash('error', 'Nie znaleziono recenzji lub brak uprawnień do jej usunięcia.');
    }

    redirect($targetUrl);
}

set_flash('error', 'Nieznana akcja.');
redirect($targetUrl);
