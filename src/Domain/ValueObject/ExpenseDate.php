<?php
declare(strict_types=1);
namespace Flexgrid\Modules\AdminExpense\Domain\ValueObject;
final class ExpenseDate
{
    private $value;
    public function __construct(string $value){$value=trim($value);if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$value,$match)!==1||!checkdate((int)$match[2],(int)$match[3],(int)$match[1])){throw new \InvalidArgumentException('Uitgavendatum moet een geldige datum in YYYY-MM-DD-formaat zijn.');}$this->value=$value;}
    public function getValue():string{return$this->value;}public function __toString():string{return$this->value;}
}
