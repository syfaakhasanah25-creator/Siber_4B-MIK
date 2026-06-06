SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin','ranap','bangsal') NOT NULL DEFAULT 'ranap',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_name` VARCHAR(50) NOT NULL,
    `room_type` ENUM('VVIP','VIP','Kelas 1','Kelas 2','Kelas 3') NOT NULL,
    `total_beds` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `beds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT NOT NULL,
    `bed_number` INT NOT NULL,
    `bed_code` VARCHAR(10) NOT NULL,
    `status` ENUM('available','booked','cleaning','maintenance') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
    INDEX `idx_room_status` (`room_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bed_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `user_role` ENUM('ranap','bangsal') NOT NULL,
    `patient_name` VARCHAR(100) NOT NULL,
    `booking_date` DATE NOT NULL,
    `check_in_date` DATE NOT NULL,
    `check_out_date` DATE DEFAULT NULL,
    `status` ENUM('active','cancelled','completed') NOT NULL DEFAULT 'active',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`bed_id`) REFERENCES `beds`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_bed_status` (`bed_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ===== SEED DATA =====

INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator',    'admin'),
('petugas_ranap',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas ranap',      'ranap'),
('petugas_bangsal',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Bangsal',  'bangsal');
-- password semua akun: password

INSERT INTO `rooms` (`room_name`, `room_type`, `total_beds`) VALUES
('Krisna',      'VVIP',    4),
('Gathotkaca',  'VVIP',    4),
('Arjuna',      'VIP',     6),
('Bima',        'VIP',     6),
('Yudhistira',  'Kelas 1', 8),
('Abimanyu',    'Kelas 1', 8),
('Nakula',      'Kelas 2', 10),
('Sadewa',      'Kelas 2', 10),
('Srikandi',    'Kelas 3', 12),
('Drupadi',     'Kelas 3', 12);

-- Krisna (room_id=1)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(1,1,'KRS-01','available'),(1,2,'KRS-02','booked'),(1,3,'KRS-03','available'),(1,4,'KRS-04','cleaning');

-- Gathotkaca (room_id=2)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(2,1,'GTK-01','available'),(2,2,'GTK-02','available'),(2,3,'GTK-03','maintenance'),(2,4,'GTK-04','booked');

-- Arjuna (room_id=3)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(3,1,'ARJ-01','available'),(3,2,'ARJ-02','booked'),(3,3,'ARJ-03','booked'),
(3,4,'ARJ-04','available'),(3,5,'ARJ-05','cleaning'),(3,6,'ARJ-06','available');

-- Bima (room_id=4)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(4,1,'BIM-01','booked'),(4,2,'BIM-02','available'),(4,3,'BIM-03','available'),
(4,4,'BIM-04','booked'),(4,5,'BIM-05','available'),(4,6,'BIM-06','maintenance');

-- Yudhistira (room_id=5)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(5,1,'YDH-01','available'),(5,2,'YDH-02','booked'),(5,3,'YDH-03','booked'),
(5,4,'YDH-04','available'),(5,5,'YDH-05','cleaning'),(5,6,'YDH-06','available'),
(5,7,'YDH-07','booked'),(5,8,'YDH-08','available');

-- Abimanyu (room_id=6)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(6,1,'ABM-01','booked'),(6,2,'ABM-02','available'),(6,3,'ABM-03','available'),
(6,4,'ABM-04','booked'),(6,5,'ABM-05','available'),(6,6,'ABM-06','cleaning'),
(6,7,'ABM-07','available'),(6,8,'ABM-08','booked');

-- Nakula (room_id=7)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(7,1,'NKL-01','available'),(7,2,'NKL-02','booked'),(7,3,'NKL-03','available'),
(7,4,'NKL-04','booked'),(7,5,'NKL-05','available'),(7,6,'NKL-06','booked'),
(7,7,'NKL-07','cleaning'),(7,8,'NKL-08','available'),(7,9,'NKL-09','booked'),(7,10,'NKL-10','available');

-- Sadewa (room_id=8)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(8,1,'SDW-01','booked'),(8,2,'SDW-02','available'),(8,3,'SDW-03','booked'),
(8,4,'SDW-04','available'),(8,5,'SDW-05','maintenance'),(8,6,'SDW-06','available'),
(8,7,'SDW-07','booked'),(8,8,'SDW-08','available'),(8,9,'SDW-09','cleaning'),(8,10,'SDW-10','booked');

-- Srikandi (room_id=9)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(9,1,'SRK-01','available'),(9,2,'SRK-02','booked'),(9,3,'SRK-03','available'),
(9,4,'SRK-04','booked'),(9,5,'SRK-05','available'),(9,6,'SRK-06','booked'),
(9,7,'SRK-07','available'),(9,8,'SRK-08','cleaning'),(9,9,'SRK-09','booked'),
(9,10,'SRK-10','available'),(9,11,'SRK-11','booked'),(9,12,'SRK-12','available');

-- Drupadi (room_id=10)
INSERT INTO `beds` (`room_id`, `bed_number`, `bed_code`, `status`) VALUES
(10,1,'DRP-01','booked'),(10,2,'DRP-02','available'),(10,3,'DRP-03','booked'),
(10,4,'DRP-04','available'),(10,5,'DRP-05','booked'),(10,6,'DRP-06','available'),
(10,7,'DRP-07','maintenance'),(10,8,'DRP-08','booked'),(10,9,'DRP-09','available'),
(10,10,'DRP-10','booked'),(10,11,'DRP-11','cleaning'),(10,12,'DRP-12','available');

INSERT INTO `bookings` (`bed_id`, `user_id`, `user_role`, `patient_name`, `booking_date`, `check_in_date`, `check_out_date`, `status`) VALUES
(2,  2, 'ranap',     'Budi Santoso',  '2026-04-20', '2026-04-20', NULL,         'active'),
(4,  3, 'bangsal', 'Siti Rahayu',   '2026-04-18', '2026-04-18', '2026-04-25', 'completed'),
(6,  2, 'ranap',     'Ahmad Fauzi',   '2026-04-22', '2026-04-22', NULL,         'active'),
(8,  2, 'ranap',     'Dewi Lestari',  '2026-04-23', '2026-04-23', NULL,         'active'),
(10, 3, 'bangsal', 'Rudi Hartono',  '2026-04-21', '2026-04-21', '2026-04-27', 'completed');
