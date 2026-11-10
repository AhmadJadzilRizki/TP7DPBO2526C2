<?php
/**
 * Class Equipment
 * ------------------------------------------------------------
 * Mengontrol tabel `equipment` (daftar alat laboratorium).
 * Stok yang tersedia dihitung dari: stock - jumlah peminjaman aktif.
 */
class Equipment
{
    private $conn;
    private $table = "equipment";

    public $id_equipment;
    public $name;
    public $category;
    public $stock;
    public $item_condition; // baik | rusak ringan | rusak berat

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * READ: seluruh alat beserta jumlah yang masih tersedia.
     * LEFT JOIN ke loan agar alat yang belum pernah dipinjam tetap muncul.
     */
    public function readAll()
    {
        $sql = "SELECT e.*, (e.stock - COUNT(l.id_loan)) AS available
                FROM {$this->table} e
                LEFT JOIN loan l
                       ON l.id_equipment = e.id_equipment AND l.status = 'dipinjam'
                GROUP BY e.id_equipment
                ORDER BY e.id_equipment DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * READ: satu alat berdasarkan id (untuk form edit).
     */
    public function readOne($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_equipment = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * CREATE: tambah alat baru.
     */
    public function create()
    {
        $sql = "INSERT INTO {$this->table} (name, category, stock, item_condition)
                VALUES (:name, :category, :stock, :cond)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':category', $this->category);
        $stmt->bindValue(':stock', $this->stock, PDO::PARAM_INT);
        $stmt->bindValue(':cond', $this->item_condition);
        return $stmt->execute();
    }

    /**
     * UPDATE: ubah data alat.
     * Stok baru tidak boleh lebih kecil dari jumlah alat yang sedang dipinjam.
     * @throws Exception bila aturan di atas dilanggar
     */
    public function update()
    {
        $active = $this->countActiveLoans($this->id_equipment);
        if ((int) $this->stock < $active) {
            throw new Exception("Stok tidak boleh kurang dari jumlah alat yang sedang dipinjam ({$active}).");
        }

        $sql = "UPDATE {$this->table}
                SET name = :name, category = :category, stock = :stock, item_condition = :cond
                WHERE id_equipment = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':category', $this->category);
        $stmt->bindValue(':stock', $this->stock, PDO::PARAM_INT);
        $stmt->bindValue(':cond', $this->item_condition);
        $stmt->bindValue(':id', $this->id_equipment, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * DELETE: hapus alat.
     * Gagal (exception FK) bila alat sudah pernah dipakai di tabel loan.
     */
    public function delete()
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id_equipment = :id");
        $stmt->bindValue(':id', $this->id_equipment, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Hitung peminjaman aktif (status 'dipinjam') untuk satu alat.
     */
    public function countActiveLoans($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total FROM loan WHERE id_equipment = :id AND status = 'dipinjam'"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Jumlah seluruh jenis alat (untuk dashboard).
     */
    public function countAll()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM {$this->table}");
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }
}
