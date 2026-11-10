<?php
// Bagian atas halaman: <head>, navigasi, dan flash message.
// Variabel $page berasal dari index.php (untuk menandai menu aktif).
$flash = getFlash();
$navItems = [
    'home'      => 'Dashboard',
    'member'    => 'Anggota',
    'equipment' => 'Alat',
    'loan'      => 'Peminjaman',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LabLoan - Peminjaman Alat Laboratorium</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">LabLoan</div>
    <nav>
        <?php foreach ($navItems as $key => $label): ?>
            <a href="index.php?page=<?= e($key) ?>"
               class="<?= $page === $key ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
