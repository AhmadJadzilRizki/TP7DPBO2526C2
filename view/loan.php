<?php
/**
 * Halaman CRUD tabel loan (peminjaman).
 * Halaman ini memakai tiga class sekaligus:
 *   - Loan      : simpan/ubah/hapus transaksi peminjaman
 *   - Member    : mengisi pilihan anggota pada form  (foreign key id_member)
 *   - Equipment : mengisi pilihan alat pada form     (foreign key id_equipment)
 */
$loan      = new Loan($db);
$member    = new Member($db);
$equipment = new Equipment($db);

$statuses = ['dipinjam', 'dikembalikan']; // nilai ENUM yang valid

// ------------------------------------------------------------
// 1. Tangani request POST (create / update / return / delete)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create' || $action === 'update') {
            $loan->id_member    = (int) ($_POST['id_member'] ?? 0);
            $loan->id_equipment = (int) ($_POST['id_equipment'] ?? 0);
            $loan->loan_date    = trim($_POST['loan_date'] ?? '');
            $loan->due_date     = trim($_POST['due_date'] ?? '');
            $loan->return_date  = trim($_POST['return_date'] ?? '');
            $loan->status       = ($action === 'create') ? 'dipinjam' : ($_POST['status'] ?? '');

            // Validasi di sisi server
            if ($loan->id_member <= 0 || $loan->id_equipment <= 0) {
                throw new Exception("Anggota dan alat wajib dipilih.");
            }
            if (!isValidDate($loan->loan_date) || !isValidDate($loan->due_date)) {
                throw new Exception("Tanggal pinjam dan jatuh tempo harus valid (format YYYY-MM-DD).");
            }
            if ($loan->due_date < $loan->loan_date) {
                throw new Exception("Tanggal jatuh tempo tidak boleh sebelum tanggal pinjam.");
            }
            if (!in_array($loan->status, $statuses, true)) {
                throw new Exception("Status peminjaman tidak valid.");
            }
            // Tanggal kembali (bila diisi) harus valid dan tidak sebelum tanggal pinjam
            if ($loan->return_date !== '') {
                if (!isValidDate($loan->return_date)) {
                    throw new Exception("Tanggal kembali tidak valid.");
                }
                if ($loan->return_date < $loan->loan_date) {
                    throw new Exception("Tanggal kembali tidak boleh sebelum tanggal pinjam.");
                }
            }

            if ($action === 'create') {
                $loan->create(); // cek stok & kondisi dilakukan di dalam class Loan
                setFlash('success', 'Peminjaman berhasil dicatat.');
            } else {
                $loan->id_loan = (int) ($_POST['id_loan'] ?? 0);
                $loan->update();
                setFlash('success', 'Data peminjaman berhasil diperbarui.');
            }
        } elseif ($action === 'return') {
            $loan->id_loan = (int) ($_POST['id_loan'] ?? 0);
            if ($loan->markReturned()) {
                setFlash('success', 'Alat ditandai sudah dikembalikan.');
            } else {
                setFlash('error', 'Peminjaman tidak ditemukan atau sudah dikembalikan.');
            }
        } elseif ($action === 'delete') {
            $loan->id_loan = (int) ($_POST['id_loan'] ?? 0);
            $loan->delete();
            setFlash('success', 'Data peminjaman berhasil dihapus.');
        }
    } catch (PDOException $e) {
        $mysqlCode = $e->errorInfo[1] ?? 0;
        if ($mysqlCode == 1452) {
            setFlash('error', 'Anggota atau alat yang dipilih tidak valid.');
        } else {
            setFlash('error', 'Terjadi kesalahan database.');
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }

    redirect('loan');
}

// ------------------------------------------------------------
// 2. Siapkan data untuk ditampilkan
// ------------------------------------------------------------
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $loan->readOne((int) $_GET['edit']);
}
$rows       = $loan->readAll();
$memberList = $member->readAll();
$equipList  = $equipment->readAll(); // sudah memuat kolom 'available'

// Nilai awal form: hari ini, jatuh tempo 7 hari lagi
$defaultLoan = date('Y-m-d');
$defaultDue  = date('Y-m-d', strtotime('+7 days'));
$today       = date('Y-m-d');
?>
<h1>Peminjaman</h1>

