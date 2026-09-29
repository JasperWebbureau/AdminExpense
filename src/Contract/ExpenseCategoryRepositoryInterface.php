<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Contract;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;

interface ExpenseCategoryRepositoryInterface
{
    public function insert(ExpenseCategory $category, int $createdAt): void;
    public function updateAccounting(ExpenseCategory $category, int $updatedAt): void;
    public function findByPublicId(TenantId $tenantId, string $publicId): ?ExpenseCategory;
    public function findByCode(TenantId $tenantId, string $code): ?ExpenseCategory;
    /** @return ExpenseCategory[] */
    public function findAll(TenantId $tenantId, bool $includeInactive = false): array;
}
