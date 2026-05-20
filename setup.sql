-- AIWeb2 MySQL/MariaDB 적용 스크립트
-- 사용: mysql -u root < setup.sql

CREATE DATABASE IF NOT EXISTS aiweb2
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON aiweb2.* TO 'aiweb2_user'@'localhost' IDENTIFIED BY 'wjsansrk';
FLUSH PRIVILEGES;

USE aiweb2;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ip_address VARCHAR(20) NULL,
    room_name VARCHAR(30) NULL,
    room_number VARCHAR(30) NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(140) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_posts_created_at (created_at),
    CONSTRAINT fk_aiweb2_posts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
