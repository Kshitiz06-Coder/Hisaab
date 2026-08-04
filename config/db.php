<?php

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'hisaab_db';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error() .
        '<br><br>Make sure XAMPP MySQL is running and you imported sql/hissab.sql');
}

mysqli_set_charset($conn, 'utf8mb4');
