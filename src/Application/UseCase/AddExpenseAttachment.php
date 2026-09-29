<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseFileStorageInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Exception\ExpenseNotFoundException;
use Flexgrid\Utils\_Time;

final class AddExpenseAttachment
{
    private $tenant,$ids,$transactions,$expenses,$storage,$clock;
    public function __construct(TenantContext$tenant,PublicIdGeneratorInterface$ids,TransactionManagerInterface$transactions,ExpenseRepositoryInterface$expenses,ExpenseFileStorageInterface$storage,_Time$clock){$this->tenant=$tenant;$this->ids=$ids;$this->transactions=$transactions;$this->expenses=$expenses;$this->storage=$storage;$this->clock=$clock;}
    public function execute(string$expensePublicId,array$uploadedFile):Expense
    {
        $tenant=$this->tenant->getTenantId();if($this->expenses->findByPublicId($tenant,$expensePublicId)===null){throw new ExpenseNotFoundException('Uitgave is niet gevonden.');}$context='expense.upload.'.hash('sha256',$tenant->toString().'|'.$expensePublicId);$stored=$this->storage->storeUploadedFile($uploadedFile,$context);
        try{return$this->transactions->transactional(function()use($tenant,$expensePublicId,$stored):Expense{$expense=$this->expenses->findByPublicIdForUpdate($tenant,$expensePublicId);if($expense===null){throw new ExpenseNotFoundException('Uitgave is niet gevonden.');}$attachment=new ExpenseAttachment($this->ids->generate(),$stored->getReference(),$stored->getOriginalName(),$stored->getMediaType(),$stored->getSizeBytes(),$stored->getSha256());$expense->addAttachment($attachment);$this->expenses->insertAttachment($tenant,$expensePublicId,$attachment,(int)$this->clock->get());return$expense;});}catch(\Throwable$exception){try{$this->storage->discard($stored->getReference(),'expense.upload.rollback');}catch(\Throwable$cleanup){throw new \RuntimeException('De bijlagekoppeling mislukte en het beveiligde bestand kon niet automatisch worden opgeruimd.',0,$exception);}throw$exception;}
    }
}
