<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\ReadModel;

final class StoredExpenseFile
{
    private $reference,$originalName,$mediaType,$sizeBytes,$sha256;
    public function __construct(string$reference,string$originalName,string$mediaType,int$sizeBytes,string$sha256){$this->reference=$reference;$this->originalName=$originalName;$this->mediaType=$mediaType;$this->sizeBytes=$sizeBytes;$this->sha256=$sha256;}
    public function getReference():string{return$this->reference;}public function getOriginalName():string{return$this->originalName;}public function getMediaType():string{return$this->mediaType;}public function getSizeBytes():int{return$this->sizeBytes;}public function getSha256():string{return$this->sha256;}
}
