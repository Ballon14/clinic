CREATE TABLE Pasien (
    id_pasien INT PRIMARY KEY AUTO_INCREMENT,
    no_rekam_medis VARCHAR(20) UNIQUE,
    nama VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P'),
    alamat TEXT,
    no_telp VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Dokter (
    id_dokter INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100),
    spesialis VARCHAR(50),
    no_izin_praktik VARCHAR(50),
    no_telp VARCHAR(15),
    email VARCHAR(100)
);

CREATE TABLE Poliklinik (
    id_poliklinik INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(50),
    deskripsi TEXT
);

CREATE TABLE Jadwal_Dokter (
    id_jadwal INT PRIMARY KEY AUTO_INCREMENT,
    id_dokter INT,
    id_poliklinik INT,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
    jam_mulai TIME,
    jam_selesai TIME,
    FOREIGN KEY (id_dokter) REFERENCES Dokter(id_dokter),
    FOREIGN KEY (id_poliklinik) REFERENCES Poliklinik(id_poliklinik)
);

CREATE TABLE Pendaftaran (
    id_pendaftaran INT PRIMARY KEY AUTO_INCREMENT,
    id_pasien INT,
    id_poliklinik INT,
    id_dokter INT,
    tanggal_pendaftaran DATE,
    no_antrian INT,
    status ENUM('Menunggu', 'Dalam Pemeriksaan', 'Selesai'),
    FOREIGN KEY (id_pasien) REFERENCES Pasien(id_pasien),
    FOREIGN KEY (id_poliklinik) REFERENCES Poliklinik(id_poliklinik),
    FOREIGN KEY (id_dokter) REFERENCES Dokter(id_dokter)
);

CREATE TABLE Pemeriksaan (
    id_pemeriksaan INT PRIMARY KEY AUTO_INCREMENT,
    id_pendaftaran INT,
    keluhan TEXT,
    diagnosa TEXT,
    tindakan TEXT,
    tanggal_pemeriksaan DATETIME,
    FOREIGN KEY (id_pendaftaran) REFERENCES Pendaftaran(id_pendaftaran)
);

CREATE TABLE Obat (
    id_obat INT PRIMARY KEY AUTO_INCREMENT,
    nama_obat VARCHAR(100),
    jenis VARCHAR(50),
    satuan VARCHAR(20),
    harga DECIMAL(10,2),
    stok INT
);

CREATE TABLE Resep (
    id_resep INT PRIMARY KEY AUTO_INCREMENT,
    id_pemeriksaan INT,
    tanggal_resep DATE,
    status ENUM('Belum Diambil', 'Sudah Diambil'),
    FOREIGN KEY (id_pemeriksaan) REFERENCES Pemeriksaan(id_pemeriksaan)
);

CREATE TABLE Detail_Resep (
    id_detail_resep INT PRIMARY KEY AUTO_INCREMENT,
    id_resep INT,
    id_obat INT,
    jumlah INT,
    aturan_pakai TEXT,
    FOREIGN KEY (id_resep) REFERENCES Resep(id_resep),
    FOREIGN KEY (id_obat) REFERENCES Obat(id_obat)
);

CREATE TABLE Layanan_Unggulan (
    id_layanan INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100),
    deskripsi TEXT,
    gambar VARCHAR(100),
    harga DECIMAL(10,2)
);

CREATE TABLE Laboratorium (
    id_lab INT PRIMARY KEY AUTO_INCREMENT,
    nama_pemeriksaan VARCHAR(100),
    deskripsi TEXT,
    harga DECIMAL(10,2)
);

CREATE TABLE Hasil_Lab (
    id_hasil_lab INT PRIMARY KEY AUTO_INCREMENT,
    id_pemeriksaan INT,
    id_lab INT,
    hasil TEXT,
    keterangan TEXT,
    tanggal_pemeriksaan DATETIME,
    FOREIGN KEY (id_pemeriksaan) REFERENCES Pemeriksaan(id_pemeriksaan),
    FOREIGN KEY (id_lab) REFERENCES Laboratorium(id_lab)
);

CREATE TABLE User (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    nama VARCHAR(100),
    level ENUM('admin', 'dokter', 'apoteker', 'petugas_pendaftaran', 'petugas_lab'),
    id_dokter INT NULL,
    FOREIGN KEY (id_dokter) REFERENCES Dokter(id_dokter)
);