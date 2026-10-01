<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/AdminBanking/tests/bootstrap.php';
require_once dirname(__DIR__, 3) . '/Flexgrid/src/Database/Connection.php';
foreach (['Contract/ReportProviderInterface.php', 'Contract/ReportPeriodListProviderInterface.php', 'ValueObject/ReportPeriod.php'] as $file) {
    require_once dirname(__DIR__, 2) . '/AdminReport/src/' . $file;
}
require_once dirname(__DIR__, 2) . '/AdminBanking/src/Contract/BankExpenseCreatorInterface.php';
foreach (['CreateExpenseFromBankTransaction.php', 'ExistingSuppliers.php', 'MatchTargetProvider.php', 'MatchProcessor.php', 'ExpenseCreator.php'] as $file) {
    require_once dirname(__DIR__) . '/src/Integration/Banking/' . $file;
}
require_once dirname(__DIR__) . '/src/Integration/Report/ReportProvider.php';

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminBanking\Domain\Model\BankAccount;
use Flexgrid\Modules\AdminBanking\Domain\Model\BankImport;
use Flexgrid\Modules\AdminBanking\Domain\Model\BankTransaction;
use Flexgrid\Modules\AdminBanking\Domain\ValueObject\BankDate;
use Flexgrid\Modules\AdminBanking\Domain\ValueObject\BankSourceSnapshot;
use Flexgrid\Modules\AdminBanking\Domain\ValueObject\CounterpartySnapshot;
use Flexgrid\Modules\AdminBanking\Infrastructure\Persistence\PdoBankAccountRepository;
use Flexgrid\Modules\AdminBanking\Infrastructure\Persistence\PdoBankImportRepository;
use Flexgrid\Modules\AdminBanking\Infrastructure\Persistence\PdoBankTransactionRepository;
use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseCategoryRepository;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseRepository;
use Flexgrid\Modules\AdminExpense\Integration\Banking\ExpenseCreator;
use Flexgrid\Modules\AdminExpense\Integration\Report\ReportProvider;
use Flexgrid\Modules\AdminReport\ValueObject\ReportPeriod;

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/.env.php';
$database = $db['name'];
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $database, $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
unset($db);
Connection::$connections[$database] = $pdo;
Connection::$default = $database;

$tenant = new TenantId('admin-expense-bank-create-pdo-test');
$accountId = 'create-expense-account';
$importId = 'create-expense-import';
$bankId = 'create-expense-bank';
$categoryId = 'create-expense-category';
$currency = Currency::euro();
$pdo->beginTransaction();
try {
    (new PdoBankAccountRepository($pdo))->insert(new BankAccount($accountId, $tenant, 'Testrekening', 'EXPENSE001', $currency), 1770000000);
    (new PdoBankImportRepository($pdo))->insert(new BankImport($importId, $tenant, $accountId, 'abn_tab', 'test.txt', str_repeat('b', 64), 1, 1, 0, 1770000000));
    (new PdoBankTransactionRepository($pdo))->insert(new BankTransaction($bankId, $tenant, $accountId, $importId, 'create-expense-external', new BankDate('2026-09-20'), new Money(-12100, $currency), new CounterpartySnapshot('Hoster BV'), 'Hosting september', 'INV-1', new BankSourceSnapshot('abn_tab', str_repeat('c', 64), [])), 1770000000);
    (new PdoExpenseCategoryRepository($pdo))->insert(new ExpenseCategory($categoryId, $tenant, 'software', 'Software'), 1770000000);

    $creator = new ExpenseCreator();
    $result = $creator->create($tenant, $bankId, ['category_public_id' => $categoryId, 'title' => 'Hosting september', 'vat_rate' => '21'], null);
    $expense = (new PdoExpenseRepository($pdo))->findByPublicId($tenant, $result['expense_public_id']);
    $bank = (new PdoBankTransactionRepository($pdo))->findByPublicId($tenant, $bankId);
    adminExpenseAssert($expense !== null && $expense->getGross()->getMinorUnits() === 12100 && $expense->getNet()->getMinorUnits() === 10000 && $expense->getTax()->getMinorUnits() === 2100, 'PDO-aanmaak moet het vaste bankbedrag met 21% uitsplitsen.');
    adminExpenseAssert($expense->getSupplierSnapshot()->getName() === '' && $bank->getStatus()->getValue() === 'matched' && $bank->getMatchedTargetPublicId() === $expense->getPublicId(), 'Uitgave zonder leverancier en bankaflettering moeten samen zijn opgeslagen.');
    $report = (new ReportProvider($tenant, $pdo))->getReportContribution(new ReportPeriod(2026, 3));
    adminExpenseAssert($report['currencies']['EUR']['costs_net_minor'] === 10000 && $report['currencies']['EUR']['input_tax_minor'] === 2100, 'Nieuwe bankuitgave moet in de rapportage als kosten en voorbelasting meetellen.');
    adminExpenseAssertThrows(DomainException::class, function () use ($creator, $tenant, $bankId, $categoryId) { $creator->create($tenant, $bankId, ['category_public_id' => $categoryId, 'title' => 'Hosting september', 'vat_rate' => '21'], null); }, 'Herhaalde PDO-aanmaak moet worden geweigerd.');
    $count = $pdo->prepare("SELECT COUNT(*) FROM `admin_expense` WHERE `tenant_id`=:tenant AND `source`='admin_banking' AND `external_id`=:bank");
    $count->execute([':tenant' => $tenant->toString(), ':bank' => $bankId]);
    adminExpenseAssert((int)$count->fetchColumn() === 1, 'Dezelfde bankregel mag slechts één uitgave opleveren.');
    $pdo->rollBack();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    throw $error;
}

$remaining = $pdo->prepare('SELECT COUNT(*) FROM `admin_expense` WHERE `tenant_id`=:tenant');
$remaining->execute([':tenant' => $tenant->toString()]);
adminExpenseAssert((int)$remaining->fetchColumn() === 0, 'PDO-test mag geen uitgaven achterlaten.');
$remainingBank = $pdo->prepare('SELECT COUNT(*) FROM `admin_bank_transaction` WHERE `tenant_id`=:tenant');
$remainingBank->execute([':tenant' => $tenant->toString()]);
adminExpenseAssert((int)$remainingBank->fetchColumn() === 0, 'PDO-test mag geen bankregels achterlaten.');
echo "AdminExpense bank creation PDO tests passed.\n";
