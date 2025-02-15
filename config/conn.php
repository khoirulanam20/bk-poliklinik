<?php
require __DIR__ . '/url.php';
$host = 'localhost';
$dbname = 'bk_poliklinik';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$conn = mysqli_connect($host, $username, $password, $dbname);

function query($query)
{
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function ubahDokter($data)
{
    global $conn;

    $id = $data["id"];
    $nama = mysqli_real_escape_string($conn, $data["nama"]);
    $alamat = mysqli_real_escape_string($conn, $data["alamat"]);
    $no_hp = mysqli_real_escape_string($conn, $data["no_hp"]);

    $query = "UPDATE dokter SET nama = '$nama', alamat = '$alamat', no_hp = '$no_hp' WHERE id = $id ";

    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn); // Return the number of affected rows
    } else {
        // Handle the error
        echo "Error updating record: " . mysqli_error($conn);
        return -1; // Or any other error indicator
    }
}

// Jadwal Periksa Sisi Dokter
function tambahJadwalPeriksa($data)
{
    try {
        global $conn;

        $id_dokter = $data["id_dokter"];
        $hari = mysqli_real_escape_string($conn, $data["hari"]);
        $jam_mulai = mysqli_real_escape_string($conn, $data["jam_mulai"]);
        $jam_selesai = mysqli_real_escape_string($conn, $data["jam_selesai"]);
        $aktif = 'T';

        // Cek apakah dokter sudah memiliki jadwal di hari yang sama
        $check_query = "SELECT * FROM jadwal_periksa WHERE id_dokter = '$id_dokter' AND hari = '$hari'";
        $result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($result) > 0) {
            return -3; // Return -3 jika dokter sudah memiliki jadwal di hari tersebut
        }

        // Jika belum ada jadwal di hari yang sama, tambahkan jadwal baru
        $query = "INSERT INTO jadwal_periksa VALUES (null, '$id_dokter', '$hari', '$jam_mulai', '$jam_selesai', '$aktif')";
        
        if (mysqli_query($conn, $query)) {
            return mysqli_affected_rows($conn);
        } else {
            echo "Error updating record: " . mysqli_error($conn);
            return -1;
        }
    } catch (\Exception $e) {
        var_dump($e->getMessage());
        return -1;
    }
}

function updateJadwalPeriksa($data, $id)
{
    try {
        global $conn;

        $hari = mysqli_real_escape_string($conn, $data["hari"]);
        $jam_mulai = mysqli_real_escape_string($conn, $data["jam_mulai"]);
        $jam_selesai = mysqli_real_escape_string($conn, $data["jam_selesai"]);
        $aktif = mysqli_real_escape_string($conn, $data["aktif"]);

        // Update jadwal tanpa mengubah status jadwal lain
        $query = "UPDATE jadwal_periksa SET 
                    hari = '$hari',
                    jam_mulai = '$jam_mulai', 
                    jam_selesai = '$jam_selesai',
                    aktif = '$aktif'
                 WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            return mysqli_affected_rows($conn); // Return the number of affected rows
        } else {
            // Handle the error
            echo "Error updating record: " . mysqli_error($conn);
            return -1; // Or any other error indicator
        }
    } catch (\Exception $e) {
        var_dump($e->getMessage());
        die();
    }
}

function hapusJadwalPeriksa($id)
{
    try {
        global $conn;

        $query = "DELETE FROM jadwal_periksa WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            return mysqli_affected_rows($conn); // Return the number of affected rows
        } else {
            // Handle the error
            echo "Error updating record: " . mysqli_error($conn);
            return -1; // Or any other error indicator
        }
    } catch (\Exception $e) {
        var_dump($e->getMessage());
    }
}

function TambahPeriksa($data)

{
    global $conn;
    // ambil data dari tiap elemen dalam form
    $tgl_periksa = htmlspecialchars($data["tgl_periksa"]);
    $catatan = htmlspecialchars($data["catatan"]);


    // query insert data
    $query = "INSERT INTO periksa
                VALUES
                ('', '$tgl_periksa','$catatan');
            ";

    mysqli_query($conn, $query);

    return mysqli_affected_rows($conn);
}

// ini belum selesai mau dilanjutin vander :v
function TambahDetailPeriksa($data)
{
    global $conn;
    // ambil data dari tiap elemen dalam form
    $tgl_periksa = htmlspecialchars($data["tgl_periksa"]);
    $catatan = htmlspecialchars($data["catatan"]);


    // query insert data
    $query = "INSERT INTO detail_periksa
                VALUES
                ('', '$tgl_periksa','$catatan');
            ";

    mysqli_query($conn, $query);

    return mysqli_affected_rows($conn);
}

function daftarPoli($data)
{
    global $pdo;

    try {
        $id_pasien = $data["id_pasien"];
        $id_jadwal = $data["id_jadwal"];
        $keluhan = $data["keluhan"];
        $no_antrian = getLatestNoAntrian($id_jadwal, $pdo) + 1;
        $status = 0;

        $query = "INSERT INTO daftar_poli VALUES (NULL, :id_pasien, :id_jadwal, :keluhan, :no_antrian, :status_periksa)";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id_pasien', $id_pasien);
        $stmt->bindParam(':id_jadwal', $id_jadwal);
        $stmt->bindParam(':keluhan', $keluhan);
        $stmt->bindParam(':no_antrian', $no_antrian);
        $stmt->bindParam(':status_periksa', $status);
        if ($stmt->execute()) {

            return $stmt->rowCount(); // Return the number of affected rows
        } else {
            // Handle the error
            echo "Error updating record: " . $stmt->errorInfo()[2];
            return -1; // Or any other error indicator
        }
    } catch (\Exception $e) {
        var_dump($e->getMessage());
    }
}

function getLatestNoAntrian($id_jadwal, $pdo)
{
    // Ambil nomor antrian terbaru untuk jadwal tertentu
    $latestNoAntrian = $pdo->prepare("SELECT MAX(no_antrian) as max_no_antrian FROM daftar_poli WHERE id_jadwal = :id_jadwal");
    $latestNoAntrian->bindParam(':id_jadwal', $id_jadwal);
    $latestNoAntrian->execute();

    $row = $latestNoAntrian->fetch();
    return $row['max_no_antrian'] ? $row['max_no_antrian'] : 0;
}

function formatRupiah($angka)
{
    return "Rp" . number_format($angka, 0, ',', '.');
}
