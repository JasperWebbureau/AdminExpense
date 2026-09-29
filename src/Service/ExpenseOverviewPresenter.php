<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Service;

use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminExpense\Application\Query\ExpenseListQuery;

final class ExpenseOverviewPresenter
{
    public function present(array$data,string$refreshAction,string$editBaseUrl):array
    {
        $query=$data['query'];$result=$data['result'];$rows=[];
        foreach($result->getItems()as$item){$rows[]=['id'=>$item->getPublicId(),'url'=>rtrim($editBaseUrl,'/').'/'.rawurlencode($item->getPublicId()),'cells'=>['title'=>['value'=>$item->getTitle(),'secondary'=>$item->getReference(),'title'=>true],'supplier'=>$item->getSupplierName(),'category'=>['value'=>$item->getCategoryName(),'secondary'=>$this->accountingLabel($item->getReportingType(),$item->getVatDeductiblePercentage()),'badge'=>'neutral'],'expense_date'=>$this->date($item->getExpenseDate()),'attachments'=>$item->getAttachmentCount()>0?(string)$item->getAttachmentCount():'—','gross_total'=>$this->money($item->getGrossTotalMinor(),$item->getCurrency())]];}
        $columns=[];foreach(['title'=>['Uitgave','left',true],'supplier'=>['Leverancier','left',true],'category'=>['Categorie','left',true],'expense_date'=>['Datum','left',true],'attachments'=>['Bijlagen','center',false],'gross_total'=>['Bedrag','right',true]]as$key=>$settings){$columns[]=['key'=>$key,'label'=>$settings[0],'align'=>$settings[1],'sortable'=>$settings[2],'sort_direction'=>$query->getSort()===$key?$query->getDirection():''];}
        $cards=[];$summaries=$data['summaries'];if($summaries===[]){$summaries=[new \Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseOverviewSummary(0,0,0,0,'EUR')];}$multiple=count($summaries)>1;
        foreach($summaries as$summary){$suffix=$multiple?' ('.$summary->getCurrency().')':'';$cards[]=['label'=>'Registraties'.$suffix,'value'=>(string)$summary->getCount(),'meta'=>'alle bewegingen binnen filters','icon'=>'fas fa-receipt','tone'=>'accent'];$cards[]=['label'=>'Resultaatkosten'.$suffix,'value'=>$this->money($summary->getNetMinor(),$summary->getCurrency()),'meta'=>'zakelijk en loon, na btw','icon'=>'fas fa-chart-line','tone'=>'info'];$cards[]=['label'=>'Aftrekbare btw'.$suffix,'value'=>$this->money($summary->getTaxMinor(),$summary->getCurrency()),'meta'=>'volgens categorie-instelling','icon'=>'fas fa-percent','tone'=>'warning'];$cards[]=['label'=>'Bruto bewegingen'.$suffix,'value'=>$this->money($summary->getGrossMinor(),$summary->getCurrency()),'meta'=>'inclusief privé en balans','icon'=>'fas fa-euro-sign','tone'=>'success'];}
        $categoryOptions=[''=>'Alle categorieën'];foreach($data['categories']as$category){$categoryOptions[$category->getPublicId()]=$category->getName().' · '.$this->accountingLabel($category->getReportingType(),$category->getVatDeductiblePercentage()).($category->isActive()?'':' (inactief)');}
        return['refreshAction'=>$refreshAction,'query'=>$query,'result'=>$result,'years'=>$data['years'],'summaryCards'=>$cards,'categoryOptions'=>$categoryOptions,'pageSizes'=>ExpenseListQuery::pageSizes(),'pages'=>$this->pages($result),'table'=>['id'=>'admin-expense-overview','label'=>'Uitgaven','columns'=>$columns,'rows'=>$rows,'empty'=>['title'=>'Geen uitgaven gevonden','message'=>'Pas de filters aan of voeg een nieuwe uitgave toe.','icon'=>'fas fa-receipt']]];
    }
    private function accountingLabel(string$type,int$percentage):string{$labels=['operating_cost'=>'Zakelijke kosten · '.$percentage.'% btw-aftrek','payroll_cost'=>'Loonkosten · geen btw','private_withdrawal'=>'Privéopname · niet in resultaat','balance_sheet'=>'Balansbeweging · niet in resultaat'];return$labels[$type]??$labels['operating_cost'];}
    private function money(int$minor,string$code):string{return($code==='EUR'?'€':$code).' '.(new Money($minor,new Currency($code)))->format();}
    private function date(string$date):string{$parts=explode('-',$date);return count($parts)===3?$parts[2].'-'.$parts[1].'-'.$parts[0]:$date;}
    private function pages($result):array{$start=max(1,$result->getPage()-2);$end=min($result->getTotalPages(),$start+4);$start=max(1,$end-4);return range($start,$end);}
}
