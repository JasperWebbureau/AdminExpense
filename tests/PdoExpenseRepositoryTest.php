<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\ExpenseDate;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\SupplierSnapshot;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseCategoryRepository;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseListRepository;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseRepository;
use Flexgrid\Modules\AdminExpense\Application\Query\ExpenseListQuery;

$projectRoot = dirname(__DIR__, 4); require $projectRoot . '/.env.php';
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['name'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]); unset($db);
$tenant = new TenantId('admin-expense-pdo-test'); $currency = Currency::euro();
$category = new ExpenseCategory('expense-category-pdo-1', $tenant, 'software', 'Software');
$expense = new Expense('expense-pdo-1', $tenant, $category->getPublicId(), $currency, new SupplierSnapshot('Hosting BV', '', 'info@example.nl'), new ExpenseDate('2026-09-19'), 'Hosting', Money::fromDecimal('100', $currency), Money::fromDecimal('21', $currency), '', 'INV-42', 'import', 'expense-row-42');
$expense->addAttachment(new ExpenseAttachment('expense-attachment-pdo-1', 'expense/receipt-42', 'factuur.pdf', 'application/pdf', 4096, str_repeat('d', 64)));
$categories = new PdoExpenseCategoryRepository($pdo); $expenses = new PdoExpenseRepository($pdo);
$pdo->beginTransaction();
try {
    $categories->insert($category, 1770000000); $expenses->insert($expense, 1770000000);
    $loaded = $expenses->findByExternalReference($tenant, 'import', 'expense-row-42');
    adminExpenseAssert($loaded !== null && $loaded->getPublicId() === 'expense-pdo-1', 'PDO-repository moet uitgave tenantgebonden hydrateren.');
    adminExpenseAssert($loaded->getGross()->getMinorUnits() === 12100 && count($loaded->getAttachments()) === 1, 'PDO-repository moet exacte bedragen en bijlagen hydrateren.');
    adminExpenseAssert($categories->findByCode($tenant, 'software')->getPublicId() === $category->getPublicId(), 'PDO-repository moet categorie op code vinden.');
    adminExpenseAssert(count($categories->findAll($tenant)) === 1, 'PDO-repository moet tenantcategorieën kunnen tonen.');
    $locked = $expenses->findByPublicIdForUpdate($tenant, 'expense-pdo-1');
    adminExpenseAssert($locked !== null, 'PDO-repository moet uitgave voor update vergrendelen.');
    $changed = new Expense('expense-pdo-1', $tenant, $category->getPublicId(), $currency, new SupplierSnapshot('Gewijzigde Hosting BV'), new ExpenseDate('2026-09-20'), 'Gewijzigde hosting', Money::fromDecimal('200', $currency), Money::fromDecimal('42', $currency), 'Gewijzigd', 'INV-43', 'import', 'expense-row-42');
    foreach ($locked->getAttachments() as $attachment) { $changed->addAttachment($attachment); }
    $expenses->update($changed, 1770000001);
    $reloaded = $expenses->findByPublicId($tenant, 'expense-pdo-1');
    adminExpenseAssert($reloaded !== null && $reloaded->getGross()->getMinorUnits() === 24200 && count($reloaded->getAttachments()) === 1, 'PDO-repository moet kop bijwerken en bijlagen behouden.');
    $lists = new PdoExpenseListRepository($pdo); $list = $lists->search($tenant, new ExpenseListQuery('Gewijzigde', $category->getPublicId(), 2026));
    adminExpenseAssert($list->getTotal() === 1 && $list->getItems()[0]->getSupplierName() === 'Gewijzigde Hosting BV', 'PDO-lijst moet tenantgebonden zoeken en leverancierssnapshot lezen.');
    adminExpenseAssert($lists->getSummaries($tenant, new ExpenseListQuery())[0]->getGrossMinor() === 24200, 'PDO-samenvatting moet exacte totalen berekenen.');
    adminExpenseAssert($lists->getYears($tenant) === [2026], 'PDO-lijst moet beschikbare jaren tonen.');
    adminExpenseAssert($expenses->findByPublicId(new TenantId('other-expense-tenant'), 'expense-pdo-1') === null, 'PDO-repository mag andere tenant niet lezen.');
    adminExpenseAssertThrows(RuntimeException::class, function () use ($expenses) { $expenses->insertAttachment(new TenantId('other-expense-tenant'), 'expense-pdo-1', new ExpenseAttachment('expense-attachment-wrong-tenant', 'secure-file:98', 'verkeerd.pdf', 'application/pdf', 64, str_repeat('f', 64)), 1770000002); }, 'Bijlagemetadata mag niet zonder tenantgebonden uitgave worden toegevoegd.');
    $expenses->insertAttachment($tenant, 'expense-pdo-1', new ExpenseAttachment('expense-attachment-pdo-2', 'secure-file:99', 'tweede.pdf', 'application/pdf', 128, str_repeat('e', 64)), 1770000002);
    adminExpenseAssert(count($expenses->findByPublicId($tenant, 'expense-pdo-1')->getAttachments()) === 2, 'PDO-repository moet losse bijlagemetadata tenantgebonden toevoegen.');
    adminExpenseAssert($expenses->removeAttachment($tenant, 'expense-pdo-1', 'expense-attachment-pdo-2') === true, 'PDO-repository moet toegevoegde bijlagemetadata verwijderen.');
    adminExpenseAssert($expenses->removeAttachment(new TenantId('other-expense-tenant'), 'expense-pdo-1', 'expense-attachment-pdo-1') === false, 'Bijlage verwijderen moet tenantgebonden zijn.');
    adminExpenseAssert($expenses->removeAttachment($tenant, 'expense-pdo-1', 'expense-attachment-pdo-1') === true, 'PDO-repository moet gekoppelde bijlage kunnen verwijderen.');
    adminExpenseAssert(count($expenses->findByPublicId($tenant, 'expense-pdo-1')->getAttachments()) === 0, 'Verwijderde bijlage mag niet meer hydrateren.');
    $pdo->rollBack();
} catch (Throwable $throwable) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $throwable; }
$check = $pdo->prepare('SELECT COUNT(*) FROM `admin_expense` WHERE `tenant_id`=:tenant'); $check->execute([':tenant' => $tenant->toString()]);
adminExpenseAssert((int)$check->fetchColumn() === 0, 'PDO-test mag geen fixtures achterlaten.');
echo "AdminExpense PDO repository tests passed.\n";
