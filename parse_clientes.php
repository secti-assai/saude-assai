<?php
$data = json_decode(file_get_contents('API-BETHA-SAUDE.json'), true);

$tags_found = [];
$interesting_keywords = ['cliente', 'paciente', 'pessoa', 'cidadao', 'usuario'];

foreach ($data['paths'] as $path => $methods) {
    foreach ($methods as $method => $details) {
        if ($method == 'parameters') continue;
        
        $match = false;
        $summary = strtolower($details['summary'] ?? '');
        $path_lower = strtolower($path);
        
        foreach ($interesting_keywords as $kw) {
            if (strpos($path_lower, $kw) !== false || strpos($summary, $kw) !== false) {
                $match = true;
                break;
            }
        }
        
        if ($match) {
            $tags = $details['tags'] ?? ['Sem Tag'];
            foreach ($tags as $tag) {
                if (!isset($tags_found[$tag])) {
                    $tags_found[$tag] = [];
                }
                $tags_found[$tag][] = strtoupper($method) . " $path - " . ($details['summary'] ?? '');
            }
        }
    }
}

$output = "Tags relacionadas a pacientes/clientes:\n\n";
foreach ($tags_found as $tag => $endpoints) {
    $output .= "=== $tag ===\n";
    $unique_endpoints = array_unique($endpoints);
    foreach ($unique_endpoints as $ep) {
        $output .= "  $ep\n";
    }
    $output .= "\n";
}

file_put_contents('betha_clientes_endpoints.txt', $output);
echo "Resultados salvos em betha_clientes_endpoints.txt\n";
