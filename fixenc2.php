<?php
$c = file_get_contents('resources/views/admin/portal.blade.php');
// Fix all utf-8 malformations
$c = mb_convert_encoding($c, 'Windows-1252', 'UTF-8');
$c = str_replace(
    '<form method="POST" action="{{ route(\'admin.portal.destroy\', $content) }}" onsubmit="return confirm(\'Certeza que deseja remover?\')">',
    '<div class="flex space-x-2 justify-end"><a href="{{ route(\'admin.portal.edit\', $content) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Editar</a><form method="POST" action="{{ route(\'admin.portal.destroy\', $content) }}" class="inline" onsubmit="return confirm(\'Certeza que deseja remover?\')">',
    $c
);
$c = str_replace('</form>', '</form></div>', $c);

// Fix potential over-replaces if running multiple times
$c = str_replace('</div></div>', '</div>', $c);
file_put_contents('resources/views/admin/portal.blade.php', $c);