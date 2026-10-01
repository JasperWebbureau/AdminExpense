<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Integration\Banking;

use Flexgrid\Modules\AdminBanking\Application\UseCase\AcceptManualBankMatch;
use Flexgrid\Modules\AdminBanking\Contract\BankTransactionRepositoryInterface;
use Flexgrid\Modules\AdminBanking\Domain\ValueObject\BankTransactionStatus;
use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminExpense\Application\Command\CreateExpenseCommand;
use Flexgrid\Modules\AdminExpense\Application\UseCase\CreateExpense;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;

final class CreateExpenseFromBankTransaction
{
    private $tenant;
    private $transactions;
    private $bankTransactions;
    private $expenses;
    private $createExpense;
    private $matchExpense;

    public function __construct(TenantContext $tenant, TransactionManagerInterface $transactions, BankTransactionRepositoryInterface $bankTransactions, ExpenseRepositoryInterface $expenses, CreateExpense $createExpense, AcceptManualBankMatch $matchExpense)
    {
        $this->tenant = $tenant;
        $this->transactions = $transactions;
        $this->bankTransactions = $bankTransactions;
        $this->expenses = $expenses;
        $this->createExpense = $createExpense;
        $this->matchExpense = $matchExpense;
    }

    public function execute(string $transactionPublicId, string $categoryPublicId, string $title, string $vatRate, string $supplierName = '', string $supplierExpensePublicId = '', string $description = '', string $reference = ''): Expense
    {
        $transactionPublicId = trim($transactionPublicId);
        $categoryPublicId = trim($categoryPublicId);
        $title = trim($title);
        if ($transactionPublicId === '' || $categoryPublicId === '' || $title === '') {
            throw new \InvalidArgumentException('Banktransactie, categorie en titel zijn verplicht.');
        }
        $rateBasisPoints = $this->rateBasisPoints($vatRate);
        return $this->transactions->transactional(function () use ($transactionPublicId, $categoryPublicId, $title, $rateBasisPoints, $supplierName, $supplierExpensePublicId, $description, $reference): Expense {
            $tenantId = $this->tenant->getTenantId();
            $bank = $this->bankTransactions->findByPublicIdForUpdate($tenantId, $transactionPublicId);
            if ($bank === null) {
                throw new \DomainException('Banktransactie is niet gevonden.');
            }
            if ($bank->getStatus()->getValue() !== BankTransactionStatus::UNMATCHED || $bank->getAmount()->getMinorUnits() >= 0) {
                throw new \DomainException('Alleen een open afschrijving kan een uitgave worden.');
            }
            $currency = $bank->getAmount()->getCurrency();
            $gross = $bank->getAmount()->negate()->getMinorUnits();
            $denominator = 10000 + $rateBasisPoints;
            $tax = intdiv($gross, $denominator) * $rateBasisPoints
                + intdiv(($gross % $denominator) * $rateBasisPoints + intdiv($denominator, 2), $denominator);
            $net = $gross - $tax;
            $supplier = $this->supplier($supplierName, $supplierExpensePublicId);
            $expense = $this->createExpense->execute(new CreateExpenseCommand(
                $currency->getCode(), $bank->getBookedOn()->getValue(), $categoryPublicId, $supplier,
                $title, (new Money($net, $currency))->format('.', ''), (new Money($tax, $currency))->format('.', ''),
                trim($description), trim($reference), [], 'admin_banking', $transactionPublicId
            ));
            if ($expense->getGross()->getMinorUnits() !== $gross || !$expense->getCurrency()->equals($currency)) {
                throw new \DomainException('De bestaande uitgave heeft niet meer hetzelfde bedrag of dezelfde valuta.');
            }
            $this->matchExpense->execute($transactionPublicId, 'expense', $expense->getPublicId());
            return $expense;
        });
    }

    private function supplier(string $name, string $expensePublicId): array
    {
        $name = trim($name);
        $expensePublicId = trim($expensePublicId);
        if ($name !== '') {
            return ['name' => $name];
        }
        if ($expensePublicId === '') {
            return [];
        }
        $expense = $this->expenses->findByPublicId($this->tenant->getTenantId(), $expensePublicId);
        if ($expense === null || $expense->getSupplierSnapshot()->getName() === '') {
            throw new \DomainException('De gekozen leverancier is niet meer beschikbaar.');
        }
        return $expense->getSupplierSnapshot()->toArray();
    }

    private function rateBasisPoints(string $rate): int
    {
        $rate = str_replace(',', '.', trim($rate));
        if (preg_match('/^(?:100(?:\.0{1,2})?|[0-9]{1,2}(?:\.[0-9]{1,2})?)$/D', $rate) !== 1) {
            throw new \InvalidArgumentException('Btw-percentage moet tussen 0 en 100 liggen, met maximaal twee decimalen.');
        }
        $parts = explode('.', $rate, 2);
        return (int)$parts[0] * 100 + (int)str_pad($parts[1] ?? '', 2, '0');
    }
}
