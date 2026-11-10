# LabLoan — Sistem Peminjaman Alat Laboratorium

Aplikasi web berbasis **PHP Native** untuk mengelola peminjaman alat di laboratorium kampus.
Kode ditulis dengan pendekatan **OOP**: setiap tabel punya class sendiri, koneksi database memakai **PDO**,
dan seluruh query dijalankan sebagai **prepared statement**.


---

## Janji
> Saya Ahmad Jadzil Rizki dengan NIM 2501839 mengerjakan TP7 dalam
> mata kuliah Desain Pemrograman Berorientasi Objek untuk keberkahanNya maka saya tidak
> melakukan kecurangan seperti yang telah dispesifikasikan. Aamiin.

---

---

## 1. Tema Website

Laboratorium punya banyak alat (multimeter, osiloskop, Arduino, dll.) yang dipinjam mahasiswa bergantian.
LabLoan membantu admin lab untuk:

- mendata **anggota** (mahasiswa) yang boleh meminjam,
- mengelola **inventaris alat** beserta stok dan kondisinya,
- mencatat **peminjaman & pengembalian**, termasuk menandai peminjaman yang terlambat.

---

## 2. Struktur Folder

```
TP7DPBO2526C_LabLoan/
├── index.php              # titik awal aplikasi, memilih halaman lewat ?page=
├── class/
│   ├── Member.php         # CRUD tabel member
│   ├── Equipment.php      # CRUD tabel equipment
│   └── Loan.php           # CRUD tabel loan + aturan peminjaman
├── config/
│   ├── database.php       # class Database (koneksi PDO)
│   └── helpers.php        # fungsi bantu: escape output, flash message, redirect, validasi tanggal
├── database/
│   └── db_lab_loan.sql    # struktur + data awal database
├── view/
│   ├── header.php         # <head>, navigasi, flash message
│   ├── footer.php         # penutup halaman
│   ├── home.php           # dashboard ringkasan
│   ├── member.php         # halaman CRUD anggota
│   ├── equipment.php      # halaman CRUD alat
│   └── loan.php           # halaman CRUD peminjaman
├── style/
    └── style.css

```

| Folder / File | Fungsi |
|---|---|
| `class/` | Satu class untuk satu tabel; semua query ada di sini |
| `config/` | Koneksi database dan fungsi bantu |
| `database/` | File `.sql` |
| `view/` | Tampilan modular (header/footer dipakai ulang di semua halaman) |
| `style/` | CSS |
| `index.php` | Front controller: memuat class, membuat koneksi, menampilkan halaman |

---

## 3. Database

Nama database: **`db_lab_loan`** (file: `database/db_lab_loan.sql`).

### Tabel `member`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_member | INT, PK, AUTO_INCREMENT | |
| nim | VARCHAR(20), UNIQUE | tidak boleh kembar |
| name | VARCHAR(100) | |
| major | VARCHAR(100) | jurusan |
| phone | VARCHAR(20), NULL | opsional |
| created_at | TIMESTAMP | otomatis |

### Tabel `equipment`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_equipment | INT, PK, AUTO_INCREMENT | |
| name | VARCHAR(100) | |
| category | VARCHAR(50) | |
| stock | INT | jumlah total unit |
| item_condition | ENUM('baik','rusak ringan','rusak berat') | |
| created_at | TIMESTAMP | otomatis |

### Tabel `loan`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_loan | INT, PK, AUTO_INCREMENT | |
| id_member | INT, **FK → member.id_member** | |
| id_equipment | INT, **FK → equipment.id_equipment** | |
| loan_date | DATE | tanggal pinjam |
| due_date | DATE | jatuh tempo |
| return_date | DATE, NULL | terisi saat dikembalikan |
| status | ENUM('dipinjam','dikembalikan') | |

### Relasi

```
member 1 ────< loan >──── 1 equipment
```

