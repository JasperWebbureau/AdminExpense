<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\Command\UpdateExpenseCommand;
use Flexgrid\Modules\AdminExpense\Application\UseCase\UpdateExpense;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\ExpenseDate;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\SupplierSnapshot;
use Flexgrid\Utils\_Time;

final class ExpenseUpdateTx implements TransactionManagerInterface{public $calls=0;public function transactional(callable$operation){++$this->calls;return$operation();}}
final class ExpenseUpdateCategories implements ExpenseCategoryRepositoryInterface{public $category;public function insert(ExpenseCategory$c,int$t):void{}public function updateAccounting(ExpenseCategory$c,int$t):void{$this->category=$c;}public function findByPublicId(TenantId$t,string$id):?ExpenseCategory{return$this->category!==null&&$this->category->getTenantId()->equals($t)&&$this->category->getPublicId()===$id?$this->category:null;}public function findByCode(TenantId$t,string$code):?ExpenseCategory{return null;}public function findAll(TenantId$t,bool$i=false):array{return$this->category===null?[]:[$this->category];}}
final class ExpenseUpdateRepository implements ExpenseRepositoryInterface{public $expense,$updated;public function insert(Expense$e,int$t):void{}public function findByPublicId(TenantId$t,string$id):?Expense{return$this->expense;}public function findByPublicIdForUpdate(TenantId$t,string$id):?Expense{return$this->expense!==null&&$this->expense->getTenantId()->equals($t)&&$this->expense->getPublicId()===$id?$this->expense:null;}public function findByExternalReference(TenantId$t,string$s,string$e):?Expense{return null;}public function update(Expense$e,int$t):void{$this->updated=$e;$this->expense=$e;}public function insertAttachment(TenantId$t,string$e,\Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment$a,int$c):void{}public function removeAttachment(TenantId$t,string$e,string$a):bool{return false;}}

$tenantId=new TenantId('expense-update-tenant');$currency=Currency::euro();$current=new Expense('expense-update-1',$tenantId,'category-old',$currency,new SupplierSnapshot('Oude leverancier'),new ExpenseDate('2026-09-01'),'Oud',Money::fromDecimal('10',$currency),Money::fromDecimal('2.10',$currency),'','','import','row-1');
$repository=new ExpenseUpdateRepository();$repository->expense=$current;$categories=new ExpenseUpdateCategories();$categories->category=new ExpenseCategory('category-new',$tenantId,'nieuw','Nieuwe categorie');$tx=new ExpenseUpdateTx();$useCase=new UpdateExpense(new TenantContext($tenantId),$tx,$repository,$categories,new _Time(1770000000));
$updated=$useCase->execute(new UpdateExpenseCommand('expense-update-1','2026-09-19','category-new',['name'=>'Nieuwe leverancier','country_code'=>'BE'],'Nieuw','20','4.20','Aangepast','REF-2'));
adminExpenseAssert($tx->calls===1&&$repository->updated===$updated,'Uitgave-update moet transactioneel worden opgeslagen.');adminExpenseAssert($updated->getGross()->getMinorUnits()===2420,'Uitgave-update moet bruto exact herberekenen.');adminExpenseAssert($updated->getSource()==='import'&&$updated->getExternalId()==='row-1','Uitgave-update moet importidentiteit behouden.');adminExpenseAssert($updated->getSupplierSnapshot()->getName()==='Nieuwe leverancier','Uitgave-update moet leverancierssnapshot vervangen.');
$categories->category=new ExpenseCategory('category-inactive',$tenantId,'inactief','Inactief','',false);$repository->expense=$updated;adminExpenseAssertThrows(DomainException::class,function()use($useCase){$useCase->execute(new UpdateExpenseCommand('expense-update-1','2026-09-19','category-inactive',['name'=>'Leverancier'],'Titel','20','4.20'));},'Wisselen naar een inactieve categorie moet worden geweigerd.');
echo "AdminExpense update tests passed.\n";
