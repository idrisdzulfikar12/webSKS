<?php
include('koneksi.php'); // Koneksi ke database

// Cek apakah ID ada di URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Menghindari SQL Injection dengan casting ID ke integer

    // Query untuk menghapus data
    $sql = "DELETE FROM inputmhs WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        // Redirect ke halaman utama dengan pesan sukses
        header("Location: index.php?message=deleted");
    } else {
        // Redirect ke halaman utama dengan pesan error
        header("Location: index.php?message=error");
    }
} else {
    // Redirect ke halaman utama jika ID tidak ditemukan
    header("Location: index.php");
}
?>
