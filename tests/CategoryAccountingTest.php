<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\Command\UpdateExpenseCategoryAccountingCommand;
use Flexgrid\Modules\AdminExpense\Application\UseCase\UpdateExpenseCategoryAccounting;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Utils\_Time;

final class CategoryAccountingTransactions implements TransactionManagerInterface{public$called=0;public function transactional(callable$operation){$this->called++;return$operation();}}
final class CategoryAccountingRepository implements ExpenseCategoryRepositoryInterface
{
    public$category,$updatedAt;
    public function insert(ExpenseCategory$category,int$createdAt):void{}
    public function updateAccounting(ExpenseCategory$category,int$updatedAt):void{$this->category=$category;$this->updatedAt=$updatedAt;}
    public function findByPublicId(TenantId$tenantId,string$publicId):?ExpenseCategory{return$this->category!==null&&$this->category->getTenantId()->equals($tenantId)&&$this->category->getPublicId()===$publicId?$this->category:null;}
    public function findByCode(TenantId$tenantId,string$code):?ExpenseCategory{return null;}
    public function findAll(TenantId$tenantId,bool$includeInactive=false):array{return$this->category===null?[]:[$this->category];}
}

$tenantId=new TenantId('expense-accounting-tenant');$repository=new CategoryAccountingRepository();$repository->category=new ExpenseCategory('salary-category',$tenantId,'salaris','Salaris');$transactions=new CategoryAccountingTransactions();$useCase=new UpdateExpenseCategoryAccounting(new TenantContext($tenantId),$transactions,$repository,new _Time(1770000000));
$updated=$useCase->execute(new UpdateExpenseCategoryAccountingCommand('salary-category',ExpenseCategory::PRIVATE_WITHDRAWAL,0));
adminExpenseAssert($updated->getReportingType()===ExpenseCategory::PRIVATE_WITHDRAWAL&&!$updated->affectsResult(),'Salariscategorie moet als privéopname uitgesloten kunnen worden.');
adminExpenseAssert($transactions->called===1&&$repository->updatedAt===1770000000,'Herclassificatie moet transactioneel met update-tijd worden opgeslagen.');
adminExpenseAssertThrows(InvalidArgumentException::class,function()use($useCase){$useCase->execute(new UpdateExpenseCategoryAccountingCommand('salary-category',ExpenseCategory::PRIVATE_WITHDRAWAL,21));},'Privéopname mag geen btw-aftrek krijgen.');
echo "AdminExpense category accounting tests passed.\n";
