<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include('conn/db.php');

// Get user data
$user_id = $_SESSION['user_id'];
$level = $_SESSION['level'];
$nama = $_SESSION['nama'];

// Check if user has permission to access this page
if ($level != 'admin' && $level != 'dokter') {
    header("Location: dashboard.php");
    exit;
}

// Check if patient ID is provided
if (!isset($_GET['id_pasien']) || empty($_GET['id_pasien'])) {
    $_SESSION['message'] = "ID Pasien tidak ditemukan";
    $_SESSION['msg_type'] = "danger";
    header("Location: pasien.php");
    exit;
}

$patient_id = $_GET['id_pasien'];

// Get patient data
$query = "SELECT * FROM pasien WHERE id_pasien = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Data pasien tidak ditemukan";
    $_SESSION['msg_type'] = "danger";
    header("Location: pasien.php");
    exit;
}

$patient = $result->fetch_assoc();
$stmt->close();

// Get pendaftaran data for this patient that doesn't have a medical record yet
$query = "SELECT p.*, d.nama as dokter_nama, k.nama as poli_nama 
          FROM Pendaftaran p 
          LEFT JOIN Dokter d ON p.id_dokter = d.id_dokter
          LEFT JOIN Poliklinik k ON p.id_poliklinik = k.id_poliklinik
          LEFT JOIN Pemeriksaan pe ON p.id_pendaftaran = pe.id_pendaftaran
          WHERE p.id_pasien = ? AND pe.id_pemeriksaan IS NULL AND p.status = 'menunggu'
          ORDER BY p.tanggal_pendaftaran DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$pendaftaran_result = $stmt->get_result();
$stmt->close();

// Get all doctors
$query = "SELECT * FROM Dokter ORDER BY nama";
$doctors = $conn->query($query);

// Get all obat - Fixed the query to handle potential column name issues
// Check if the 'nama' column exists in the Obat table
$checkQuery = "SHOW COLUMNS FROM Obat LIKE 'nama'";
$checkResult = $conn->query($checkQuery);

if ($checkResult->num_rows > 0) {
    // If 'nama' column exists, use it
    $query = "SELECT * FROM Obat ORDER BY nama";
} else {
    // If 'nama' column doesn't exist, try with 'nama_obat' or don't use ORDER BY
    $checkQuery = "SHOW COLUMNS FROM Obat LIKE 'nama_obat'";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult->num_rows > 0) {
        $query = "SELECT * FROM Obat ORDER BY nama_obat";
    } else {
        // If neither column exists, don't use ORDER BY
        $query = "SELECT * FROM Obat";
    }
}

$obat_list = $conn->query($query);

