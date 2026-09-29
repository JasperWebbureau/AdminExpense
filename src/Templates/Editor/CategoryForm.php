<?php if(!isset($h)){$h=function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};}?>
<grid class="admin-expense-category-sidebar fluid">
    <section class="panel admin-panel" style="--cw:12">
        <div class="panel__header admin-panel__header"><h3><i class="fas fa-scale-balanced"></i> Wat telt mee?</h3></div>
        <div class="panel__body admin-panel__body">
            <div class="admin-help-panel"><i class="fas fa-circle-info"></i><div><strong>Een betaling is niet altijd een bedrijfskost.</strong><p>Kies daarom per categorie wat de boekhoudkundige betekenis is. Dit voorkomt dat privéopnamen, aflossingen of overboekingen je resultaat onterecht verlagen.</p></div></div>
            <dl class="admin-expense-accounting-help"><?php foreach(($reportingTypes??[])as$type){?><div><dt><?=$h($type['label'])?></dt><dd><?=$h($type['description'])?></dd></div><?php }?></dl>
            <p class="admin-expense-accounting-note"><strong>Let op:</strong> bij een eenmanszaak is geld dat je naar jezelf overmaakt meestal een privéopname. Bij een BV kan echt DGA- of personeelsloon wel een loonkost zijn.</p>
        </div>
    </section>
    <form class="admin-form admin-expense-category-form" data-admin-expense-accounting-form ajax="true" action="<?=$h($categoryAction??'')?>" method="post">
        <?php if(!empty($publicId)){?><input type="hidden" name="expense_public_id" value="<?=$h($publicId)?>"><?php }?>
        <grid class="fluid"><section class="panel admin-panel" style="--cw:12"><div class="panel__header admin-panel__header"><h3><i class="fas fa-tags"></i> Nieuwe categorie</h3></div><grid class="panel__body admin-panel__body admin-expense-category-fields fluid">
            <label class="admin-field"><span>Naam *</span><input name="name" required maxlength="128" placeholder="Bijvoorbeeld software"></label>
            <label class="admin-field"><span>Code *</span><input name="code" required maxlength="64" pattern="[a-z][a-z0-9_-]+" placeholder="software"></label>
            <label class="admin-field"><span>Verwerking *</span><select name="reporting_type" required><?php foreach(($reportingTypes??[])as$value=>$type){?><option value="<?=$h($value)?>"><?=$h($type['label'])?></option><?php }?></select><small class="admin-field__help">Bepaalt of het bedrag als kosten in het resultaat komt.</small></label>
            <label class="admin-field"><span>Aftrekbare btw</span><input type="number" name="vat_deductible_percentage" value="100" min="0" max="100" step="1" inputmode="numeric"><small class="admin-field__help">0–100%. Gebruik 0% bij loon, privé en balansbewegingen.</small></label>
            <label class="admin-field"><span>Omschrijving</span><textarea name="description" rows="3" maxlength="1000"></textarea></label>
        </grid><div class="admin-expense-category-actions"><button class="button button-secondary" type="submit"><i class="fas fa-plus"></i> Categorie toevoegen</button></div></section></grid>
    </form>
    <?php if(!empty($categories)){?><section class="panel admin-panel" style="--cw:12"><div class="panel__header admin-panel__header"><h3><i class="fas fa-sliders"></i> Bestaande categorieën</h3></div><div class="panel__body admin-panel__body admin-expense-category-list">
        <p class="admin-expense-category-intro">Pas hier bijvoorbeeld <strong>Salaris</strong> aan naar privéopname of loonkosten. De rapportage wordt daarna opnieuw berekend.</p>
        <?php foreach($categories as$category){?><form class="admin-form admin-expense-category-accounting" data-admin-expense-accounting-form ajax="true" action="<?=$h($updateCategoryAction??'')?>" method="post">
            <input type="hidden" name="category_public_id" value="<?=$h($category->getPublicId())?>"><?php if(!empty($publicId)){?><input type="hidden" name="expense_public_id" value="<?=$h($publicId)?>"><?php }?>
            <strong><?=$h($category->getName())?></strong><small><?=$h($category->getCode())?></small>
            <label class="admin-field"><span>Verwerking</span><select name="reporting_type"><?php foreach(($reportingTypes??[])as$value=>$type){?><option value="<?=$h($value)?>"<?=$category->getReportingType()===$value?' selected':''?>><?=$h($type['label'])?></option><?php }?></select></label>
            <label class="admin-field"><span>Aftrekbare btw</span><span class="admin-expense-vat-input"><input type="number" name="vat_deductible_percentage" value="<?=$h($category->getVatDeductiblePercentage())?>" min="0" max="100" step="1" inputmode="numeric"><i>%</i></span></label>
            <button class="button button-secondary" type="submit"><i class="fas fa-save"></i> Verwerking opslaan</button>
        </form><?php }?>
    </div></section><?php }?>
</grid>
