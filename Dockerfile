# Menggunakan image resmi PHP 8.1 dengan Apache
FROM php:8.1-apache

# Menginstal ekstensi PHP yang diperlukan untuk koneksi ke database MySQL/MariaDB
RUN docker-php-ext-install pdo_mysql
