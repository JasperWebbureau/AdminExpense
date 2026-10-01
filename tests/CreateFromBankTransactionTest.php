<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/AdminBanking/tests/bootstrap.php';
require_once dirname(__DIR__, 2) . '/AdminBanking/src/Contract/BankExpenseCreatorInterface.php';
require_once dirname(__DIR__, 2) . '/AdminBanking/src/Service/BankExpenseCreatorLoader.php';
require_once dirname(__DIR__) . '/src/Integration/Banking/CreateExpenseFromBankTransaction.php';

use Flexgrid\Modules\AdminBanking\Application\ReadModel\BankMatchContext;
use Flexgrid\Modules\AdminBanking\Application\ReadModel\BankMatchTarget;
use Flexgrid\Modules\AdminBanking\Application\UseCase\AcceptManualBankMatch;
use Flexgrid\Modules\AdminBanking\Contract\BankMatchProcessorInterface;
use Flexgrid\Modules\AdminBanking\Contract\BankMatchTargetProviderInterface;
use Flexgrid\Modules\AdminBanking\Contract\BankTransactionRepositoryInterface;
use Flexgrid\Modules\AdminBanking\Domain\Model\BankTransaction;
use Flexgrid\Modules\AdminBanking\Domain\ValueObject\BankDate;
use Flexgrid\Modules\AdminBanking\Domain\ValueObject\BankSourceSnapshot;
use Flexgrid\Modules\AdminBanking\Domain\ValueObject\CounterpartySnapshot;
use Flexgrid\Modules\AdminBanking\Service\BankExpenseCreatorLoader;
use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\UseCase\CreateExpense;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Modules\AdminExpense\Integration\Banking\CreateExpenseFromBankTransaction;
use Flexgrid\Utils\_Time;

final class BankExpenseTestTransactions implements TransactionManagerInterface
{
    public function transactional(callable $operation) { return $operation(); }
}

final class BankExpenseTestIds implements PublicIdGeneratorInterface
{
    private $next = 0;
    public function generate(): string { return 'created-expense-' . ++$this->next; }
}

final class BankExpenseTestBankRepository implements BankTransactionRepositoryInterface
{
    public $bank;
    public function insert(BankTransaction $transaction, int $createdAt): void { $this->bank = $transaction; }
    public function findByExternalId(TenantId $tenant, string $account, string $externalId): ?BankTransaction { return null; }
    public function findByPublicId(TenantId $tenant, string $publicId): ?BankTransaction { return $this->bank !== null && $this->bank->getTenantId()->equals($tenant) && $this->bank->getPublicId() === $publicId ? $this->bank : null; }
    public function findByPublicIdForUpdate(TenantId $tenant, string $publicId): ?BankTransaction { return $this->findByPublicId($tenant, $publicId); }
    public function updateMatchState(BankTransaction $transaction, int $updatedAt): void { $this->bank = $transaction; }
}

final class BankExpenseTestExpenseRepository implements ExpenseRepositoryInterface
{
    public $items = [];
    public $inserts = 0;
    public function insert(Expense $expense, int $createdAt): void { $this->items[$expense->getPublicId()] = $expense; ++$this->inserts; }
    public function findByPublicId(TenantId $tenant, string $publicId): ?Expense { $item = $this->items[$publicId] ?? null; return $item !== null && $item->getTenantId()->equals($tenant) ? $item : null; }
    public function findByPublicIdForUpdate(TenantId $tenant, string $publicId): ?Expense { return $this->findByPublicId($tenant, $publicId); }
    public function findByExternalReference(TenantId $tenant, string $source, string $externalId): ?Expense { foreach ($this->items as $item) { if ($item->getTenantId()->equals($tenant) && $item->getSource() === $source && $item->getExternalId() === $externalId) { return $item; } } return null; }
    public function update(Expense $expense, int $updatedAt): void { $this->items[$expense->getPublicId()] = $expense; }
    public function insertAttachment(TenantId $tenant, string $expensePublicId, \Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment $attachment, int $createdAt): void {}
    public function removeAttachment(TenantId $tenant, string $expensePublicId, string $attachmentPublicId): bool { return false; }
}

final class BankExpenseTestCategories implements ExpenseCategoryRepositoryInterface
{
    public $category;
    public function insert(ExpenseCategory $category, int $createdAt): void { $this->category = $category; }
    public function updateAccounting(ExpenseCategory $category, int $updatedAt): void { $this->category = $category; }
    public function findByPublicId(TenantId $tenant, string $publicId): ?ExpenseCategory { return $this->category !== null && $this->category->getTenantId()->equals($tenant) && $this->category->getPublicId() === $publicId ? $this->category : null; }
    public function findByCode(TenantId $tenant, string $code): ?ExpenseCategory { return null; }
    public function findAll(TenantId $tenant, bool $includeInactive = false): array { return [$this->category]; }
}

