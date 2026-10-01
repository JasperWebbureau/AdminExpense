<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Integration\Banking;

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminBanking\Application\UseCase\AcceptManualBankMatch;
use Flexgrid\Modules\AdminBanking\Contract\BankExpenseCreatorInterface;
use Flexgrid\Modules\AdminBanking\Infrastructure\Persistence\PdoBankTransactionRepository;
use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Infrastructure\PdoTransactionManager;
use Flexgrid\Modules\AdminCore\Infrastructure\UuidV4Generator;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\UseCase\CreateExpense;
use Flexgrid\Modules\AdminExpense\Application\UseCase\AddExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Infrastructure\File\SecureExpenseFileStorage;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseCategoryRepository;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseRepository;
use Flexgrid\Utils\_Time;

final class ExpenseCreator implements BankExpenseCreatorInterface
{
    public function categories(TenantId $tenant): array
    {
        $categories = (new PdoExpenseCategoryRepository(Connection::getConnections()))->findAll($tenant);
        $rows = [];
        foreach ($categories as $category) {
            if ($category->isActive()) {
                $types = ['operating_cost' => 'Zakelijke kosten', 'payroll_cost' => 'Loonkosten', 'private_withdrawal' => 'Privéopname', 'balance_sheet' => 'Balansbeweging'];
                $rows[] = ['public_id' => $category->getPublicId(), 'name' => $category->getName(), 'type_label' => $types[$category->getReportingType()] ?? 'Zakelijke kosten'];
            }
        }
        return $rows;
    }

    public function suppliers(TenantId $tenant): array
    {
        return (new ExistingSuppliers())->list($tenant);
    }

    public function create(TenantId $tenant, string $transactionPublicId, array $fields, ?array $attachment): array
    {
        $connection = Connection::getConnections();
        $context = new TenantContext($tenant);
        $transactions = new PdoTransactionManager($connection);
        $bank = new PdoBankTransactionRepository($connection);
        $expenses = new PdoExpenseRepository($connection);
        $categories = new PdoExpenseCategoryRepository($connection);
        $clock = new _Time();
        $createExpense = new CreateExpense($context, new UuidV4Generator(), $transactions, $expenses, $categories, $clock);
        $match = new AcceptManualBankMatch($context, $transactions, $bank, [new MatchTargetProvider()], [new MatchProcessor()], $clock);
        $expense = (new CreateExpenseFromBankTransaction($context, $transactions, $bank, $expenses, $createExpense, $match))->execute(
            $transactionPublicId,
            (string)($fields['category_public_id'] ?? ''),
            (string)($fields['title'] ?? ''),
            (string)($fields['vat_rate'] ?? ''),
            (string)($fields['supplier_name'] ?? ''),
            (string)($fields['supplier_expense_public_id'] ?? ''),
            (string)($fields['description'] ?? ''),
            (string)($fields['reference'] ?? '')
        );
        $attachmentError = '';
        $attachmentUploaded = false;
        if ($attachment !== null && isset($attachment['error']) && (int)$attachment['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                (new AddExpenseAttachment($context, new UuidV4Generator(), $transactions, $expenses, new SecureExpenseFileStorage(), $clock))->execute($expense->getPublicId(), $attachment);
                $attachmentUploaded = true;
            } catch (\Throwable $error) {
                $attachmentError = $error instanceof \InvalidArgumentException || $error instanceof \DomainException
                    ? $error->getMessage() : 'Upload het bewijsstuk op de uitgavepagina.';
            }
        }
        return ['expense_public_id' => $expense->getPublicId(), 'attachment_uploaded' => $attachmentUploaded, 'attachment_error' => $attachmentError];
    }
}
