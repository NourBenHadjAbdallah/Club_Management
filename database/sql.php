<?php
// Include the database connection
require_once 'db_connect.php';

// Drop existing tables if they exist (for resetting purposes, remove in production)
$pdo->exec("DROP TABLE IF EXISTS equipment_requests");
$pdo->exec("DROP TABLE IF EXISTS announcements");
$pdo->exec("DROP TABLE IF EXISTS events");
$pdo->exec("DROP TABLE IF EXISTS equipment");
$pdo->exec("DROP TABLE IF EXISTS users");

// Create users table
$pdo->exec("
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(50) NOT NULL,
        lastname VARCHAR(50) NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        birthday DATE,
        role ENUM('admin', 'member') DEFAULT 'member',
        status ENUM('active', 'inactive') DEFAULT 'active'
    )
");

// Create equipment table
$pdo->exec("
    CREATE TABLE equipment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create equipment_requests table
$pdo->exec("
    CREATE TABLE equipment_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        equipment_id INT NOT NULL,
        request_date DATE NOT NULL,
        status ENUM('pending', 'approved', 'denied') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (equipment_id) REFERENCES equipment(id)
    )
");

// Create events table
$pdo->exec("
    CREATE TABLE events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )
");

// Create announcements table
$pdo->exec("
    CREATE TABLE announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )
");

