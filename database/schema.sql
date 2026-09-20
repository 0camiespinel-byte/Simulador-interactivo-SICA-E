-- ==========================================================
-- SICA-E: Sistema Inteligente de Control de Acceso Escolar
-- Institución Educativa Brighton Pamplona
-- Base de Datos MySQL
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `sica_e` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sica_e`;

-- 1. TABLA DE USUARIOS (Administradores y Docentes)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'profesor') NOT NULL DEFAULT 'profesor',
  `titular_section` ENUM('primaria', 'secundaria') DEFAULT NULL,
  `titular_grade` VARCHAR(50) DEFAULT NULL,
  `titular_course` VARCHAR(10) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `photo_url` TEXT DEFAULT NULL,
  `status` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA DE VISITANTES
CREATE TABLE IF NOT EXISTS `visitors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_type` VARCHAR(20) NOT NULL DEFAULT 'CC',
  `document_number` VARCHAR(30) NOT NULL UNIQUE,
  `full_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `role` ENUM('padre', 'organizacion', 'universidad') NOT NULL DEFAULT 'padre',
  `org_or_university_name` VARCHAR(160) DEFAULT NULL,
  `photo_url` TEXT DEFAULT NULL,
  `default_motivo` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA DE REGISTROS DE ACCESO (Entradas y Salidas)
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `visitor_id` INT NULL,
  `visitor_name` VARCHAR(120) NOT NULL,
  `document_number` VARCHAR(30) NOT NULL,
  `motivo` VARCHAR(200) NOT NULL,
  `entrada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `salida` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('autorizado', 'rechazado', 'pendiente') NOT NULL DEFAULT 'autorizado',
  `source` ENUM('manual', 'sensor') NOT NULL DEFAULT 'manual',
  `authorized_by` INT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_entrada` (`entrada` DESC),
  INDEX `idx_document` (`document_number`),
  CONSTRAINT `fk_access_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_access_authorized_by` FOREIGN KEY (`authorized_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE ESTUDIANTES
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(120) NOT NULL,
  `document_number` VARCHAR(30) NOT NULL UNIQUE,
  `section` ENUM('primaria', 'secundaria') NOT NULL,
  `grade` VARCHAR(50) NOT NULL,
  `course` VARCHAR(10) NOT NULL,
  `photo_url` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_student_group` (`section`, `grade`, `course`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA DE ASISTENCIA
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('presente', 'justificada', 'ausente') NOT NULL DEFAULT 'presente',
  `recorded_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_student_date` (`student_id`, `date`),
  INDEX `idx_attendance_date` (`date`),
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLA DE AUDITORÍA DE ASISTENCIA
CREATE TABLE IF NOT EXISTS `attendance_audit` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `attendance_id` INT NULL,
  `student_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `old_status` VARCHAR(20) NULL,
  `new_status` VARCHAR(20) NOT NULL,
  `changed_by` INT NULL,
  `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audit_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABLA DE EXCUSAS Y JUSTIFICACIONES
CREATE TABLE IF NOT EXISTS `excuses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `teacher_id` INT NULL,
  `motivo` VARCHAR(200) NOT NULL,
  `fecha` DATE NOT NULL,
  `descripcion` TEXT NULL,
  `status` ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_excuses_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_excuses_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- DATOS SEMILLA / BOOTSTRAP INICIAL
-- ==========================================================

-- Contraseña para brightonadmi@gmail.com: BrightonAdmin2026
-- Hash bcrypt para 'BrightonAdmin2026'
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `status`)
VALUES 
(1, 'Administración Brighton', 'brightonadmi@gmail.com', '$2y$10$tZc4s7c1V7sT1cM4Y6vj0.x/sVd9AEvXhP6E1.1F0N9o9mXbVreWW', 'admin', 'activo')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Contraseña para jesus@gmail.com: profejesus
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `titular_section`, `titular_grade`, `titular_course`, `status`)
VALUES 
(2, 'Jesús Docente', 'jesus@gmail.com', '$2y$10$d6lM41aT2vX6jY9eQ4mEFeZg4/R9mQ2.9K5yX7E4V8r6a0mX1e1s2', 'profesor', 'secundaria', 'Undécimo', '02', 'activo')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- ESTUDIANTES DE EJEMPLO
INSERT INTO `students` (`full_name`, `document_number`, `section`, `grade`, `course`, `photo_url`) VALUES
-- Undécimo 02
('Yersson Eduardo Niño Gómez', '10901110201', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=EduardoNino&backgroundColor=b6e3f4'),
('Valentina Rojas Mora', '10901110202', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=ValentinaRojas&backgroundColor=ffd5dc'),
('Santiago Pérez Cárdenas', '10901110203', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=SantiagoPerez&backgroundColor=d1d4f9'),
('Isabela Gómez Ruiz', '10901110204', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=IsabelaGomez&backgroundColor=ffdfbf'),
('Mateo Carrillo Bautista', '10901110205', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=MateoCarrillo&backgroundColor=b6e3f4'),
('Laura Ortiz Pinilla', '10901110206', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=LauraOrtiz&backgroundColor=ffd5dc'),
('Nicolás Villamizar Quintero', '10901110207', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=NicolasVillamizar&backgroundColor=d1d4f9'),
('Daniela Mendoza Salinas', '10901110208', 'secundaria', 'Undécimo', '02', 'https://api.dicebear.com/9.x/avataaars/svg?seed=DanielaMendoza&backgroundColor=ffdfbf'),

-- Undécimo 01
('Andrés Felipe Torres Ruiz', '10901110101', 'secundaria', 'Undécimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=AndresTorres&backgroundColor=b6e3f4'),
('Sara Camila Bautista Mora', '10901110102', 'secundaria', 'Undécimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=SaraBautista&backgroundColor=ffd5dc'),
('Tomás Quintero Gómez', '10901110103', 'secundaria', 'Undécimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=TomasQuintero&backgroundColor=d1d4f9'),
('Mariana Pinilla Pérez', '10901110104', 'secundaria', 'Undécimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=MarianaPinilla&backgroundColor=ffdfbf'),

-- Décimo 01
('Samuel David Salinas Ortiz', '10901100101', 'secundaria', 'Décimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=SamuelSalinas&backgroundColor=b6e3f4'),
('Sofía Antonella Mora Ruiz', '10901100102', 'secundaria', 'Décimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=SofiaMora&backgroundColor=ffd5dc'),
('Sebastián Cárdenas Rojas', '10901100103', 'secundaria', 'Décimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=SebastianCardenas&backgroundColor=d1d4f9'),
('Gabriela Juliana Villamizar', '10901100104', 'secundaria', 'Décimo', '01', 'https://api.dicebear.com/9.x/avataaars/svg?seed=GabrielaVillamizar&backgroundColor=ffdfbf')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- VISITANTES DE EJEMPLO
INSERT INTO `visitors` (`id`, `document_type`, `document_number`, `full_name`, `phone`, `email`, `role`, `org_or_university_name`, `photo_url`, `default_motivo`) VALUES
(1, 'CC', '1090234567', 'Martha Cecilia Gómez Ruiz', '3124567890', 'martha.gomez@gmail.com', 'padre', NULL, 'https://api.dicebear.com/9.x/avataaars/svg?seed=MarthaGomez&backgroundColor=ffd5dc', 'Reunión con docente'),
(2, 'CC', '88123456', 'Carlos Alberto Mendoza', '3109876543', 'carlos.mendoza@unipamplona.edu.co', 'universidad', 'Universidad de Pamplona', 'https://api.dicebear.com/9.x/avataaars/svg?seed=CarlosMendoza&backgroundColor=b6e3f4', 'Prácticas pedagógicas'),
(3, 'CC', '1090543210', 'Andrea Carolina Torres', '3157654321', 'andrea.torres@redescritura.org', 'organizacion', 'Fundación Semillas del Saber', 'https://api.dicebear.com/9.x/avataaars/svg?seed=AndreaTorres&backgroundColor=d1d4f9', 'Entrega de documentos'),
(4, 'CC', '1090876543', 'Jorge Enrique Pinilla', '3182345678', 'jorge.pinilla@gmail.com', 'padre', NULL, 'https://api.dicebear.com/9.x/avataaars/svg?seed=JorgePinilla&backgroundColor=ffdfbf', 'Recoger estudiante')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- LOGS DE ACCESO RECIENTES DE PRUEBA
INSERT INTO `access_logs` (`visitor_id`, `visitor_name`, `document_number`, `motivo`, `entrada`, `salida`, `status`, `source`, `authorized_by`, `notes`) VALUES
(1, 'Martha Cecilia Gómez Ruiz', '1090234567', 'Reunión con docente', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 30 MINUTE), 'autorizado', 'manual', 1, 'Visita a grado 1102'),
(2, 'Carlos Alberto Mendoza', '88123456', 'Prácticas pedagógicas', DATE_SUB(NOW(), INTERVAL 1 HOUR), NULL, 'autorizado', 'manual', 1, 'Docente en formación inglés'),
(3, 'Andrea Carolina Torres', '1090543210', 'Entrega de documentos', DATE_SUB(NOW(), INTERVAL 45 MINUTE), NULL, 'autorizado', 'manual', 1, 'Rectoría / Secretaría'),
(4, 'Jorge Enrique Pinilla', '1090876543', 'Recoger estudiante', DATE_SUB(NOW(), INTERVAL 10 MINUTE), NULL, 'autorizado', 'manual', 1, 'Autorización previa de coordinación');
