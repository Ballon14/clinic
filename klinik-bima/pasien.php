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

// Check if user has permission to access this page
if($level != 'admin' && $level != 'petugas_pendaftaran') {
    header("Location: dashboard.php");
    exit;
}

// Process delete operation
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM Pasien WHERE id_pasien = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        $_SESSION['message'] = "Pasien berhasil dihapus.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menghapus pasien: " . $conn->error;
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: pasien.php");
    exit;
}

// Setup pagination
$limit = 10; // Records per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
if(!empty($search)) {
    $search = '%' . $search . '%';
    $where = "WHERE nama LIKE ? OR no_rekam_medis LIKE ? OR no_telp LIKE ?";
}

// Count total records for pagination
if(!empty($where)) {
    $query = "SELECT COUNT(*) as total FROM Pasien $where";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $search, $search, $search);
} else {
    $query = "SELECT COUNT(*) as total FROM Pasien";
    $stmt = $conn->prepare($query);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$total_records = $data['total'];
$total_pages = ceil($total_records / $limit);

// Fetch patients data
if(!empty($where)) {
    $query = "SELECT * FROM Pasien $where ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssii", $search, $search, $search, $start, $limit);
} else {
    $query = "SELECT * FROM Pasien ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$result = $stmt->get_result();
$patients = [];
while($row = $result->fetch_assoc()) {
    $patients[] = $row;

}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien - Sistem Informasi Klinik</title>
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
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-semibold text-gray-900">Data Pasien</h1>
                        <a href="pasien_tambah.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Tambah Pasien
                        </a>
                    </div>
                    
                    <?php if(isset($_SESSION['message'])): ?>
                    <div class="mt-4 p-4 mb-4 <?php echo $_SESSION['msg_type'] == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> rounded-lg">
                        <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['msg_type']);
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Search box -->
                    <div class="mt-6">
                        <form action="pasien.php" method="GET" class="flex items-center">
                            <div class="relative flex-grow">
                                <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Cari nama, no rekam medis, atau no telepon..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                            <button type="submit" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cari
                            </button>
                            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="pasien.php" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Reset
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <!-- Patient data table -->
                    <div class="mt-6">
                        <div class="flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. RM</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Lahir</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Kelamin</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Telepon</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php if(empty($patients)): ?>
                                                <tr>
                                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data pasien yang ditemukan</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach($patients as $patient): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($patient['no_rekam_medis']); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($patient['nama']); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo date('d/m/Y', strtotime($patient['tanggal_lahir'])); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo $patient['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($patient['no_telp']); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <a href="pasien_detail.php?id=<?php echo $patient['id_pasien']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="pasien_edit.php?id=<?php echo $patient['id_pasien']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="#" onclick="confirmDelete(<?php echo $patient['id_pasien']; ?>)" class="text-red-600 hover:text-red-900">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
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
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="mt-4 flex justify-center">
                        <nav class="inline-flex rounded-md shadow">
                            <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page == $i ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div><!-- Bottom Navbar for Mobile -->
                <div class="md:hidden fixed bottom-0 w-full bg-white shadow-lg border-t">
                    <div class="flex justify-around py-3">
                        <a href="dashboard.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
                            <i class="fas fa-home text-lg"></i>
                            <span class="text-xs">Dashboard</span>
                        </a>
                        <a href="pasien.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600">
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
    
    <!-- Delete confirmation modal -->
    <div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Hapus Pasien
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Apakah Anda yakin ingin menghapus data pasien ini? Tindakan ini tidak dapat dibatalkan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a id="confirmDeleteBtn" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Hapus
                    </a>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Delete confirmation
        function confirmDelete(id) {
            const modal = document.getElementById('deleteModal');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            modal.classList.remove('hidden');
            confirmBtn.href = `pasien.php?delete=${id}`;
        }
        
        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>