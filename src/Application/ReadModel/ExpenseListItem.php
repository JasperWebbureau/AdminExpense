<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\ReadModel;

final class ExpenseListItem
{
    private $publicId, $title, $supplierName, $expenseDate, $categoryName, $reference, $grossTotalMinor, $currency, $attachmentCount,$reportingType,$vatDeductiblePercentage;
    public function __construct(string $publicId, string $title, string $supplierName, string $expenseDate, string $categoryName, string $reference, int $grossTotalMinor, string $currency, int $attachmentCount,string$reportingType='operating_cost',int$vatDeductiblePercentage=100)
    { $this->publicId=$publicId;$this->title=$title;$this->supplierName=$supplierName;$this->expenseDate=$expenseDate;$this->categoryName=$categoryName;$this->reference=$reference;$this->grossTotalMinor=$grossTotalMinor;$this->currency=$currency;$this->attachmentCount=$attachmentCount;$this->reportingType=$reportingType;$this->vatDeductiblePercentage=$vatDeductiblePercentage; }
    public function getPublicId(): string{return $this->publicId;} public function getTitle(): string{return $this->title;} public function getSupplierName(): string{return $this->supplierName;} public function getExpenseDate(): string{return $this->expenseDate;} public function getCategoryName(): string{return $this->categoryName;} public function getReference(): string{return $this->reference;} public function getGrossTotalMinor(): int{return $this->grossTotalMinor;} public function getCurrency(): string{return $this->currency;} public function getAttachmentCount(): int{return $this->attachmentCount;}public function getReportingType():string{return$this->reportingType;}public function getVatDeductiblePercentage():int{return$this->vatDeductiblePercentage;}
}
