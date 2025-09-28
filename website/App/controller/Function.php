<?php
// File ini berisi fungsi-fungsi dasar
// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ==============================================
//              Kontrol Database
// ============================================== 
// Fungsi Login
// ==============================================
// Fungsi Login Admin
function LoginAdmin($username, $password) {
    global $conn;
    
    $query = "SELECT a.*, r.nama_role FROM admin a 
              JOIN role r ON a.id_role = r.id_role 
              WHERE a.username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $passwordDB = $data['password'];
        $md5Password = md5($password);
        
        if ($passwordDB == $md5Password) {
            session_start();
            $_SESSION['login'] = true;
            $_SESSION['id_admin'] = $data['id_admin'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama_admin'] = $data['nama_admin'];
            $_SESSION['id_role'] = $data['id_role'];
            $_SESSION['nama_role'] = $data['nama_role'];
            
            header("Location: Controller.php?u=home");
            exit;
        } else {
            echo "<script>
                alert('Password salah!');
                window.location.href = 'Controller.php?u=login';
            </script>";
            exit;
        }
    } else {
        echo "<script>
            alert('Username tidak ditemukan!');
            window.location.href = 'Controller.php?u=login';
        </script>";
        exit;
    }
}

// Role-based access control functions
function getUserRole() {
    if (!isset($_SESSION['id_role'])) {
        return 0; // No role
    }
    return $_SESSION['id_role'];
}

function isOwner() {
    return getUserRole() == 1;
}

function isAdmin() {
    return getUserRole() == 2;
}

function isKasir() {
    return getUserRole() == 3;
}

function canAccessAdminSection() {
    return isOwner();
}

function canAccessTransactionOperations() {
    return isOwner() || isAdmin() || isKasir();
}

function canViewTransactionData() {
    return isOwner() || isAdmin(); // Removed isKasir() so only owners and admins can view transaction data
}

