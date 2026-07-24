CREATE DATABASE IF NOT EXISTS tra_revenue;
USE tra_revenue;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tx_ref VARCHAR(100) NOT NULL UNIQUE,
  gateway_transaction_id VARCHAR(100) DEFAULT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone_number VARCHAR(30) NOT NULL,
  tin VARCHAR(50) NOT NULL,
  tax_type VARCHAR(100) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  description TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
);
