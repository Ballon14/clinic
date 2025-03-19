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

// Check if patient ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID Pasien tidak ditemukan";
    $_SESSION['msg_type'] = "danger";
    header("Location: pasien.php");
    exit;
}

$patient_id = $_GET['id'];

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
$stmt->close(); // Close statement before executing a new one

// Get patient's medical records
$query = "SELECT p.*, d.nama as dokter_nama 
          FROM Pemeriksaan p 
          LEFT JOIN Pendaftaran pd ON p.id_pendaftaran = pd.id_pendaftaran
          LEFT JOIN Dokter d ON pd.id_dokter = d.id_dokter 
          WHERE pd.id_pasien = ? 
          ORDER BY p.tanggal_pemeriksaan DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$medical_records = $stmt->get_result();
$stmt->close(); // Close statement

// Get patient's appointments
$query = "SELECT a.*, d.nama as dokter_nama, p.nama as poli_nama 
          FROM Pendaftaran a 
          LEFT JOIN Dokter d ON a.id_dokter = d.id_dokter 
          LEFT JOIN Poliklinik p ON a.id_poliklinik = p.id_poliklinik 
          WHERE a.id_pasien = ? 
          ORDER BY a.tanggal_pendaftaran DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close(); // Close statement
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pasien - Sistem Informasi Klinik</title>
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
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Detail Pasien</h1>
                            <p class="mt-1 text-sm text-gray-600">Informasi lengkap tentang pasien</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="pasien.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-arrow-left mr-2"></i> Kembali
                            </a>
                            <a href="pasien_edit.php?id=<?php echo $patient_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-edit mr-2"></i> Edit
                            </a>
                        </div>
                    </div>
                    
                    <!-- Patient information -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                        <div class="px-4 py-5 sm:px-6 bg-gray-50">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Pasien</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">Detail data pribadi pasien</p>
                        </div>
                        <div class="border-t border-gray-200">
                            <dl>
                                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['nama']); ?></dd>
                                </div>
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">No. Rekam Medis</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['no_rekam_medis']); ?></dd>
                                </div>
                                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">NIK</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['nik']); ?></dd>
                                </div>
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo date('d F Y', strtotime($patient['tanggal_lahir'])); ?></dd>
                                </div>
                                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Jenis Kelamin</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo $patient['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></dd>
                                </div>
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Alamat</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo nl2br(htmlspecialchars($patient['alamat'])); ?></dd>
                                </div>
                                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">No. Telepon</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['no_telp']); ?></dd>
                                </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['email']); ?></dd>
                                </div>
                                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Jenis Asuransi</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['jenis_asuransi']); ?></dd>
                                </div>
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">No. Asuransi</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($patient['no_asuransi']); ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <!-- Medical Records Section -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                        <div class="px-4 py-5 sm:px-6 bg-gray-50 flex justify-between items-center">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Riwayat Rekam Medis</h3>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">Daftar pemeriksaan medis pasien</p>
                            </div>
                            <?php if ($level == 'admin'): ?>
                            <a href="rekam_medis_tambah.php?id_pasien=<?php echo $patient_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-plus mr-2"></i> Tambah Rekam Medis
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="border-t border-gray-200 overflow-x-auto">
                            <?php if ($medical_records->num_rows > 0): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokter</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poliklinik</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosa</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($record = $medical_records->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y', strtotime($record['tanggal_pemeriksaan'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($record['dokter_nama']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($record['poli_nama']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($record['diagnosa']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($record['tindakan']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="rekam_medis_detail.php?id=<?php echo $record['id_rekam_medis']; ?>" class="text-blue-600 hover:text-blue-900">Detail</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="p-6 text-center text-sm text-gray-500">
                                Belum ada data rekam medis untuk pasien ini.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Appointments Section -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 bg-gray-50 flex justify-between items-center">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Riwayat Pendaftaran</h3>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">Daftar kunjungan pasien</p>
                            </div>
                            <a href="pendaftaran_tambah.php?id_pasien=<?php echo $patient_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-plus mr-2"></i> Tambah Pendaftaran
                            </a>
                        </div>
                        <div class="border-t border-gray-200 overflow-x-auto">
                            <?php if ($appointments->num_rows > 0): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Kunjungan</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokter</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poliklinik</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y', strtotime($appointment['tanggal_pendaftaran'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y', strtotime($appointment['tanggal_kunjungan'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($appointment['dokter_nama']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($appointment['poli_nama']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php
                                                switch ($appointment['status']) {
                                                    case 'menunggu':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'dalam_pemeriksaan':
                                                        echo 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'selesai':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'batal':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php
                                                switch ($appointment['status']) {
                                                    case 'menunggu':
                                                        echo 'Menunggu';
                                                        break;
                                                    case 'dalam_pemeriksaan':
                                                        echo 'Dalam Pemeriksaan';
                                                        break;
                                                    case 'selesai':
                                                        echo 'Selesai';
                                                        break;
                                                    case 'batal':
                                                        echo 'Batal';
                                                        break;
                                                    default:
                                                        echo $appointment['status'];
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="pendaftaran_detail.php?id=<?php echo $appointment['id_pendaftaran']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Detail</a>
                                            <?php if ($appointment['status'] == 'menunggu'): ?>
                                            <a href="pendaftaran_edit.php?id=<?php echo $appointment['id_pendaftaran']; ?>" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="p-6 text-center text-sm text-gray-500">
                                Belum ada data pendaftaran untuk pasien ini.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Optional JavaScript for enhanced UI interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const sidebar = document.querySelector('.sidebar');
            
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>