Satu anggota bisa punya banyak peminjaman, dan satu alat bisa dipinjam berkali-kali.
Kedua foreign key memakai `ON DELETE RESTRICT`, jadi anggota atau alat yang sudah punya riwayat
peminjaman tidak bisa dihapus (aplikasi menampilkan pesan yang jelas, bukan error mentah).

---

## 4. Fitur & Aturan Logika

Aplikasi punya 4 halaman: **Dashboard**, **Anggota**, **Alat**, dan **Peminjaman**. Menu ada di bagian atas halaman.
Setelah setiap aksi, muncul pesan berwarna: **hijau** untuk berhasil dan **merah** untuk ditolak beserta alasannya.

### 4.1 Dashboard
Menampilkan tiga angka ringkasan: jumlah **anggota terdaftar**, jumlah **jenis alat**, dan jumlah **peminjaman aktif**
(status `dipinjam`). Setiap kartu bisa diklik untuk pindah ke halaman terkait.

### 4.2 Halaman Anggota (tabel `member`)

| Aksi | Perilaku |
|---|---|
| Tambah | Mengisi NIM, nama, jurusan, dan nomor telepon (opsional). |
| Tampil | Seluruh anggota ditampilkan di tabel, data terbaru di atas. |
| Edit | Tombol **Edit** mengisi form dengan data lama; tombol berubah menjadi **Simpan Perubahan** dan muncul tombol **Batal**. |
| Hapus | Muncul dialog konfirmasi sebelum data dihapus. |

Aturan yang berlaku:
- **NIM tidak boleh kembar.** Jika NIM sudah terdaftar, data ditolak dengan pesan *"NIM sudah terdaftar, gunakan NIM lain."* (berlaku saat tambah maupun edit).
- NIM, nama, dan jurusan **wajib diisi**. NIM hanya boleh huruf/angka (3-20 karakter).
- Nomor telepon hanya boleh berisi angka, spasi, `+`, atau `-`.
- **Anggota yang punya riwayat peminjaman tidak bisa dihapus.** Pesannya: *"Anggota tidak bisa dihapus karena masih punya riwayat peminjaman."*

### 4.3 Halaman Alat (tabel `equipment`)

| Aksi | Perilaku |
|---|---|
| Tambah | Mengisi nama, kategori, stok, dan kondisi (`baik`, `rusak ringan`, `rusak berat`). |
| Tampil | Tabel menampilkan **Stok** (total unit) dan **Tersedia** (unit yang tidak sedang dipinjam), serta label warna untuk kondisi. |
| Edit | Sama seperti halaman anggota. |
| Hapus | Dengan dialog konfirmasi. |

Aturan yang berlaku:
- **Tersedia = Stok - jumlah peminjaman berstatus `dipinjam`.** Nilainya dihitung otomatis, jadi berkurang saat alat dipinjam dan bertambah saat dikembalikan.
- Stok harus bilangan bulat 0 atau lebih.
- **Stok tidak boleh diubah menjadi lebih kecil dari jumlah unit yang sedang dipinjam.** Contoh: jika 2 unit sedang dipinjam, stok tidak bisa diubah menjadi 1. Pesan: *"Stok tidak boleh kurang dari jumlah alat yang sedang dipinjam."*
- **Alat yang sudah pernah tercatat di data peminjaman tidak bisa dihapus.** Pesan: *"Alat tidak bisa dihapus karena sudah tercatat di data peminjaman."*

### 4.4 Halaman Peminjaman (tabel `loan`)

**Form catat peminjaman**
- Anggota dipilih lewat dropdown (menampilkan NIM dan nama), alat juga lewat dropdown.
- Pada dropdown alat, setiap pilihan menampilkan jumlah yang tersedia dan kondisinya.
- **Alat yang stoknya habis atau kondisinya `rusak berat` tampil abu-abu dan tidak bisa dipilih.**
- Tanggal pinjam terisi otomatis dengan hari ini, jatuh tempo otomatis 7 hari setelahnya (bisa diubah).
- Peminjaman baru selalu berstatus `dipinjam`.

