
<?php
/**
 * Wspólna stopka aplikacji „Baza filmów”.
 * Oczekiwane zmienne opcjonalne:
 * - $basePath (string)
 * - $extraScripts (array: ścieżki względne do skryptów JS)
 */

$basePath = $basePath ?? (function_exists('base_url') ? base_url('') : '');
$extraScripts = $extraScripts ?? [];
?>
    </div>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <span class="brand-badge">BF</span>
            <p class="footer-copy">
                Baza filmów &copy; <?= date('Y') ?>. Katalog filmów, aktorów i recenzji społeczności.
            </p>
        </div>
        <nav class="footer-nav" aria-label="Linki w stopce">
            <a href="<?= e($basePath) ?>index.php" class="footer-link">Katalog filmów</a>
            <a href="<?= e($basePath) ?>actors.php" class="footer-link">Katalog aktorów</a>
        </nav>
    </div>
</footer>

<script src="<?= e($basePath) ?>assets/js/main.js" defer></script>
<?php foreach ($extraScripts as $scriptPath): ?>
    <script src="<?= e($basePath . ltrim((string) $scriptPath, '/')) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>