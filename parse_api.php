<?php
$data = json_decode(file_get_contents('API-BETHA-SAUDE.json'), true);

if (!$data) {
    echo "Could not parse JSON or file not found.\n";
    exit(1);
}

echo "OpenAPI Version: " . ($data['openapi'] ?? $data['swagger'] ?? 'Unknown') . "\n";
echo "Title: " . ($data['info']['title'] ?? 'N/A') . "\n";
echo "Description: " . substr($data['info']['description'] ?? 'N/A', 0, 500) . "...\n";

$paths = array_keys($data['paths'] ?? []);
$farmaciaPaths = [];

$keywords = ['farmacia', 'medicamento', 'dispensa', 'estoque', 'lote', 'receita', 'movimentacao', 'material'];

foreach ($paths as $path) {
    $matched = false;
    foreach ($keywords as $kw) {
        if (stripos($path, $kw) !== false) {
            $matched = true;
            break;
        }
    }
    
    $methods = array_keys($data['paths'][$path] ?? []);
    foreach ($methods as $method) {
        if ($method == 'parameters') continue;
        $summary = $data['paths'][$path][$method]['summary'] ?? 'No summary';
        $desc = substr($data['paths'][$path][$method]['description'] ?? '', 0, 100);
        
        $tags = implode(', ', $data['paths'][$path][$method]['tags'] ?? []);
        
        if (!$matched) {
            foreach ($keywords as $kw) {
                if (stripos($summary, $kw) !== false || stripos($tags, $kw) !== false || stripos($desc, $kw) !== false) {
                    $matched = true;
                    break;
                }
            }
        }
        
        if ($matched) {
             $farmaciaPaths[] = str_pad(strtoupper($method), 7) . " $path\n        Tags: $tags\n        Summary: $summary";
        }
    }
}

echo "\nRelevant endpoints found: " . count($farmaciaPaths) . "\n\n";

foreach (array_slice($farmaciaPaths, 0, 100) as $p) echo "$p\n";
