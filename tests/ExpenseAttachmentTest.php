<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseFileDownload;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\StoredExpenseFile;
use Flexgrid\Modules\AdminExpense\Application\UseCase\AddExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Application\UseCase\DownloadExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseFileStorageInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\ExpenseDate;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\SupplierSnapshot;
use Flexgrid\Utils\_Time;

final class AttachmentIds implements PublicIdGeneratorInterface{public function generate():string{return'attachment-test-1';}}
final class AttachmentTx implements TransactionManagerInterface{public function transactional(callable$operation){return$operation();}}
final class AttachmentStorage implements ExpenseFileStorageInterface{public $stored=false,$discarded=false,$content='proof';public function storeUploadedFile(array$file,string$context):StoredExpenseFile{$this->stored=true;return new StoredExpenseFile('secure-file:42','bewijs.pdf','application/pdf',strlen($this->content),hash('sha256',$this->content));}public function read(string$reference,string$name,string$type):ExpenseFileDownload{return new ExpenseFileDownload($this->content,$name,$type);}public function discard(string$reference,string$context):void{$this->discarded=true;}}
final class AttachmentRepository implements ExpenseRepositoryInterface{public $expense,$failInsert=false;public function insert(Expense$e,int$t):void{}public function findByPublicId(TenantId$t,string$id):?Expense{return$this->expense!==null&&$this->expense->getTenantId()->equals($t)?$this->expense:null;}public function findByPublicIdForUpdate(TenantId$t,string$id):?Expense{return$this->findByPublicId($t,$id);}public function findByExternalReference(TenantId$t,string$s,string$e):?Expense{return null;}public function update(Expense$e,int$t):void{}public function insertAttachment(TenantId$t,string$e,ExpenseAttachment$a,int$c):void{if($this->failInsert){throw new RuntimeException('insert failed');}}public function removeAttachment(TenantId$t,string$e,string$a):bool{return false;}}

$tenant=new TenantId('expense-attachment-tenant');$currency=Currency::euro();$expense=new Expense('expense-attachment-1',$tenant,'category-1',$currency,new SupplierSnapshot('Leverancier'),new ExpenseDate('2026-09-19'),'Bewijsstuk',Money::fromDecimal('10',$currency),Money::fromDecimal('2.10',$currency));$repo=new AttachmentRepository();$repo->expense=$expense;$storage=new AttachmentStorage();$add=new AddExpenseAttachment(new TenantContext($tenant),new AttachmentIds(),new AttachmentTx(),$repo,$storage,new _Time(1770000000));$withAttachment=$add->execute('expense-attachment-1',['name'=>'bewijs.pdf']);
adminExpenseAssert($storage->stored&&count($withAttachment->getAttachments())===1,'Upload-use-case moet bestand opslaan en metadata koppelen.');$download=(new DownloadExpenseAttachment(new TenantContext($tenant),$repo,$storage))->execute('expense-attachment-1','attachment-test-1');adminExpenseAssert($download->getContent()==='proof'&&$download->getFileName()==='bewijs.pdf','Download moet alleen gekoppeld tenantbewijsstuk leveren.');adminExpenseAssertThrows(\Flexgrid\Modules\AdminExpense\Exception\ExpenseNotFoundException::class,function()use($tenant,$repo,$storage){(new DownloadExpenseAttachment(new TenantContext($tenant),$repo,$storage))->execute('expense-attachment-1','unknown');},'Ongekoppelde attachment-id moet worden geweigerd.');
$otherExpense=new Expense('expense-attachment-2',$tenant,'category-1',$currency,new SupplierSnapshot('Leverancier'),new ExpenseDate('2026-09-19'),'Tweede',Money::fromDecimal('10',$currency),Money::fromDecimal('2.10',$currency));$repo->expense=$otherExpense;$repo->failInsert=true;$storage->discarded=false;adminExpenseAssertThrows(RuntimeException::class,function()use($add){$add->execute('expense-attachment-2',['name'=>'bewijs.pdf']);},'Metadatafout moet upload laten falen.');adminExpenseAssert($storage->discarded,'Metadatafout moet versleuteld bestand opruimen.');
echo "AdminExpense attachment tests passed.\n";
