<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseFileDownload;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseFileStorageInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Exception\ExpenseNotFoundException;

final class DownloadExpenseAttachment
{
    private $tenant,$expenses,$storage;public function __construct(TenantContext$tenant,ExpenseRepositoryInterface$expenses,ExpenseFileStorageInterface$storage){$this->tenant=$tenant;$this->expenses=$expenses;$this->storage=$storage;}
    public function execute(string$expensePublicId,string$attachmentPublicId):ExpenseFileDownload
    {
        $expense=$this->expenses->findByPublicId($this->tenant->getTenantId(),$expensePublicId);if($expense===null){throw new ExpenseNotFoundException('Uitgave is niet gevonden.');}$attachment=null;foreach($expense->getAttachments()as$candidate){if(hash_equals($candidate->getPublicId(),$attachmentPublicId)){$attachment=$candidate;break;}}if($attachment===null){throw new ExpenseNotFoundException('Bewijsstuk is niet gevonden bij deze uitgave.');}$download=$this->storage->read($attachment->getFileReference(),$attachment->getOriginalName(),$attachment->getMediaType());if(!hash_equals($attachment->getSha256(),hash('sha256',$download->getContent()))){throw new \UnexpectedValueException('Controle van het bewijsstuk is mislukt.');}return$download;
    }
}
