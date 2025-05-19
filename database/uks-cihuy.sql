CREATE DATABASE IF NOT EXISTS uks_db;
USE uks_db;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nis VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS health_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    complaint TEXT NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    status ENUM('pending', 'treated', 'referred') NOT NULL DEFAULT 'pending',
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `health_news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

INSERT INTO users (username, password, role) VALUES
('admin', MD5('admin123'), 'admin'),
('guru', MD5('guru123'), 'teacher');

INSERT INTO students (nis, name, class, gender, created_at) VALUES
('2024100001', 'Aditya Nugroho', 'X TM A', 'Laki-laki', NOW()),
('2024100002', 'Budi Santoso', 'XII TKRO A', 'Laki-laki', NOW()),
('2024100003', 'Cahyo Pratama', 'XI PPLG C', 'Laki-laki', NOW()),
('2024100004', 'Dwi Setiawan', 'X TEI B', 'Laki-laki', NOW()),
('2024100005', 'Eko Ramadhan', 'XII TK A', 'Laki-laki', NOW()),
('2024100006', 'Fajar Kurniawan', 'XI TOI B', 'Laki-laki', NOW()),
('2024100007', 'Galih Saputra', 'XI TKP', 'Laki-laki', NOW()),
('2024100008', 'Hari Prasetyo', 'X TK B', 'Laki-laki', NOW()),
('2024100009', 'Indra Wijaya', 'XI TEI C', 'Laki-laki', NOW()),
('2024100010', 'Joko Susanto', 'XII TM C', 'Laki-laki', NOW()),
('2024100011', 'Kevin Hartanto', 'X PPLG A', 'Laki-laki', NOW()),
('2024100012', 'Lutfi Maulana', 'XI TBSM A', 'Laki-laki', NOW()),
('2024100013', 'Miko Azhari', 'XII TKP', 'Laki-laki', NOW()),
('2024100014', 'Nugroho Aditya', 'XII DPIB B', 'Laki-laki', NOW()),
('2024100015', 'Oscar Rahmat', 'X TM D', 'Laki-laki', NOW()),
('2024100016', 'Pandu Saputra', 'XII TOI C', 'Laki-laki', NOW()),
('2024100017', 'Qori Pratama', 'XI TK A', 'Laki-laki', NOW()),
('2024100018', 'Rizky Hidayat', 'XII TEI A', 'Laki-laki', NOW()),
('2024100019', 'Syahrul Fadli', 'X TKP', 'Laki-laki', NOW()),
('2024100020', 'Teguh Prasetya', 'XII TBSM B', 'Laki-laki', NOW()),
('2024100021', 'Umar Hanif', 'XI TM E', 'Laki-laki', NOW()),
('2024100022', 'Vino Ardiansyah', 'X TOI A', 'Laki-laki', NOW()),
('2024100023', 'Wahyu Firmansyah', 'XI TK B', 'Laki-laki', NOW()),
('2024100024', 'Yoga Saputro', 'XII TEI B', 'Laki-laki', NOW()),
('2024100025', 'Zaki Akbar', 'X TKRO B', 'Laki-laki', NOW()),
('2024100026', 'Ayu Lestari', 'XII PPLG A', 'Perempuan', NOW()),
('2024100027', 'Bella Putri', 'X TK B', 'Perempuan', NOW()),
('2024100028', 'Citra Handayani', 'XII DPIB A', 'Perempuan', NOW()),
('2024100029', 'Dewi Anggraini', 'X TKP', 'Perempuan', NOW()),
('2024100030', 'Eka Sari', 'XI TBSM A', 'Perempuan', NOW()),
('2024100031', 'Fitriani Rahma', 'XII TM A', 'Perempuan', NOW()),
('2024100032', 'Gita Savitri', 'XII TOI C', 'Perempuan', NOW()),
('2024100033', 'Hana Oktaviani', 'XI TEI A', 'Perempuan', NOW()),
('2024100034', 'Intan Nuraini', 'X TK A', 'Perempuan', NOW()),
('2024100035', 'Julia Permatasari', 'XII TM B', 'Perempuan', NOW()),
('2024100036', 'Kartika Ayu', 'XI TKRO A', 'Perempuan', NOW()),
('2024100037', 'Lestari Wulandari', 'XII PPLG B', 'Perempuan', NOW()),
('2024100038', 'Mega Andini', 'XI TM C', 'Perempuan', NOW()),
('2024100039', 'Nadya Rizky', 'XII DPIB B', 'Perempuan', NOW()),
('2024100040', 'Olivia Rahmawati', 'X TK B', 'Perempuan', NOW()),
('2024100041', 'Putri Maharani', 'XI TOI C', 'Perempuan', NOW()),
('2024100042', 'Qory Annisa', 'XII TBSM A', 'Perempuan', NOW()),
('2024100043', 'Rani Oktaviani', 'XI TM A', 'Perempuan', NOW()),
('2024100044', 'Sari Dewi', 'XII TKP', 'Perempuan', NOW()),
('2024100045', 'Tiara Melati', 'XI TEI C', 'Perempuan', NOW()),
('2024100046', 'Utami Sari', 'XII TM E', 'Perempuan', NOW()),
('2024100047', 'Vina Aurel', 'X TKRO A', 'Perempuan', NOW()),
('2024100048', 'Winda Paramita', 'XI TOI B', 'Perempuan', NOW()),
('2024100049', 'Yuni Marlina', 'X PPLG C', 'Perempuan', NOW()),
('2024100050', 'Zahra Kusuma', 'XI DPIB B', 'Perempuan', NOW()),
('2024100051', 'Reza Pahlevi', 'XI TM B', 'Laki-laki', NOW()),
('2024100052', 'Farhan Hidayat', 'XII TOI A', 'Laki-laki', NOW()),
('2024100053', 'Iqbal Ramadhan', 'X TEI B', 'Laki-laki', NOW()),
('2024100054', 'Rian Saputra', 'XI TK A', 'Laki-laki', NOW()),
('2024100055', 'Dimas Nugraha', 'X TK B', 'Laki-laki', NOW()),
('2024100056', 'Andi Maulana', 'XII TKRO A', 'Laki-laki', NOW()),
('2024100057', 'Bayu Aji', 'X TEI A', 'Laki-laki', NOW()),
('2024100058', 'Gilang Pramana', 'XI TBSM B', 'Laki-laki', NOW()),
('2024100059', 'Hasanudin Putra', 'XII TM D', 'Laki-laki', NOW()),
('2024100060', 'Nadia Khairunnisa', 'XI PPLG B', 'Perempuan', NOW()),
('2024100061', 'Arum Lestari', 'XII TEI C', 'Perempuan', NOW()),
('2024100062', 'Cindy Oktaviani', 'XI DPIB A', 'Perempuan', NOW()),
('2024100063', 'Dina Ramadhani', 'X TKRO B', 'Perempuan', NOW()),
('2024100064', 'Elisa Rachmawati', 'XI TKP', 'Perempuan', NOW()),
('2024100065', 'Fani Maulidya', 'X TM E', 'Perempuan', NOW()),
('2024100066', 'Greta Salma', 'XII TBSM B', 'Perempuan', NOW()),
('2024100067', 'Helena Anggraeni', 'XII PPLG C', 'Perempuan', NOW()),
('2024100068', 'Ika Nurhayati', 'XI TK B', 'Perempuan', NOW());

INSERT INTO teachers (name, nip, phone) VALUES
('Dr. Andi Wijaya', '197001012010011001', '081234567890'),
('Dr. Fitriani Zahra', '197202022010011002', '081234567891'),
('Dr. Bambang Setiawan', '197303032010011003', '081234567892');

INSERT INTO health_records (student_id, complaint, diagnosis, treatment, status) VALUES
(1, 'Demam dan batuk', 'Flu', 'Istirahat dan minum obat', 'treated'),
(2, 'Sakit kepala', 'Migrain', 'Minum obat pereda nyeri', 'treated'),
(3, 'Mual dan pusing', 'Masuk angin', 'Istirahat dan minum air hangat', 'pending');

INSERT INTO health_news (title, content, created_by) VALUES
('Tips Menjaga Kesehatan di Musim Hujan', 'Berikut adalah beberapa tips untuk menjaga kesehatan di musim hujan...', 1),
('Pentingnya Sarapan Pagi', 'Sarapan pagi sangat penting untuk memulai hari dengan energi yang cukup...', 1),
('Cara Mencegah Flu', 'Flu adalah penyakit yang sering terjadi, berikut cara mencegahnya...', 1);
