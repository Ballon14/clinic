<?php
session_start();
// if(isset($_SESSION['user_id'])) {
//     header("Location: dashboard.php");
//     exit;
// }

// Database connection
include('conn/db.php');

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama = $_POST['nama'];
    $level = 'pasien'; // Default level for registration
    
    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($nama)) {
        $error = "Semua field harus diisi!";
    } elseif ($password != $confirm_password) {
        $error = "Password tidak sama!";
    } else {
        // Check if username already exists
        $check_query = "SELECT * FROM User WHERE username = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_query = "INSERT INTO User (username, password, nama, level) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssss", $username, $hashed_password, $nama, $level);
            
            if ($insert_stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Registrasi gagal: " . $conn->error;
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
    <title>Registrasi - Sistem Informasi Klinik</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-md">
            <div>
                <img class="mx-auto h-24 w-auto" src="assets/images/logo.png" alt="Logo Klinik">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Registrasi Akun</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Daftar untuk mengakses layanan klinik online
                </p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <form class="mt-8 space-y-6" method="POST" action="">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="nama" class="sr-only">Nama Lengkap</label>
                        <input id="nama" name="nama" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Nama Lengkap">
                    </div>
                    <div>
                        <label for="username" class="sr-only">Username</label>
                        <input id="username" name="username" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Username">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                    <div>
                        <label for="confirm_password" class="sr-only">Konfirmasi Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Konfirmasi Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        Daftar
                    </button>
                </div>
                
                <div class="text-sm text-center">
                    <a href="index.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Sudah punya akun? Login di sini
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>