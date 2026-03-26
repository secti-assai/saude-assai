<?php

$file = 'resources/views/admin/portal.blade.php';

$content = file_get_contents($file);

$content = str_replace(
    [
        'TÃƒtulo',
        'ConteÃƒÂºdo',
        'NotÃƒcia',
        'NotÃƒcias',
        'AÃƒÂ§ÃƒÂµes',
        'DescriÃƒÂ§ÃƒÂ£o',
        'pÃƒÂºblico',
        'ÃƒÂº'
    ],
    [
        'Título',
        'Conteúdo',
        'Notícia',
        'Notícias',
        'Ações',
        'Descrição',
        'público',
        'ú'
    ],
    $content
);

file_put_contents($file, $content);