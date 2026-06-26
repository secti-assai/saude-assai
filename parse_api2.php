<?php
$data = json_decode(file_get_contents('API-BETHA-SAUDE.json'), true);

$tags_found = [];

foreach ($data['paths'] as $path => $methods) {
    foreach ($methods as $method => $details) {
        if ($method == 'parameters') continue;
        $tags = $details['tags'] ?? ['Sem Tag'];
        foreach ($tags as $tag) {
            if (!isset($tags_found[$tag])) {
                $tags_found[$tag] = [];
            }
            $tags_found[$tag][] = strtoupper($method) . " $path - " . ($details['summary'] ?? '');
        }
    }
}

$interesting_keywords = ['farm', 'medicamento', 'dispensa', 'estoque', 'receita', 'produto', 'lote', 'material'];
$filtered_tags = [];

foreach ($tags_found as $tag => $endpoints) {
    $match = false;
    foreach ($interesting_keywords as $kw) {
        if (stripos($tag, $kw) !== false) {
            $match = true;
            break;
        }
    }
    if ($match) {
        $filtered_tags[$tag] = $endpoints;
    }
}

$output = "Tags relacionadas à farmácia, estoque, medicamentos, etc:\n\n";
foreach ($filtered_tags as $tag => $endpoints) {
    $output .= "=== $tag ===\n";
    $unique_endpoints = array_unique($endpoints);
    foreach ($unique_endpoints as $ep) {
        $output .= "  $ep\n";
    }
    $output .= "\n";
}

file_put_contents('betha_farmacia_endpoints.txt', $output);
echo "Resultados salvos em betha_farmacia_endpoints.txt\n";
