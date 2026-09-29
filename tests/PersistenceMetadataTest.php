<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$projectRoot = dirname(__DIR__, 4); $framework = $projectRoot . '/flexgrid/flexgrid/src';
if (!class_exists('Repository\\RepositoryEntity')) { eval('namespace Repository; class RepositoryEntity {}'); }
foreach (['Utils/_Enum.php', 'Utils/_String.php', 'Database/Column/DefaultColumnLenth.php', 'Database/Column/ColumnType.php', 'Database/Column/Column.php', 'Autowire/Definition/PropertyDefinition.php', 'Autowire/Definition/EntityDefinition.php', 'Autowire/Scanner/EntityScanner.php', 'Autowire/Schema/SchemaIndexDefinition.php'] as $file) { require_once $framework . '/' . $file; }
foreach (['ExpenseRecord.php', 'ExpenseCategoryRecord.php', 'ExpenseAttachmentRecord.php'] as $file) { require_once dirname(__DIR__) . '/src/Entity/' . $file; }

use Flexgrid\Autowire\Scanner\EntityScanner;
use Flexgrid\Autowire\Schema\SchemaIndexDefinition;
use Flexgrid\Modules\AdminExpense\Entity\ExpenseAttachmentRecord;
use Flexgrid\Modules\AdminExpense\Entity\ExpenseCategoryRecord;
use Flexgrid\Modules\AdminExpense\Entity\ExpenseRecord;

$scanner = new EntityScanner();
$expense = $scanner->parseEntity(ExpenseRecord::class, dirname(__DIR__) . '/src/Entity/ExpenseRecord.php');
$category = $scanner->parseEntity(ExpenseCategoryRecord::class, dirname(__DIR__) . '/src/Entity/ExpenseCategoryRecord.php');
$attachment = $scanner->parseEntity(ExpenseAttachmentRecord::class, dirname(__DIR__) . '/src/Entity/ExpenseAttachmentRecord.php');
adminExpenseAssert($expense->getTableName() === 'admin_expense', 'Expense moet eigen tabel definiëren.');
adminExpenseAssert($category->getTableName() === 'admin_expense_category', 'Categorie moet eigen tabel definiëren.');
foreach(['reportingType','vatDeductiblePercentage']as$name){adminExpenseAssert(isset($category->getProperties()[$name]),'ExpenseCategoryRecord mist '.$name);}
adminExpenseAssert($attachment->getTableName() === 'admin_expense_attachment', 'Bijlage moet eigen tabel definiëren.');
foreach (['tenantId', 'publicId', 'categoryPublicId', 'supplierSnapshot', 'netAmountMinor', 'taxAmountMinor', 'grossAmountMinor', 'source', 'externalId'] as $name) { adminExpenseAssert(isset($expense->getProperties()[$name]), 'ExpenseRecord mist ' . $name); }
adminExpenseAssert($expense->getProperties()['grossAmountMinor']->getDatabaseType() === 'BIGINT', 'Uitgaventotalen moeten BIGINT zijn.');
adminExpenseAssert($attachment->getProperties()['sizeBytes']->getDatabaseType() === 'BIGINT', 'Bijlagegrootte moet BIGINT zijn.');
$indexes = $expense->getClassAnnotations()['Index'];
$external = SchemaIndexDefinition::fromEntityAnnotation($expense, 'tenant_source_external', $indexes['tenant_source_external']);
adminExpenseAssert(strpos($external->buildCreateSql('admin_expense'), 'UNIQUE INDEX') !== false, 'Externe uitgave-id moet per tenant en bron uniek zijn.');
$attachmentIndexes = $attachment->getClassAnnotations()['Index'];
$sha = SchemaIndexDefinition::fromEntityAnnotation($attachment, 'tenant_expense_sha', $attachmentIndexes['tenant_expense_sha']);
adminExpenseAssert(strpos($sha->buildCreateSql('admin_expense_attachment'), 'UNIQUE INDEX') !== false, 'Bijlage-inhoud moet per uitgave uniek zijn.');

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/src'));
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') { continue; }
    $content = (string)file_get_contents($file->getPathname());
    $bankingIntegration = strpos(str_replace('\\', '/', $file->getPathname()), '/src/Integration/Banking/') !== false;
    foreach (['Flexgrid\\Modules\\AdminInvoice\\', 'Flexgrid\\Modules\\AdminQuote\\', 'Flexgrid\\Modules\\AdminCustomer\\', 'Flexgrid\\Modules\\AdminPayment\\', 'Flexgrid\\Modules\\AdminBanking\\', 'App\\Administration\\'] as $forbidden) { if ($bankingIntegration && $forbidden === 'Flexgrid\\Modules\\AdminBanking\\') { continue; } adminExpenseAssert(strpos($content, $forbidden) === false, 'AdminExpense bevat verboden import buiten expliciete integratie: ' . $forbidden); }
}

echo "AdminExpense persistence metadata tests passed.\n";
