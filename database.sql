-- Database schema for Kost Management Application
-- UAS_30122007_MuhammadFakhri

CREATE DATABASE IF NOT EXISTS kost_management;
USE kost_management;

-- Table: tb_penghuni (Tenants)
CREATE TABLE tb_penghuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_ktp VARCHAR(20) UNIQUE NOT NULL,
    no_hp VARCHAR(15) NOT NULL,
    tgl_masuk DATE NOT NULL,
    tgl_keluar DATE NULL
);

-- Table: tb_kamar (Rooms)
CREATE TABLE tb_kamar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor VARCHAR(10) UNIQUE NOT NULL,
    harga DECIMAL(10,2) NOT NULL
);

-- Table: tb_barang (Additional Items)
CREATE TABLE tb_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    harga DECIMAL(10,2) NOT NULL
);

-- Table: tb_kmr_penghuni (Room Occupancy)
CREATE TABLE tb_kmr_penghuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kamar INT NOT NULL,
    id_penghuni INT NOT NULL,
    tgl_masuk DATE NOT NULL,
    tgl_keluar DATE NULL,
    FOREIGN KEY (id_kamar) REFERENCES tb_kamar(id),
    FOREIGN KEY (id_penghuni) REFERENCES tb_penghuni(id)
);

-- Table: tb_brng_bawaan (Tenant Items)
CREATE TABLE tb_brng_bawaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penghuni INT NOT NULL,
    id_barang INT NOT NULL,
    FOREIGN KEY (id_penghuni) REFERENCES tb_penghuni(id),
    FOREIGN KEY (id_barang) REFERENCES tb_barang(id)
);

-- Table: tb_tagihan (Bills)
CREATE TABLE tb_tagihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    id_kmr_penghuni INT NOT NULL,
    jml_tagihan DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_kmr_penghuni) REFERENCES tb_kmr_penghuni(id)
);

-- Table: tb_bayar (Payments)
CREATE TABLE tb_bayar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tagihan INT NOT NULL,
    jml_bayar DECIMAL(10,2) NOT NULL,
    status ENUM('lunas', 'cicil') NOT NULL,
    tgl_bayar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tagihan) REFERENCES tb_tagihan(id)
);

-- Insert sample data
INSERT INTO tb_kamar (nomor, harga) VALUES 
('A1', 800000),
('A2', 800000),
('A3', 850000),
('B1', 900000),
('B2', 900000),
('C1', 1000000);

INSERT INTO tb_barang (nama, harga) VALUES 
('AC', 100000),
('Kipas Angin', 50000),
('TV', 75000),
('Kulkas', 150000),
('WiFi', 25000); 