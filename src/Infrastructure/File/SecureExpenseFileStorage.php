<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Infrastructure\File;

use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseFileDownload;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\StoredExpenseFile;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseFileStorageInterface;
use Flexgrid\SecureFile\Entity\SecureFile;
use Flexgrid\SecureFile\Repository\SecureFileRepository;
use Flexgrid\SecureFile\SecureFileService;

final class SecureExpenseFileStorage implements ExpenseFileStorageInterface
{
    private const PREFIX='secure-file:';
    private $service,$repository;
    public function __construct(?SecureFileService$service=null,?SecureFileRepository$repository=null){$this->service=$service?:new SecureFileService();$this->repository=$repository?:new SecureFileRepository();}
    public function storeUploadedFile(array$uploadedFile,string$context):StoredExpenseFile
    {
        $file=$this->service->storeUploadedFile($uploadedFile,['owner_type'=>'admin_expense','context'=>substr($context,0,120),'max_bytes'=>25*1024*1024,'allowed_mime_types'=>['application/pdf','image/jpeg','image/png','image/webp']]);
        return new StoredExpenseFile(self::PREFIX.(int)$file->getId(),(string)$file->getOriginalName(),(string)$file->getMimeType(),(int)$file->getSizeBytes(),(string)$file->getChecksumSha256());
    }
    public function read(string$reference,string$fileName,string$mediaType):ExpenseFileDownload
    {
        $file=$this->find($reference);if(!$file->isStored()||$this->service->isExpired($file)){throw new \RuntimeException('Het bewijsstuk is niet meer beschikbaar.');}$content=$this->service->read($file,['actor_type'=>'admin_expense','context'=>'expense.download']);return new ExpenseFileDownload($content,$fileName,$mediaType);
    }
    public function discard(string$reference,string$context):void
    {
        try{$file=$this->find($reference);if($file->isStored()){$this->service->markDeleted($file,['actor_type'=>'admin_expense','context'=>substr($context,0,120)]);}}catch(\Throwable$exception){throw new \RuntimeException('Het beveiligde bestand kon niet worden opgeruimd.',0,$exception);}
    }
    private function find(string$reference):SecureFile
    {
        if(strpos($reference,self::PREFIX)!==0||preg_match('/^[1-9][0-9]*$/D',substr($reference,strlen(self::PREFIX)))!==1){throw new \InvalidArgumentException('Ongeldige beveiligde bestandsreferentie.');}$file=$this->repository->findById((int)substr($reference,strlen(self::PREFIX)));if(!$file instanceof SecureFile){throw new \RuntimeException('Het bewijsstuk is niet gevonden.');}return$file;
    }
}
