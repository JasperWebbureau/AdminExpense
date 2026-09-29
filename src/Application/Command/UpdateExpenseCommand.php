<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\Command;

final class UpdateExpenseCommand
{
    private $publicId, $expenseDate, $categoryPublicId, $title, $netAmount, $taxAmount, $description, $reference;
    private $supplier;

    public function __construct(string $publicId, string $expenseDate, string $categoryPublicId, array $supplier, string $title, string $netAmount, string $taxAmount, string $description = '', string $reference = '')
    {
        $this->publicId = trim($publicId); $this->expenseDate = trim($expenseDate); $this->categoryPublicId = trim($categoryPublicId); $this->supplier = $supplier; $this->title = trim($title); $this->netAmount = trim($netAmount); $this->taxAmount = trim($taxAmount); $this->description = trim($description); $this->reference = trim($reference);
    }

    public function getPublicId(): string { return $this->publicId; }
    public function getExpenseDate(): string { return $this->expenseDate; }
    public function getCategoryPublicId(): string { return $this->categoryPublicId; }
    public function getSupplier(): array { return $this->supplier; }
    public function getTitle(): string { return $this->title; }
    public function getNetAmount(): string { return $this->netAmount; }
    public function getTaxAmount(): string { return $this->taxAmount; }
    public function getDescription(): string { return $this->description; }
    public function getReference(): string { return $this->reference; }
}