// Fungsi Ubah Akun Admin
function ubahAkunAdmin($id_admin, $old_password, $username, $password, $nama_admin){
    include "Database.php";
    
    // Verifikasi password lama
    $query = mysqli_query($conn, "SELECT password FROM admin WHERE id_admin='$id_admin'");
    $result = mysqli_fetch_assoc($query);

    if (md5($old_password) == $result['password']) {
        // Tentukan apakah password baru diisi atau tidak
        if (!empty($password)) {
            // Enkripsi password baru menggunakan MD5
            $hashed_password = md5($password);
        } else {
            // Gunakan password lama
            $hashed_password = $result['password'];
        }
        
        // Update data di database
        $query_update = mysqli_query($conn, "UPDATE admin SET username='$username', password='$hashed_password', nama_admin='$nama_admin' WHERE id_admin='$id_admin'");
        
        if (!$query_update) {
            die("Query error: " . mysqli_error($conn));
        } else {
            // Update session data
            $_SESSION['username'] = $username;
            $_SESSION['nama_admin'] = $nama_admin;
            
            echo "<script>window.location='$_SERVER[PHP_SELF]?u=logout';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Password lama salah!');window.location='$_SERVER[PHP_SELF]?u=home';</script>";
    }
}

// Fungsi Periksa Session Login 
function LoginSessionCheck(){
    session_start();
    if(!empty($_SESSION['username']) AND !empty($_SESSION['nama_admin']) AND !empty($_SESSION['key'])){
        echo "<script>alert('Anda sudah login');window.location='$_SERVER[PHP_SELF]?u=home';</script>";
        exit;
    }
}

// Fungsi Periksa Session
function SessionCheck(){
    session_start();
    if(empty($_SESSION['username']) AND empty($_SESSION['nama_admin']) AND empty($_SESSION['key'])){
        echo "<script>alert('Session telah habis. silahkan login kembali.');
        window.location='$_SERVER[PHP_SELF]?u=login'</script>";
        exit;
    }
}

// Logout
function Logout(){
    session_start();
    session_destroy();
    echo "<script>alert('Logout berhasil');window.location='$_SERVER[PHP_SELF]?u=login';</script>";
    exit;
}

// Fungsi Tambah Admin
function tambahAdmin($username, $password, $nama_admin, $id_role = 2) {
    global $conn;
    
    // Check if username already exists
    $check = mysqli_query($conn, "SELECT * FROM admin WHERE username = '$username'");
    if(mysqli_num_rows($check) > 0) {
        echo "<script>
            alert('Username sudah terdaftar!');
            window.location.href = 'Controller.php?u=data-admin';
        </script>";
        return;
    }
    
    $md5Password = md5($password);
    
    $query = mysqli_query($conn, "INSERT INTO admin (username, password, nama_admin, id_role) VALUES ('$username', '$md5Password', '$nama_admin', '$id_role')");
    
    if($query) {
        echo "<script>
            alert('Admin berhasil ditambahkan!');
            window.location.href = 'Controller.php?u=data-admin';
        </script>";
    } else {
        echo "<script>
            alert('Admin gagal ditambahkan: " . mysqli_error($conn) . "');
            window.location.href = 'Controller.php?u=data-admin';
        </script>";
    }
}

// Fungsi Edit Admin
function editAdmin($id_admin, $username, $password, $nama_admin, $id_role) {
    global $conn;
    
    // Check if username already exists for other admin
    $check = mysqli_query($conn, "SELECT * FROM admin WHERE username = '$username' AND id_admin != '$id_admin'");
    if(mysqli_num_rows($check) > 0) {
        echo "<script>
            alert('Username sudah digunakan oleh admin lain!');
            window.location.href = 'Controller.php?u=data-admin';
        </script>";
        return;
    }
    
    // Check if password is being changed
    if(!empty($password)) {
        $md5Password = md5($password);
        $query = mysqli_query($conn, "UPDATE admin SET username = '$username', password = '$md5Password', nama_admin = '$nama_admin', id_role = '$id_role' WHERE id_admin = '$id_admin'");
    } else {
        $query = mysqli_query($conn, "UPDATE admin SET username = '$username', nama_admin = '$nama_admin', id_role = '$id_role' WHERE id_admin = '$id_admin'");
    }
    
    if($query) {
        echo "<script>
            alert('Data admin berhasil diperbarui!');
            window.location.href = 'Controller.php?u=data-admin';
        </script>";
    } else {
        echo "<script>
            alert('Data admin gagal diperbarui: " . mysqli_error($conn) . "');
            window.location.href = 'Controller.php?u=data-admin';
        </script>";
    }
}

// Fungsi Hapus Admin
function hapusAdmin($id_admin) {
    include "Database.php";
    
    // Check if this is the last admin
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin");
    $count = mysqli_fetch_assoc($count_query)['total'];
    
    if ($count <= 1) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Dapat Menghapus Admin',
                    text: 'Sistem harus memiliki minimal satu admin',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    window.location.href = '{$_SERVER['PHP_SELF']}?u=data-admin';
                });
            });
        </script>";
        exit;
    }
    
    // Don't allow deletion of the currently logged in admin
    if ($_SESSION['id_admin'] == $id_admin) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Dapat Menghapus',
                    text: 'Anda tidak dapat menghapus akun admin yang sedang digunakan',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    window.location.href = '{$_SERVER['PHP_SELF']}?u=data-admin';
                });
            });
        </script>";
        exit;
    }
    
    $query = mysqli_query($conn, "DELETE FROM admin WHERE id_admin='$id_admin'");
    if (!$query) {
        die("Query error: " . mysqli_error($conn));
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Admin Dihapus',
                    text: 'Data admin berhasil dihapus dari sistem',
                    confirmButtonColor: '#4B49AC'
                }).then((result) => {
                    window.location.href = '{$_SERVER['PHP_SELF']}?u=data-admin';
                });
            });
        </script>";
        exit;
    }
}

