<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminExpense\Application\Command\CreateExpenseCategoryCommand;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Utils\_Time;

final class CreateExpenseCategory
{
    private $tenant, $ids, $transactions, $categories, $clock;

    public function __construct(TenantContext $tenant, PublicIdGeneratorInterface $ids, TransactionManagerInterface $transactions, ExpenseCategoryRepositoryInterface $categories, _Time $clock)
    {
        $this->tenant = $tenant; $this->ids = $ids; $this->transactions = $transactions; $this->categories = $categories; $this->clock = $clock;
    }

    public function execute(CreateExpenseCategoryCommand $command): ExpenseCategory
    {
        $tenantId = $this->tenant->getTenantId();
        try {
            return $this->transactions->transactional(function () use ($command, $tenantId): ExpenseCategory {
                $existing = $this->categories->findByCode($tenantId, $command->getCode());
                if ($existing !== null) { return $existing; }
                $category = new ExpenseCategory($this->ids->generate(), $tenantId, $command->getCode(), $command->getName(), $command->getDescription(), $command->isActive(), $command->getReportingType(), $command->getVatDeductiblePercentage());
                $this->categories->insert($category, (int)$this->clock->get());
                return $category;
            });
        } catch (\PDOException $exception) {
            if ((string)$exception->getCode() === '23000') {
                $existing = $this->categories->findByCode($tenantId, $command->getCode());
                if ($existing !== null) { return $existing; }
            }
            throw $exception;
        }
    }
}
