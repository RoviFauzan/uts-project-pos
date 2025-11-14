<?php
// Pengaturan koneksi untuk lingkungan Docker
$host     = "db";
$user     = "root";
$pass     = "12345678"; // Pastikan ini sama dengan di docker-compose.yml
$database = "crud_db";

// Membuat koneksi ke database
$conn = new mysqli($host,$user,$pass,$database);

if (mysqli_connect_errno()){
    ActivityLogger::log('db_connect_error', ['error' => mysqli_connect_error()]);
    trigger_error("Koneksi gagal : ". mysqli_connect_error(), E_USER_ERROR);
} else {
    ActivityLogger::log('db_connect_ok', ['host' => $host, 'db' => $database]);
}

// Ensure auto-increment never treats 0 as a normal value
if ($conn && !$conn->connect_errno) {
    // Remove NO_AUTO_VALUE_ON_ZERO from current session SQL mode
    $conn->query("SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'NO_AUTO_VALUE_ON_ZERO', '')");

    // Helper: ensure an id column is AUTO_INCREMENT (handles tables created without AI)
    $ensureAI = function($table, $column) use ($conn) {
        // Check if column already auto_increment
        $sql = sprintf(
            "SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
            $conn->real_escape_string($table),
            $conn->real_escape_string($column)
        );
        if ($res = $conn->query($sql)) {
            $row = $res->fetch_assoc();
            $isAI = $row && stripos($row['EXTRA'], 'auto_increment') !== false;
            $res->free();
            if (!$isAI) {
                // Ensure primary key exists
                $pkRes = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = 'PRIMARY'");
                if ($pkRes && $pkRes->num_rows === 0) {
                    // Try add PK (ignore error if it already exists)
                    @$conn->query("ALTER TABLE `$table` ADD PRIMARY KEY (`$column`)");
                }
                if ($pkRes) $pkRes->free();
                // Make column AUTO_INCREMENT (ignore error if not needed)
                @$conn->query("ALTER TABLE `$table` MODIFY `$column` INT UNSIGNED NOT NULL AUTO_INCREMENT");
                ActivityLogger::log('ensure_auto_increment', ['table' => $table, 'column' => $column]);
            }
        }
    };

    // Enforce AI for our key tables
    $ensureAI('admin', 'id_admin');
    $ensureAI('barang', 'id_barang');
    $ensureAI('pelanggan', 'id_pelanggan');
}
