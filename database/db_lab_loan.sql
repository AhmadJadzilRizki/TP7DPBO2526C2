-- ============================================================
-- Database : db_lab_loan  (Sistem Peminjaman Alat Laboratorium)
-- Tabel    : member, equipment, loan
-- Relasi   : loan.id_member    -> member.id_member
--            loan.id_equipment -> equipment.id_equipment
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_lab_loan
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_lab_loan;

-- Hapus tabel lama (urutan: tabel anak dulu agar tidak bentrok FK)
DROP TABLE IF EXISTS loan;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS member;

-- ------------------------------------------------------------
-- Tabel member : anggota yang boleh meminjam alat
-- ------------------------------------------------------------
CREATE TABLE member (
  id_member  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nim        VARCHAR(20)  NOT NULL,
  name       VARCHAR(100) NOT NULL,
  major      VARCHAR(100) NOT NULL,
  phone      VARCHAR(20)  NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_member),
  UNIQUE KEY uq_member_nim (nim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel equipment : daftar alat laboratorium
-- ------------------------------------------------------------
CREATE TABLE equipment (
  id_equipment   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name           VARCHAR(100) NOT NULL,
  category       VARCHAR(50)  NOT NULL,
  stock          INT UNSIGNED NOT NULL DEFAULT 0,
  item_condition ENUM('baik','rusak ringan','rusak berat') NOT NULL DEFAULT 'baik',
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_equipment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel loan : transaksi peminjaman (punya 2 foreign key)
-- ------------------------------------------------------------
CREATE TABLE loan (
  id_loan      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_member    INT UNSIGNED NOT NULL,
  id_equipment INT UNSIGNED NOT NULL,
  loan_date    DATE NOT NULL,
  due_date     DATE NOT NULL,
  return_date  DATE NULL,
  status       ENUM('dipinjam','dikembalikan') NOT NULL DEFAULT 'dipinjam',
  PRIMARY KEY (id_loan),
  KEY idx_loan_member (id_member),
  KEY idx_loan_equipment (id_equipment),
  CONSTRAINT fk_loan_member
    FOREIGN KEY (id_member) REFERENCES member (id_member)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_loan_equipment
    FOREIGN KEY (id_equipment) REFERENCES equipment (id_equipment)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Data awal
-- ------------------------------------------------------------
INSERT INTO member (nim, name, major, phone) VALUES
  ('2200101', 'Raka Pratama',   'Ilmu Komputer',        '081234567801'),
  ('2200102', 'Sinta Maharani', 'Teknik Elektro',       '081234567802'),
  ('2200103', 'Dimas Aryanto',  'Pendidikan Teknik Mesin', NULL),
  ('2200104', 'Laila Nurhaliza','Sistem Informasi',     '081234567804');

INSERT INTO equipment (name, category, stock, item_condition) VALUES
  ('Multimeter Digital',   'Pengukur',     5, 'baik'),
  ('Osiloskop 2 Channel',  'Pengukur',     2, 'baik'),
  ('Solder Station',       'Perakitan',    4, 'rusak ringan'),
  ('Arduino Uno R3',       'Mikrokontroler', 8, 'baik'),
  ('Power Supply Variabel','Catu Daya',    1, 'rusak berat');

INSERT INTO loan (id_member, id_equipment, loan_date, due_date, return_date, status) VALUES
  (1, 1, '2026-10-26', '2026-11-02', '2026-11-01', 'dikembalikan'),
  (2, 2, '2026-11-03', '2026-11-10', NULL,         'dipinjam'),
  (3, 4, '2026-11-04', '2026-11-11', NULL,         'dipinjam'),
  (4, 4, '2026-11-05', '2026-11-12', NULL,         'dipinjam');