**Aturan peminjaman**
1. **Alat `rusak berat` tidak bisa dipinjam.** Pesan: *"Alat ... rusak berat dan tidak dapat dipinjam."*
2. **Alat dengan stok tersedia 0 tidak bisa dipinjam.** Pesan: *"Stok alat ... sedang habis."*
3. Dua aturan di atas dicek lagi di sisi server (di class `Loan`), jadi tetap berlaku walaupun pilihan di dropdown dipaksa.
4. Pengecekan memakai **transaksi + `SELECT ... FOR UPDATE`**, sehingga dua peminjaman yang masuk bersamaan tidak bisa sama-sama mengambil unit terakhir.
5. Tanggal harus valid, **jatuh tempo tidak boleh sebelum tanggal pinjam**, dan tanggal kembali (jika diisi) tidak boleh sebelum tanggal pinjam.

**Tabel riwayat**
- Menampilkan nama + NIM peminjam, nama alat, tanggal pinjam, jatuh tempo, tanggal kembali, dan status (data diambil dengan `JOIN` ke tabel `member` dan `equipment`).
- Peminjaman yang **melewati jatuh tempo dan belum kembali** diberi label merah **terlambat**.

**Tombol Kembalikan**
- Hanya muncul pada peminjaman berstatus `dipinjam`.
- Mengubah status menjadi `dikembalikan` dan mengisi tanggal kembali dengan hari ini. Stok tersedia alat bertambah lagi.

**Edit peminjaman**
- Saat edit, muncul tambahan field **Status** dan **Tanggal Kembali**.
- Jika status diubah ke `dipinjam`, tanggal kembali dikosongkan dan ketersediaan alat dicek ulang (peminjaman yang sedang diedit tidak ikut dihitung).
- Jika status diubah ke `dikembalikan` dan tanggal kembali dikosongkan, tanggal kembali terisi otomatis dengan hari ini.

**Hapus**
- Menghapus catatan peminjaman setelah dialog konfirmasi.

### 4.5 Aturan Umum
- Semua input divalidasi di sisi server; output ditampilkan lewat `htmlspecialchars` untuk mencegah XSS.
- Error database ditangkap berdasarkan kode MySQL (1062 data kembar, 1451 data masih dipakai, 1452 relasi tidak valid) lalu ditampilkan sebagai pesan yang mudah dipahami, bukan error mentah.
- Setelah form dikirim, halaman dialihkan (redirect) sehingga data tidak terkirim ulang saat halaman di-refresh.

### 4.6 Ringkasan Skenario Penolakan (data awal)

| Skenario | Hasil |
|---|---|
| Tambah anggota dengan NIM yang sudah ada (misal `2200101`) | Ditolak: NIM sudah terdaftar |
| Hapus anggota "Raka Pratama" (punya riwayat peminjaman) | Ditolak: masih punya riwayat peminjaman |
| Pinjam "Power Supply Variabel" (kondisi rusak berat) | Tidak bisa dipilih / ditolak: rusak berat |
| Pinjam alat yang tersedia 0 | Tidak bisa dipilih / ditolak: stok habis |
| Ubah stok "Arduino Uno R3" menjadi kurang dari jumlah yang sedang dipinjam | Ditolak: stok kurang dari jumlah dipinjam |
| Hapus alat "Multimeter Digital" (sudah ada di data peminjaman) | Ditolak: sudah tercatat di peminjaman |
| Isi jatuh tempo lebih awal dari tanggal pinjam | Ditolak: tanggal tidak valid |

---

## 5. Flow Kode

Alur umum setiap aksi (contoh: menekan tombol **Simpan** pada form peminjaman):

