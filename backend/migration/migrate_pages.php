<?php
/**
 * Database Migration Script for cms_pages
 */

$conn = require_once dirname(__DIR__) . '/conn.php';

if (!$conn) {
    die("Database connection failed.\n");
}

echo "Migrating cms_pages table...\n";

$pages_cols = [
    'heading' => 'VARCHAR(255) NULL AFTER title',
    'subheading' => 'TEXT NULL AFTER heading',
    'btn_text' => 'VARCHAR(100) NULL AFTER subheading',
    'btn_link' => 'VARCHAR(255) NULL AFTER btn_text',
    'custom_css' => 'TEXT NULL AFTER content',
    'home_json' => 'LONGTEXT NULL AFTER custom_css',
    'about_json' => 'LONGTEXT NULL AFTER home_json',
    'contact_json' => 'LONGTEXT NULL AFTER about_json',
    'type' => "ENUM('static', 'dynamic') NOT NULL DEFAULT 'static'"
];

foreach ($pages_cols as $col_name => $col_definition) {
    $check_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE '$col_name'");
    if ($check_col->num_rows === 0) {
        $conn->query("ALTER TABLE cms_pages ADD COLUMN $col_name $col_definition");
        echo "Added column '$col_name'.\n";
    } else {
        echo "Column '$col_name' already exists.\n";
    }
}

echo "Seeding/checking default system pages...\n";

$standard_pages = [
    [
        'title' => 'Home',
        'slug' => 'home',
        'heading' => 'Welcome to ACCS The Association for Critical Care Sciences',
        'subheading' => 'RECOGNITION . STANDARDS . EXCELLENCE .',
        'btn_text' => 'JOIN US TODAY',
        'btn_link' => '/membership',
        'content' => '<p>ACCS is dedicated to advancing clinical excellence, promoting education, and strengthening the future workforce in Critical Care Science. Together, we work for recognition, standardization, and growth of our profession.</p>',
        'status' => 'published'
    ],
    [
        'title' => 'Notices & Announcements',
        'slug' => 'notices-announcements',
        'heading' => 'Notices & Announcements',
        'subheading' => 'Stay updated with latest announcements',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Membership',
        'slug' => 'membership',
        'heading' => 'Membership Registration',
        'subheading' => 'Join the critical care sciences network',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Application Status Check',
        'slug' => 'membership-status',
        'heading' => '',
        'subheading' => 'Enter your Membership ID / Reference ID and Date of Birth to check your status.',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ],
    [
        'title' => 'About Us',
        'slug' => 'about-us',
        'heading' => 'About Us',
        'subheading' => 'Delivering Free Healthcare to Underserved Communities Worldwide',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '<p>The Association for Critical Care Sciences (ACCS) was founded to represent, strengthen, and advance the discipline of Critical Care Sciences in India. As an association, we work to foster collaboration between healthcare delivery systems, universities, government authorities, regulatory bodies, and industry stakeholders.</p>',
        'status' => 'published'
    ],
    [
        'title' => 'Contact Us',
        'slug' => 'contact-us',
        'heading' => 'Contact Us',
        'subheading' => 'Get in touch with us today',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ]
];

foreach ($standard_pages as $sp) {
    $check_page = $conn->prepare("SELECT id FROM cms_pages WHERE slug = ?");
    $check_page->bind_param("s", $sp['slug']);
    $check_page->execute();
    $check_page->store_result();
    if ($check_page->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO cms_pages (title, slug, heading, subheading, btn_text, btn_link, content, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $sp['title'], $sp['slug'], $sp['heading'], $sp['subheading'], $sp['btn_text'], $sp['btn_link'], $sp['content'], $sp['status']);
        if ($stmt->execute()) {
            echo "Seeded page '{$sp['slug']}'.\n";
        }
        $stmt->close();
    } else {
        echo "Page '{$sp['slug']}' already exists.\n";
    }
    $check_page->close();
}

echo "Page migration finished.\n";