<section class="card">
    <h2><?= $editData ? 'Edit Peminjaman' : 'Catat Peminjaman' ?></h2>
    <form method="post" action="index.php?page=loan" class="form-grid">
        <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
        <?php if ($editData): ?>
            <input type="hidden" name="id_loan" value="<?= e($editData['id_loan']) ?>">
        <?php endif; ?>

        <label>Anggota
            <select name="id_member" required>
                <option value="">-- pilih anggota --</option>
                <?php foreach ($memberList as $m): ?>
                    <option value="<?= e($m['id_member']) ?>"
                        <?= (($editData['id_member'] ?? '') == $m['id_member']) ? 'selected' : '' ?>>
                        <?= e($m['nim'] . ' - ' . $m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Alat
            <select name="id_equipment" required>
                <option value="">-- pilih alat --</option>
                <?php foreach ($equipList as $eq): ?>
                    <?php
                    // Pada form tambah, alat yang habis / rusak berat dinonaktifkan.
                    // (Pengecekan sebenarnya tetap dilakukan di class Loan.)
                    $blocked = ($eq['available'] <= 0) || ($eq['item_condition'] === 'rusak berat');
                    $disabled = (!$editData && $blocked) ? 'disabled' : '';
                    ?>
                    <option value="<?= e($eq['id_equipment']) ?>" <?= $disabled ?>
                        <?= (($editData['id_equipment'] ?? '') == $eq['id_equipment']) ? 'selected' : '' ?>>
                        <?= e($eq['name']) ?> (tersedia: <?= e($eq['available']) ?>, <?= e($eq['item_condition']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Tanggal Pinjam
            <input type="date" name="loan_date" required
                   value="<?= e($editData['loan_date'] ?? $defaultLoan) ?>">
        </label>
        <label>Jatuh Tempo
            <input type="date" name="due_date" required
                   value="<?= e($editData['due_date'] ?? $defaultDue) ?>">
        </label>

        <?php if ($editData): ?>
            <!-- Status & tanggal kembali hanya muncul saat edit -->
            <label>Status
                <select name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>" <?= ($editData['status'] === $s) ? 'selected' : '' ?>>
                            <?= e(ucfirst($s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tanggal Kembali (kosongkan = hari ini)
                <input type="date" name="return_date" value="<?= e($editData['return_date'] ?? '') ?>">
            </label>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editData ? 'Simpan Perubahan' : 'Simpan' ?></button>
            <?php if ($editData): ?>
                <a href="index.php?page=loan" class="btn">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <h2>Riwayat Peminjaman</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>No</th><th>Peminjam</th><th>Alat</th><th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th><th>Tgl Kembali</th><th>Status</th><th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="8" class="empty">Belum ada peminjaman.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $row): ?>
                <?php $overdue = ($row['status'] === 'dipinjam' && $row['due_date'] < $today); ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($row['member_name']) ?> <span class="mono muted">(<?= e($row['nim']) ?>)</span></td>
                    <td><?= e($row['equipment_name']) ?></td>
                    <td class="mono"><?= e($row['loan_date']) ?></td>
                    <td class="mono"><?= e($row['due_date']) ?></td>
                    <td class="mono"><?= e($row['return_date'] ?? '-') ?></td>
                    <td>
                        <span class="badge status-<?= e($row['status']) ?>"><?= e($row['status']) ?></span>
                        <?php if ($overdue): ?><span class="badge status-terlambat">terlambat</span><?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if ($row['status'] === 'dipinjam'): ?>
                            <form method="post" action="index.php?page=loan">
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="id_loan" value="<?= e($row['id_loan']) ?>">
                                <button type="submit" class="btn btn-small btn-primary">Kembalikan</button>
                            </form>
                        <?php endif; ?>
                        <a class="btn btn-small" href="index.php?page=loan&edit=<?= e($row['id_loan']) ?>">Edit</a>
                        <form method="post" action="index.php?page=loan"
                              onsubmit="return confirm('Hapus data peminjaman ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_loan" value="<?= e($row['id_loan']) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
