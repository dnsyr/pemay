-- ignore syntax loop, if u run this for the first time 
BEGIN FOR seq_name IN (
  SELECT sequence_name
  FROM user_sequences
  WHERE sequence_name IN (
      'SEQ_PEGAWAI',
      'SEQ_KANDANG',
      'SEQ_PEMILIKHEWAN',
      'SEQ_KATEGORIPRODUK',
      'SEQ_KATEGORIOBAT',
      'SEQ_JENISLAYANANSALON',
      'SEQ_JENISLAYANANMEDIS',
      'SEQ_HEWAN',
      'SEQ_PRODUK',
      'SEQ_LAYANANHOTEL',
      'SEQ_LAYANANSALON',
      'SEQ_LAYANANMEDIS',
      'SEQ_OBAT'
    )
) LOOP EXECUTE IMMEDIATE 'DROP SEQUENCE ' || seq_name.sequence_name;
END LOOP;
END;
/ -- run create type one by one
-- VARRAY untuk menyimpan array ID dari jenis layanan medis, layanan salon, & produk
CREATE TYPE ArrayJenisLayananSalon AS VARRAY(15) OF NUMBER;
CREATE TYPE ArrayJenisLayananMedis AS VARRAY(15) OF NUMBER;
CREATE TYPE ArrayProduk AS VARRAY(100) OF NUMBER;
-- Sequence untuk auto generate nomor kandang 
CREATE SEQUENCE seq_nomorkandang START WITH 1 INCREMENT BY 1;
-- Tabel Pegawai
CREATE TABLE Pegawai (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL,
  Username VARCHAR2(15) NOT NULL UNIQUE,
  Password VARCHAR2(255) NOT NULL,
  Posisi VARCHAR2(15) NOT NULL,
  Email VARCHAR2(50) NOT NULL UNIQUE,
  NomorTelpon VARCHAR2(20) NOT NULL UNIQUE,
  onDelete NUMBER(1) DEFAULT 0
);
CREATE OR REPLACE TRIGGER trg_pegawai BEFORE
INSERT ON Pegawai FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel Kandang
CREATE TABLE Kandang (
  ID VARCHAR2(36) PRIMARY KEY,
  Nomor NUMBER,
  Ukuran VARCHAR2(10) NOT NULL,
  Status VARCHAR2(10) NOT NULL,
  onDelete NUMBER(1) DEFAULT 0
);
CREATE OR REPLACE TRIGGER trg_kandang BEFORE
INSERT ON Kandang FOR EACH ROW BEGIN -- Generate UUID and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
-- Generate Nomor from the sequence
SELECT seq_nomorkandang.NEXTVAL INTO :NEW.Nomor
FROM dual;
END;
-- Tabel PemilikHewan
CREATE TABLE PemilikHewan (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL,
  Email VARCHAR2(50) NOT NULL UNIQUE,
  NomorTelpon VARCHAR2(20) NOT NULL UNIQUE,
  onDelete NUMBER(1) DEFAULT 0
);
CREATE OR REPLACE TRIGGER trg_pemilikhewan BEFORE
INSERT ON PemilikHewan FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel KategoriProduk
CREATE TABLE KategoriProduk (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL UNIQUE,
  onDelete NUMBER(1) DEFAULT 0
);
CREATE OR REPLACE TRIGGER trg_kategoriproduk BEFORE
INSERT ON KategoriProduk FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel KategoriObat
CREATE TABLE KategoriObat (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL UNIQUE,
  onDelete NUMBER(1) DEFAULT 0
);
CREATE OR REPLACE TRIGGER trg_kategoriobat BEFORE
INSERT ON KategoriObat FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel JenisLayananSalon
CREATE TABLE JenisLayananSalon (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL UNIQUE,
  Biaya NUMBER NOT NULL,
  onDelete NUMBER(1) DEFAULT 0
);
CREATE OR REPLACE TRIGGER trg_jenislayanansalon BEFORE
INSERT ON JenisLayananSalon FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel JenisLayananMedis
CREATE TABLE JenisLayananMedis (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL UNIQUE,
  Biaya NUMBER NOT NULL,
  onDelete NUMBER(1) DEFAULT 0
);
CREATE OR REPLACE TRIGGER trg_jenislayananmedis BEFORE
INSERT ON JenisLayananMedis FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel Hewan
CREATE TABLE Hewan (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL,
  Ras VARCHAR2(20) NOT NULL,
  Spesies VARCHAR2(10) NOT NULL,
  Gender VARCHAR2(10) NOT NULL,
  Berat NUMBER NOT NULL,
  TanggalLahir TIMESTAMP,
  Tinggi NUMBER NOT NULL,
  Lebar NUMBER NOT NULL,
  onDelete NUMBER(1) DEFAULT 0,
  PemilikHewan_ID VARCHAR2(36) NOT NULL,
  FOREIGN KEY (PemilikHewan_ID) REFERENCES PemilikHewan(ID)
);
CREATE OR REPLACE TRIGGER trg_hewan BEFORE
INSERT ON Hewan FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel Produk
CREATE TABLE Produk (
  ID VARCHAR2(36) PRIMARY KEY,
  Nama VARCHAR2(50) NOT NULL UNIQUE,
  Jumlah NUMBER NOT NULL,
  Harga NUMBER NOT NULL,
  onDelete NUMBER(1) DEFAULT 0,
  Pegawai_ID VARCHAR2(36) NOT NULL,
  KategoriProduk_ID VARCHAR2(36) NOT NULL,
  FOREIGN KEY (Pegawai_ID) REFERENCES Pegawai(ID),
  FOREIGN KEY (KategoriProduk_ID) REFERENCES KategoriProduk(ID)
);
CREATE OR REPLACE TRIGGER trg_produk BEFORE
INSERT ON Produk FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel LogProduk (Tracking Stok Produk)
CREATE TABLE LogProduk (
  ID NUMBER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  StokAwal NUMBER NOT NULL,
  StokAkhir NUMBER NOT NULL,
  Perubahan NUMBER NOT NULL,
  keterangan VARCHAR2(200),
  produk_id NUMBER,
  TanggalPerubahan DATE DEFAULT SYSDATE NOT NULL,

  ID INT NOT NULL,
  Keterangan VARCHAR(10) NOT NULL,
  TanggalPerubahan DATE NOT NULL,
  Pegawai_ID INT NOT NULL,
  Produk_ID INT NOT NULL,
  PRIMARY KEY (ID),
  FOREIGN KEY (Pegawai_ID) REFERENCES Pegawai(ID),
  FOREIGN KEY (Produk_ID) REFERENCES Produk(ID)
);
-- Tabel LayananHotel
CREATE TABLE LayananHotel (
  ID VARCHAR2(36) PRIMARY KEY,
  CheckIn TIMESTAMP NOT NULL,
  CheckOut TIMESTAMP NOT NULL,
  TotalBiaya NUMBER NOT NULL,
  Status VARCHAR2(15) NOT NULL,
  onDelete NUMBER(1) DEFAULT 0,
  Hewan_ID VARCHAR2(36) NOT NULL,
  Pegawai_ID VARCHAR2(36) NOT NULL,
  Kandang_ID VARCHAR2(36) NOT NULL,
  FOREIGN KEY (Hewan_ID) REFERENCES Hewan(ID),
  FOREIGN KEY (Pegawai_ID) REFERENCES Pegawai(ID),
  FOREIGN KEY (Kandang_ID) REFERENCES Kandang(ID)
);
CREATE OR REPLACE TRIGGER trg_layananhotel BEFORE
INSERT ON LayananHotel FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel LayananSalon
CREATE TABLE LayananSalon (
  ID VARCHAR2(36) PRIMARY KEY,
  Tanggal TIMESTAMP NOT NULL,
  TotalBiaya NUMBER NOT NULL,
  JenisLayanan ArrayJenisLayananSalon,
  -- Array untuk menyimpan banyak ID dari JenisLayananSalon
  Status VARCHAR2(15) NOT NULL,
  onDelete NUMBER(1) DEFAULT 0,
  Hewan_ID VARCHAR2(36) NOT NULL,
  Pegawai_ID VARCHAR2(36) NOT NULL,
  FOREIGN KEY (Hewan_ID) REFERENCES Hewan(ID),
  FOREIGN KEY (Pegawai_ID) REFERENCES Pegawai(ID)
);
CREATE OR REPLACE TRIGGER trg_layanansalon BEFORE
INSERT ON LayananSalon FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel LayananMedis
CREATE TABLE LayananMedis (
  ID VARCHAR2(36) PRIMARY KEY,
  Tanggal TIMESTAMP NOT NULL,
  TotalBiaya NUMBER NOT NULL,
  Description VARCHAR2(255) NOT NULL,
  Status VARCHAR2(15) NOT NULL,
  JenisLayanan ArrayJenisLayananMedis,
  -- Array untuk menyimpan banyak ID dari JenisLayananMedis
  onDelete NUMBER(1) DEFAULT 0,
  Pegawai_ID VARCHAR2(36) NOT NULL,
  Hewan_ID VARCHAR2(36) NOT NULL,
  FOREIGN KEY (Pegawai_ID) REFERENCES Pegawai(ID),
  FOREIGN KEY (Hewan_ID) REFERENCES Hewan(ID)
);
CREATE OR REPLACE TRIGGER trg_layananmedis BEFORE
INSERT ON LayananMedis FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
-- Tabel Obat
CREATE TABLE Obat (
  ID VARCHAR2(36) PRIMARY KEY,
  Dosis VARCHAR2(15) NOT NULL,
  Nama VARCHAR2(50) NOT NULL,
  Frekuensi VARCHAR2(20) NOT NULL,
  Instruksi VARCHAR2(255) NOT NULL,
  Harga NUMBER NOT NULL,
  onDelete NUMBER(1) DEFAULT 0,
  LayananMedis_ID VARCHAR2(36) NOT NULL,
  KategoriObat_ID VARCHAR2(36) NOT NULL,
  FOREIGN KEY (LayananMedis_ID) REFERENCES LayananMedis(ID),
  FOREIGN KEY (KategoriObat_ID) REFERENCES KategoriObat(ID)
);
CREATE OR REPLACE TRIGGER trg_obat BEFORE
INSERT ON Obat FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
CREATE TABLE Penjualan (
  ID VARCHAR2(36) PRIMARY KEY,
  TanggalTransaksi TIMESTAMP NOT NULL,
  Produk ArrayProduk,
  Pegawai_ID VARCHAR2(36) NOT NULL,
  LayananHotel_ID VARCHAR2(36),
  LayananSalon_ID VARCHAR2(36),
  LayananMedis_ID VARCHAR2(36),
  PemilikHewan_ID VARCHAR2(36) NOT NULL,
  FOREIGN KEY (Pegawai_ID) REFERENCES Pegawai(ID),
  FOREIGN KEY (LayananHotel_ID) REFERENCES LayananHotel(ID),
  FOREIGN KEY (LayananSalon_ID) REFERENCES LayananSalon(ID),
  FOREIGN KEY (LayananMedis_ID) REFERENCES LayananMedis(ID),
  FOREIGN KEY (PemilikHewan_ID) REFERENCES PemilikHewan(ID)
);
CREATE OR REPLACE TRIGGER trg_penjualan BEFORE
INSERT ON Penjualan FOR EACH ROW BEGIN -- Generate UUID in the specified format and assign it to the ID column
  :NEW.ID := SUBSTR(RAWTOHEX(SYS_GUID()), 1, 8) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 9, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 13, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 17, 4) || '-' || SUBSTR(RAWTOHEX(SYS_GUID()), 21, 12);
END;
COMMIT;