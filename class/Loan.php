<?php
/**
 * Class Loan
 * ------------------------------------------------------------
 * Mengontrol tabel `loan` (transaksi peminjaman).
 * Tabel ini punya dua foreign key:
 *   - id_member    -> member.id_member
 *   - id_equipment -> equipment.id_equipment
 *
 * Aturan logika yang dijaga di class ini:
 *   1. Alat dengan kondisi 'rusak berat' tidak bisa dipinjam.
 *   2. Alat tidak bisa dipinjam bila stok tersedia sudah habis.
 *   3. Saat status 'dikembalikan', tanggal kembali terisi otomatis.
 */
class Loan
{
    private $conn;
    private $table = "loan";

    public $id_loan;
    public $id_member;
    public $id_equipment;
    public $loan_date;
    public $due_date;
    public $return_date;
    public $status; // dipinjam | dikembalikan

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * READ: seluruh peminjaman + nama anggota & nama alat (JOIN).
     */
    public function readAll()
    {
        $sql = "SELECT l.*, m.name AS member_name, m.nim, e.name AS equipment_name
                FROM {$this->table} l
                JOIN member m    ON m.id_member = l.id_member
                JOIN equipment e ON e.id_equipment = l.id_equipment
                ORDER BY l.id_loan DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * READ: satu peminjaman berdasarkan id (untuk form edit).
     */
    public function readOne($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_loan = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * CREATE: catat peminjaman baru.
     * Dibungkus transaksi + SELECT ... FOR UPDATE agar dua peminjaman
     * yang masuk bersamaan tidak sama-sama mengambil stok terakhir.
     * @throws Exception bila alat tidak bisa dipinjam
     */
    public function create()
    {
        $this->conn->beginTransaction();
        try {
            $this->assertBorrowable($this->id_equipment, 0);

            $sql = "INSERT INTO {$this->table}
                        (id_member, id_equipment, loan_date, due_date, return_date, status)
                    VALUES (:member, :equipment, :loan_date, :due_date, NULL, 'dipinjam')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':member', $this->id_member, PDO::PARAM_INT);
            $stmt->bindValue(':equipment', $this->id_equipment, PDO::PARAM_INT);
            $stmt->bindValue(':loan_date', $this->loan_date);
            $stmt->bindValue(':due_date', $this->due_date);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e; // diteruskan ke view untuk ditampilkan sebagai pesan
        }
    }

    /**
     * UPDATE: ubah data peminjaman.
     * - status 'dipinjam'     -> tanggal kembali dikosongkan & stok dicek ulang
     *                            (peminjaman ini sendiri tidak ikut dihitung)
     * - status 'dikembalikan' -> tanggal kembali diisi (default: hari ini)
     * @throws Exception bila alat tidak bisa dipinjam
     */
    public function update()
    {
        $this->conn->beginTransaction();
        try {
            if ($this->status === 'dipinjam') {
                $this->assertBorrowable($this->id_equipment, (int) $this->id_loan);
                $returnDate = null;
            } else {
                $returnDate = ($this->return_date === '' || $this->return_date === null)
                    ? date('Y-m-d')
                    : $this->return_date;
            }

            $sql = "UPDATE {$this->table}
                    SET id_member = :member, id_equipment = :equipment,
                        loan_date = :loan_date, due_date = :due_date,
                        return_date = :return_date, status = :status
                    WHERE id_loan = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':member', $this->id_member, PDO::PARAM_INT);
            $stmt->bindValue(':equipment', $this->id_equipment, PDO::PARAM_INT);
            $stmt->bindValue(':loan_date', $this->loan_date);
            $stmt->bindValue(':due_date', $this->due_date);
            $stmt->bindValue(':return_date', $returnDate);
            $stmt->bindValue(':status', $this->status);
            $stmt->bindValue(':id', $this->id_loan, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Tandai peminjaman sebagai sudah dikembalikan (tombol "Kembalikan").
     * Hanya berlaku untuk peminjaman yang masih berstatus 'dipinjam'.
     */
    public function markReturned()
    {
        $sql = "UPDATE {$this->table}
                SET status = 'dikembalikan', return_date = CURDATE()
                WHERE id_loan = :id AND status = 'dipinjam'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $this->id_loan, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0; // true bila ada baris yang berubah
    }

    /**
     * DELETE: hapus catatan peminjaman.
     */
    public function delete()
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id_loan = :id");
        $stmt->bindValue(':id', $this->id_loan, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Jumlah peminjaman yang masih berjalan (untuk dashboard).
     */
    public function countActive()
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM {$this->table} WHERE status = 'dipinjam'");
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Memastikan sebuah alat boleh dipinjam.
     * Baris alat dikunci (FOR UPDATE) selama transaksi berjalan.
     *
     * @param int $idEquipment alat yang akan dipinjam
     * @param int $excludeLoan id peminjaman yang diabaikan saat menghitung
     *                         (dipakai ketika mengedit peminjaman yang sama)
     * @throws Exception bila alat tidak ada, rusak berat, atau stok habis
     */
    private function assertBorrowable($idEquipment, $excludeLoan)
    {
        // Ambil data alat dan kunci barisnya
        $stmt = $this->conn->prepare(
            "SELECT name, stock, item_condition FROM equipment WHERE id_equipment = :id FOR UPDATE"
        );
        $stmt->bindValue(':id', $idEquipment, PDO::PARAM_INT);
        $stmt->execute();
        $equipment = $stmt->fetch();

        if (!$equipment) {
            throw new Exception("Alat yang dipilih tidak ditemukan.");
        }
        if ($equipment['item_condition'] === 'rusak berat') {
            throw new Exception("Alat \"{$equipment['name']}\" rusak berat dan tidak dapat dipinjam.");
        }

        // Hitung peminjaman aktif untuk alat ini (kecuali peminjaman yang sedang diedit)
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total FROM {$this->table}
             WHERE id_equipment = :id AND status = 'dipinjam' AND id_loan <> :exclude"
        );
        $stmt->bindValue(':id', $idEquipment, PDO::PARAM_INT);
        $stmt->bindValue(':exclude', $excludeLoan, PDO::PARAM_INT);
        $stmt->execute();
        $active = (int) $stmt->fetch()['total'];

        if ((int) $equipment['stock'] - $active <= 0) {
            throw new Exception("Stok alat \"{$equipment['name']}\" sedang habis.");
        }
    }
}
