-- ============================================================
--  LocalService Hub — Database Schema
--  Run this in phpMyAdmin or MySQL CLI:
--  mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS lsh_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lsh_db;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100)  NOT NULL,
  email      VARCHAR(150)  NOT NULL UNIQUE,
  password   VARCHAR(255)  NOT NULL,          -- bcrypt hash
  phone      VARCHAR(15)   DEFAULT NULL,
  type       ENUM('customer','provider','admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Providers ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS providers (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT           NOT NULL,
  biz_name    VARCHAR(150)  NOT NULL,
  category_id INT           NOT NULL,
  experience  INT           DEFAULT 1,
  price       DECIMAL(10,2) DEFAULT 0,
  rating      DECIMAL(3,1)  DEFAULT 4.5,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Services (offered by a provider) ─────────────────────────
CREATE TABLE IF NOT EXISTS services (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT          NOT NULL,
  name        VARCHAR(150) NOT NULL,
  price       DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
);

-- ── Bookings ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT           NOT NULL,
  provider_id INT           NOT NULL,
  service     VARCHAR(150)  NOT NULL,
  book_date   DATE          NOT NULL,
  description TEXT          DEFAULT NULL,
  price       DECIMAL(10,2) DEFAULT 0,
  status      ENUM('pending','accepted','completed','rejected') DEFAULT 'pending',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
);

-- ── Seed Data ─────────────────────────────────────────────────
-- Default admin + demo users (password for all: 123456)
INSERT INTO users (name, email, password, phone, type) VALUES
('Admin',        'admin@lsh.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL,         'admin'),
('Rahul Kumar',  'customer@lsh.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543210', 'customer'),
('Suresh Kumar', 'provider@lsh.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543211', 'provider'),
('Amit Sharma',  'amit@lsh.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543212', 'provider'),
('Meena Devi',   'meena@lsh.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543213', 'provider'),
('Ramesh Gupta', 'ramesh@lsh.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543214', 'provider');

-- Providers (category_id: 1=Plumbing,2=Electrical,3=Cleaning,4=Painting,5=Tutoring)
INSERT INTO providers (user_id, biz_name, category_id, experience, price, rating) VALUES
(3, 'Suresh Plumbing',   1, 8,  500,  4.8),
(4, 'Amit Electricals',  2, 10, 300,  4.9),
(5, 'Meena Home Clean',  3, 4,  800,  4.7),
(6, 'Ramesh Colors',     4, 9,  3000, 4.7);

-- Services per provider
INSERT INTO services (provider_id, name, price) VALUES
(1, 'Pipe Leak Repair',   500),
(1, 'Bathroom Fitting',   1200),
(1, 'Drain Unclogging',   400),
(2, 'Home Wiring',        1500),
(2, 'Fan Installation',   300),
(2, 'MCB Repair',         400),
(3, 'Full Home Cleaning', 800),
(3, 'Kitchen Cleaning',   500),
(4, 'Interior Paint',     3000),
(4, 'Exterior Paint',     5000);

-- Sample bookings
INSERT INTO bookings (customer_id, provider_id, service, book_date, price, status) VALUES
(2, 1, 'Pipe Leak Repair',   '2026-03-10', 500,  'completed'),
(2, 2, 'Fan Installation',   '2026-03-12', 300,  'accepted'),
(2, 3, 'Full Home Cleaning', '2026-03-15', 800,  'pending');
