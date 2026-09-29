<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\Command;

final class UpdateExpenseCategoryAccountingCommand
{
    private $publicId,$reportingType,$vatDeductiblePercentage;
    public function __construct(string$publicId,string$reportingType,int$vatDeductiblePercentage){$this->publicId=trim($publicId);$this->reportingType=strtolower(trim($reportingType));$this->vatDeductiblePercentage=$vatDeductiblePercentage;}
    public function getPublicId():string{return$this->publicId;}
    public function getReportingType():string{return$this->reportingType;}
    public function getVatDeductiblePercentage():int{return$this->vatDeductiblePercentage;}
}
