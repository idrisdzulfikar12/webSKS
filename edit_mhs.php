<?php
session_start();
include 'koneksi.php'; // File koneksi database

// Mendapatkan data mahasiswa
$idMhs = $_GET['id']; // ID mahasiswa dari URL
$queryMhs = "SELECT * FROM inputmhs WHERE id = ?";
$stmtMhs = $conn->prepare($queryMhs);
$stmtMhs->bind_param("i", $idMhs);
$stmtMhs->execute();
$resultMhs = $stmtMhs->get_result();
$dataMhs = $resultMhs->fetch_assoc();
$stmtMhs->close();

// Mendapatkan daftar mata kuliah
$queryMatkul = "SELECT * FROM jwl_matakuliah";
$resultMatkul = $conn->query($queryMatkul);

// Mendapatkan mata kuliah yang sudah diambil
$queryKrs = "SELECT * FROM krs WHERE id_mhs = ?";
$stmtKrs = $conn->prepare($queryKrs);
$stmtKrs->bind_param("i", $idMhs);
$stmtKrs->execute();
$resultKrs = $stmtKrs->get_result();
$stmtKrs->close();

// Menangani form tambah mata kuliah
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idMatkul = $_POST['id_matkul'];

    $insertKrs = "INSERT INTO krs (id_mhs, id_matkul) VALUES (?, ?)";
    $stmtInsert = $conn->prepare($insertKrs);
    $stmtInsert->bind_param("ii", $idMhs, $idMatkul);

    if ($stmtInsert->execute()) {
        $successMessage = "Mata kuliah berhasil ditambahkan.";
    } else {
        $errorMessage = "Gagal menambahkan mata kuliah: " . $stmtInsert->error;
    }

    $stmtInsert->close();

    // Refresh halaman untuk melihat hasil
    header("Location: edit_mhs.php?id=$idMhs");
    exit;
}

// Menangani hapus mata kuliah dari KRS
if (isset($_GET['hapus'])) {
    if (isset($_GET['id'])) {
        $idKrs = $_GET['hapus'];
        $idMhs = $_GET['id'];

        // Debugging output to check if the URL parameters are correct
        echo "Deleting KRS with ID: $idKrs for student ID: $idMhs";

        // Prepare the SQL DELETE statement
        $deleteKrs = "DELETE FROM krs WHERE id = ?";
        $stmtDelete = $conn->prepare($deleteKrs);
        $stmtDelete->bind_param("i", $idKrs);

        // Execute the delete statement
        if ($stmtDelete->execute()) {
            $successMessage = "Mata kuliah berhasil dihapus.";
        } else {
            $errorMessage = "Gagal menghapus mata kuliah: " . $stmtDelete->error;
        }

        $stmtDelete->close();

        // After deletion, redirect back to the same page
        header("Location: edit_mhs.php?id=$idMhs");
        exit;
    } else {
        echo "Error: 'id' parameter is missing.";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input KRS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <h1>Sistem Input Kartu Rencana Studi (KRS)</h1>
                <p class="mb-0">Input data KRS mahasiswa dengan mudah dan cepat!</p>
            </div>
            <div class="card-body">
                <!-- Menampilkan pesan sukses atau error -->
                <?php if (isset($successMessage)) { ?>
                    <div class="alert alert-success">
                        <?php echo $successMessage; ?>
                    </div>
                <?php } ?>
                <?php if (isset($errorMessage)) { ?>
                    <div class="alert alert-danger">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php } ?>

                <!-- Informasi Mahasiswa -->
                <div class="alert alert-info">
                    <strong>Mahasiswa:</strong> <?php echo $dataMhs['namaMhs']; ?> | 
                    <strong>NIM:</strong> <?php echo $dataMhs['nim']; ?> | 
                    <strong>IPK:</strong> <?php echo $dataMhs['ipk']; ?>
                </div>

                <!-- Form Input Mata Kuliah -->
                <form method="POST" class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label for="id_matkul" class="form-label">Pilih Mata Kuliah:</label>
                        <select id="id_matkul" name="id_matkul" class="form-select" required>
                            <?php while ($rowMatkul = $resultMatkul->fetch_assoc()) { ?>
                                <option value="<?php echo $rowMatkul['id']; ?>">
                                    <?php echo $rowMatkul['matakuliah']; ?> (<?php echo $rowMatkul['sks']; ?> SKS)
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">Simpan</button>
                    </div>
                </form>

                <!-- Tabel Mata Kuliah yang Diambil -->
                <h2 class="text-center mb-3">Matkul yang Diambil</h2>
                <table class="table table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Kelompok</th>
                            <th>Ruangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($rowKrs = $resultKrs->fetch_assoc()) {
                            // Mendapatkan detail mata kuliah dari ID
                            $queryDetail = "SELECT * FROM jwl_matakuliah WHERE id = ?";
                            $stmtDetail = $conn->prepare($queryDetail);
                            $stmtDetail->bind_param("i", $rowKrs['id_matkul']);
                            $stmtDetail->execute();
                            $resultDetail = $stmtDetail->get_result();
                            $dataMatkul = $resultDetail->fetch_assoc();
                            $stmtDetail->close();
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $dataMatkul['matakuliah']; ?></td>
                                <td><?php echo $dataMatkul['sks']; ?></td>
                                <td><?php echo $dataMatkul['kelp']; ?></td>
                                <td><?php echo $dataMatkul['ruangan']; ?></td>
                                <td>
                                    <a href="input_krs.php?id=<?php echo $idMhs; ?>&hapus=<?php echo $rowKrs['id']; ?>" class="btn btn-danger btn-sm">Hapus</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-warning">Kembali ke data mahasiswa</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