// Fungsi Ambil Data Admin
function getDataAdmin() {
    global $conn;
    $data = [];
    
    $query = mysqli_query($conn, "SELECT a.*, r.nama_role FROM admin a 
                                  JOIN role r ON a.id_role = r.id_role 
                                  ORDER BY a.id_admin");
    
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    
    return $data;
}

// =========================
// Barang Function
// =========================

// Fungsi Tambah Barang
function tambahBarang($nama_barang, $merk, $harga_beli, $harga_jual, $stok){
    include "Database.php";

    // Masukkan data ke database
    $query_insert = mysqli_query($conn, "INSERT INTO barang (nama_barang, merk, harga_beli, harga_jual, stok) VALUES ('$nama_barang', '$merk', '$harga_beli', '$harga_jual', '$stok')");
    if (!$query_insert) {
        die("Query error: " . mysqli_error($conn));
    } else {
        echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-barang';</script>";
        exit;
    }
}

// Fungsi Ambil Data Barang
function getDataBarang(){
    include "Database.php";
    $result = mysqli_query($conn, "SELECT * FROM barang");
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    $array = [];
    while ($barang = mysqli_fetch_array($result)) {
        $array[] = $barang;
    }
    return $array;
}

// Fungsi Edit Barang
function editBarang($conn, $id_barang, $nama_barang, $harga_beli, $harga_jual, $stok, $merk) {
    $query = mysqli_prepare($conn, "UPDATE barang SET nama_barang=?, harga_beli=?, harga_jual=?, stok=?, merk=? WHERE id_barang=?");
    mysqli_stmt_bind_param($query, 'sssssi', $nama_barang, $harga_beli, $harga_jual, $stok, $merk, $id_barang);
    mysqli_stmt_execute($query);

    if (mysqli_stmt_affected_rows($query) > 0) {
        echo "<script>alert('Data barang berhasil diupdate');</script>";
    } else {
        echo "<script>alert('Gagal mengupdate data barang');</script>";
    } 
    
    mysqli_stmt_close($query);
    mysqli_close($conn);
    echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-barang';</script>";
    exit;
}

// Fungsi Hapus Barang
function hapusBarang($id_barang){
    include "Database.php";
    $query = mysqli_query($conn, "DELETE FROM barang WHERE id_barang='$id_barang'");
    if (!$query) {
        die("Query error: " . mysqli_error($conn));
    } else {
        echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-barang';</script>";
        exit;
    }
}

// Fungsi Hitung Jumlah Baris Barang
function countRowsBarang(){
    include "Database.php";
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total_rows FROM barang");
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($result);
    return $row['total_rows'];
}

// Fungsi Tambah Pelanggan
function tambahPelanggan($nama_pelanggan, $no_hp, $alamat, $email){
    include "Database.php";

    // Masukkan data ke database
    $query_insert = mysqli_query($conn, "INSERT INTO pelanggan (nama_pelanggan, no_hp, alamat, email) VALUES ('$nama_pelanggan', '$no_hp', '$alamat', '$email')");
    if (!$query_insert) {
        die("Query error: " . mysqli_error($conn));
    } else {
        echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-pelanggan';</script>";
        exit;
    }
}


// Fungsi Ambil Data Pelanggan
function getDataPelanggan(){
    include "Database.php";
    $result = mysqli_query($conn, "SELECT * FROM pelanggan");
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    $array = [];
    while ($pelanggan = mysqli_fetch_array($result)) {
        $array[] = $pelanggan;
    }
    return $array;
}

// edit pelanggan
function editPelanggan($conn, $id_pelanggan, $nama_pelanggan, $no_hp, $alamat, $email){
    $query = mysqli_prepare($conn, "UPDATE pelanggan SET nama_pelanggan=?, no_hp=?, alamat=?, email=? WHERE id_pelanggan=?");
    mysqli_stmt_bind_param($query, 'ssssi', $nama_pelanggan, $no_hp, $alamat, $email, $id_pelanggan);
    mysqli_stmt_execute($query);

    if (mysqli_stmt_affected_rows($query) > 0) {
        echo "<script>alert('Data pelanggan berhasil diupdate');</script>";
    } else {
        echo "<script>alert('Gagal mengupdate data pelanggan');</script>";
    }

    mysqli_stmt_close($query);
    mysqli_close($conn);
    echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-pelanggan';</script>";
    exit;
}


// Fungsi Hapus Pelanggan
function hapusPelanggan($id_pelanggan){
    include "Database.php";
    $query = mysqli_query($conn, "DELETE FROM pelanggan WHERE id_pelanggan='$id_pelanggan'");
    if (!$query) {
        die("Query error: " . mysqli_error($conn));
    } else {
        echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-pelanggan';</script>";
        exit;
    }
}

// Fungsi Hitung Jumlah Baris Pelanggan
function countRowsPelanggan(){
    include "Database.php";
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total_rows FROM pelanggan");
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($result);
    return $row['total_rows'];
}

// Improved function for transaction processing with better error handling
function tambahTransaksi($tanggal, $id_pelanggan, $total_pembelian, $bayar, $kembalian, $keterangan) {
    include "Database.php";
    
    try {
        // Set longer timeout for large transactions
        set_time_limit(120); // 2 minutes
        
        // Begin transaction to ensure data integrity
        mysqli_begin_transaction($conn);
        
        // Insert the transaction with error checking
        $query_insert = mysqli_query($conn, "INSERT INTO transaksi 
                                    (tanggal, id_pelanggan, total_pembelian, bayar, kembalian, keterangan) 
                                    VALUES ('$tanggal', '$id_pelanggan', '$total_pembelian', '$bayar', '$kembalian', '$keterangan')");
        
        if (!$query_insert) {
            throw new Exception("Database error: " . mysqli_error($conn));
        }
        
        $id_transaksi = mysqli_insert_id($conn);
        if (!$id_transaksi) {
            throw new Exception("Tidak dapat mendapatkan ID transaksi");
        }
        
        return $id_transaksi;
        
    } catch (Exception $e) {
        // Log the error for server-side debugging
        error_log("Error in tambahTransaksi: " . $e->getMessage());
        
        // If a transaction was started, roll it back
        if (mysqli_ping($conn)) {
            mysqli_rollback($conn);
        }
        
        throw $e; // Re-throw to be handled by the caller
    }
}

// Improved function for transaction detail processing
function tambahDetailTransaksi($id_transaksi, $id_barang, $qty) {
    include "Database.php";
    
    try {
        // Check stock availability first with timeout prevention
        $start_time = microtime(true);
        $max_execution_time = 10; // seconds
        
        $query_check_stock = mysqli_query($conn, "SELECT stok, nama_barang FROM barang WHERE id_barang = '$id_barang'");
        
        // Check for timeout
        if ((microtime(true) - $start_time) > $max_execution_time) {
            throw new Exception("Operasi terlalu lama saat mengecek stok (timeout)");
        }
        
        if (!$query_check_stock) {
            throw new Exception("Database error saat mengecek stok: " . mysqli_error($conn));
        }
        
        $barang = mysqli_fetch_assoc($query_check_stock);
        if (!$barang) {
            throw new Exception("Barang dengan ID $id_barang tidak ditemukan");
        }
        
        $current_stock = $barang['stok'];
        $nama_barang = $barang['nama_barang'];
        
        if ($current_stock < $qty) {
            throw new Exception("Stok tidak mencukupi untuk $nama_barang (tersedia: $current_stock, diminta: $qty)");
        }
        
        // Insert detail transaksi
        $query_insert = mysqli_query($conn, "INSERT INTO detail_transaksi (id_transaksi, id_barang, qty) 
                                     VALUES ('$id_transaksi', '$id_barang', '$qty')");
        
        // Check for timeout again
        if ((microtime(true) - $start_time) > $max_execution_time) {
            throw new Exception("Operasi terlalu lama saat menyimpan detail (timeout)");
        }
        
        if (!$query_insert) {
            throw new Exception("Database error saat menyimpan detail: " . mysqli_error($conn));
        }
        
        // Update stock in barang table
        $new_stock = $current_stock - $qty;
        $query_update_stock = mysqli_query($conn, "UPDATE barang SET stok = '$new_stock' WHERE id_barang = '$id_barang'");
        
        // Final timeout check
        if ((microtime(true) - $start_time) > $max_execution_time) {
            throw new Exception("Operasi terlalu lama saat memperbarui stok (timeout)");
        }
        
        if (!$query_update_stock) {
            throw new Exception("Database error saat memperbarui stok: " . mysqli_error($conn));
        }
        
        return true;
        
    } catch (Exception $e) {
        // Log the error for server-side debugging
        error_log("Error in tambahDetailTransaksi: " . $e->getMessage());
        throw $e; // Re-throw to be handled by the caller
    }
}

// Fungsi Ambil Data Transaksi
function getDataTransaksi(){
    include "Database.php";
    $result = mysqli_query($conn, "SELECT * FROM transaksi");
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    $array = [];
    while ($transaksi = mysqli_fetch_array($result)) {
        $array[] = $transaksi;
    }
    return $array;
}

// Fungsi Edit Transaksi
function editTransaksi($id_transaksi, $tanggal, $total_pembelian, $kembalian, $bayar, $keterangan){
    include "Database.php";
    $query = mysqli_query($conn, "UPDATE transaksi SET tanggal='$tanggal', total_pembelian='$total_pembelian', kembalian='$kembalian', bayar='$bayar', keterangan='$keterangan' WHERE id_transaksi='$id_transaksi'");
    if (!$query) {
        die("Query error: " . mysqli_error($conn));
    } else {
        echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-transaksi';</script>";
        exit;
    }
}

// Fungsi Hapus Transaksi
function hapusTransaksi($id_transaksi){
    include "Database.php";
    // Hapus detail transaksi terlebih dahulu
    $query_detail = mysqli_query($conn, "DELETE FROM detail_transaksi WHERE id_transaksi='$id_transaksi'");
    if (!$query_detail) {
        die("Query error: " . mysqli_error($conn));
    }

    // Setelah menghapus detail transaksi, hapus transaksi itu sendiri
    $query_transaksi = mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi='$id_transaksi'");
    if (!$query_transaksi) {
        die("Query error: " . mysqli_error($conn));
    } else {
        echo "<script>window.location='$_SERVER[PHP_SELF]?u=data-transaksi';</script>";
        exit;
    }
}


// Fungsi Hitung Omset Penjualan
function hitungOmsetPenjualan(){
    include "Database.php";
    $result = mysqli_query($conn, "SELECT SUM(total_pembelian) AS omset FROM transaksi");
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($result);
    return $row['omset'];
}

// Fungsi Hitung Pendapatan Bersih
function hitungPendapatanBersih(){
    include "Database.php";
    
    // Menghitung total harga jual dari detail transaksi yang terhubung dengan tabel barang
    $resultTotalHargaJual = mysqli_query($conn, "SELECT SUM(barang.harga_jual * detail_transaksi.qty) AS total_harga_jual FROM detail_transaksi INNER JOIN barang ON detail_transaksi.id_barang = barang.id_barang");
    if (!$resultTotalHargaJual) {
        die("Query error: " . mysqli_error($conn));
    }
    $rowTotalHargaJual = mysqli_fetch_assoc($resultTotalHargaJual);
    $totalHargaJual = $rowTotalHargaJual['total_harga_jual'];

    // Menghitung total harga beli dari detail transaksi yang terhubung dengan tabel barang
    $resultTotalHargaBeli = mysqli_query($conn, "SELECT SUM(barang.harga_beli * detail_transaksi.qty) AS total_harga_beli FROM detail_transaksi INNER JOIN barang ON detail_transaksi.id_barang = barang.id_barang");
    if (!$resultTotalHargaBeli) {
        die("Query error: " . mysqli_error($conn));
    }
    $rowTotalHargaBeli = mysqli_fetch_assoc($resultTotalHargaBeli);
    $totalHargaBeli = $rowTotalHargaBeli['total_harga_beli'];

    // Menghitung pendapatan bersih
    $pendapatanBersih = $totalHargaJual - $totalHargaBeli;
    
    return $pendapatanBersih;
}

// Fungsi untuk mengambil detail transaksi berdasarkan id transaksi
function getDetailTransaksiByTransaksiId($id_transaksi){
    include "Database.php";
    $query = "SELECT detail_transaksi.id_detail_transaksi, detail_transaksi.id_barang, barang.nama_barang, detail_transaksi.qty, barang.harga_jual, (detail_transaksi.qty * barang.harga_jual) AS total FROM detail_transaksi INNER JOIN barang ON detail_transaksi.id_barang = barang.id_barang WHERE detail_transaksi.id_transaksi = $id_transaksi";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    $detailTransaksi = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $detailTransaksi[] = $row;
    }
    return $detailTransaksi;
}

// fungsi cetak nota
function cetakNota($id_transaksi) {
    include "Database.php";
    try {
        // Validate the transaction ID
        $id_transaksi = max(1, intval($id_transaksi));
        
        // Query untuk mendapatkan data transaksi
        $query_transaksi = mysqli_query($conn, "SELECT t.*, p.nama_pelanggan, p.alamat, p.no_hp 
                                              FROM transaksi t 
                                              JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
                                              WHERE t.id_transaksi='$id_transaksi'");
                                              
        if (!$query_transaksi || mysqli_num_rows($query_transaksi) == 0) {
            throw new Exception("Transaksi dengan ID $id_transaksi tidak ditemukan");
        }
        
        $transaksi = mysqli_fetch_assoc($query_transaksi);
        
        // Ensure id_transaksi is at least 1
        $transaksi['id_transaksi'] = max(1, intval($transaksi['id_transaksi']));

        // Query untuk mendapatkan detail transaksi
        $query_detail = mysqli_query($conn, "SELECT dt.*, b.nama_barang, b.harga_jual 
                                           FROM detail_transaksi dt 
                                           JOIN barang b ON dt.id_barang = b.id_barang 
                                           WHERE dt.id_transaksi='$id_transaksi'");
                                           
        if (!$query_detail) {
            throw new Exception("Gagal mengambil detail transaksi: " . mysqli_error($conn));
        }
        
        $detailTransaksi = [];
        while ($row = mysqli_fetch_assoc($query_detail)) {
            $detailTransaksi[] = $row;
        }

        // Periksa apakah ada detail transaksi
        if (empty($detailTransaksi)) {
            throw new Exception("Detail transaksi kosong untuk transaksi ID $id_transaksi");
        }
        
        // Include view untuk cetak nota
        include "../view/cetaknota.php";
    } catch (Exception $e) {
        error_log("Error in cetakNota: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Terjadi kesalahan saat mencetak nota: " . $e->getMessage() . "</div>";
    }
}

// Fungsi untuk mendapatkan data transaksi terbaru
function getRecentTransactions($limit = 3) {
    include "Database.php";
    $query = "SELECT transaksi.*, pelanggan.nama_pelanggan 
              FROM transaksi 
              INNER JOIN pelanggan ON transaksi.id_pelanggan = pelanggan.id_pelanggan 
              ORDER BY transaksi.tanggal DESC 
              LIMIT $limit";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    $recentTransactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recentTransactions[] = $row;
    }
    return $recentTransactions;
}

// Fungsi untuk mendapatkan data penjualan per bulan untuk grafik
function getMonthlySalesData($year = null) {
    include "Database.php";
    
    if ($year === null) {
        $year = date('Y'); // Current year if not specified
    }
    
    // Initialize all months with zero
    $salesData = [
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
    ];
    
    $query = "SELECT 
                MONTH(tanggal) as bulan,
                SUM(total_pembelian) as total
              FROM transaksi 
              WHERE YEAR(tanggal) = $year
              GROUP BY MONTH(tanggal)
              ORDER BY MONTH(tanggal)";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return $salesData; // Return zeros if query failed
    }
    
    // Fill data with actual values
    while ($row = mysqli_fetch_assoc($result)) {
        $month = (int)$row['bulan'] - 1; // JavaScript months are 0-based
        $salesData[$month] = (float)$row['total'];
    }
    
    return $salesData;
}

// Fungsi untuk mendapatkan jumlah transaksi
function countTransactions() {
    include "Database.php";
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi");
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Fungsi untuk mendapatkan data kategori barang untuk pie chart - simplified version
function getCategoryData() {
    include "Database.php";
    
    // Get unique product brands/categories
    $query = "SELECT merk, COUNT(*) as jumlah FROM barang GROUP BY merk ORDER BY jumlah DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }
    
    $categories = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['merk'];
        $counts[] = (int)$row['jumlah'];
    }
    
    return [
        'categories' => $categories,
        'counts' => $counts
    ];
}

// Get Sales Data for Chart
function getMonthlyChartData() {
    include "Database.php";
    $year = date('Y');
    
    // Initialize array with zeroes for all months
    $salesData = array_fill(1, 12, 0);
    
    // Get monthly totals
    $query = "SELECT MONTH(tanggal) as month, SUM(total_pembelian) as total 
              FROM transaksi 
              WHERE YEAR(tanggal) = $year 
              GROUP BY MONTH(tanggal)";
              
    $result = mysqli_query($conn, $query);
    
    while($row = mysqli_fetch_assoc($result)) {
        $salesData[(int)$row['month']] = (float)$row['total'];
    }
    
    return array_values($salesData); // Convert to indexed array
}

function ubahNamaAdmin($id_admin, $nama_admin) {
    include "Database.php";

    // Update nama_admin di database
    $query_update = mysqli_query($conn, "UPDATE admin SET nama_admin='$nama_admin' WHERE id_admin='$id_admin'");

    if (!$query_update) {
        die("Query error: " . mysqli_error($conn));
    } else {
        // Update session data
        $_SESSION['nama_admin'] = $nama_admin;

        // Redirect to dashboard after update
        echo "<script>alert('Nama admin berhasil diperbarui.'); window.location='dashboard.php';</script>";
        exit;
    }
}

// Get Product Distribution Data for Chart
function getProductDistributionData($viewType = 'count') {
    include "Database.php";
    
    // Initialize return array
    $result = [
        'labels' => [],
        'data' => []
    ];
    
    if ($viewType === 'count') {
        // Group by category/merk and count products
        $query = "SELECT merk, COUNT(*) as total 
                  FROM barang 
                  GROUP BY merk 
                  ORDER BY total DESC 
                  LIMIT 7";
    } else {
        // Group by category/merk and sum stock
        $query = "SELECT merk, SUM(stok) as total 
                  FROM barang 
                  GROUP BY merk 
                  ORDER BY total DESC 
                  LIMIT 7";
    }
    
    $queryResult = mysqli_query($conn, $query);
    
    if (!$queryResult) {
        throw new Exception("Error fetching product distribution data: " . mysqli_error($conn));
    }
    
    while ($row = mysqli_fetch_assoc($queryResult)) {
        $result['labels'][] = $row['merk'];
        $result['data'][] = (int)$row['total'];
    }
    
    return $result;
}
?>