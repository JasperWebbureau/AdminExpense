<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminExpense\Application\Command\UpdateExpenseCategoryAccountingCommand;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Utils\_Time;

final class UpdateExpenseCategoryAccounting
{
    private$tenant,$transactions,$categories,$clock;
    public function __construct(TenantContext$tenant,TransactionManagerInterface$transactions,ExpenseCategoryRepositoryInterface$categories,_Time$clock){$this->tenant=$tenant;$this->transactions=$transactions;$this->categories=$categories;$this->clock=$clock;}
    public function execute(UpdateExpenseCategoryAccountingCommand$command):ExpenseCategory
    {
        if($command->getPublicId()===''){throw new \InvalidArgumentException('Categorie-id is verplicht.');}
        $tenantId=$this->tenant->getTenantId();
        return$this->transactions->transactional(function()use($command,$tenantId):ExpenseCategory{
            $current=$this->categories->findByPublicId($tenantId,$command->getPublicId());
            if($current===null){throw new \DomainException('Uitgavencategorie is niet gevonden binnen de actieve administratie.');}
            $updated=$current->withAccounting($command->getReportingType(),$command->getVatDeductiblePercentage());
            $this->categories->updateAccounting($updated,(int)$this->clock->get());
            return$updated;
        });
    }
}
