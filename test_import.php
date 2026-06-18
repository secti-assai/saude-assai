<?php
$content = file_get_contents("C:\\Users\\KawanHarshe\\Downloads\\Entrada de produtos - Analítico (1).csv");
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
if (substr($content, 0, 3) === "\xef\xbb\xbf") {
    $content = substr($content, 3);
}

$lines = explode("\n", $content);
$count = 0;
foreach($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    $parts = array_map('trim', explode(';', $line));
    $nonEmpty = array_values(array_filter($parts, fn($v) => $v !== ''));

    if (count($nonEmpty) === 0) continue;

    if ($nonEmpty[0] === 'Entrada') {
        if (count($nonEmpty) >= 4) {
            $lote = $nonEmpty[1];
            $quantStr = $nonEmpty[2];
            $valStr = $nonEmpty[3];
        } elseif (count($nonEmpty) === 3) {
            $lote = null;
            $quantStr = $nonEmpty[1];
            $valStr = $nonEmpty[2];
        } else {
            continue;
        }
        
        $rawVal = $valStr;
        $quantStr = str_replace(['.', ','], ['', '.'], $quantStr);
        $valStr = str_replace(['.', ','], ['', '.'], $valStr);

        $quant = (float) $quantStr;
        $val = (float) $valStr;
        
        echo "Lote: $lote | Quant: $quantStr ($quant) | Val: $rawVal -> $valStr ($val)\n";
        $count++;
        if ($count >= 10) break;
    }
}