final class BankExpenseTestTargetProvider implements BankMatchTargetProviderInterface
{
    private $expenses;
    public function __construct(BankExpenseTestExpenseRepository $expenses) { $this->expenses = $expenses; }
    public function supports(string $targetType): bool { return $targetType === 'expense'; }
    public function search(TenantId $tenant, BankMatchContext $context, string $query, int $limit): array { return []; }
    public function find(TenantId $tenant, BankMatchContext $context, string $targetPublicId): ?BankMatchTarget
    {
        $expense = $this->expenses->findByPublicId($tenant, $targetPublicId);
        if ($expense === null) { return null; }
        $gross = $expense->getGross()->getMinorUnits();
        return new BankMatchTarget('expense', $targetPublicId, $expense->getTitle(), '', '', $expense->getExpenseDate()->getValue(), $gross, $gross, $expense->getCurrency()->getCode(), $gross === abs($context->getAmountMinor()));
    }
}

final class BankExpenseTestProcessor implements BankMatchProcessorInterface
{
    public function supports(string $targetType): bool { return $targetType === 'expense'; }
    public function process(TenantId $tenant, BankTransaction $transaction, string $targetPublicId): void {}
}

$tenantId = new TenantId('bank-expense-tenant');
$tenant = new TenantContext($tenantId);
$transactions = new BankExpenseTestTransactions();
$banks = new BankExpenseTestBankRepository();
$expenses = new BankExpenseTestExpenseRepository();
$categories = new BankExpenseTestCategories();
$categories->category = new ExpenseCategory('category-1', $tenantId, 'software', 'Software');
$clock = new _Time(1770000000);
$createExpense = new CreateExpense($tenant, new BankExpenseTestIds(), $transactions, $expenses, $categories, $clock);
$match = new AcceptManualBankMatch($tenant, $transactions, $banks, [new BankExpenseTestTargetProvider($expenses)], [new BankExpenseTestProcessor()], $clock);
$create = new CreateExpenseFromBankTransaction($tenant, $transactions, $banks, $expenses, $createExpense, $match);
$banks->bank = new BankTransaction('bank-expense-1', $tenantId, 'account-1', 'import-1', 'external-1', new BankDate('2026-09-20'), new Money(-12100, Currency::euro()), new CounterpartySnapshot('Hosting BV'), 'Hosting', 'INV-1', new BankSourceSnapshot('abn_tab', str_repeat('d', 64), []));
$expense = $create->execute('bank-expense-1', 'category-1', 'Hosting', '21');
adminExpenseAssert($expense->getNet()->getMinorUnits() === 10000 && $expense->getTax()->getMinorUnits() === 2100, 'Btw moet uit het vaste bankbrutobedrag worden teruggerekend.');
adminExpenseAssert($expense->getSupplierSnapshot()->getName() === '', 'Leverancier moet bij bankuitgaven optioneel zijn.');
adminExpenseAssert($expense->getSource() === 'admin_banking' && $expense->getExternalId() === 'bank-expense-1', 'Uitgave moet aan de unieke bankbron herkenbaar blijven.');
adminExpenseAssert($banks->bank->getStatus()->getValue() === 'matched' && $banks->bank->getMatchedTargetPublicId() === $expense->getPublicId(), 'Dezelfde actie moet de bankregel afletteren.');
adminExpenseAssertThrows(DomainException::class, function () use ($create) { $create->execute('bank-expense-1', 'category-1', 'Hosting', '21'); }, 'Een tweede submit mag geen extra uitgave maken.');
adminExpenseAssert($expenses->inserts === 1, 'Dubbele submit mag geen dubbele uitgave opslaan.');

$banks->bank = new BankTransaction('bank-expense-2', $tenantId, 'account-1', 'import-1', 'external-2', new BankDate('2026-09-21'), new Money(-10900, Currency::euro()), new CounterpartySnapshot('Hosting BV'), 'Andere hosting', 'INV-2', new BankSourceSnapshot('abn_tab', str_repeat('e', 64), []));
$second = $create->execute('bank-expense-2', 'category-1', 'Andere hosting', '9', 'Hosting BV');
adminExpenseAssert($second->getNet()->getMinorUnits() === 10000 && $second->getTax()->getMinorUnits() === 900, 'Aangepast btw-percentage moet exact uit het bankbedrag worden berekend.');
adminExpenseAssertThrows(InvalidArgumentException::class, function () use ($create) { $create->execute('bank-expense-2', 'category-1', 'Andere hosting', '101'); }, 'Btw-percentage boven 100 moet worden geweigerd.');

$banks->bank = new BankTransaction('bank-expense-3', $tenantId, 'account-1', 'import-1', 'external-3', new BankDate('2026-09-22'), new Money(-10000, Currency::euro()), new CounterpartySnapshot('Hosting BV'), 'Derde hosting', 'INV-3', new BankSourceSnapshot('abn_tab', str_repeat('f', 64), []));
$third = $create->execute('bank-expense-3', 'category-1', 'Derde hosting', '0', '', $second->getPublicId());
adminExpenseAssert($third->getSupplierSnapshot()->getName() === 'Hosting BV' && $third->getTax()->getMinorUnits() === 0, 'Eerdere leverancier moet optioneel hergebruikt kunnen worden.');
adminExpenseAssert((new BankExpenseCreatorLoader(dirname(__DIR__, 2)))->getCreator() instanceof \Flexgrid\Modules\AdminExpense\Integration\Banking\ExpenseCreator, 'Banking moet de Expense-integratie zonder directe module-import ontdekken.');

echo "AdminExpense bank creation tests passed.\n";
