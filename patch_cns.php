<?php

function isValidCns(?string $cns): bool {
    $cns = preg_replace('/\D/', '', (string) $cns);
    if (strlen($cns) !== 15) return false;

    if (in_array($cns[0], ['1', '2'])) {
        $pis = substr($cns, 0, 11);
        $soma = 0;
        for ($i = 0; $i < 11; $i++) {
            $soma += ((int) $pis[$i]) * (15 - $i);
        }
        $resto = $soma % 11;
        $dv = 11 - $resto;
        if ($dv === 11) {
            $dv = 0;
        }
        
        if ($dv === 10) {
            $soma = 0;
            for ($i = 0; $i < 11; $i++) {
                $soma += ((int) $pis[$i]) * (15 - $i);
            }
            $soma += 2;
            $resto = $soma % 11;
            $dv = 11 - $resto;
            $resultado = $pis . "001" . (string) $dv;
        } else {
            $resultado = $pis . "000" . (string) $dv;
        }
        return $cns === $resultado;
    } elseif (in_array($cns[0], ['7', '8', '9'])) {
        $soma = 0;
        for ($i = 0; $i < 15; $i++) {
            $soma += ((int) $cns[$i]) * (15 - $i);
        }
        return $soma % 11 === 0;
    }
    
    return false;
}

$testCases = [
    '706307748265370', // From user's error log
    '707701785608631', // From user's error log
    '898001033308856', // Valid starting with 8 (found in some datasets)
    '123456789012345', // Invalid
];

foreach ($testCases as $cns) {
    echo $cns . ' is ' . (isValidCns($cns) ? 'VALID' : 'INVALID') . "\n";
}
