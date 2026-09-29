<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseFileStorageInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Exception\ExpenseNotFoundException;

final class RemoveExpenseAttachment
{
    private $tenant,$transactions,$expenses,$storage;public function __construct(TenantContext$tenant,TransactionManagerInterface$transactions,ExpenseRepositoryInterface$expenses,ExpenseFileStorageInterface$storage){$this->tenant=$tenant;$this->transactions=$transactions;$this->expenses=$expenses;$this->storage=$storage;}
    public function execute(string$expensePublicId,string$attachmentPublicId):Expense
    {
        $tenant=$this->tenant->getTenantId();$reference='';$expense=$this->transactions->transactional(function()use($tenant,$expensePublicId,$attachmentPublicId,&$reference):Expense{$expense=$this->expenses->findByPublicIdForUpdate($tenant,$expensePublicId);if($expense===null){throw new ExpenseNotFoundException('Uitgave is niet gevonden.');}$found=false;foreach($expense->getAttachments()as$attachment){if(hash_equals($attachment->getPublicId(),$attachmentPublicId)){$reference=$attachment->getFileReference();$found=true;break;}}if(!$found||!$this->expenses->removeAttachment($tenant,$expensePublicId,$attachmentPublicId)){throw new ExpenseNotFoundException('Bewijsstuk is niet gevonden bij deze uitgave.');}return$this->expenses->findByPublicId($tenant,$expensePublicId);});
        $this->storage->discard($reference,'expense.remove');return$expense;
    }
}
