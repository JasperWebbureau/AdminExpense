<?php
declare(strict_types=1);
namespace Flexgrid\Modules\AdminExpense\Domain\Model;
final class ExpenseAttachment
{
    private $publicId,$fileReference,$originalName,$mediaType,$sizeBytes,$sha256;
    public function __construct(string $publicId,string $fileReference,string $originalName,string $mediaType,int $sizeBytes,string $sha256){$publicId=trim($publicId);$fileReference=trim($fileReference);$originalName=trim($originalName);$mediaType=strtolower(trim($mediaType));$sha256=strtolower(trim($sha256));if($publicId===''||strlen($publicId)>64){throw new \InvalidArgumentException('Uitgavenbijlage vereist een geldige publieke id.');}if($fileReference===''||strlen($fileReference)>255||$originalName===''||strlen($originalName)>255){throw new \InvalidArgumentException('Bijlagereferentie of bestandsnaam is ongeldig.');}if(preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$#D',$mediaType)!==1){throw new \InvalidArgumentException('Bijlage heeft een ongeldig mediatype.');}if($sizeBytes<1||$sizeBytes>25*1024*1024){throw new \InvalidArgumentException('Bijlage moet tussen 1 byte en 25 MB zijn.');}if(preg_match('/^[a-f0-9]{64}$/D',$sha256)!==1){throw new \InvalidArgumentException('Bijlage vereist een geldige SHA-256-hash.');}$this->publicId=$publicId;$this->fileReference=$fileReference;$this->originalName=$originalName;$this->mediaType=$mediaType;$this->sizeBytes=$sizeBytes;$this->sha256=$sha256;}
    public function getPublicId():string{return$this->publicId;}public function getFileReference():string{return$this->fileReference;}public function getOriginalName():string{return$this->originalName;}public function getMediaType():string{return$this->mediaType;}public function getSizeBytes():int{return$this->sizeBytes;}public function getSha256():string{return$this->sha256;}
}
