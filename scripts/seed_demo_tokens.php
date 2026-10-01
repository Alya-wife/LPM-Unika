<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$tokens = [
    [
        'token'             => 'LPM-DEMO26',
        'kunjungan_id'      => null,
        'nama_institusi'    => 'Universitas Katolik Widya Mandala Surabaya',
        'email'             => 'qa@ukwms.ac.id',
        'tanggal_kunjungan' => date('Y-m-d'),
        'perihal'           => 'Studi Banding Implementasi SPMI & Sistem Informasi Mutu',
        'berlaku_mulai'     => date('Y-m-d H:i:s', time() - 3600),
        'berlaku_sampai'    => date('Y-m-d H:i:s', time() + (2 * 86400)), // 2 days from now
        'status'            => 'Aktif'
    ],
    [
        'token'             => 'LPM-EXP26',
        'kunjungan_id'      => null,
        'nama_institusi'    => 'Institut Sains dan Teknologi Pradita',
        'email'             => 'lpm@pradita.ac.id',
        'tanggal_kunjungan' => date('Y-m-d', time() - (3 * 86400)),
        'perihal'           => 'Kunjungan Kerja Tata Kelola Penjaminan Mutu Internal',
        'berlaku_mulai'     => date('Y-m-d H:i:s', time() - (3 * 86400)),
        'berlaku_sampai'    => date('Y-m-d H:i:s', time() - (1 * 86400)), // expired yesterday
        'status'            => 'Aktif'
    ]
];

$ins = $db->prepare("INSERT INTO kunjungan_feedback_token 
    (token, kunjungan_id, nama_institusi, email, tanggal_kunjungan, perihal, berlaku_mulai, berlaku_sampai, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    nama_institusi = VALUES(nama_institusi),
    berlaku_mulai = VALUES(berlaku_mulai),
    berlaku_sampai = VALUES(berlaku_sampai),
    status = VALUES(status)
");

foreach ($tokens as $t) {
    $ins->execute([
        $t['token'],
        $t['kunjungan_id'],
        $t['nama_institusi'],
        $t['email'],
        $t['tanggal_kunjungan'],
        $t['perihal'],
        $t['berlaku_mulai'],
        $t['berlaku_sampai'],
        $t['status']
    ]);
}

echo "Demo tokens inserted successfully.\n";
