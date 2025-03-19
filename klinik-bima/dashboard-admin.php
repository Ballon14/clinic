<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Klinik Bima Husada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-blue-700 text-white p-5">
            <h2 class="text-2xl font-bold mb-6">Klinik Bima Husada</h2>
            <ul>
                <li class="mb-4"><a href="#" class="block p-2 hover:bg-blue-500 rounded">Dashboard</a></li>
                <li class="mb-4"><a href="#" class="block p-2 hover:bg-blue-500 rounded">Data Pasien</a></li>
                <li class="mb-4"><a href="#" class="block p-2 hover:bg-blue-500 rounded">Jadwal Dokter</a></li>
                <li class="mb-4"><a href="#" class="block p-2 hover:bg-blue-500 rounded">Rekam Medis</a></li>
                <li class="mb-4"><a href="#" class="block p-2 hover:bg-blue-500 rounded">Pengaturan</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 p-6">
            <h1 class="text-3xl font-semibold mb-4">Dashboard</h1>
            
            <!-- Statistik -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-5 rounded shadow-md">
                    <h2 class="text-xl font-bold">Total Pasien</h2>
                    <p class="text-3xl text-blue-600">1,250</p>
                </div>
                <div class="bg-white p-5 rounded shadow-md">
                    <h2 class="text-xl font-bold">Dokter Bertugas</h2>
                    <p class="text-3xl text-green-600">15</p>
                </div>
                <div class="bg-white p-5 rounded shadow-md">
                    <h2 class="text-xl font-bold">Janji Temu Hari Ini</h2>
                    <p class="text-3xl text-red-600">30</p>
                </div>
            </div>
            
            <!-- Tabel Janji Temu -->
            <div class="bg-white p-5 rounded shadow-md">
                <h2 class="text-2xl font-semibold mb-4">Janji Temu Hari Ini</h2>
                <table class="w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border p-2">Nama Pasien</th>
                            <th class="border p-2">Dokter</th>
                            <th class="border p-2">Waktu</th>
                            <th class="border p-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border p-2">Ahmad Fauzi</td>
                            <td class="border p-2">Dr. Siti</td>
                            <td class="border p-2">09:00</td>
                            <td class="border p-2 text-green-600">Dijadwalkan</td>
                        </tr>
                        <tr>
                            <td class="border p-2">Rina Wijaya</td>
                            <td class="border p-2">Dr. Budi</td>
                            <td class="border p-2">10:30</td>
                            <td class="border p-2 text-yellow-600">Menunggu</td>
                        </tr>
                        <tr>
                            <td class="border p-2">Bagus Pratama</td>
                            <td class="border p-2">Dr. Dewi</td>
                            <td class="border p-2">11:00</td>
                            <td class="border p-2 text-red-600">Dibatalkan</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
