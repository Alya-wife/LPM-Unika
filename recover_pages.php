<?php
$log = 'C:/Users/ACER/.gemini/antigravity-ide/brain/92ae4a07-96fb-4d43-8d4a-221ae678bcef/.system_generated/logs/transcript_full.jsonl';
$fp = fopen($log, 'r');
$idx = 0;
while (($line = fgets($fp)) !== false) {
    $idx++;
    if (strpos($line, 'Branch 1: Tenaga Ahli') !== false) {
        echo "Found at step $idx\n";
        $j = json_decode($line, true);
        if (isset($j['content'])) {
            $lines = explode("\n", $j['content']);
            for ($k = 0; $k < count($lines); $k++) {
                if (strpos($lines[$k], 'Branch 1: Tenaga Ahli') !== false) {
                    echo implode("\n", array_slice($lines, max(0, $k - 30), 60)) . "\n";
                    break;
                }
            }
        }
    }
}
fclose($fp);
