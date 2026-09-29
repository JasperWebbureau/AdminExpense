<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\ExpenseDate;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\SupplierSnapshot;

$currency = Currency::euro();
$supplier = new SupplierSnapshot('Leverancier BV', 'Mevrouw Jansen', 'info@example.nl', '12345678', 'NL123456789B01', 'NL', 'supplier-1');
$expense = new Expense('expense-1', new TenantId('tenant-expense'), 'category-office', $currency, $supplier, new ExpenseDate('2026-09-19'), 'Kantoorartikelen', Money::fromDecimal('100.00', $currency), Money::fromDecimal('21.00', $currency), 'Papier en toner', 'BON-42', 'import', 'row-42');
$attachment = new ExpenseAttachment('attachment-1', 'expense/2026/receipt-42', 'bon.pdf', 'application/pdf', 2048, str_repeat('a', 64));
$expense->addAttachment($attachment);

adminExpenseAssert($expense->getGross()->getMinorUnits() === 12100, 'Bruto uitgave moet exact netto plus btw zijn.');
adminExpenseAssert($expense->getSupplierSnapshot()->getSourcePublicId() === 'supplier-1', 'Leverancierssnapshot moet publieke bron-id bewaren.');
adminExpenseAssert(SupplierSnapshot::fromArray($supplier->toArray())->getName() === 'Leverancier BV', 'Leverancierssnapshot moet versioned kunnen roundtrippen.');
adminExpenseAssert(count($expense->getAttachments()) === 1, 'Uitgave moet bijlagemetadata bevatten.');
adminExpenseAssert((new ExpenseCategory('category-1', new TenantId('tenant-expense'), 'kantoor', 'Kantoor'))->isActive(), 'Nieuwe categorie moet standaard actief zijn.');
$privateCategory=new ExpenseCategory('category-private',new TenantId('tenant-expense'),'prive','Privéopname','',true,ExpenseCategory::PRIVATE_WITHDRAWAL,0);
adminExpenseAssert(!$privateCategory->affectsResult()&&$privateCategory->getVatDeductiblePercentage()===0,'Privéopname mag resultaat of btw-aftrek niet beïnvloeden.');
$payrollCategory=new ExpenseCategory('category-payroll',new TenantId('tenant-expense'),'loon','Loonkosten','',true,ExpenseCategory::PAYROLL_COST,0);
adminExpenseAssert($payrollCategory->affectsResult(),'Echte loonkosten moeten het resultaat beïnvloeden.');
adminExpenseAssertThrows(InvalidArgumentException::class,function(){new ExpenseCategory('category-invalid',new TenantId('tenant-expense'),'prive','Privé','',true,ExpenseCategory::PRIVATE_WITHDRAWAL,100);},'Privéopname met aftrekbare btw moet worden geweigerd.');

adminExpenseAssertThrows(InvalidArgumentException::class, function () { new ExpenseDate('2026-02-30'); }, 'Ongeldige kalenderdatum moet worden geweigerd.');
adminExpenseAssertThrows(InvalidArgumentException::class, function () { new SupplierSnapshot('', '', '', '', '', 'NL'); }, 'Leveranciersnaam moet verplicht zijn.');
adminExpenseAssertThrows(DomainException::class, function () use ($expense, $attachment) { $expense->addAttachment($attachment); }, 'Dubbele bijlage moet worden geweigerd.');
adminExpenseAssertThrows(InvalidArgumentException::class, function () { new ExpenseAttachment('attachment-2', 'ref', 'bon.exe', 'ongeldig', 10, str_repeat('b', 64)); }, 'Ongeldig MIME-type moet worden geweigerd.');

echo "AdminExpense domain tests passed.\n";
