<?php $h=function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};?>
<section class="admin-page admin-expense-create">
    <div class="admin-page-header"><div><p class="admin-page-header__eyebrow">Uitgaven</p><h1>Nieuwe uitgave</h1><p class="admin-page-header__intro">Leg het document, de leverancier en de exacte bedragen vast.</p></div><a class="button button-secondary" href="<?=$h($overviewUrl??'')?>"><i class="fas fa-arrow-left"></i> Terug naar overzicht</a></div>
    <?php if($categories===[]){?><div class="notification notification--error">Maak eerst minimaal één uitgavencategorie aan.</div><?php }?>
    <grid class="admin-expense-form-layout">
        <form class="admin-form admin-expense-form" style="--cw:8;--cw-sm:12" ajax="true" action="<?=$h($storeAction??'')?>" method="post">
            <grid class="fluid">
            <section class="panel admin-panel" style="--cw:12"><div class="panel__header admin-panel__header"><h3><i class="fas fa-receipt"></i> Uitgave</h3></div><grid class="panel__body admin-panel__body admin-form-grid fluid">
                <label class="admin-field admin-field--wide"><span>Titel *</span><input name="title" required maxlength="255" placeholder="Bijvoorbeeld hosting september"></label>
                <label class="admin-field"><span>Datum *</span><input type="date" name="expense_date" value="<?=$h($expenseDate??'')?>" required></label>
                <label class="admin-field"><span>Categorie *</span><select name="category_public_id" required><option value="">Kies een categorie</option><?php foreach($categories as$category){if(!$category->isActive()){continue;}$type=$reportingTypes[$category->getReportingType()]??null;?><option value="<?=$h($category->getPublicId())?>"><?=$h($category->getName().' · '.($type['label']??$category->getReportingType()))?></option><?php }?></select><small class="admin-field__help">De categorie bepaalt of deze betaling het resultaat en de btw-aftrek beïnvloedt.</small></label>
                <label class="admin-field"><span>Netto *</span><input name="net_amount" value="0,00" required inputmode="decimal"></label><label class="admin-field"><span>Btw *</span><input name="tax_amount" value="0,00" required inputmode="decimal"></label><input type="hidden" name="currency" value="EUR">
                <label class="admin-field"><span>Referentie</span><input name="reference" maxlength="128"></label><label class="admin-field admin-field--wide"><span>Omschrijving</span><textarea name="description" rows="4" maxlength="10000"></textarea></label>
            </grid></section>
            <section class="panel admin-panel" style="--cw:12"><div class="panel__header admin-panel__header"><h3><i class="fas fa-building"></i> Leverancier</h3></div><grid class="panel__body admin-panel__body admin-form-grid fluid">
                <label class="admin-field admin-field--wide"><span>Naam *</span><input name="supplier[name]" required maxlength="255" autocomplete="organization"></label><label class="admin-field"><span>Contactpersoon</span><input name="supplier[contact_name]" maxlength="128"></label><label class="admin-field"><span>E-mail</span><input type="email" name="supplier[email]" maxlength="255"></label><label class="admin-field"><span>KvK-nummer</span><input name="supplier[registration_number]" maxlength="128"></label><label class="admin-field"><span>Btw-nummer</span><input name="supplier[tax_number]" maxlength="128"></label><label class="admin-field"><span>Landcode *</span><input name="supplier[country_code]" value="NL" maxlength="2" pattern="[A-Za-z]{2}" required></label>
            </grid></section>
            </grid>
            <div class="admin-form-actions"><a class="button button-secondary" href="<?=$h($overviewUrl??'')?>">Annuleren</a><button class="button button-publish" type="submit"<?=$categories===[]?' disabled':''?>><i class="fas fa-save"></i> Uitgave opslaan</button></div>
        </form>
        <aside style="--cw:4;--cw-sm:12"><?php include dirname(__DIR__).'/Editor/CategoryForm.php';?></aside>
    </grid>
</section>
