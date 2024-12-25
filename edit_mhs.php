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
$queryKrs = "SELECT * FROM jwl_mhs WHERE mhs_id = ?";
$stmtKrs = $conn->prepare($queryKrs);
$stmtKrs->bind_param("i", $idMhs);
$stmtKrs->execute();
$resultKrs = $stmtKrs->get_result();
$stmtKrs->close();

// Menangani form tambah mata kuliah
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idMatkul = $_POST['id_matkul'];
    // Pastikan id_matkul adalah integer
    $idMatkul = intval($idMatkul);
    $queryIdMatkul = "SELECT * FROM jwl_matakuliah WHERE id = $idMatkul";
    $resultIdMatkul = $conn->query($queryIdMatkul);
    $rowIdMatkul = $resultIdMatkul->fetch_assoc();
    $Matkul = $rowIdMatkul['matakuliah'];
    $sks = $rowIdMatkul['sks'];
    $kelp = $rowIdMatkul['kelp'];
    $ruangan = $rowIdMatkul['ruangan'];

    $conn->begin_transaction();
    try {
        // Insert ke tabel jwl_mhs
        $insertKrs = "INSERT INTO jwl_mhs (mhs_id, matakuliah, sks, kelp, ruangan) VALUES (?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($insertKrs);
        $stmtInsert->bind_param("isiss", $idMhs, $Matkul, $sks, $kelp, $ruangan);

        if (!$stmtInsert->execute()) {
            throw new Exception("Gagal menambahkan mata kuliah: " . $stmtInsert->error);
        }

        // Update tabel inputmhs
        $insertInputmhs = "UPDATE inputmhs JOIN (
            SELECT mhs_id, GROUP_CONCAT(matakuliah SEPARATOR ', ') AS matakuliah 
            FROM jwl_mhs GROUP BY mhs_id
        ) AS subquery ON inputmhs.id = subquery.mhs_id
        SET inputmhs.matakuliah = subquery.matakuliah;";

        if (!$conn->query($insertInputmhs)) {
            throw new Exception("Gagal memperbarui tabel inputmhs: " . $conn->error);
        }

        $conn->commit();
        $successMessage = "Mata kuliah berhasil ditambahkan.";
    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = $e->getMessage();
    }

    $stmtInsert->close();

    // Refresh halaman untuk melihat hasil
    header("Location: edit_mhs.php?id=$idMhs");
    exit;
}

// Menangani hapus mata kuliah dari KRS
if (isset($_GET['hapus'])) {
    if (isset($_GET['id'])) {
        $idKrs = intval($_GET['hapus']);
        $idMhs = intval($_GET['id']);

        // Debugging output to check if the URL parameters are correct (optional, can be removed in production)
        // echo "Deleting KRS with ID: $idKrs for student ID: $idMhs";

        $conn->begin_transaction();
        try {
            // Hapus data tabel jwl_mhs
            $deleteKrs = "DELETE FROM jwl_mhs WHERE id = ?";
            $stmtDelete = $conn->prepare($deleteKrs);
            $stmtDelete->bind_param("i", $idKrs);

            // Execute the delete statement
            if (!$stmtDelete->execute()) {
                throw new Exception("Gagal menghapus mata kuliah: " . $stmtDelete->error);
            }

            $stmtDelete->close();

            // Update tabel inputmhs
            $updateInputmhs = "UPDATE inputmhs 
                LEFT JOIN (
                    SELECT mhs_id, GROUP_CONCAT(matakuliah SEPARATOR ', ') AS matakuliah 
                    FROM jwl_mhs GROUP BY mhs_id
                ) AS subquery ON inputmhs.id = subquery.mhs_id
                SET inputmhs.matakuliah = COALESCE(subquery.matakuliah, '') 
                WHERE inputmhs.id = ?";
            
            $stmtUpdate = $conn->prepare($updateInputmhs);
            $stmtUpdate->bind_param("i", $idMhs);

            if (!$stmtUpdate->execute()) {
                throw new Exception("Gagal memperbarui tabel inputmhs: " . $stmtUpdate->error);
            }

            $stmtUpdate->close();

            $conn->commit();
            $successMessage = "Mata kuliah berhasil dihapus.";
        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = $e->getMessage();
        }

        // Redirect back to the same page
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
                        while ($dataMatkul = $resultKrs->fetch_assoc()) {
                        
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $dataMatkul['matakuliah']; ?></td>
                                <td><?php echo $dataMatkul['sks']; ?></td>
                                <td><?php echo $dataMatkul['kelp']; ?></td>
                                <td><?php echo $dataMatkul['ruangan']; ?></td>
                                <td>
                                    <a href="edit_mhs.php?id=<?php echo $idMhs; ?>&hapus=<?php echo $dataMatkul['id']; ?>" class="btn btn-danger btn-sm">Hapus</a>
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

