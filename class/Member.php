<?php
/**
 * Class Member
 * ------------------------------------------------------------
 * Mengontrol tabel `member` (anggota yang boleh meminjam alat).
 * Seluruh query memakai prepared statement.
 */
class Member
{
    private $conn;                 // objek PDO
    private $table = "member";     // nama tabel

    // Properti = kolom tabel. Diisi dari view sebelum create()/update().
    public $id_member;
    public $nim;
    public $name;
    public $major;
    public $phone;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * READ: ambil seluruh anggota (terbaru di atas).
     */
    public function readAll()
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} ORDER BY id_member DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * READ: ambil satu anggota berdasarkan id (untuk form edit).
     */
    public function readOne($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_member = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(); // false jika tidak ada
    }

    /**
     * CREATE: tambah anggota baru dari nilai properti.
     */
    public function create()
    {
        $sql = "INSERT INTO {$this->table} (nim, name, major, phone)
                VALUES (:nim, :name, :major, :phone)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nim', $this->nim);
        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':major', $this->major);
        // Nomor telepon boleh kosong -> simpan NULL
        $stmt->bindValue(':phone', $this->phone === '' ? null : $this->phone);
        return $stmt->execute();
    }

    /**
     * UPDATE: ubah data anggota berdasarkan id_member.
     */
    public function update()
    {
        $sql = "UPDATE {$this->table}
                SET nim = :nim, name = :name, major = :major, phone = :phone
                WHERE id_member = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nim', $this->nim);
        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':major', $this->major);
        $stmt->bindValue(':phone', $this->phone === '' ? null : $this->phone);
        $stmt->bindValue(':id', $this->id_member, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * DELETE: hapus anggota.
     * Akan gagal (exception FK) bila anggota masih punya riwayat peminjaman.
     */
    public function delete()
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id_member = :id");
        $stmt->bindValue(':id', $this->id_member, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Jumlah seluruh anggota (untuk dashboard).
     */
    public function countAll()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM {$this->table}");
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }
}
