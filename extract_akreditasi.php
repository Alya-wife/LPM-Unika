<?php
$files = glob('C:/Users/ACER/.gemini/antigravity-ide/brain/*/.system_generated/logs/transcript_full.jsonl');
foreach ($files as $f) {
    $fp = fopen($f, 'r');
    while (($line = fgets($fp)) !== false) {
        if (strpos($line, 'akreditasi_institusi_peringkat') !== false && strpos($line, 'prodi_list') !== false && strpos($line, 'fakultas_data') !== false) {
            $data = json_decode($line, true);
            $content = $data['content'] ?? ($data['tool_calls'][0]['args']['CodeContent'] ?? '');
            if (strlen($content) > 10000) {
                echo "Found full content in $f, length: " . strlen($content) . PHP_EOL;
                file_put_contents('akreditasi_original_backup.php', $content);
                break 2;
            }
        }
    }
    fclose($fp);
}
echo "Done search." . PHP_EOL;
