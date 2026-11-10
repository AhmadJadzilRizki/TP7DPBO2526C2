<?php
/**
 * Halaman CRUD tabel equipment.
 * Alur sama dengan halaman anggota: view -> class Equipment -> PDO -> view.
 */
$equipment = new Equipment($db);
$conditions = ['baik', 'rusak ringan', 'rusak berat']; // nilai ENUM yang valid

// ------------------------------------------------------------
// 1. Tangani request POST (create / update / delete)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create' || $action === 'update') {
            $equipment->name           = trim($_POST['name'] ?? '');
            $equipment->category       = trim($_POST['category'] ?? '');
            $equipment->item_condition = $_POST['item_condition'] ?? '';
            $stockInput                = $_POST['stock'] ?? '';

            // Validasi di sisi server
            if ($equipment->name === '' || $equipment->category === '') {
                throw new Exception("Nama dan kategori alat wajib diisi.");
            }
            // Stok harus bilangan bulat >= 0
            if (filter_var($stockInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
                throw new Exception("Stok harus berupa bilangan bulat 0 atau lebih.");
            }
            $equipment->stock = (int) $stockInput;

            if (!in_array($equipment->item_condition, $conditions, true)) {
                throw new Exception("Kondisi alat tidak valid.");
            }

            if ($action === 'create') {
                $equipment->create();
                setFlash('success', 'Alat berhasil ditambahkan.');
            } else {
                $equipment->id_equipment = (int) ($_POST['id_equipment'] ?? 0);
                $equipment->update(); // bisa melempar Exception bila stok < peminjaman aktif
                setFlash('success', 'Data alat berhasil diperbarui.');
            }
        } elseif ($action === 'delete') {
            $equipment->id_equipment = (int) ($_POST['id_equipment'] ?? 0);
            $equipment->delete();
            setFlash('success', 'Alat berhasil dihapus.');
        }
    } catch (PDOException $e) {
        $mysqlCode = $e->errorInfo[1] ?? 0;
        if ($mysqlCode == 1451) {
            setFlash('error', 'Alat tidak bisa dihapus karena sudah tercatat di data peminjaman.');
        } else {
            setFlash('error', 'Terjadi kesalahan database.');
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }

    redirect('equipment');
}

// ------------------------------------------------------------
// 2. Siapkan data untuk ditampilkan
// ------------------------------------------------------------
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $equipment->readOne((int) $_GET['edit']);
}
$rows = $equipment->readAll();
?>
<h1>Alat Laboratorium</h1>

<section class="card">
    <h2><?= $editData ? 'Edit Alat' : 'Tambah Alat' ?></h2>
    <form method="post" action="index.php?page=equipment" class="form-grid">
        <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
        <?php if ($editData): ?>
            <input type="hidden" name="id_equipment" value="<?= e($editData['id_equipment']) ?>">
        <?php endif; ?>

        <label>Nama Alat
            <input type="text" name="name" required maxlength="100"
                   value="<?= e($editData['name'] ?? '') ?>">
        </label>
        <label>Kategori
            <input type="text" name="category" required maxlength="50"
                   value="<?= e($editData['category'] ?? '') ?>">
        </label>
        <label>Stok
            <input type="number" name="stock" required min="0"
                   value="<?= e($editData['stock'] ?? 1) ?>">
        </label>
        <label>Kondisi
            <select name="item_condition">
                <?php foreach ($conditions as $c): ?>
                    <option value="<?= e($c) ?>"
                        <?= (($editData['item_condition'] ?? 'baik') === $c) ? 'selected' : '' ?>>
                        <?= e(ucfirst($c)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editData ? 'Simpan Perubahan' : 'Tambah' ?></button>
            <?php if ($editData): ?>
                <a href="index.php?page=equipment" class="btn">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <h2>Daftar Alat</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>No</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Tersedia</th><th>Kondisi</th><th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="7" class="empty">Belum ada alat.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['category']) ?></td>
                    <td><?= e($row['stock']) ?></td>
                    <td><?= e($row['available']) ?></td>
                    <td>
                        <span class="badge cond-<?= e(str_replace(' ', '-', $row['item_condition'])) ?>">
                            <?= e($row['item_condition']) ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a class="btn btn-small" href="index.php?page=equipment&edit=<?= e($row['id_equipment']) ?>">Edit</a>
                        <form method="post" action="index.php?page=equipment"
                              onsubmit="return confirm('Hapus alat ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_equipment" value="<?= e($row['id_equipment']) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
