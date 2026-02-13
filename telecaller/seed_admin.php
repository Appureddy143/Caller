#!/usr/bin/env php
<?php
// seed_admin.php — Run this ONCE to create the first admin user
// Usage: php seed_admin.php

require_once __DIR__ . '/includes/config.php';

$db = getDB();

// Create tables
$sql = file_get_contents(__DIR__ . '/schema.sql');
// Remove INSERT into users from schema (we'll do it manually)
$db->exec($sql);

$tempPass = 'ADMIN@2024';
$hash = password_hash($tempPass, PASSWORD_DEFAULT);

$stmt = $db->prepare("
  INSERT INTO users (name, email, phone, gender, dob, role, temp_password, password_set)
  VALUES (?, ?, ?, ?, ?, 'admin', ?, FALSE)
  ON CONFLICT (email) DO UPDATE SET temp_password = EXCLUDED.temp_password
  RETURNING id
");
$stmt->execute(['Super Admin', 'admin@college.com', '9999999999', 'Male', '1990-01-01', $hash]);
$id = $stmt->fetchColumn();

echo "✅ Admin user created/updated.\n";
echo "   Email:    admin@college.com\n";
echo "   Password: $tempPass\n";
echo "   User ID:  $id\n";
echo "\n⚠  On first login, you will be prompted to set a new password.\n";
