<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Exception\ExpenseNotFoundException;

final class GetExpense
{
    private $tenant, $expenses;
    public function __construct(TenantContext $tenant, ExpenseRepositoryInterface $expenses) { $this->tenant = $tenant; $this->expenses = $expenses; }
    public function execute(string $publicId): Expense
    {
        $expense = $this->expenses->findByPublicId($this->tenant->getTenantId(), $publicId);
        if ($expense === null) { throw new ExpenseNotFoundException('Uitgave is niet gevonden.'); }
        return $expense;
    }
}
