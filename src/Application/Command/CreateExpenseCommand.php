<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\Command;

final class CreateExpenseCommand
{
    /** @var string */
    private $currency, $expenseDate, $categoryPublicId, $title, $netAmount, $taxAmount, $description, $reference, $source, $externalId;
    /** @var array */
    private $supplier, $attachments;

    public function __construct(string $currency, string $expenseDate, string $categoryPublicId, array $supplier, string $title, string $netAmount, string $taxAmount, string $description = '', string $reference = '', array $attachments = [], string $source = '', string $externalId = '')
    {
        $this->currency = trim($currency);
        $this->expenseDate = trim($expenseDate);
        $this->categoryPublicId = trim($categoryPublicId);
        $this->supplier = $supplier;
        $this->title = trim($title);
        $this->netAmount = trim($netAmount);
        $this->taxAmount = trim($taxAmount);
        $this->description = trim($description);
        $this->reference = trim($reference);
        $this->attachments = $attachments;
        $this->source = trim($source);
        $this->externalId = trim($externalId);
    }

    public function getCurrency(): string { return $this->currency; }
    public function getExpenseDate(): string { return $this->expenseDate; }
    public function getCategoryPublicId(): string { return $this->categoryPublicId; }
    public function getSupplier(): array { return $this->supplier; }
    public function getTitle(): string { return $this->title; }
    public function getNetAmount(): string { return $this->netAmount; }
    public function getTaxAmount(): string { return $this->taxAmount; }
    public function getDescription(): string { return $this->description; }
    public function getReference(): string { return $this->reference; }
    public function getAttachments(): array { return $this->attachments; }
    public function getSource(): string { return $this->source; }
    public function getExternalId(): string { return $this->externalId; }
}
