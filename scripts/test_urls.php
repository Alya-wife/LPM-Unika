<?php
$urls = [
    'http://localhost/LPM/pelatihan-eksternal.php',
    'http://localhost/LPM/pelatihan.php',
    'http://localhost/LPM/layanan.php',
    'http://localhost/LPM/feedback-kunjungan.php',
    'http://localhost/LPM/api/check-token.php?token=LPM-TEST01'
];

foreach ($urls as $url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $status_line = $http_response_header[0] ?? 'NO RESPONSE';
    echo "URL: $url\n  Status: $status_line\n  Length: " . strlen($body) . " bytes\n\n";
}
