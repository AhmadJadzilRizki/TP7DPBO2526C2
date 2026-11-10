<?php
// Halaman dashboard: ringkasan jumlah data dari ketiga class.
$memberObj    = new Member($db);
$equipmentObj = new Equipment($db);
$loanObj      = new Loan($db);

$totalMember    = $memberObj->countAll();
$totalEquipment = $equipmentObj->countAll();
$totalActive    = $loanObj->countActive();
?>
<h1>Dashboard</h1>
<p class="lead">Catat anggota, kelola inventaris alat, dan pantau peminjaman yang masih berjalan.</p>

<div class="stats">
    <a class="stat" href="index.php?page=member">
        <span class="stat-num"><?= $totalMember ?></span>
        <span class="stat-label">Anggota terdaftar</span>
    </a>
    <a class="stat" href="index.php?page=equipment">
        <span class="stat-num"><?= $totalEquipment ?></span>
        <span class="stat-label">Jenis alat</span>
    </a>
    <a class="stat" href="index.php?page=loan">
        <span class="stat-num"><?= $totalActive ?></span>
        <span class="stat-label">Peminjaman aktif</span>
    </a>
</div>
