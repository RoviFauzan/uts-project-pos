<?php
// Pengaturan koneksi untuk lingkungan Docker
$host     = "db";
$user     = "root";
$pass     = "12345678"; // Pastikan ini sama dengan di docker-compose.yml
$database = "crud_db";

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $pass, $database);

// Memeriksa apakah koneksi berhasil atau gagal
if (mysqli_connect_errno()){
    trigger_error("Koneksi gagal : ". mysqli_connect_error(), E_USER_ERROR);
}
?>
