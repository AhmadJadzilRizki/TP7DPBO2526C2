<?php
/**
 * Class Database
 * ------------------------------------------------------------
 * Bertugas membuat koneksi ke MySQL memakai PDO.
 * Semua class entitas (Member, Equipment, Loan) menerima objek PDO
 * hasil dari getConnection() lewat constructor-nya.
 */
class Database
{
    // Kredensial koneksi (sesuaikan dengan Laragon / XAMPP masing-masing)
    private $host     = "localhost";
    private $db_name  = "db_lab_loan";
    private $username = "root";
    private $password = "";

    // Menyimpan objek PDO supaya koneksi tidak dibuat berulang kali
    private $conn = null;

    /**
     * Membuat (atau mengembalikan) koneksi PDO.
     * @return PDO
     */
    public function getConnection()
    {
        // Jika koneksi sudah ada, pakai yang lama
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->username, $this->password, [
                // Error database dilempar sebagai exception agar mudah di-catch
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Hasil fetch berupa array asosiatif (nama kolom => nilai)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Matikan emulasi: prepared statement benar-benar dijalankan oleh MySQL
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }

        return $this->conn;
    }
}
