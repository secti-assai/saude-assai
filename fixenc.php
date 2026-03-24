<?php
\ = file_get_contents('resources/views/admin/portal.blade.php');
\ = str_replace(['TÃƒtulo','ConteÃƒÂºdo','NotÃƒcia','NotÃƒcias','AÃƒÂ§ÃƒÂµes','DescriÃƒÂ§ÃƒÂ£o', 'pÃƒÂºblico', 'ÃƒÂº'], ['Título','Conteúdo','Notícia','Notícias','Ações','Descrição', 'público', 'ú'], \);
file_put_contents('resources/views/admin/portal.blade.php', \);

