<?php
declare(strict_types=1);
namespace Flexgrid\Modules\AdminExpense\Domain\Model;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
final class ExpenseCategory
{
    public const OPERATING_COST='operating_cost';
    public const PAYROLL_COST='payroll_cost';
    public const PRIVATE_WITHDRAWAL='private_withdrawal';
    public const BALANCE_SHEET='balance_sheet';

    private $publicId,$tenantId,$code,$name,$description,$active,$reportingType,$vatDeductiblePercentage;

    public function __construct(string $publicId,TenantId $tenantId,string $code,string $name,string $description='',bool $active=true,string $reportingType=self::OPERATING_COST,int $vatDeductiblePercentage=100)
    {
        $publicId=trim($publicId);$code=strtolower(trim($code));$name=trim($name);$description=trim($description);$reportingType=strtolower(trim($reportingType));
        if($publicId===''||strlen($publicId)>64){throw new \InvalidArgumentException('Uitgavencategorie vereist een geldige publieke id.');}
        if(preg_match('/^[a-z][a-z0-9_-]{1,63}$/D',$code)!==1){throw new \InvalidArgumentException('Categoriecode is ongeldig.');}
        if($name===''||strlen($name)>128||strlen($description)>1000){throw new \InvalidArgumentException('Categorienaam of omschrijving is ongeldig.');}
        if(!in_array($reportingType,self::reportingTypes(),true)){throw new \InvalidArgumentException('Kies een geldige boekhoudkundige verwerking.');}
        if($vatDeductiblePercentage<0||$vatDeductiblePercentage>100){throw new \InvalidArgumentException('Btw-aftrek moet tussen 0 en 100 procent liggen.');}
        if($reportingType!==self::OPERATING_COST&&$vatDeductiblePercentage!==0){throw new \InvalidArgumentException('Alleen zakelijke kosten kunnen aftrekbare btw hebben.');}
        $this->publicId=$publicId;$this->tenantId=$tenantId;$this->code=$code;$this->name=$name;$this->description=$description;$this->active=$active;$this->reportingType=$reportingType;$this->vatDeductiblePercentage=$vatDeductiblePercentage;
    }

    public static function reportingTypes():array{return[self::OPERATING_COST,self::PAYROLL_COST,self::PRIVATE_WITHDRAWAL,self::BALANCE_SHEET];}
    public function getPublicId():string{return$this->publicId;}public function getTenantId():TenantId{return$this->tenantId;}public function getCode():string{return$this->code;}public function getName():string{return$this->name;}public function getDescription():string{return$this->description;}public function isActive():bool{return$this->active;}public function getReportingType():string{return$this->reportingType;}public function getVatDeductiblePercentage():int{return$this->vatDeductiblePercentage;}public function affectsResult():bool{return in_array($this->reportingType,[self::OPERATING_COST,self::PAYROLL_COST],true);}
    public function withAccounting(string$reportingType,int$vatDeductiblePercentage):self{return new self($this->publicId,$this->tenantId,$this->code,$this->name,$this->description,$this->active,$reportingType,$vatDeductiblePercentage);}
}
