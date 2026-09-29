<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Controller;

use Flexgrid\Event\AjaxEvent;
use Flexgrid\Modules\AdminExpense\Application\Command\CreateExpenseCategoryCommand;
use Flexgrid\Modules\AdminExpense\Application\Command\CreateExpenseCommand;
use Flexgrid\Modules\AdminExpense\Application\Command\UpdateExpenseCommand;
use Flexgrid\Modules\AdminExpense\Application\Command\UpdateExpenseCategoryAccountingCommand;
use Flexgrid\Modules\AdminExpense\Application\Query\ExpenseListQuery;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Exception\ExpenseNotFoundException;
use Flexgrid\Modules\AdminExpense\Service\AdminExpenseFactory;
use Flexgrid\Response\AjaxResponse;
use Flexgrid\Response\PageResponse;
use Flexgrid\Response\TemplateResponse;
use Flexgrid\Utils\Request\Request;

/** @FG\Controller [name=AdminExpense,type=Module,icon=fas fa-receipt,level=2,administrationPanel=true,administrationLabel=Uitgaven,administrationRoute=expenses,administrationPriority=50] */
final class AdminExpenseController
{
    public function index(){return $this->expenses();}
    public function expenses()
    {
        appendIconAndTitleToHeader('fas fa-receipt','Uitgaven','Administratie');$this->assets();PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Table/Css/Table.scss');PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Table/Js/Table.js');PageResponse::addAsset('Flexgrid/Modules/AdminExpense/src/Templates/Expenses/Js/Expenses.js');
        return new TemplateResponse('Flexgrid/Modules/AdminExpense/src/Templates/Expenses/Index.php',['content'=>(string)$this->renderContent($this->query()),'createUrl'=>$this->url('create')]);
    }
    public function create()
    {
        appendIconAndTitleToHeader('fas fa-circle-plus','Nieuwe uitgave','Administratie');$this->assets(true);
        return new TemplateResponse('Flexgrid/Modules/AdminExpense/src/Templates/Create/Index.php',['storeAction'=>$this->action('store'),'categoryAction'=>$this->action('storeCategory'),'updateCategoryAction'=>$this->action('updateCategoryAccounting'),'overviewUrl'=>$this->url('expenses'),'categories'=>AdminExpenseFactory::createListCategories()->execute(true),'reportingTypes'=>$this->reportingTypes(),'expenseDate'=>date('Y-m-d')]);
    }
    public function store()
    {
        try{$request=new Request();$expense=AdminExpenseFactory::createCreateExpense()->execute(new CreateExpenseCommand($this->string($request,'currency')?:'EUR',$this->string($request,'expense_date'),$this->string($request,'category_public_id'),$this->array($request,'supplier'),$this->string($request,'title'),$this->string($request,'net_amount'),$this->string($request,'tax_amount'),$this->string($request,'description'),$this->string($request,'reference')));$response=new AjaxResponse();$response->success=true;$response->redirect=$this->url('edit/'.rawurlencode($expense->getPublicId()));return$response;}catch(\Throwable$throwable){return$this->error($throwable);}
    }
    public function edit($args=[])
    {
        try{$expense=AdminExpenseFactory::createGetExpense()->execute($this->routeArgument($args));}catch(\Throwable$throwable){return$this->expenses();}
        appendIconAndTitleToHeader('fas fa-receipt','Uitgave bewerken','Administratie');$this->assets(true);
        return new TemplateResponse('Flexgrid/Modules/AdminExpense/src/Templates/Editor/Index.php',['content'=>(string)$this->editorContent($expense),'overviewUrl'=>$this->url('expenses')]);
    }
    public function update()
    {
        try{$request=new Request();$expense=AdminExpenseFactory::createUpdateExpense()->execute(new UpdateExpenseCommand($this->string($request,'public_id'),$this->string($request,'expense_date'),$this->string($request,'category_public_id'),$this->array($request,'supplier'),$this->string($request,'title'),$this->string($request,'net_amount'),$this->string($request,'tax_amount'),$this->string($request,'description'),$this->string($request,'reference')));$response=new AjaxResponse();$response->success=true;$response->notifications=['<div class="notification notification--success" fade="2400">Uitgave opgeslagen.</div>'];$response->setContainer('[data-admin-expense-editor]',(string)$this->editorContent($expense));return$response;}catch(\Throwable$throwable){return$this->error($throwable);}
    }
    public function storeCategory()
    {
        try{$request=new Request();AdminExpenseFactory::createCreateCategory()->execute(new CreateExpenseCategoryCommand($this->string($request,'code'),$this->string($request,'name'),$this->string($request,'description'),true,$this->string($request,'reporting_type'),$request->getInt('vat_deductible_percentage')));return$this->categoryRedirect($this->string($request,'expense_public_id'),'Categorie toegevoegd.');}catch(\Throwable$throwable){return$this->error($throwable);}
    }
    public function updateCategoryAccounting()
    {
        try{$request=new Request();AdminExpenseFactory::createUpdateCategoryAccounting()->execute(new UpdateExpenseCategoryAccountingCommand($this->string($request,'category_public_id'),$this->string($request,'reporting_type'),$request->getInt('vat_deductible_percentage')));return$this->categoryRedirect($this->string($request,'expense_public_id'),'Boekhoudkundige verwerking bijgewerkt.');}catch(\Throwable$throwable){return$this->error($throwable);}
    }
    public function uploadAttachment()
    {
        try{$request=new Request();$expense=AdminExpenseFactory::createAddAttachment()->execute($this->string($request,'public_id'),isset($_FILES['attachment'])&&is_array($_FILES['attachment'])?$_FILES['attachment']:[]);return$this->editorResponse($expense,'Bewijsstuk veilig opgeslagen.');}catch(\Throwable$throwable){return$this->error($throwable);}
    }
    public function removeAttachment()
    {
        try{$request=new Request();$expense=AdminExpenseFactory::createRemoveAttachment()->execute($this->string($request,'public_id'),$this->string($request,'attachment_id'));return$this->editorResponse($expense,'Bewijsstuk verwijderd.');}catch(\Throwable$throwable){return$this->error($throwable);}
    }
    public function downloadAttachment($args=[])
    {
        try{$expenseId=$this->routeArgument($args,0);$attachmentId=$this->routeArgument($args,1);$download=AdminExpenseFactory::createDownloadAttachment()->execute($expenseId,$attachmentId);$this->streamDownload($download);return null;}catch(\Throwable$throwable){return$this->expenses();}
    }
    public function refresh()
    {
        $query=$this->query();$response=new AjaxResponse();$response->success=true;$response->setContainer('[data-admin-expense-content]',(string)$this->renderContent($query));$response->replaceUrl=$this->overviewUrl($query);return$response;
    }
    private function editorContent(Expense$expense):TemplateResponse{return new TemplateResponse('Flexgrid/Modules/AdminExpense/src/Templates/Editor/Content.php',$this->formData($expense)+['updateAction'=>$this->action('update'),'categoryAction'=>$this->action('storeCategory'),'updateCategoryAction'=>$this->action('updateCategoryAccounting'),'uploadAction'=>$this->action('uploadAttachment'),'removeAttachmentAction'=>$this->action('removeAttachment'),'downloadBaseUrl'=>$this->url('downloadAttachment/'.$expense->getPublicId())]);}
    private function formData(Expense$expense):array{return['expense'=>$expense,'publicId'=>$expense->getPublicId(),'supplier'=>$expense->getSupplierSnapshot()->toArray(),'categories'=>AdminExpenseFactory::createListCategories()->execute(true),'reportingTypes'=>$this->reportingTypes(),'netAmount'=>$expense->getNet()->format(',',''),'taxAmount'=>$expense->getTax()->format(',',''),'grossAmount'=>$expense->getGross()->format(',','')];}
    private function renderContent(ExpenseListQuery$query):TemplateResponse{$data=AdminExpenseFactory::createListExpenses()->execute($query);return new TemplateResponse('Flexgrid/Modules/AdminExpense/src/Templates/Expenses/Content.php',AdminExpenseFactory::createOverviewPresenter()->present($data,$this->action('refresh'),$this->url('edit')));}
    private function query():ExpenseListQuery{$request=new Request();$year=$this->string($request,'year');$sort=$this->string($request,'sort');$direction=strtolower($this->string($request,'direction'));$perPage=$request->getInt('per_page');return new ExpenseListQuery(substr($this->string($request,'q'),0,120),$this->string($request,'category'),preg_match('/^[0-9]{4}$/D',$year)===1?(int)$year:null,in_array($sort,ExpenseListQuery::sorts(),true)?$sort:'expense_date',in_array($direction,['asc','desc'],true)?$direction:'desc',max(1,$request->getInt('page')),in_array($perPage,ExpenseListQuery::pageSizes(),true)?$perPage:10);}
    private function string(Request$request,string$key):string{$value=$request->get($key,'');return is_string($value)||is_int($value)?trim(strip_tags((string)$value)):'';}
    private function array(Request$request,string$key):array{$values=$request->get($key,[]);if(!is_array($values)){return[];}$clean=[];foreach($values as$name=>$value){if((is_string($name)||is_int($name))&&(is_string($value)||is_int($value))){$clean[(string)$name]=trim(strip_tags((string)$value));}}return$clean;}
    private function routeArgument($args,int$position=0):string{$value=is_array($args)?($args[$position]??''):($position===0?$args:'');return is_string($value)||is_int($value)?trim((string)$value):'';}
    private function action(string$method):string{$event=new AjaxEvent(self::class,$method);$event->setMinimumAccessLevel(2);return$event->getName();}
    private function error(\Throwable$throwable):AjaxResponse{$response=new AjaxResponse();$response->success=false;$message=$throwable instanceof \InvalidArgumentException||$throwable instanceof \DomainException||$throwable instanceof ExpenseNotFoundException?$throwable->getMessage():'De uitgave kon niet worden opgeslagen.';$response->error=$message;$response->notifications=['<div class="notification notification--error" fade="6000">'.htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</div>'];return$response;}
    private function editorResponse(Expense$expense,string$message):AjaxResponse{$response=new AjaxResponse();$response->success=true;$response->notifications=['<div class="notification notification--success" fade="2400">'.htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</div>'];$response->setContainer('[data-admin-expense-editor]',(string)$this->editorContent($expense));return$response;}
    private function categoryRedirect(string$expenseId,string$message):AjaxResponse{$response=new AjaxResponse();$response->success=true;$response->notifications=['<div class="notification notification--success" fade="2400">'.htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</div>'];$response->redirect=$expenseId!==''?$this->url('edit/'.rawurlencode($expenseId)):$this->url('create');return$response;}
    private function reportingTypes():array{return[
        'operating_cost'=>['label'=>'Zakelijke kosten','description'=>'Tellen mee in het resultaat. Alleen het ingestelde btw-deel geldt als voorbelasting.','vat_allowed'=>true],
        'payroll_cost'=>['label'=>'Loonkosten','description'=>'Tellen mee in het resultaat, zonder btw. Gebruik dit voor echte personeels- of DGA-loonkosten, niet voor een privéopname.','vat_allowed'=>false],
        'private_withdrawal'=>['label'=>'Privéopname','description'=>'Verlaagt het banksaldo, maar is geen bedrijfskost en verlaagt de winst niet.','vat_allowed'=>false],
        'balance_sheet'=>['label'=>'Balansbeweging','description'=>'Bijvoorbeeld leningaflossing, belastingbetaling of interne overboeking; geen omzet of kosten.','vat_allowed'=>false],
    ];}
    private function streamDownload($download):void{while(ob_get_level()>0){ob_end_clean();}$name=preg_replace('/[^a-zA-Z0-9._-]+/','_',basename(str_replace('\\','/',$download->getFileName())))?:'bewijsstuk';header('Content-Type: '.$download->getMediaType());header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.(string)$download->getSize());header('Cache-Control: private, no-store, max-age=0');header('Pragma: no-cache');header('X-Content-Type-Options: nosniff');echo$download->getContent();exit;}
    private function assets(bool$editor=false):void{PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Admin/Css/AdminUi.scss');if($editor){PageResponse::addAsset('Flexgrid/Modules/AdminExpense/src/Templates/Editor/Css/Editor.scss');PageResponse::addAsset('Flexgrid/Modules/AdminExpense/src/Templates/Editor/Js/Editor.js');}}
    private function url(string$path):string{return rtrim(__DOMAIN__,'/').'/Flexgrid/AdminExpense/'.ltrim($path,'/');}
    private function overviewUrl(ExpenseListQuery$query):string{$parameters=array_filter(['q'=>$query->getSearch(),'category'=>$query->getCategoryPublicId(),'year'=>$query->getYear(),'sort'=>$query->getSort(),'direction'=>$query->getDirection(),'page'=>$query->getPage(),'per_page'=>$query->getPerPage()],function($value):bool{return$value!==''&&$value!==null;});return$this->url('expenses').'?'.http_build_query($parameters);}
}