// Check if the query was successful
if (!$obat_list) {
    // Handle the error
    $_SESSION['message'] = "Error: Unable to fetch medications list - " . $conn->error;
    $_SESSION['msg_type'] = "danger";
    
    // Use a simple query without ORDER BY as fallback
    $query = "SELECT * FROM Obat";
    $obat_list = $conn->query($query);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $id_pendaftaran = $_POST['id_pendaftaran'];
    $tanggal_pemeriksaan = $_POST['tanggal_pemeriksaan'];
    $keluhan = $_POST['keluhan'];
    $diagnosa = $_POST['diagnosa'];
    $tindakan = $_POST['tindakan'];
    $catatan = $_POST['catatan'];
    $resep = $_POST['resep'] ?? '';
    $obat_ids = $_POST['obat_ids'] ?? [];
    $obat_qty = $_POST['obat_qty'] ?? [];
    $obat_aturan = $_POST['obat_aturan'] ?? [];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert into Pemeriksaan table
        $query = "INSERT INTO Pemeriksaan (id_pendaftaran, tanggal_pemeriksaan, keluhan, diagnosa, tindakan, catatan, resep) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssss", $id_pendaftaran, $tanggal_pemeriksaan, $keluhan, $diagnosa, $tindakan, $catatan, $resep);
        $stmt->execute();
        
        $id_pemeriksaan = $conn->insert_id;
        $stmt->close();
        
        // Insert medications if any
        if (!empty($obat_ids)) {
            $query = "INSERT INTO ResepObat (id_pemeriksaan, id_obat, jumlah, aturan_pakai) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            
            for ($i = 0; $i < count($obat_ids); $i++) {
                if (!empty($obat_ids[$i]) && !empty($obat_qty[$i])) {
                    $stmt->bind_param("iiis", $id_pemeriksaan, $obat_ids[$i], $obat_qty[$i], $obat_aturan[$i]);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }
        
        // Update pendaftaran status
        $query = "UPDATE Pendaftaran SET status = 'selesai' WHERE id_pendaftaran = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_pendaftaran);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = "Data rekam medis berhasil ditambahkan";
        $_SESSION['msg_type'] = "success";
        header("Location: pasien_detail.php?id=" . $patient_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Rekam Medis - Sistem Informasi Klinik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <img class="h-10 w-auto" src="assets/images/logo.png" alt="Logo Klinik">
                            <span class="ml-2 text-xl font-bold">Klinik Bima Husada</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="hidden md:ml-4 md:flex md:items-center">
                            <div class="ml-3 relative group">
                                <div class="flex items-center">
                                    <span class="mr-2"><?php echo $nama; ?></span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profil</a>
                                    <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex-grow flex">
            <!-- Sidebar -->
            <div class="hidden md:flex md:flex-shrink-0">
                <div class="flex flex-col w-64 bg-white shadow-lg">
                    <div class="flex flex-col h-0 flex-1">
                        <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                            <nav class="mt-5 flex-1 px-2 space-y-1">
                                <a href="dashboard.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-home mr-3 text-gray-500"></i>
                                    Dashboard
                                </a>
                                
                                <?php if ($level == 'admin'): ?>
                                <a href="pasien.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-users mr-3 text-blue-500"></i>
                                    Pasien
                                </a>
                                <a href="dokter.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-user-md mr-3 text-gray-500"></i>
                                    Dokter
                                </a>
                                <a href="poliklinik.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-hospital mr-3 text-gray-500"></i>
                                    Poliklinik
                                </a>
                                <a href="layanan_unggulan.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-star mr-3 text-gray-500"></i>
                                    Layanan Unggulan
                                </a>
                                <a href="obat.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-pills mr-3 text-gray-500"></i>
                                    Obat
                                </a>
                                <a href="laporan.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-chart-bar mr-3 text-gray-500"></i>
                                    Laporan
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($level == 'dokter'): ?>
                                <a href="jadwal_praktek.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-calendar-alt mr-3 text-gray-500"></i>
                                    Jadwal Praktek
                                </a>
                                <a href="pasien_dokter.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-user-injured mr-3 text-gray-500"></i>
                                    Pasien
                                </a>
                                <a href="pemeriksaan.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-stethoscope mr-3 text-blue-500"></i>
                                    Pemeriksaan
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="flex-1 overflow-auto focus:outline-none">
                <div class="py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Tambah Rekam Medis</h1>
                            <p class="mt-1 text-sm text-gray-600">Tambahkan data rekam medis untuk pasien</p>
                        </div>
                        <div>
                            <a href="pasien_detail.php?id=<?php echo $patient_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-arrow-left mr-2"></i> Kembali
                            </a>
                        </div>
                    </div>
                    
                    <!-- Patient information card -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                        <div class="px-4 py-5 sm:px-6 bg-gray-50">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Pasien</h3>
                        </div>
                        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                            <dl class="sm:divide-y sm:divide-gray-200">
                                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['nama']); ?></dd>
                                </div>
                                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">No. Rekam Medis</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['no_rekam_medis']); ?></dd>
                                </div>
                                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo date('d F Y', strtotime($patient['tanggal_lahir'])); ?></dd>
                                </div>
                                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Jenis Kelamin</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo $patient['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <!-- Medical Record Form -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 bg-gray-50">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Form Rekam Medis</h3>
                            <p class="mt-1 text-sm text-gray-500">Masukkan data pemeriksaan pasien</p>
                        </div>
                        
                        <!-- Display message if any -->
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="px-4 py-3 mb-4 <?php echo $_SESSION['msg_type'] == 'danger' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> rounded-md">
                                <?php 
                                    echo $_SESSION['message']; 
                                    unset($_SESSION['message']);
                                    unset($_SESSION['msg_type']);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="border-t border-gray-200 px-4 py-5">
                            <form method="POST" action="">
                                <!-- Pendaftaran Selection -->
                                <div class="mb-6">
                                    <label for="id_pendaftaran" class="block text-sm font-medium text-gray-700 mb-1">Pilih Pendaftaran</label>
                                    <select id="id_pendaftaran" name="id_pendaftaran" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                        <option value="">-- Pilih Pendaftaran --</option>
                                        <?php 
                                        if ($pendaftaran_result->num_rows > 0) {
                                            while ($row = $pendaftaran_result->fetch_assoc()) {
                                                echo '<option value="' . $row['id_pendaftaran'] . '">' . 
                                                     date('d M Y', strtotime($row['tanggal_kunjungan'])) . 
                                                     ' - Dr. ' . htmlspecialchars($row['dokter_nama']) . 
                                                     ' (' . htmlspecialchars($row['poli_nama']) . ')</option>';
                                            }
                                        } else {
                                            echo '<option value="" disabled>Tidak ada pendaftaran yang menunggu</option>';
                                        }
                                        ?>
                                    </select>
                                    <?php if ($pendaftaran_result->num_rows === 0): ?>
                                    <p class="mt-2 text-sm text-red-600">
                                        Tidak ada pendaftaran yang menunggu pemeriksaan. 
                                        <a href="pendaftaran_tambah.php?id_pasien=<?php echo $patient_id; ?>" class="text-blue-600 hover:text-blue-800">
                                            Tambah pendaftaran baru
                                        </a>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Date of Examination -->
                                <div class="mb-6">
                                    <label for="tanggal_pemeriksaan" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pemeriksaan</label>
                                    <input type="date" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" value="<?php echo date('Y-m-d'); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                </div>
                                
                                <!-- Complaints -->
                                <div class="mb-6">
                                    <label for="keluhan" class="block text-sm font-medium text-gray-700 mb-1">Keluhan</label>
                                    <textarea id="keluhan" name="keluhan" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required placeholder="Tuliskan keluhan pasien"></textarea>
                                </div>
                                
                                <!-- Diagnosis -->
                                <div class="mb-6">
                                    <label for="diagnosa" class="block text-sm font-medium text-gray-700 mb-1">Diagnosa</label>
                                    <textarea id="diagnosa" name="diagnosa" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required placeholder="Tuliskan hasil diagnosa"></textarea>
                                </div>
                                
                                <!-- Treatment -->
                                <div class="mb-6">
                                    <label for="tindakan" class="block text-sm font-medium text-gray-700 mb-1">Tindakan</label>
                                    <textarea id="tindakan" name="tindakan" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Tuliskan tindakan yang dilakukan"></textarea>
                                </div>
                                
                                <!-- Notes -->
                                <div class="mb-6">
                                    <label for="catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                                    <textarea id="catatan" name="catatan" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Catatan tambahan jika ada"></textarea>
                                </div>
                                
                                <!-- Medications Section -->
                                <div class="mb-6">
                                    <div class="flex justify-between items-center mb-3">
                                        <label class="block text-sm font-medium text-gray-700">Resep Obat</label>
                                        <button type="button" id="addMedication" class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-plus mr-2"></i> Tambah Obat
                                        </button>
                                    </div>
                                    
                                    <div id="medications-container" class="space-y-4">
                                        <!-- Medication template, will be dynamically added -->
                                    </div>
                                </div>
                                
                                <!-- Prescription Text -->
                                <div class="mb-6">
                                    <label for="resep" class="block text-sm font-medium text-gray-700 mb-1">Catatan Resep</label>
                                    <textarea id="resep" name="resep" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Catatan tambahan untuk resep"></textarea>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save mr-2"></i> Simpan Rekam Medis
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Medication template
            const medicationTemplate = `
                <div class="medication-item bg-gray-50 p-4 rounded-md">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-5">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Nama Obat</label>
                            <select name="obat_ids[]" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                <option value="">-- Pilih Obat --</option>
                                <?php 
                                if ($obat_list->num_rows > 0) {
                                    // Reset pointer to beginning
                                    $obat_list->data_seek(0);
                                    while ($obat = $obat_list->fetch_assoc()) {
                                        echo '<option value="' . $obat['id_obat'] . '">' . 
                                             htmlspecialchars($obat['nama']) . ' (' . 
                                             htmlspecialchars($obat['satuan']) . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Jumlah</label>
                            <input type="number" name="obat_qty[]" min="1" value="1" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Aturan Pakai</label>
                            <input type="text" name="obat_aturan[]" placeholder="Contoh: 3x1 Setelah Makan" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                        </div>
                        <div class="md:col-span-1 flex items-end justify-center">
                            <button type="button" class="remove-medication text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add medication button handler
            document.getElementById('addMedication').addEventListener('click', function() {
                const container = document.getElementById('medications-container');
                const newMedicationDiv = document.createElement('div');
                newMedicationDiv.innerHTML = medicationTemplate;
                container.appendChild(newMedicationDiv);
                
                // Add remove button handler for the new element
                newMedicationDiv.querySelector('.remove-medication').addEventListener('click', function() {
                    container.removeChild(newMedicationDiv);
                });
            });
            
            // Add initial medication field
            document.getElementById('addMedication').click();
        });
    </script>
</body>
</html>