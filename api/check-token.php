<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$token_raw = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$token = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $token_raw));

if (empty($token)) {
    echo json_encode([
        'valid'   => false,
        'status'  => 'empty',
        'message' => 'Silakan masukkan kode token feedback kunjungan Anda.'
    ]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM kunjungan_feedback_token WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'valid'   => false,
            'status'  => 'not_found',
            'token'   => $token,
            'message' => 'Kode token "' . htmlspecialchars($token) . '" tidak ditemukan dalam sistem LPM. Mohon periksa kembali kode yang diberikan oleh tim sekretariat.'
        ]);
        exit;
    }

    if ($row['status'] === 'Digunakan') {
        $used_time = !empty($row['used_at']) ? date('d/m/Y \p\u\k\u\l H:i', strtotime($row['used_at'])) : '-';
        echo json_encode([
            'valid'          => false,
            'status'         => 'used',
            'token'          => $token,
            'nama_institusi' => $row['nama_institusi'],
            'message'        => 'Kode token ini telah digunakan untuk mengisi kuesioner pada ' . $used_time . ' WIB. Terima kasih atas partisipasi institusi Anda.'
        ]);
        exit;
    }

    if ($row['status'] === 'Nonaktif') {
        echo json_encode([
            'valid'          => false,
            'status'         => 'inactive',
            'token'          => $token,
            'nama_institusi' => $row['nama_institusi'],
            'message'        => 'Kode token ini telah dinonaktifkan oleh administrator LPM.'
        ]);
        exit;
    }

    $now   = time();
    $mulai = strtotime($row['berlaku_mulai']);
    $akhir = strtotime($row['berlaku_sampai']);

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];

    if ($now < $mulai) {
        $tgl_m = date('d', $mulai) . ' ' . $bulan[(int)date('n', $mulai)] . ' ' . date('Y', $mulai) . ' pukul ' . date('H:i', $mulai);
        echo json_encode([
            'valid'                   => false,
            'status'                  => 'not_started',
            'token'                   => $token,
            'nama_institusi'          => $row['nama_institusi'],
            'berlaku_mulai_formatted' => $tgl_m . ' WIB',
            'message'                 => 'Kode token ini baru dapat digunakan mulai ' . $tgl_m . ' WIB (pada jadwal pelaksanaan kunjungan).'
        ]);
        exit;
    }

    $tgl_a = date('d', $akhir) . ' ' . $bulan[(int)date('n', $akhir)] . ' ' . date('Y', $akhir) . ' pukul ' . date('H:i', $akhir);

    if ($now > $akhir) {
        echo json_encode([
            'valid'                    => false,
            'status'                   => 'expired',
            'token'                    => $token,
            'nama_institusi'           => $row['nama_institusi'],
            'berlaku_sampai_formatted' => $tgl_a . ' WIB',
            'message'                  => 'Masa berlaku kode token ini telah berakhir pada ' . $tgl_a . ' WIB. Silakan hubungi sekretariat LPM untuk mendapatkan kode token baru.'
        ]);
        exit;
    }

    // Active & Valid Token
    $diff  = $akhir - $now;
    $days  = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    $mins  = floor(($diff % 3600) / 60);

    $parts = [];
    if ($days > 0) $parts[] = $days . ' hari';
    if ($hours > 0) $parts[] = $hours . ' jam';
    if ($mins > 0 || empty($parts)) $parts[] = $mins . ' menit';
    $sisa_str = implode(' ', $parts);

    echo json_encode([
        'valid'                    => true,
        'status'                   => 'active',
        'token'                    => $row['token'],
        'nama_institusi'           => $row['nama_institusi'],
        'tanggal_kunjungan'        => $row['tanggal_kunjungan'],
        'perihal'                  => $row['perihal'] ?? '',
        'berlaku_sampai_raw'       => $row['berlaku_sampai'],
        'berlaku_sampai_formatted' => $tgl_a . ' WIB',
        'sisa_waktu'               => $sisa_str,
        'message'                  => 'Token valid untuk ' . $row['nama_institusi'] . '! Berlaku sampai ' . $tgl_a . ' WIB (Sisa waktu: ' . $sisa_str . ').'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'valid'   => false,
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem saat memeriksa token.'
    ]);
}
