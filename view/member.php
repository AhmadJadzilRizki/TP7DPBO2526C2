<?php
/**
 * Halaman CRUD tabel member.
 * Alur: form/tombol -> POST ke halaman ini -> panggil class Member
 *       -> class menjalankan prepared statement lewat PDO -> redirect -> tabel tampil ulang.
 */
$member = new Member($db);

// ------------------------------------------------------------
// 1. Tangani request POST (create / update / delete)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create' || $action === 'update') {
            // Ambil & bersihkan input
            $member->nim   = trim($_POST['nim'] ?? '');
            $member->name  = trim($_POST['name'] ?? '');
            $member->major = trim($_POST['major'] ?? '');
            $member->phone = trim($_POST['phone'] ?? '');

            // Validasi di sisi server
            if ($member->nim === '' || $member->name === '' || $member->major === '') {
                throw new Exception("NIM, nama, dan jurusan wajib diisi.");
            }
            if (!preg_match('/^[0-9A-Za-z]{3,20}$/', $member->nim)) {
                throw new Exception("NIM hanya boleh huruf/angka (3-20 karakter).");
            }
            if (!preg_match('/^[0-9+\- ]{0,20}$/', $member->phone)) {
                throw new Exception("Nomor telepon hanya boleh angka, spasi, + atau -.");
            }

            if ($action === 'create') {
                $member->create();
                setFlash('success', 'Anggota berhasil ditambahkan.');
            } else {
                $member->id_member = (int) ($_POST['id_member'] ?? 0);
                $member->update();
                setFlash('success', 'Data anggota berhasil diperbarui.');
            }
        } elseif ($action === 'delete') {
            $member->id_member = (int) ($_POST['id_member'] ?? 0);
            $member->delete();
            setFlash('success', 'Anggota berhasil dihapus.');
        }
    } catch (PDOException $e) {
        // errorInfo[1] = kode error MySQL
        $mysqlCode = $e->errorInfo[1] ?? 0;
        if ($mysqlCode == 1062) {
            setFlash('error', 'NIM sudah terdaftar, gunakan NIM lain.');
        } elseif ($mysqlCode == 1451) {
            setFlash('error', 'Anggota tidak bisa dihapus karena masih punya riwayat peminjaman.');
        } else {
            setFlash('error', 'Terjadi kesalahan database.');
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }

    redirect('member'); // Post/Redirect/Get
}

// ------------------------------------------------------------
// 2. Siapkan data untuk ditampilkan
// ------------------------------------------------------------
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $member->readOne((int) $_GET['edit']); // false bila tidak ditemukan
}
$rows = $member->readAll();
?>
<h1>Anggota</h1>

<section class="card">
    <h2><?= $editData ? 'Edit Anggota' : 'Tambah Anggota' ?></h2>
    <form method="post" action="index.php?page=member" class="form-grid">
        <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
        <?php if ($editData): ?>
            <input type="hidden" name="id_member" value="<?= e($editData['id_member']) ?>">
        <?php endif; ?>

        <label>NIM
            <input type="text" name="nim" required maxlength="20"
                   value="<?= e($editData['nim'] ?? '') ?>">
        </label>
        <label>Nama
            <input type="text" name="name" required maxlength="100"
                   value="<?= e($editData['name'] ?? '') ?>">
        </label>
        <label>Jurusan
            <input type="text" name="major" required maxlength="100"
                   value="<?= e($editData['major'] ?? '') ?>">
        </label>
        <label>No. Telepon (opsional)
            <input type="text" name="phone" maxlength="20"
                   value="<?= e($editData['phone'] ?? '') ?>">
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editData ? 'Simpan Perubahan' : 'Tambah' ?></button>
            <?php if ($editData): ?>
                <a href="index.php?page=member" class="btn">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <h2>Daftar Anggota</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>No</th><th>NIM</th><th>Nama</th><th>Jurusan</th><th>Telepon</th><th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="6" class="empty">Belum ada anggota.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="mono"><?= e($row['nim']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['major']) ?></td>
                    <td><?= e($row['phone'] ?? '-') ?></td>
                    <td class="actions">
                        <a class="btn btn-small" href="index.php?page=member&edit=<?= e($row['id_member']) ?>">Edit</a>
                        <form method="post" action="index.php?page=member"
                              onsubmit="return confirm('Hapus anggota ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_member" value="<?= e($row['id_member']) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
