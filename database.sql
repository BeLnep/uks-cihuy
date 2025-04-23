CREATE DATABASE IF NOT EXISTS uks_db;
USE uks_db;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(32) NOT NULL,
    role ENUM('admin', 'siswa') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE health_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    complaint TEXT NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    status ENUM('pending', 'treated', 'referred') NOT NULL,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE health_news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    nip VARCHAR(20) NOT NULL UNIQUE,
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, role) VALUES 
('admin', '0192023a7bbd73250516f069df18b500', 'admin');

INSERT INTO students (name, class, student_id) VALUES
('Ahmad Fauzi', 'X IPA 1', '2024001'),
('Siti Nurhaliza', 'X IPA 1', '2024002'),
('Budi Santoso', 'X IPA 2', '2024003'),
('Rina Kartika', 'X IPA 2', '2024004'),
('Dewi Lestari', 'XI IPA 1', '2023001');

INSERT INTO teachers (name, nip, phone) VALUES
('Dr. Andi Wijaya', '197001012010011001', '081234567800'),
('Dr. Fitriani Zahra', '197202022010011002', '081234567801'),
('Dr. Bambang Setiawan', '197303032010011003', '081234567802');

INSERT INTO health_records (student_id, complaint, diagnosis, treatment, status) VALUES
(1, 'Demam dan batuk', 'Flu', 'Istirahat dan minum obat', 'treated'),
(2, 'Sakit kepala', 'Migrain', 'Minum obat pereda nyeri', 'treated'),
(3, 'Mual dan pusing', 'Masuk angin', 'Istirahat dan minum air hangat', 'pending');

INSERT INTO health_news (title, content, created_by) VALUES
('Tips Menjaga Kesehatan di Musim Hujan', 'Berikut adalah beberapa tips untuk menjaga kesehatan di musim hujan...', 1),
('Pentingnya Sarapan Pagi', 'Sarapan pagi sangat penting untuk memulai hari dengan energi yang cukup...', 1),
('Cara Mencegah Flu', 'Flu adalah penyakit yang sering terjadi, berikut cara mencegahnya...', 1);
