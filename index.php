<?php
/**
 * index.php - titik awal aplikasi (front controller).
 * Tugas file ini:
 *   1. memuat konfigurasi & class,
 *   2. membuat koneksi database,
 *   3. memilih halaman dari parameter ?page= lalu menampilkannya
 *      di antara header dan footer.
 * Halaman bawaan (default) adalah "home" (dashboard).
 */

// Output ditahan dulu supaya view bisa memanggil header("Location: ...")
// setelah memproses form tanpa error "headers already sent".
ob_start();
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/class/Member.php';
require_once __DIR__ . '/class/Equipment.php';
require_once __DIR__ . '/class/Loan.php';

// Satu koneksi PDO dipakai bersama oleh semua view/class
$database = new Database();
$db = $database->getConnection();

// Daftar halaman yang diizinkan (whitelist) agar ?page= tidak bisa
// dipakai untuk memuat file sembarangan.
$allowedPages = ['home', 'member', 'equipment', 'loan'];
$page = $_GET['page'] ?? 'home';
if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

include __DIR__ . '/view/header.php';
include __DIR__ . "/view/{$page}.php";
include __DIR__ . '/view/footer.php';

ob_end_flush();