```
Browser (form di view/loan.php)
   │  1. POST ke index.php?page=loan  (action=create, data form)
   ▼
index.php
   │  2. memuat config + class, membuat koneksi PDO ($db) dari class Database
   │  3. memuat view/header.php → view/loan.php → view/footer.php
   ▼
view/loan.php
   │  4. membaca & memvalidasi input POST
   │  5. membuat objek Loan($db), mengisi propertinya, memanggil $loan->create()
   ▼
class/Loan.php
   │  6. beginTransaction → cek alat (SELECT ... FOR UPDATE) → INSERT lewat prepare()/execute()
   │     → commit  (jika gagal: rollBack + lempar Exception)
   ▼
MySQL (db_lab_loan)
   │  7. hasil dikembalikan ke class
   ▼
view/loan.php
   │  8. menyimpan flash message di session, lalu redirect (Post/Redirect/Get)
   ▼
Browser memuat ulang index.php?page=loan
      9. view memanggil $loan->readAll() (JOIN member & equipment) → tabel tampil dengan data terbaru
```

Penjelasan tiap bagian:

- **`index.php`** — memuat semua file, membuat satu koneksi `$db`, lalu memilih halaman. Nilai `?page=` dicek
  dengan *whitelist* (`home`, `member`, `equipment`, `loan`); selain itu jatuh ke `home`.
- **`config/database.php`** — class `Database` dengan method `getConnection()` yang mengembalikan objek `PDO`
  (mode exception aktif, fetch associative, `EMULATE_PREPARES = false` agar prepared statement dijalankan oleh MySQL).
- **`class/*.php`** — tiap class menerima `PDO` lewat constructor, menyimpan kolom tabel sebagai properti publik,
  dan menyediakan `readAll()`, `readOne()`, `create()`, `update()`, `delete()` (serta method khusus seperti
  `markReturned()` dan `countActive()`). Tidak ada query yang dijalankan tanpa `prepare()`.
- **`view/*.php`** — di bagian atas file menangani POST (memanggil class), di bagian bawah menampilkan form dan tabel.
  Semua output dilewatkan `e()` (`htmlspecialchars`) untuk mencegah XSS.

### Contoh prepared statement (`class/Member.php`)

```php
$stmt = $this->conn->prepare("DELETE FROM member WHERE id_member = :id");
$stmt->bindValue(':id', $this->id_member, PDO::PARAM_INT);
$stmt->execute();
```

---

## 6. Cara Menjalankan

1. Nyalakan **Apache** dan **MySQL** (Laragon / XAMPP).
2. Salin folder project ke `www` (Laragon) atau `htdocs` (XAMPP).
3. Import `database/db_lab_loan.sql` lewat phpMyAdmin (atau jalankan isinya di tab SQL). Database `db_lab_loan` akan dibuat otomatis.
4. Cek kredensial di `config/database.php` (bawaan: user `root`, password kosong, host `localhost`).
5. Buka `http://localhost/TP7DPBO2526C_LabLoan/` di browser.

---

## 7. Dokumentasi

Dokumentasi berupa rekaman layar (tanpa narasi suara) yang memperlihatkan operasi **tambah, tampil, ubah, dan hapus**
pada ketiga tabel. Setiap tabel direkam dalam satu video.

### Video 1: Anggota (tabel `member`)
Link: [Tabel Anggota](https://drive.google.com/file/d/1N4Kqv94eLFdTSd4tLjTn6yEN864GPac5/view?usp=drive_link)


### Video 2: Alat (tabel `equipment`)
Link: [Tabel Alat](https://drive.google.com/file/d/1xdnt5Ou7Y9mUUJLQOlNhiQKh6EtjG24V/view?usp=drive_link)_



### Video 3: Peminjaman (tabel `loan`)
Link: [Tabel Peminjaman](https://drive.google.com/file/d/1kCAc7O6CpEWTw-bdgVXaC77T4llglKMX/view?usp=drive_link)

