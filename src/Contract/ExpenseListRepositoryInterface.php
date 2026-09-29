<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Contract;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\Query\ExpenseListQuery;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseListResult;

interface ExpenseListRepositoryInterface
{
    public function search(TenantId $tenantId, ExpenseListQuery $query): ExpenseListResult;
    public function getSummaries(TenantId $tenantId, ExpenseListQuery $query): array;
    public function getYears(TenantId $tenantId): array;
}
