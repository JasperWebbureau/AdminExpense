<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Integration\Banking;

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminBanking\Contract\BankMatchProcessorInterface;
use Flexgrid\Modules\AdminBanking\Domain\Model\BankTransaction;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseRepository;

final class MatchProcessor implements BankMatchProcessorInterface
{
    public function supports(string $targetType): bool
    {
        return strtolower(trim($targetType)) === 'expense';
    }

    public function process(TenantId $tenant, BankTransaction $transaction, string $targetPublicId): void
    {
        if (!$transaction->getTenantId()->equals($tenant) || $transaction->getAmount()->getMinorUnits() >= 0) {
            throw new \DomainException('Alleen een negatieve bankregel van de actieve administratie kan aan een uitgave worden gekoppeld.');
        }

        $connection = Connection::getConnections();
        $alreadyMatched = $connection->prepare("SELECT `public_id` FROM `admin_bank_transaction`
            WHERE `tenant_id`=:tenant
              AND `status`='matched'
              AND `matched_target_type`='expense'
              AND `matched_target_public_id`=:target
              AND `public_id`<>:transaction
            LIMIT 1 FOR UPDATE");
        $alreadyMatched->execute([
            ':tenant' => $tenant->toString(),
            ':target' => trim($targetPublicId),
            ':transaction' => $transaction->getPublicId(),
        ]);
        if ($alreadyMatched->fetchColumn() !== false) {
            throw new \DomainException('Deze uitgave is al aan een andere banktransactie gekoppeld.');
        }

        $expense = (new PdoExpenseRepository($connection))->findByPublicIdForUpdate($tenant, trim($targetPublicId));
        if ($expense === null) {
            throw new \DomainException('Voorgestelde uitgave is niet meer beschikbaar.');
        }
        if (!$expense->getCurrency()->equals($transaction->getAmount()->getCurrency())
            || $expense->getGross()->getMinorUnits() !== abs($transaction->getAmount()->getMinorUnits())
        ) {
            throw new \DomainException('Uitgave en bankregel hebben niet langer hetzelfde brutobedrag en valuta.');
        }
    }
}
