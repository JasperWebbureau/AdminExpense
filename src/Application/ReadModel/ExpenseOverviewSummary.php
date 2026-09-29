<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\ReadModel;

final class ExpenseOverviewSummary
{
    private $count,$netMinor,$taxMinor,$grossMinor,$currency;
    public function __construct(int$count,int$netMinor,int$taxMinor,int$grossMinor,string$currency){if($count<0){throw new \InvalidArgumentException('Uitgavenaantal mag niet negatief zijn.');}$this->count=$count;$this->netMinor=$netMinor;$this->taxMinor=$taxMinor;$this->grossMinor=$grossMinor;$this->currency=$currency;}
    public function getCount():int{return$this->count;} public function getNetMinor():int{return$this->netMinor;} public function getTaxMinor():int{return$this->taxMinor;} public function getGrossMinor():int{return$this->grossMinor;} public function getCurrency():string{return$this->currency;}
}
