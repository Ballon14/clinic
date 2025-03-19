<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include('conn/db.php');

// Get user data
$user_id = $_SESSION['user_id'];
$level = $_SESSION['level'];
$nama = $_SESSION['nama'];

// Fetch statistics for admin dashboard
$stats = [
    'total_pasien' => 0,
    'total_dokter' => 0,
    'total_pendaftaran' => 0,
    'total_layanan' => 0
];

if ($level == 'admin') {
    // Total pasien
    $query = "SELECT COUNT(*) as total FROM Pasien";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_pasien'] = $row['total'];
    }
    
    // Total dokter
    $query = "SELECT COUNT(*) as total FROM Dokter";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_dokter'] = $row['total'];
    }
    
    // Total pendaftaran
    $query = "SELECT COUNT(*) as total FROM Pendaftaran";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_pendaftaran'] = $row['total'];
    }
    
    // Total layanan unggulan
    $query = "SELECT COUNT(*) as total FROM Layanan_Unggulan";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_layanan'] = $row['total'];
    }
}

// Fetch recent appointments
$recent_appointments = [];
if ($level == 'admin' || $level == 'dokter' || $level == 'petugas_pendaftaran') {
    $query = "SELECT p.id_pendaftaran, ps.nama as nama_pasien, pl.nama as nama_poliklinik, 
              d.nama as nama_dokter, p.tanggal_pendaftaran, p.status
              FROM Pendaftaran p
              JOIN Pasien ps ON p.id_pasien = ps.id_pasien
              JOIN Poliklinik pl ON p.id_poliklinik = pl.id_poliklinik
              JOIN Dokter d ON p.id_dokter = d.id_dokter
              ORDER BY p.tanggal_pendaftaran DESC
              LIMIT 5";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_appointments[] = $row;
        }
    }
}

