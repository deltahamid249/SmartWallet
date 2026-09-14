CREATE DATABASE IF NOT EXISTS smart_wallet
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE smart_wallet;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(150) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','blocked','pending') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'SDG',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_wallet_balance CHECK (balance >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id BIGINT UNSIGNED NOT NULL,
    type ENUM('deposit','withdraw','transfer_in','transfer_out','payment')
        NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    reference VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    status ENUM('pending','completed','failed','cancelled')
        NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaction_wallet
        FOREIGN KEY (wallet_id) REFERENCES wallets(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_transaction_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_wallet_id BIGINT UNSIGNED NOT NULL,
    receiver_wallet_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    reference VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('pending','completed','failed','cancelled')
        NOT NULL DEFAULT 'completed',
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transfer_sender
        FOREIGN KEY (sender_wallet_id) REFERENCES wallets(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_transfer_receiver
        FOREIGN KEY (receiver_wallet_id) REFERENCES wallets(id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_transfer_amount CHECK (amount > 0),
    CONSTRAINT chk_transfer_wallets
        CHECK (sender_wallet_id <> receiver_wallet_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    merchant_name VARCHAR(150) NOT NULL,
    reference VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('pending','completed','failed','cancelled')
        NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_payment_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    phone VARCHAR(30),
    ip_address VARCHAR(45),
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_transactions_wallet_created
    ON transactions(wallet_id, created_at, id);
CREATE INDEX idx_transfers_sender_created
    ON transfers(sender_wallet_id, created_at);
CREATE INDEX idx_transfers_receiver_created
    ON transfers(receiver_wallet_id, created_at);
CREATE INDEX idx_payments_user_created
    ON payments(user_id, created_at);
CREATE INDEX idx_login_phone_created
    ON login_attempts(phone, created_at);
CREATE INDEX idx_login_ip_created
    ON login_attempts(ip_address, created_at);

CREATE TABLE IF NOT EXISTS deposit_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    wallet_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    bank_name VARCHAR(150) NOT NULL,
    sender_name VARCHAR(150),
    bank_reference VARCHAR(150) NOT NULL,
    proof_file VARCHAR(255),
    note VARCHAR(255),
    status ENUM('pending','approved','rejected','cancelled')
        NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    review_note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_deposit_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_deposit_wallet
        FOREIGN KEY (wallet_id) REFERENCES wallets(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_deposit_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT chk_deposit_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_deposit_user_created
    ON deposit_requests(user_id, created_at);

CREATE INDEX idx_deposit_status_created
    ON deposit_requests(status, created_at);

CREATE INDEX idx_deposit_bank_reference
    ON deposit_requests(bank_reference);

