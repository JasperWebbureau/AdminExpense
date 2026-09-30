<?php $h=function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};?>
<section class="admin-page admin-expense-editor-page"><div class="admin-page-header"><div><p class="admin-page-header__eyebrow">Uitgave</p><h1>Uitgave bewerken</h1></div></div><div data-admin-expense-editor><?=$content??''?></div></section>
