<?php
include('koneksi.php'); // Koneksi ke database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Input KRS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Sistem Input Kartu Rencana Studi (KRS)</h1>
        <form method="POST" class="mt-4">
            <div class="row mb-3">
                <div class="col">
                    <input type="text" name="namaMhs" class="form-control" placeholder="Masukkan Nama Mahasiswa" required>
                </div>
                <div class="col">
                    <input type="text" name="nim" class="form-control" placeholder="Masukkan NIM" required>
                </div>
                <div class="col">
                    <input type="number" step="0.01" name="ipk" class="form-control" placeholder="Masukkan IPK" required>
                </div>
                <div class="col">
                    <button type="submit" name="submit" class="btn btn-primary w-100">Input Mahasiswa</button>
                </div>
            </div>
        </form>

        <?php
        if (isset($_POST['submit'])) {
            $namaMhs = $_POST['namaMhs'];
            $nim = $_POST['nim'];
            $ipk = floatval($_POST['ipk']);
            $sks = $ipk < 3 ? 20 : 24; // Menentukan SKS maksimal berdasarkan IPK

            // Cek apakah NIM sudah ada
            $check = $conn->query("SELECT * FROM inputmhs WHERE nim = '$nim'");
            if ($check->num_rows > 0) {
                echo "<div class='alert alert-danger'>NIM sudah digunakan!</div>";
            } else {
                // Menambahkan data ke database
                $sql = "INSERT INTO inputmhs (namaMhs, nim, ipk, sks) VALUES ('$namaMhs', '$nim', '$ipk', '$sks')";
                if ($conn->query($sql) === TRUE) {
                    echo "<div class='alert alert-success'>Mahasiswa berhasil ditambahkan!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $conn->error . "</div>";
                }
            }
        }

        if (isset($_GET['message'])) {
            if ($_GET['message'] == 'deleted') {
                echo "<div class='alert alert-success'>Data berhasil dihapus!</div>";
            } elseif ($_GET['message'] == 'error') {
                echo "<div class='alert alert-danger'>Terjadi kesalahan saat menghapus data.</div>";
            }
        }
        ?>

        <table class="table table-bordered mt-4">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Mahasiswa</th>
                    <th>IPK</th>
                    <th>SKS Maksimal</th>
                    <th>Matkul yang diambil</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM inputmhs");
                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    $matakuliah = $row['matakuliah'] ?: "-";
                    echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['namaMhs']}</td>
                        <td>{$row['ipk']}</td>
                        <td>{$row['sks']}</td>
                        <td>{$matakuliah}</td>
                        <td>
                            <a href='edit_mhs.php?id={$row['id']}' class='btn btn-warning btn-sm'>Edit</a>
                            <a href='hapus_mhs.php?id={$row['id']}' class='btn btn-danger btn-sm'>Hapus</a>
                            <a href='lihat_matkul.php?id={$row['id']}' class='btn btn-info btn-sm'>Lihat</a>
                        </td>
                    </tr>";
                    $no++;
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
