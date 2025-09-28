<?php 
include "Function.php";
date_default_timezone_set('Asia/Jakarta');

// POST Method
if(isset($_POST['login-admin'])){
    include "Database.php";
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    LoginAdmin($username, $password);
} 
else if(isset($_POST['tambah-admin'])){
    include "Database.php";
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nama_admin = mysqli_real_escape_string($conn, $_POST['nama_admin']);
    $id_role = mysqli_real_escape_string($conn, $_POST['id_role']);
    tambahAdmin($username, $password, $nama_admin, $id_role);
}
else if(isset($_POST['ubah-akun-admin'])){
    include "Database.php";
    $id_admin = mysqli_real_escape_string($conn, $_POST['id_admin']); 
    $old_password = mysqli_real_escape_string($conn, $_POST['old_password']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nama_admin = mysqli_real_escape_string($conn, $_POST['nama_admin']);
    ubahAkunAdmin($id_admin, $old_password, $username, $password, $nama_admin);
} 
else if(isset($_POST['tambah-data-pelanggan'])){
    include "Database.php";
    
    // Check if user is owner
    session_start();
    if(isOwner()) {
        echo "<script>
            alert('Maaf, akun dengan role Owner tidak diizinkan untuk menambahkan pelanggan');
            window.location.href = '{$_SERVER['PHP_SELF']}?u=data-pelanggan';
        </script>";
        exit;
    }
    
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    tambahPelanggan($nama_pelanggan, $no_hp, $alamat, $email);
} 
else if(isset($_POST['edit-pelanggan'])){
    include "Database.php";
    $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    editPelanggan($conn, $id_pelanggan, $nama_pelanggan, $no_hp, $alamat, $email);
} 
else if(isset($_POST['tambah-data-barang'])){
    include "Database.php";
    $harga_beli = mysqli_real_escape_string($conn, $_POST['harga_beli']);
    $harga_jual = mysqli_real_escape_string($conn, $_POST['harga_jual']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $merk = mysqli_real_escape_string($conn, $_POST['merk']);
    tambahBarang($nama_barang, $merk, $harga_beli, $harga_jual, $stok);
} 
else if(isset($_POST['edit-barang'])){
    include "Database.php";
    $id_barang = mysqli_real_escape_string($conn, $_POST['id_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $merk = mysqli_real_escape_string($conn, $_POST['merk']);
    $harga_beli = mysqli_real_escape_string($conn, $_POST['harga_beli']);
    $harga_jual = mysqli_real_escape_string($conn, $_POST['harga_jual']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    editBarang($conn, $id_barang, $nama_barang, $harga_beli, $harga_jual, $stok, $merk);
} 
else if(isset($_POST['tambah-transaksi'])) {
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Include database connection and ensure it's available
    include_once "Database.php";
    
    // Make sure the connection is active and working
    if (!isset($conn) || !$conn) {
        echo "<div class='alert alert-danger'>Error: Database connection failed</div>";
        echo "<a href='Controller.php?u=transaksi' class='btn btn-primary mt-3'>Kembali</a>";
        exit;
    }
    
    // Start a session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    try {
        // Extract and sanitize form data
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal_transaksi']);
        $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
        $total_pembelian = floatval($_POST['total_pembelian']);
        $bayar = floatval($_POST['bayar']);
        $kembalian = $bayar - $total_pembelian;
        $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($conn, $_POST['keterangan']) : '';
        
        // Get transaction details
        $detail_transaksi = $_POST['detail_transaksi'];
        if (is_array($detail_transaksi)) {
            if (isset($detail_transaksi[0])) {
                $detail_transaksi = $detail_transaksi[0];
            }
        }
        
        // Parse transaction details
        $detail_transaksi_decoded = json_decode($detail_transaksi, true);
        if (!is_array($detail_transaksi_decoded) || empty($detail_transaksi_decoded)) {
            throw new Exception("Invalid transaction details format");
        }
        
        // Begin the transaction - disable autocommit
        mysqli_autocommit($conn, FALSE);
        
        // FIX: Use a different way to ensure ID starts at 1, without referencing the table twice
        $check_id_query = "SELECT MAX(id_transaksi) + 1 as next_id FROM transaksi";
        $check_result = mysqli_query($conn, $check_id_query);
        $next_id = 1; // Default to 1 if no rows
        
        if ($check_result && $row = mysqli_fetch_assoc($check_result)) {
            $next_id = $row['next_id'];
            if ($next_id === NULL || $next_id <= 0) {
                $next_id = 1;
            }
        }
        
        // Now insert with a known ID value
        $insert_query = "INSERT INTO transaksi (id_transaksi, tanggal, id_pelanggan, total_pembelian, bayar, kembalian, keterangan) 
                       VALUES ($next_id, '$tanggal', '$id_pelanggan', $total_pembelian, $bayar, $kembalian, '$keterangan')";
        
        // Execute the insert
        if (!mysqli_query($conn, $insert_query)) {
            throw new Exception("Error inserting transaction: " . mysqli_error($conn));
        }
        
        // Get the last insert ID immediately after insertion
        $id_transaksi = $next_id;
        
        // Process each item in the cart
        foreach ($detail_transaksi_decoded as $detail) {
            $id_barang = (int)$detail['id_barang'];
            $qty = (int)$detail['qty'];
            
            // Check stock first
            $stock_query = "SELECT stok FROM barang WHERE id_barang = $id_barang";
            $stock_result = mysqli_query($conn, $stock_query);
            
            if (!$stock_result || mysqli_num_rows($stock_result) == 0) {
                throw new Exception("Product with ID $id_barang not found");
            }
            
            $stock_row = mysqli_fetch_assoc($stock_result);
            $current_stock = $stock_row['stok'];
            
            if ($current_stock < $qty) {
                throw new Exception("Insufficient stock for product ID $id_barang");
            }
            
            // Insert transaction detail
            $detail_query = "INSERT INTO detail_transaksi (id_transaksi, id_barang, qty) VALUES ($id_transaksi, $id_barang, $qty)";
            
            if (!mysqli_query($conn, $detail_query)) {
                throw new Exception("Failed to insert transaction detail: " . mysqli_error($conn));
            }
            
            // Update stock
            $update_query = "UPDATE barang SET stok = stok - $qty WHERE id_barang = $id_barang";
            
            if (!mysqli_query($conn, $update_query)) {
                throw new Exception("Failed to update product stock: " . mysqli_error($conn));
            }
        }
        
        // If we've made it here, commit the transaction
        if (!mysqli_commit($conn)) {
            throw new Exception("Failed to commit transaction: " . mysqli_error($conn));
        }
        
        // Enable autocommit again
        mysqli_autocommit($conn, TRUE);
        
        // Redirect to print receipt
        header("Location: Controller.php?u=print-nota&id=$id_transaksi");
        exit;
        
    } catch (Exception $e) {
        // Rollback the transaction
        if (isset($conn) && $conn) {
            mysqli_rollback($conn);
            mysqli_autocommit($conn, TRUE); // Re-enable autocommit
        }
        
        // Log the error
        error_log("Transaction error: " . $e->getMessage());
        
        // Display user-friendly error
        echo "<div class='alert alert-danger'>";
        echo "<h4>Transaction Failed</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<p>Please check your database connection and try again.</p>";
        echo "</div>";
        
        echo "<div class='text-center'>";
        echo "<a href='Controller.php?u=transaksi' class='btn btn-primary'>Return to Transaction Form</a>";
        echo "</div>";
        exit;
    }
} 
else if(isset($_POST['hapus-pelanggan'])){
    include "Database.php";
    $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    hapusPelanggan($id_pelanggan);
} 
else if(isset($_POST['hapus-barang'])){
    include "Database.php";
    $id_barang = mysqli_real_escape_string($conn, $_POST['id_barang']);
    hapusBarang($id_barang);
} 
else if(isset($_POST['hapus-transaksi'])){
    include "Database.php";
    $id_transaksi = mysqli_real_escape_string($conn, $_POST['id_transaksi']);
    hapusTransaksi($id_transaksi);
} 
else if(isset($_POST['hapus-detail-transaksi'])){
    include "Database.php";
    $id_detail_transaksi = mysqli_real_escape_string($conn, $_POST['id_detail_transaksi']);
    hapusDetailTransaksi($id_detail_transaksi);
} 
else if (isset($_POST['ubah-nama-admin'])) {
    include "Database.php";
    $id_admin = mysqli_real_escape_string($conn, $_POST['id_admin']); 
    $nama_admin = mysqli_real_escape_string($conn, $_POST['nama_admin']);

    ubahNamaAdmin($id_admin, $nama_admin);
}
else if(isset($_POST['edit-admin'])){
    include "Database.php";
    $id_admin = mysqli_real_escape_string($conn, $_POST['id_admin']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nama_admin = mysqli_real_escape_string($conn, $_POST['nama_admin']);
    $id_role = mysqli_real_escape_string($conn, $_POST['id_role']);
    editAdmin($id_admin, $username, $password, $nama_admin, $id_role);
}

// GET Method
if(isset($_GET['u'])){
    $url = $_GET["u"];
    if($url == "login"){
        LoginSessionCheck();
        include "../view/login.php";
    } else if($url == "logout"){
        Logout();
    } else if($url == "home"){
        SessionCheck();
        include "../view/dashboard.php";
    } else if($url == "data-pelanggan"){
        SessionCheck();
        include "../view/data-pelanggan.php";
    } else if($url == "data-barang"){
        SessionCheck();
        include "../view/data-barang.php";
    } else if($url == "data-transaksi"){
        SessionCheck();
        // Restrict access to data-transaksi for kasir role
        if(isKasir()) {
            echo "<script>
                alert('Maaf, akun dengan role Kasir tidak diizinkan untuk mengakses data transaksi');
                window.location.href = '{$_SERVER['PHP_SELF']}?u=home';
            </script>";
            exit;
        }
        include "../view/data-transaksi.php";
    } else if($url == "del-data-pelanggan"){
        SessionCheck();
        $id = $_GET['id'];    
        hapusPelanggan($id);
    } else if($url == "del-data-barang"){
        SessionCheck();
        $id = $_GET['id'];    
        hapusBarang($id);
    } else if($url == "del-data-transaksi"){
        SessionCheck();
        $id = $_GET['id'];    
        hapusTransaksi($id);
    } else if($url == "del-data-admin"){
        SessionCheck();
        $id = $_GET['id'];    
        hapusAdmin($id);
    } else if($url == "edit-pelanggan"){
        SessionCheck();
        include "../view/edit-pelanggan.php";
    } else if($url == "edit-barang"){
        SessionCheck();
        include "../view/edit-barang.php";
    } else if($url == "edit-transaksi"){
        SessionCheck();
        include "../view/edit-transaksi.php";
    } else if($url == "print-nota"){
        SessionCheck();
        $id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // Check if ID is valid
        if ($id_transaksi <= 0) {
            echo "<script>
                alert('ID Transaksi tidak valid');
                window.location.href = '{$_SERVER['PHP_SELF']}?u=data-transaksi';
            </script>";
            exit;
        }
        
        // Use try-catch to handle potential errors
        try {
            include_once "Database.php";
            $query = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = $id_transaksi");
            
            if (!$query || mysqli_num_rows($query) == 0) {
                echo "<script>
                    alert('Transaksi tidak ditemukan');
                    window.location.href = '{$_SERVER['PHP_SELF']}?u=data-transaksi';
                </script>";
                exit;
            }
            
            cetakNota($id_transaksi);
        } catch (Exception $e) {
            echo "<script>
                alert('Error: " . addslashes($e->getMessage()) . "');
                window.location.href = '{$_SERVER['PHP_SELF']}?u=data-transaksi';
            </script>";
            exit;
        }
    } else if($url == "transaksi"){
        SessionCheck();
        // Only allow access to kasir role (role ID 3)
        if (isKasir()) {
            include "../view/transaksi.php";
        } else {
            // Redirect users with other roles (owner, admin) to dashboard with message
            echo "<script>
                alert('Maaf, hanya kasir yang dapat membuat transaksi baru');
                window.location.href = '{$_SERVER['PHP_SELF']}?u=home';
            </script>";
            exit;
        }
    }
    else if($url == "export-transaksi"){
        SessionCheck();
        
        include_once "../helpers/ExportHelper.php";
        
        // Get all transaction data with customer names
        include "Database.php";
        $query = "SELECT t.*, p.nama_pelanggan 
                  FROM transaksi t 
                  JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
                  ORDER BY t.tanggal DESC";
        
        $result = mysqli_query($conn, $query);
        if (!$result) {
            die("Query error: " . mysqli_error($conn));
        }
        
        $transactions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }
        
        // Export to Excel
        ExportHelper::exportToExcel($transactions);
    }
    else if($url == "data-admin"){
        SessionCheck();
        include "../view/data-admin.php";
    }
} 
// Handle chart data requests
else if(isset($_GET['chart_data'])) {
    include "Database.php";
    header('Content-Type: application/json');
    
    $data_type = $_GET['chart_data'];
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    try {
        if ($data_type === 'monthly_sales') {
            $salesData = getMonthlySalesData($year);
            echo json_encode(array_values($salesData));
        } 
        else if ($data_type === 'product_distribution') {
            $view_type = isset($_GET['view']) ? $_GET['view'] : 'count';
            $productData = getProductDistributionData($view_type);
            echo json_encode($productData);
        }
        else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data type requested']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>

