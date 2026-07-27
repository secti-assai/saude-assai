<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CnsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidCns($value)) {
            $fail('O campo :attribute deve ser um CNS válido.');
        }
    }

    private function isValidCns(?string $cns): bool
    {
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
}
