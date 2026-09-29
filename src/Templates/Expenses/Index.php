<?php $h=function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};?>
<section class="admin-page admin-expense-overview">
    <div class="admin-page-header">
        <div><p class="admin-page-header__eyebrow">Uitgaven</p><h1>Overzicht</h1><p class="admin-page-header__intro">Registreer zakelijke kosten, btw en bijbehorende leveranciersgegevens.</p></div>
        <div class="admin-page-header__actions"><a class="button button-publish" href="<?=$h($createUrl??'')?>"><i class="fas fa-plus"></i> Nieuwe uitgave</a></div>
    </div>
    <div data-admin-expense-content><?=$content??''?></div>
</section>
