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
if ($level != 'admin' && $level != 'petugas_pendaftaran') {
    header("Location: dashboard.php");
    exit;
}

// Generate unique medical record number
function generateMedicalRecordNumber($conn) {
    $prefix = date('Ym');
    $query = "SELECT MAX(SUBSTRING(no_rekam_medis, 8)) as max_id FROM Pasien WHERE no_rekam_medis LIKE '$prefix%'";
    $result = $conn->query($query);
    $data = $result->fetch_assoc();
    
    $max_id = (int)($data['max_id'] ?? 0);
    $next_id = $max_id + 1;
    
    return $prefix . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

$no_rekam_medis = generateMedicalRecordNumber($conn);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $errors = [];
    
    // Validate name
    if (empty($_POST['nama'])) {
        $errors[] = "Nama pasien wajib diisi";
    }
    
    // Validate NIK
    if (empty($_POST['nik'])) {
        $errors[] = "NIK wajib diisi";
    } elseif (strlen($_POST['nik']) != 16) {
        $errors[] = "NIK harus 16 digit";
    }
    
    // Validate date of birth
    if (empty($_POST['tanggal_lahir'])) {
        $errors[] = "Tanggal lahir wajib diisi";
    }
    
    // Validate gender
    if (empty($_POST['jenis_kelamin'])) {
        $errors[] = "Jenis kelamin wajib dipilih";
    }
    
    // Validate address
    if (empty($_POST['alamat'])) {
        $errors[] = "Alamat wajib diisi";
    }
    
    // Validate phone number
    if (empty($_POST['no_telp'])) {
        $errors[] = "Nomor telepon wajib diisi";
    }
    
    // If no errors, insert new patient
    if (empty($errors)) {
        $nama_pasien = $_POST['nama'];
        $nik = $_POST['nik'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $alamat = $_POST['alamat'];
        $no_telp = $_POST['no_telp'];
        $email = $_POST['email'] ?? null;
        $golongan_darah = $_POST['golongan_darah'] ?? null;
        $alergi = $_POST['alergi'] ?? null;
        $riwayat_penyakit = $_POST['riwayat_penyakit'] ?? null;
        $jenis_asuransi = $_POST['jenis_asuransi'] ?? null;
        $no_asuransi = $_POST['no_asuransi'] ?? null;
        
        $query = "INSERT INTO Pasien (no_rekam_medis, nama, nik, tanggal_lahir, jenis_kelamin, alamat, no_telp, email, golongan_darah, jenis_asuransi, no_asuransi, alergi, riwayat_penyakit, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssss", $no_rekam_medis, $nama_pasien, $nik, $tanggal_lahir, $jenis_kelamin, $alamat, $no_telp, $email, $golongan_darah, $jenis_asuransi, $no_asuransi, $alergi, $riwayat_penyakit);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Pasien berhasil ditambahkan dengan No. RM: " . $no_rekam_medis;
            $_SESSION['msg_type'] = "success";
            header("Location: pasien.php");
            exit;
        } else {
            $errors[] = "Gagal menambahkan pasien: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pasien - Sistem Informasi Klinik</title>
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
                                
                                <?php if ($level == 'petugas_pendaftaran'): ?>
                                <a href="pendaftaran_list.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-clipboard-list mr-3 text-gray-500"></i>
                                    Pendaftaran
                                </a>
                                <a href="pasien.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-users mr-3 text-blue-500"></i>
                                    Pasien
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
                    <div class="mb-6">
                        <h1 class="text-2xl font-semibold text-gray-900">Tambah Pasien Baru</h1>
                        <p class="mt-1 text-sm text-gray-600">Tambahkan data pasien baru ke dalam sistem</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul class="list-disc pl-5">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bg-white shadow rounded-lg p-6">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="no_rekam_medis" class="block text-sm font-medium text-gray-700">No. Rekam Medis</label>
                                    <input type="text" name="no_rekam_medis" id="no_rekam_medis" value="<?php echo $no_rekam_medis; ?>" class="mt-1 bg-gray-100 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" readonly>
                                    <p class="mt-1 text-xs text-gray-500">Nomor rekam medis akan dibuat otomatis oleh sistem</p>
                                </div>
                                
                                <div>
                                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-600">*</span></label>
                                    <input type="text" name="nama" id="nama" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                </div>
                                
                                <div>
                                    <label for="nik" class="block text-sm font-medium text-gray-700">NIK <span class="text-red-600">*</span></label>
                                    <input type="text" name="nik" id="nik" value="<?php echo isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : ''; ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" maxlength="16" required>
                                    <p class="mt-1 text-xs text-gray-500">Nomor Induk Kependudukan (16 digit)</p>
                                </div>
                                
                                <div>
                                    <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700">Tanggal Lahir <span class="text-red-600">*</span></label>
                                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="<?php echo isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : ''; ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                </div>
                                
                                <div>
                                    <label for="jenis_kelamin" class="block text-sm font-medium text-gray-700">Jenis Kelamin <span class="text-red-600">*</span></label>
                                    <select name="jenis_kelamin" id="jenis_kelamin" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="P" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="alamat" class="block text-sm font-medium text-gray-700">Alamat <span class="text-red-600">*</span></label>
                                    <textarea name="alamat" id="alamat" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                                </div>
                                
                                <div>
                                    <label for="no_telp" class="block text-sm font-medium text-gray-700">No. Telepon <span class="text-red-600">*</span></label>
                                    <input type="tel" name="no_telp" id="no_telp" value="<?php echo isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : ''; ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>
                                
                                <div>
                                    <label for="golongan_darah" class="block text-sm font-medium text-gray-700">Golongan Darah</label>
                                    <select name="golongan_darah" id="golongan_darah" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <option value="">Pilih Golongan Darah</option>
                                        <option value="A" <?php echo (isset($_POST['golongan_darah']) && $_POST['golongan_darah'] == 'A') ? 'selected' : ''; ?>>A</option>
                                        <option value="B" <?php echo (isset($_POST['golongan_darah']) && $_POST['golongan_darah'] == 'B') ? 'selected' : ''; ?>>B</option>
                                        <option value="AB" <?php echo (isset($_POST['golongan_darah']) && $_POST['golongan_darah'] == 'AB') ? 'selected' : ''; ?>>AB</option>
                                        <option value="O" <?php echo (isset($_POST['golongan_darah']) && $_POST['golongan_darah'] == 'O') ? 'selected' : ''; ?>>O</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="jenis_asuransi" class="block text-sm font-medium text-gray-700">Jenis Asuransi</label>
                                    <select name="jenis_asuransi" id="jenis_asuransi" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <option value="">Pilih Jenis Asuransi</option>
                                        <option value="BPJS" <?php echo (isset($_POST['jenis_asuransi']) && $_POST['jenis_asuransi'] == 'BPJS') ? 'selected' : ''; ?>>BPJS</option>
                                        <option value="Mandiri" <?php echo (isset($_POST['jenis_asuransi']) && $_POST['jenis_asuransi'] == 'Mandiri') ? 'selected' : ''; ?>>Mandiri</option>
                                        <option value="Asuransi Swasta" <?php echo (isset($_POST['jenis_asuransi']) && $_POST['jenis_asuransi'] == 'Asuransi Swasta') ? 'selected' : ''; ?>>Asuransi Swasta</option>
                                        <option value="Tidak Ada" <?php echo (isset($_POST['jenis_asuransi']) && $_POST['jenis_asuransi'] == 'Tidak Ada') ? 'selected' : ''; ?>>Tidak Ada</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="no_asuransi" class="block text-sm font-medium text-gray-700">No. Asuransi</label>
                                    <input type="text" name="no_asuransi" id="no_asuransi" value="<?php echo isset($_POST['no_asuransi']) ? htmlspecialchars($_POST['no_asuransi']) : ''; ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak memiliki asuransi</p>
                                </div>
                                <div>
                                    <label for="alergi" class="block text-sm font-medium text-gray-700">Alergi</label>
                                    <input type="text" name="alergi" id="alergi" value="<?php echo isset($_POST['alergi']) ? htmlspecialchars($_POST['alergi']) : ''; ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    <p class="mt-1 text-xs text-gray-500">Pisahkan dengan koma jika lebih dari satu</p>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="riwayat_penyakit" class="block text-sm font-medium text-gray-700">Riwayat Penyakit</label>
                                    <textarea name="riwayat_penyakit" id="riwayat_penyakit" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"><?php echo isset($_POST['riwayat_penyakit']) ? htmlspecialchars($_POST['riwayat_penyakit']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex items-center justify-between">
                                <div class="text-sm">
                                    <span class="text-red-600">*</span> Wajib diisi
                                </div>
                                <div class="flex space-x-3">
                                    <a href="pasien.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Batal
                                    </a>
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Simpan Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Navbar for Mobile -->
            <div class="md:hidden fixed bottom-0 w-full bg-white shadow-lg border-t">
                <div class="flex justify-around py-3">
                    <a href="dashboard.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
                        <i class="fas fa-home text-lg"></i>
                        <span class="text-xs">Dashboard</span>
                    </a>
                    <a href="pasien.php" class="flex flex-col items-center text-blue-600">
                        <i class="fas fa-users text-lg"></i>
                        <span class="text-xs">Pasien</span>
                    </a>
                    <a href="profile.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
                        <i class="fas fa-user text-lg"></i>
                        <span class="text-xs">Profil</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // NIK validation - only numbers allowed
        document.getElementById('nik').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 16) {
                this.value = this.value.slice(0, 16);
            }
        });
        
        // Phone number validation - only numbers and + allowed
        document.getElementById('no_telp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9+]/g, '');
        });
    </script>
</body>
</html>