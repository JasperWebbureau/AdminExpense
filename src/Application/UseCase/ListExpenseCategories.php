<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;

final class ListExpenseCategories
{
    private $tenant, $categories;
    public function __construct(TenantContext $tenant, ExpenseCategoryRepositoryInterface $categories) { $this->tenant = $tenant; $this->categories = $categories; }
    public function execute(bool $includeInactive = false): array { return $this->categories->findAll($this->tenant->getTenantId(), $includeInactive); }
}
