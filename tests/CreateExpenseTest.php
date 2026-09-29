<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\Command\CreateExpenseCategoryCommand;
use Flexgrid\Modules\AdminExpense\Application\Command\CreateExpenseCommand;
use Flexgrid\Modules\AdminExpense\Application\UseCase\CreateExpense;
use Flexgrid\Modules\AdminExpense\Application\UseCase\CreateExpenseCategory;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Utils\_Time;

final class ExpenseTestIds implements PublicIdGeneratorInterface { private $index = 0; public function generate(): string { return 'expense-test-' . (++$this->index); } }
final class ExpenseTestTransactions implements TransactionManagerInterface { public $calls = 0; public function transactional(callable $operation) { ++$this->calls; return $operation(); } }
final class ExpenseTestCategoryRepository implements ExpenseCategoryRepositoryInterface
{
    public $byCode, $byPublic, $inserted;
    public function insert(ExpenseCategory $category, int $createdAt): void { $this->inserted = $category; $this->byCode = $category; $this->byPublic = $category; }
    public function updateAccounting(ExpenseCategory $category, int $updatedAt): void { $this->byCode=$category;$this->byPublic=$category; }
    public function findByPublicId(TenantId $tenantId, string $publicId): ?ExpenseCategory { return $this->byPublic !== null && $this->byPublic->getTenantId()->equals($tenantId) && $this->byPublic->getPublicId() === $publicId ? $this->byPublic : null; }
    public function findByCode(TenantId $tenantId, string $code): ?ExpenseCategory { return $this->byCode; }
    public function findAll(TenantId $tenantId, bool $includeInactive = false): array { return $this->byPublic === null ? [] : [$this->byPublic]; }
}
final class ExpenseTestRepository implements ExpenseRepositoryInterface
{
    public $inserted, $existing;
    public function insert(Expense $expense, int $createdAt): void { $this->inserted = $expense; }
    public function findByPublicId(TenantId $tenantId, string $publicId): ?Expense { return $this->existing; }
    public function findByPublicIdForUpdate(TenantId $tenantId, string $publicId): ?Expense { return $this->existing; }
    public function findByExternalReference(TenantId $tenantId, string $source, string $externalId): ?Expense { return $this->existing; }
    public function update(Expense $expense, int $updatedAt): void { $this->inserted = $expense; $this->existing = $expense; }
    public function insertAttachment(TenantId $tenantId, string $expensePublicId, \Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment $attachment, int $createdAt): void { $this->existing->addAttachment($attachment); }
    public function removeAttachment(TenantId $tenantId, string $expensePublicId, string $attachmentPublicId): bool { return false; }
}

$tenantId = new TenantId('expense-create-tenant'); $tenant = new TenantContext($tenantId); $ids = new ExpenseTestIds(); $transactions = new ExpenseTestTransactions(); $categories = new ExpenseTestCategoryRepository(); $expenses = new ExpenseTestRepository(); $clock = new _Time(1770000000);
$createCategory = new CreateExpenseCategory($tenant, $ids, $transactions, $categories, $clock);
$category = $createCategory->execute(new CreateExpenseCategoryCommand('software', 'Software', 'Online diensten'));
adminExpenseAssert($categories->inserted === $category, 'Categorie moet worden opgeslagen.');
adminExpenseAssert($createCategory->execute(new CreateExpenseCategoryCommand('software', 'Andere naam')) === $category, 'Categorie-aanmaak moet op tenant en code idempotent zijn.');

$command = new CreateExpenseCommand('EUR', '2026-09-19', $category->getPublicId(), ['name' => 'Hosting BV', 'email' => 'facturen@example.nl', 'country_code' => 'NL'], 'Hosting september', '100.00', '21.00', '', 'INV-42', [['file_reference' => 'expense/receipt-42', 'original_name' => 'factuur.pdf', 'media_type' => 'application/pdf', 'size_bytes' => 1024, 'sha256' => str_repeat('c', 64)]], 'csv_import', 'row-42');
$createExpense = new CreateExpense($tenant, $ids, $transactions, $expenses, $categories, $clock);
$expense = $createExpense->execute($command);
adminExpenseAssert($expenses->inserted === $expense, 'Uitgave moet worden opgeslagen.');
adminExpenseAssert($expense->getGross()->getMinorUnits() === 12100, 'Create-use-case moet bedragen exact omzetten.');
adminExpenseAssert($expense->getTenantId()->equals($tenantId), 'Create-use-case moet actieve tenant gebruiken.');
adminExpenseAssert(count($expense->getAttachments()) === 1, 'Create-use-case moet bijlagemetadata toevoegen.');
$expenses->existing = $expense;
adminExpenseAssert($createExpense->execute($command) === $expense, 'Externe retry moet bestaande uitgave teruggeven.');

$inactive = new ExpenseCategory('category-inactive', $tenantId, 'inactief', 'Inactief', '', false); $categories->byPublic = $inactive; $expenses->existing = null;
adminExpenseAssertThrows(DomainException::class, function () use ($createExpense, $command) { $createExpense->execute($command); }, 'Inactieve categorie moet worden geweigerd.');
adminExpenseAssertThrows(TypeError::class, function () use ($category) { new CreateExpenseCommand('EUR', '2026-09-19', $category->getPublicId(), ['name' => 'Leverancier'], 'Titel', 10.50, '2.21'); }, 'Floatbedragen mogen niet door de commandolaag lekken.');

echo "AdminExpense create tests passed.\n";
