<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Contract;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment;

interface ExpenseRepositoryInterface
{
    public function insert(Expense $expense, int $createdAt): void;
    public function findByPublicId(TenantId $tenantId, string $publicId): ?Expense;
    public function findByPublicIdForUpdate(TenantId $tenantId, string $publicId): ?Expense;
    public function findByExternalReference(TenantId $tenantId, string $source, string $externalId): ?Expense;
    public function update(Expense $expense, int $updatedAt): void;
    public function insertAttachment(TenantId $tenantId, string $expensePublicId, ExpenseAttachment $attachment, int $createdAt): void;
    public function removeAttachment(TenantId $tenantId, string $expensePublicId, string $attachmentPublicId): bool;
}
