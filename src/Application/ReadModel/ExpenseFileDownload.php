<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\ReadModel;

final class ExpenseFileDownload
{
    private $content,$fileName,$mediaType;
    public function __construct(string$content,string$fileName,string$mediaType){if($content===''){throw new \InvalidArgumentException('Downloadinhoud mag niet leeg zijn.');}$this->content=$content;$this->fileName=$fileName;$this->mediaType=$mediaType;}
    public function getContent():string{return$this->content;}public function getFileName():string{return$this->fileName;}public function getMediaType():string{return$this->mediaType;}public function getSize():int{return strlen($this->content);}
}
