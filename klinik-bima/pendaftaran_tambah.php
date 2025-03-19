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
if ($level != 'admin' && $level != 'pendaftaran') {
    header("Location: dashboard.php");
    exit;
}

// Check if patient ID is provided for pre-fill
$patient_id = isset($_GET['id_pasien']) ? intval($_GET['id_pasien']) : 0;
$patient_data = null;

if ($patient_id > 0) {
    // Get patient data
    $query = "SELECT * FROM pasien WHERE id_pasien = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $patient_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// Get all doctors - Modified query to handle potential schema differences
$check_column_query = "SHOW COLUMNS FROM Dokter LIKE 'id_poliklinik'";
$check_result = $conn->query($check_column_query);
$has_poliklinik_column = $check_result->num_rows > 0;

if ($has_poliklinik_column) {
    // If the relationship exists, use join query
    $query = "SELECT d.*, p.nama as poli_nama FROM Dokter d 
            LEFT JOIN Poliklinik p ON d.id_poliklinik = p.id_poliklinik
            ORDER BY d.nama";
} else {
    // If relationship doesn't exist, use simple query
    $query = "SELECT * FROM Dokter ORDER BY nama";
}
$doctors = $conn->query($query);

// Get all poliklinik
$query = "SELECT * FROM Poliklinik ORDER BY nama";
$poliklinik = $conn->query($query);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $id_pasien = $_POST['id_pasien'];
    $id_dokter = $_POST['id_dokter'];
    $id_poliklinik = $_POST['id_poliklinik'];
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'];
    $waktu_kunjungan = $_POST['waktu_kunjungan'];
    $keluhan_awal = $_POST['keluhan_awal'];
    $cara_bayar = $_POST['cara_bayar'];
    $no_bpjs = isset($_POST['no_bpjs']) ? $_POST['no_bpjs'] : '';
    $status = 'menunggu';
    
    // Generate nomor antrian
    $query = "SELECT COUNT(*) as total FROM Pendaftaran 
            WHERE DATE(tanggal_kunjungan) = ? AND id_dokter = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $tanggal_kunjungan, $id_dokter);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nomor_antrian = $row['total'] + 1;
    $stmt->close();
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert into Pendaftaran table
        $query = "INSERT INTO Pendaftaran (id_pasien, id_dokter, id_poliklinik, tanggal_pendaftaran, 
                  tanggal_kunjungan, waktu_kunjungan, keluhan_awal, cara_bayar, no_bpjs, status, nomor_antrian) 
                  VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiisssssi", $id_pasien, $id_dokter, $id_poliklinik, $tanggal_kunjungan, 
                          $waktu_kunjungan, $keluhan_awal, $cara_bayar, $no_bpjs, $status, $nomor_antrian);
        $stmt->execute();
        
        $id_pendaftaran = $conn->insert_id;
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = "Pendaftaran berhasil disimpan. Nomor antrian: " . $nomor_antrian;
        $_SESSION['msg_type'] = "success";
        
        // Redirect to appropriate page
        if ($patient_id > 0) {
            header("Location: pasien_detail.php?id=" . $patient_id);
        } else {
            header("Location: pendaftaran.php");
        }
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
    <title>Tambah Pendaftaran - Sistem Informasi Klinik</title>
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
                                <a href="pasien.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-users mr-3 text-gray-500"></i>
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
                                <a href="pendaftaran.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-clipboard-list mr-3 text-blue-500"></i>
                                    Pendaftaran
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
                                
                                <?php if ($level == 'pendaftaran'): ?>
                                <a href="pasien.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-users mr-3 text-gray-500"></i>
                                    Pasien
                                </a>
                                <a href="pendaftaran.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-clipboard-list mr-3 text-blue-500"></i>
                                    Pendaftaran
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
                            <h1 class="text-2xl font-semibold text-gray-900">Tambah Pendaftaran</h1>
                            <p class="mt-1 text-sm text-gray-600">Tambahkan data pendaftaran pasien baru</p>
                        </div>
                        <div>
                            <a href="<?php echo $patient_id > 0 ? 'pasien_detail.php?id=' . $patient_id : 'pendaftaran.php'; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-arrow-left mr-2"></i> Kembali
                            </a>
                        </div>
                    </div>
                    
                    <!-- Registration Form -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 bg-gray-50">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Form Pendaftaran</h3>
                            <p class="mt-1 text-sm text-gray-500">Masukkan data pendaftaran pasien</p>
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
                                <!-- Patient Selection -->
                                <div class="mb-6">
                                    <label for="id_pasien" class="block text-sm font-medium text-gray-700 mb-1">Pasien</label>
                                    <?php if ($patient_id > 0 && $patient_data): ?>
                                        <input type="hidden" name="id_pasien" value="<?php echo $patient_id; ?>">
                                        <div class="flex items-center">
                                            <div class="px-4 py-2 bg-gray-100 rounded-md">
                                                <span class="font-medium"><?php echo htmlspecialchars($patient_data['nama']); ?></span>
                                                <br>
                                                <span class="text-sm text-gray-600">No. RM: <?php echo htmlspecialchars($patient_data['no_rekam_medis']); ?></span>
                                            </div>
                                            <a href="pasien.php" class="ml-4 text-sm text-blue-600 hover:text-blue-800">Pilih pasien lain</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center">
                                            <select id="id_pasien" name="id_pasien" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                                <option value="">-- Pilih Pasien --</option>
                                                <?php
                                                // Get all patients
                                                $query = "SELECT id_pasien, nama, no_rekam_medis FROM pasien ORDER BY nama";
                                                $patients = $conn->query($query);
                                                
                                                if ($patients->num_rows > 0) {
                                                    while ($patient = $patients->fetch_assoc()) {
                                                        echo '<option value="' . $patient['id_pasien'] . '">' . 
                                                             htmlspecialchars($patient['nama']) . ' - RM: ' . 
                                                             htmlspecialchars($patient['no_rekam_medis']) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <a href="pasien_tambah.php" class="ml-4 text-sm text-blue-600 hover:text-blue-800">Tambah pasien baru</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Doctor Selection -->
                                <div class="mb-6">
                                    <label for="id_dokter" class="block text-sm font-medium text-gray-700 mb-1">Dokter</label>
                                    <select id="id_dokter" name="id_dokter" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                        <option value="">-- Pilih Dokter --</option>
                                        <?php 
                                        if ($doctors->num_rows > 0) {
                                            while ($doctor = $doctors->fetch_assoc()) {
                                                echo '<option value="' . $doctor['id_dokter'] . '"';
                                                
                                                // Add data-poli attribute only if relationship exists
                                                if ($has_poliklinik_join && isset($doctor['id_poliklinik'])) {
                                                    echo ' data-poli="' . $doctor['id_poliklinik'] . '"';
                                                }
                                                
                                                echo '>Dr. ' . htmlspecialchars($doctor['nama']);
                                                
                                                // Add poliklinik name if available
                                                if ($has_poliklinik_join && isset($doctor['poli_nama'])) {
                                                    echo ' - ' . htmlspecialchars($doctor['poli_nama']);
                                                }
                                                
                                                echo '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Poliklinik Selection -->
                                <div class="mb-6">
                                    <label for="id_poliklinik" class="block text-sm font-medium text-gray-700 mb-1">Poliklinik</label>
                                    <select id="id_poliklinik" name="id_poliklinik" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                        <option value="">-- Pilih Poliklinik --</option>
                                        <?php 
                                        if ($poliklinik->num_rows > 0) {
                                            while ($poli = $poliklinik->fetch_assoc()) {
                                                echo '<option value="' . $poli['id_poliklinik'] . '">' . 
                                                     htmlspecialchars($poli['nama']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Visit Date -->
                                <div class="mb-6">
                                    <label for="tanggal_kunjungan" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kunjungan</label>
                                    <input type="date" id="tanggal_kunjungan" name="tanggal_kunjungan" value="<?php echo date('Y-m-d'); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                </div>
                                
                                <!-- Visit Time -->
                                <div class="mb-6">
                                    <label for="waktu_kunjungan" class="block text-sm font-medium text-gray-700 mb-1">Waktu Kunjungan</label>
                                    <select id="waktu_kunjungan" name="waktu_kunjungan" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                        <option value="">-- Pilih Waktu --</option>
                                        <option value="08:00">08:00</option>
                                        <option value="09:00">09:00</option>
                                        <option value="10:00">10:00</option>
                                        <option value="11:00">11:00</option>
                                        <option value="13:00">13:00</option>
                                        <option value="14:00">14:00</option>
                                        <option value="15:00">15:00</option>
                                        <option value="16:00">16:00</option>
                                    </select>
                                </div>
                                
                                <!-- Initial Complaint -->
                                <div class="mb-6">
                                    <label for="keluhan_awal" class="block text-sm font-medium text-gray-700 mb-1">Keluhan Awal</label>
                                    <textarea id="keluhan_awal" name="keluhan_awal" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required placeholder="Tuliskan keluhan awal pasien"></textarea>
                                </div>
                                
                                <!-- Payment Method -->
                                <div class="mb-6">
                                    <label for="cara_bayar" class="block text-sm font-medium text-gray-700 mb-1">Cara Pembayaran</label>
                                    <select id="cara_bayar" name="cara_bayar" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                        <option value="tunai">Tunai</option>
                                        <option value="bpjs">BPJS</option>
                                        <option value="asuransi">Asuransi Lainnya</option>
                                    </select>
                                </div>
                                
                                <!-- BPJS Number (conditionally shown) -->
                                <div id="bpjs-section" class="mb-6 hidden">
                                    <label for="no_bpjs" class="block text-sm font-medium text-gray-700 mb-1">Nomor BPJS</label>
                                    <input type="text" id="no_bpjs" name="no_bpjs" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Masukkan nomor BPJS">
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save mr-2"></i> Simpan Pendaftaran
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
            // Show/hide BPJS number field based on payment method selection
            const caraBayarSelect = document.getElementById('cara_bayar');
            const bpjsSection = document.getElementById('bpjs-section');
            
            caraBayarSelect.addEventListener('change', function() {
                if (this.value === 'bpjs') {
                    bpjsSection.classList.remove('hidden');
                    document.getElementById('no_bpjs').setAttribute('required', 'required');
                } else {
                    bpjsSection.classList.add('hidden');
                    document.getElementById('no_bpjs').removeAttribute('required');
                }
            });
            
           // Auto-select poliklinik based on selected doctor
            const dokterSelect = document.getElementById('id_dokter');
            const poliklinikSelect = document.getElementById('id_poliklinik');

            dokterSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const poliId = selectedOption.getAttribute('data-poli');
                
                if (poliId) {
                    for (let i = 0; i < poliklinikSelect.options.length; i++) {
                        if (poliklinikSelect.options[i].value === poliId) {
                            poliklinikSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>