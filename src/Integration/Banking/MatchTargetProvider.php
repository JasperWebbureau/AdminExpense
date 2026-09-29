<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Integration\Banking;

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminBanking\Application\ReadModel\BankMatchContext;
use Flexgrid\Modules\AdminBanking\Application\ReadModel\BankMatchTarget;
use Flexgrid\Modules\AdminBanking\Contract\BankMatchTargetProviderInterface;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

final class MatchTargetProvider implements BankMatchTargetProviderInterface
{
    public function supports(string $targetType): bool
    {
        return strtolower(trim($targetType)) === 'expense';
    }

    public function search(TenantId $tenant, BankMatchContext $context, string $query, int $limit): array
    {
        if ($context->getAmountMinor() >= 0) {
            return [];
        }

        $sql = $this->selectSql() . "
            WHERE e.`tenant_id`=:tenant
              AND e.`currency`=:currency
              AND (
                    e.`title` LIKE :query
                 OR COALESCE(e.`reference`,'') LIKE :query
                 OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.`supplier_snapshot`,'$.name')),'') LIKE :query
              )
              AND NOT EXISTS (
                  SELECT 1 FROM `admin_bank_transaction` b
                   WHERE b.`tenant_id`=e.`tenant_id`
                     AND b.`status`='matched'
                     AND b.`matched_target_type`='expense'
                     AND b.`matched_target_public_id`=e.`public_id`
                     AND b.`public_id`<>:transaction_id
              )
            ORDER BY e.`expense_date` DESC, e.`id` DESC
            LIMIT " . max(1, min(50, $limit));
        $statement = Connection::getConnections()->prepare($sql);
        $statement->execute([
            ':tenant' => $tenant->toString(),
            ':currency' => $context->getCurrency(),
            ':query' => '%' . trim($query) . '%',
            ':transaction_id' => $context->getTransactionPublicId(),
        ]);

        return array_map(function (array $row) use ($context): BankMatchTarget {
            return $this->map($row, $context);
        }, $statement->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function find(TenantId $tenant, BankMatchContext $context, string $targetPublicId): ?BankMatchTarget
    {
        if ($context->getAmountMinor() >= 0) {
            return null;
        }

        $statement = Connection::getConnections()->prepare($this->selectSql() . "
            WHERE e.`tenant_id`=:tenant
              AND e.`public_id`=:public_id
              AND e.`currency`=:currency
              AND NOT EXISTS (
                  SELECT 1 FROM `admin_bank_transaction` b
                   WHERE b.`tenant_id`=e.`tenant_id`
                     AND b.`status`='matched'
                     AND b.`matched_target_type`='expense'
                     AND b.`matched_target_public_id`=e.`public_id`
                     AND b.`public_id`<>:transaction_id
              )
            LIMIT 1");
        $statement->execute([
            ':tenant' => $tenant->toString(),
            ':public_id' => trim($targetPublicId),
            ':currency' => $context->getCurrency(),
            ':transaction_id' => $context->getTransactionPublicId(),
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $this->map($row, $context) : null;
    }

    private function selectSql(): string
    {
        return "SELECT e.`public_id`,e.`expense_date`,e.`title`,e.`reference`,e.`gross_amount_minor`,e.`currency`,
                       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.`supplier_snapshot`,'$.name')),'') AS supplier_name
                  FROM `admin_expense` e";
    }

    private function map(array $row, BankMatchContext $context): BankMatchTarget
    {
        $gross = (int)$row['gross_amount_minor'];
        $selectable = $gross === abs($context->getAmountMinor());
        $warning = $selectable ? '' : 'Het bankbedrag wijkt af van het brutobedrag van deze uitgave.';

        return new BankMatchTarget(
            'expense',
            (string)$row['public_id'],
            (string)$row['title'],
            (string)$row['supplier_name'],
            (string)($row['reference'] ?? ''),
            (string)$row['expense_date'],
            $gross,
            $gross,
            (string)$row['currency'],
            $selectable,
            $warning
        );
    }
}
