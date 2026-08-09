<?php
/**
 * Database Migration Script for IACCS Settings
 * Creates the cms_settings table and seeds default values if empty.
 */

// Load the database connection
$conn = require_once dirname(__DIR__) . '/conn.php';

if (!$conn) {
    die("Database connection failed. Please check backend/conn.php configuration.\n");
}

echo "Starting migration...\n";

// 1. Create the cms_settings table
$create_table_query = "
CREATE TABLE IF NOT EXISTS cms_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_logo VARCHAR(255) NULL,
    site_title VARCHAR(255) NULL,
    site_title_hindi VARCHAR(255) NULL,
    last_updated_on VARCHAR(100) NULL,
    address TEXT NULL,
    footer_text TEXT NULL,
    social_links TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($create_table_query)) {
    echo "Table 'cms_settings' checked/created successfully.\n";
} else {
    die("Error creating table: " . $conn->error . "\n");
}

// 2. Seed default values if empty
$check_empty = $conn->query("SELECT COUNT(*) as cnt FROM cms_settings");
$row = $check_empty->fetch_assoc();

if ($row['cnt'] == 0) {
    $default_socials = json_encode([
        'facebook' => ['image' => '', 'link' => '', 'order' => 1],
        'instagram' => ['image' => '', 'link' => '', 'order' => 2],
        'linkedin' => ['image' => '', 'link' => '', 'order' => 3],
        'x' => ['image' => '', 'link' => '', 'order' => 4]
    ]);
    
    $stmt = $conn->prepare("INSERT INTO cms_settings (site_title, site_title_hindi, site_logo, last_updated_on, address, footer_text, social_links) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $site_title = 'Association for Critical Care Sciences';
    $site_title_hindi = '';
    $site_logo = '';
    $last_updated_on = '';
    $address = '';
    $footer_text = '';
    
    $stmt->bind_param("sssssss", $site_title, $site_title_hindi, $site_logo, $last_updated_on, $address, $footer_text, $default_socials);
    if ($stmt->execute()) {
        echo "Default settings seeded successfully.\n";
    } else {
        echo "Error seeding settings: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "Table 'cms_settings' already contains data. Seeding skipped.\n";
}

echo "Migration finished successfully.\n";
