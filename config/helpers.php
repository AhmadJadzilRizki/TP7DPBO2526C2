<?php
/**
 * Fungsi bantu kecil yang dipakai di banyak view.
 */

/**
 * Escape output agar aman dari XSS saat ditampilkan di HTML.
 */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Menyimpan pesan sementara (flash message) di session.
 * Pesan ditampilkan sekali saja pada halaman berikutnya.
 * $type: "success" atau "error".
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Mengambil flash message lalu menghapusnya dari session.
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Pindah halaman (pola Post/Redirect/Get) supaya form tidak
 * terkirim ulang saat halaman di-refresh.
 */
function redirect($page)
{
    header("Location: index.php?page=" . urlencode($page));
    exit;
}

/**
 * Validasi string tanggal berformat Y-m-d.
 */
function isValidDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}
