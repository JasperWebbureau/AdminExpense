<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminExpense\Application\Query\ExpenseListQuery;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseListRepositoryInterface;

final class ListExpenses
{
    private $tenant,$expenses,$categories;
    public function __construct(TenantContext$tenant,ExpenseListRepositoryInterface$expenses,ExpenseCategoryRepositoryInterface$categories){$this->tenant=$tenant;$this->expenses=$expenses;$this->categories=$categories;}
    public function execute(ExpenseListQuery$query):array{$tenant=$this->tenant->getTenantId();return['query'=>$query,'result'=>$this->expenses->search($tenant,$query),'summaries'=>$this->expenses->getSummaries($tenant,$query),'years'=>$this->expenses->getYears($tenant),'categories'=>$this->categories->findAll($tenant,true)];}
}