// Fetch user's appointments (if user is a patient)
$user_appointments = [];
if ($level == 'pasien') {
    // Get patient ID
    $query = "SELECT id_pasien FROM Pasien WHERE id_user = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        $patient_id = $patient['id_pasien'];
        
        // Get appointments
        $query = "SELECT p.id_pendaftaran, pl.nama as nama_poliklinik, 
                  d.nama as nama_dokter, p.tanggal_pendaftaran, p.status
                  FROM Pendaftaran p
                  JOIN Poliklinik pl ON p.id_poliklinik = pl.id_poliklinik
                  JOIN Dokter d ON p.id_dokter = d.id_dokter
                  WHERE p.id_pasien = ?
                  ORDER BY p.tanggal_pendaftaran DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $user_appointments[] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Informasi Klinik</title>
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
                                <a href="dashboard.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-home mr-3 text-blue-500"></i>
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
                                <a href="jadwal.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-calendar-alt mr-3 text-gray-500"></i>
                                    Jadwal
                                </a>
                                <a href="pemeriksaan.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-stethoscope mr-3 text-gray-500"></i>
                                    Pemeriksaan
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($level == 'pasien'): ?>
                                <a href="pendaftaran.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-clipboard-list mr-3 text-gray-500"></i>
                                    Pendaftaran
                                </a>
                                <a href="riwayat.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-history mr-3 text-gray-500"></i>
                                    Riwayat Pemeriksaan
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($level == 'apoteker'): ?>
                                <a href="resep.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-prescription mr-3 text-gray-500"></i>
                                    Resep
                                </a>
                                <a href="stok_obat.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-pills mr-3 text-gray-500"></i>
                                    Stok Obat
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($level == 'petugas_lab'): ?>
                                <a href="lab_request.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-flask mr-3 text-gray-500"></i>
                                    Permintaan Lab
                                </a>
                                <a href="hasil_lab.php" class="text-gray-600 hover:bg-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                    <i class="fas fa-file-medical-alt mr-3 text-gray-500"></i>
                                    Hasil Lab
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
                    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                    
                    <?php if ($level == 'admin'): ?>
                    <!-- Admin Dashboard -->
                    <div class="mt-6">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            <!-- Card 1 -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                            <i class="fas fa-users text-white"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Pasien</dt>
                                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['total_pasien']; ?></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-4 sm:px-6">
                                    <div class="text-sm">
                                        <a href="pasien.php" class="font-medium text-blue-600 hover:text-blue-500">Lihat semua pasien</a>
                                    </div>                              
                                </div>
                            </div>
                        <!-- Card 2 -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                            <i class="fas fa-user-md text-white"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Dokter</dt>
                                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['total_dokter']; ?></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-4 sm:px-6">
                                    <div class="text-sm">
                                        <a href="dokter.php" class="font-medium text-green-600 hover:text-green-500">Lihat semua dokter</a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card 3 -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                            <i class="fas fa-calendar-check text-white"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Pendaftaran</dt>
                                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['total_pendaftaran']; ?></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-4 sm:px-6">
                                    <div class="text-sm">
                                        <a href="pendaftaran_list.php" class="font-medium text-purple-600 hover:text-purple-500">Lihat semua pendaftaran</a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card 4 -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                            <i class="fas fa-star text-white"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Layanan Unggulan</dt>
                                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['total_layanan']; ?></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-4 sm:px-6">
                                    <div class="text-sm">
                                        <a href="layanan_unggulan.php" class="font-medium text-yellow-600 hover:text-yellow-500">Lihat semua layanan</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <h2 class="text-lg font-medium text-gray-900">Pendaftaran Terbaru</h2>
                        <div class="mt-4 flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pasien</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poliklinik</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokter</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php if (empty($recent_appointments)): ?>
                                                <tr>
                                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data pendaftaran</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_appointments as $appointment): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $appointment['nama_pasien']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['nama_poliklinik']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['nama_dokter']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($appointment['tanggal_pendaftaran'])); ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <?php if ($appointment['status'] == 'Menunggu'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu</span>
                                                            <?php elseif ($appointment['status'] == 'Dalam Pemeriksaan'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Dalam Pemeriksaan</span>
                                                            <?php elseif ($appointment['status'] == 'Selesai'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($level == 'dokter'): ?>
                    <!-- Doctor Dashboard -->
                    <div class="mt-6">
                        <h2 class="text-lg font-medium text-gray-900">Jadwal Pemeriksaan Hari Ini</h2>
                        <div class="mt-4 flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pasien</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poliklinik</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Antrian</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php if (empty($recent_appointments)): ?>
                                                <tr>
                                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada jadwal pemeriksaan hari ini</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_appointments as $appointment): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $appointment['nama_pasien']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['nama_poliklinik']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['no_antrian']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <?php if ($appointment['status'] == 'Menunggu'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu</span>
                                                            <?php elseif ($appointment['status'] == 'Dalam Pemeriksaan'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Dalam Pemeriksaan</span>
                                                            <?php elseif ($appointment['status'] == 'Selesai'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                            <a href="pemeriksaan.php?id=<?php echo $appointment['id_pendaftaran']; ?>" class="text-blue-600 hover:text-blue-900">Periksa</a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($level == 'pasien'): ?>
                    <!-- Patient Dashboard -->
                    <div class="mt-6">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <!-- Info Card -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Klinik</h3>
                                    <div class="mt-4">
                                        <div class="flex items-center mb-3">
                                            <div class="flex-shrink-0 text-blue-500">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-gray-700">Jam Buka: 08:00 - 20:00 WIB</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center mb-3">
                                            <div class="flex-shrink-0 text-blue-500">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-gray-700">Telepon: (021) 123-4567</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 text-blue-500">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-gray-700">Alamat: Jl. Sehat No. 123, Jakarta</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Register Button -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Pendaftaran Pemeriksaan</h3>
                                    < class="mt-4">
                                        <p class="text-sm text-gray-500">Silakan daftar untuk pemeriksaan di klinik kami. Pilih poliklinik dan dokter sesuai kebutuhan Anda.</p>
                                        <div class="mt-4">
                                            <a href="pendaftaran.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                Daftar Pemeriksaan
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <h2 class="text-lg font-medium text-gray-900">Riwayat Pendaftaran</h2>
                            <div class="mt-4 flex flex-col">
                                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poliklinik</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokter</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    <?php if (empty($user_appointments)): ?>
                                                    <tr>
                                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Anda belum pernah mendaftar pemeriksaan</td>
                                                    </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($user_appointments as $appointment): ?>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['nama_poliklinik']; ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['nama_dokter']; ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($appointment['tanggal_pendaftaran'])); ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <?php if ($appointment['status'] == 'Menunggu'): ?>
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu</span>
                                                                <?php elseif ($appointment['status'] == 'Dalam Pemeriksaan'): ?>
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Dalam Pemeriksaan</span>
                                                                <?php elseif ($appointment['status'] == 'Selesai'): ?>
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                                <a href="riwayat_detail.php?id=<?php echo $appointment['id_pendaftaran']; ?>" class="text-blue-600 hover:text-blue-900">Lihat Detail</a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($level == 'apoteker'): ?>
                    <!-- Pharmacist Dashboard -->
                    <div class="mt-6">
                        <h2 class="text-lg font-medium text-gray-900">Resep Obat Terbaru</h2>
                        <div class="mt-4 flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pasien</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokter</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr>
                                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada resep baru</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <h2 class="text-lg font-medium text-gray-900">Stok Obat Menipis</h2>
                        <div class="mt-4 flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Obat</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr>
                                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada obat dengan stok menipis</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($level == 'petugas_lab'): ?>
                    <!-- Lab Officer Dashboard -->
                    <div class="mt-6">
                        <h2 class="text-lg font-medium text-gray-900">Permintaan Laboratorium Terbaru</h2>
                        <div class="mt-4 flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pasien</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokter</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Pemeriksaan</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr>
                                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada permintaan laboratorium baru</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($level == 'petugas_pendaftaran'): ?>
                    <!-- Registration Officer Dashboard -->
                    <div class="mt-6">
                        <h2 class="text-lg font-medium text-gray-900">Pendaftaran Hari Ini</h2>
                        <div class="mt-4 flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pasien</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poliklinik</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokter</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Antrian</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php if (empty($recent_appointments)): ?>
                                                <tr>
                                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada pendaftaran hari ini</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_appointments as $appointment): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $appointment['nama_pasien']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['nama_poliklinik']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['nama_dokter']; ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $appointment['no_antrian']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <?php if ($appointment['status'] == 'Menunggu'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu</span>
                                                            <?php elseif ($appointment['status'] == 'Dalam Pemeriksaan'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Dalam Pemeriksaan</span>
                                                            <?php elseif ($appointment['status'] == 'Selesai'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                            <a href="pendaftaran_detail.php?id=<?php echo $appointment['id_pendaftaran']; ?>" class="text-blue-600 hover:text-blue-900">Detail</a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Tambah Pendaftaran Baru</h2>
                            <a href="pendaftaran_tambah.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i> Tambah Pendaftaran
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Mobile menu -->
                    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200">
                        <div class="flex justify-around">
                            <a href="dashboard.php" class="py-3 px-4 text-blue-500">
                                <i class="fas fa-home block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Dashboard</span>
                            </a>
                            
                            <?php if ($level == 'admin'): ?>
                            <a href="pasien.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-users block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Pasien</span>
                            </a>
                            <a href="dokter.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-user-md block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Dokter</span>
                            </a>
                            <a href="laporan.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-chart-bar block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Laporan</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($level == 'pasien'): ?>
                            <a href="pendaftaran.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-clipboard-list block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Daftar</span>
                            </a>
                            <a href="riwayat.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-history block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Riwayat</span>
                            </a>
                            <a href="profile.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-user block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Profil</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($level == 'dokter'): ?>
                            <a href="jadwal.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-calendar-alt block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Jadwal</span>
                            </a>
                            <a href="pemeriksaan.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-stethoscope block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Periksa</span>
                            </a>
                            <a href="profile.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-user block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Profil</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($level == 'apoteker'): ?>
                            <a href="resep.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-prescription block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Resep</span>
                            </a>
                            <a href="stok_obat.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-pills block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Stok</span>
                            </a>
                            <a href="profile.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-user block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Profil</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($level == 'petugas_lab'): ?>
                            <a href="lab_request.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-flask block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Permintaan</span>
                            </a>
                            <a href="hasil_lab.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-file-medical-alt block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Hasil</span>
                            </a>
                            <a href="profile.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-user block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Profil</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($level == 'petugas_pendaftaran'): ?>
                            <a href="pendaftaran_list.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-clipboard-list block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Pendaftaran</span>
                            </a>
                            <a href="pasien.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-users block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Pasien</span>
                            </a>
                            <a href="profile.php" class="py-3 px-4 text-gray-500">
                                <i class="fas fa-user block text-center text-xl mb-1"></i>
                                <span class="block text-xs">Profil</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-white">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:items-center md:justify-between">
                <div class="mt-8 md:mt-0 md:order-1">
                    <p class="text-center text-base text-gray-400">
                        &copy; <?php echo date('Y'); ?> Klinik Bima Husada. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script>
        // Add any JavaScript functionality needed for the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const profileDropdown = document.querySelector('.group-hover\\:block');
            const profileButton = document.querySelector('.group');
            
            if (profileButton && profileDropdown) {
                profileButton.addEventListener('click', function(e) {
                    profileDropdown.classList.toggle('hidden');
                    e.stopPropagation();
                });
                
                document.addEventListener('click', function() {
                    profileDropdown.classList.add('hidden');
                });
            }
            
            // Add any charts or additional functionality here
        });
    </script>
</body>
</html>