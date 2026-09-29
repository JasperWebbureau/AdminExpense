<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\Command;

final class CreateExpenseCategoryCommand
{
    /** @var string */ private $code, $name, $description, $reportingType;
    /** @var bool */ private $active;
    /** @var int */ private $vatDeductiblePercentage;

    public function __construct(string $code, string $name, string $description = '', bool $active = true, string $reportingType = 'operating_cost', int $vatDeductiblePercentage = 100)
    {
        $this->code = trim($code);
        $this->name = trim($name);
        $this->description = trim($description);
        $this->active = $active;
        $this->reportingType = strtolower(trim($reportingType));
        $this->vatDeductiblePercentage = $vatDeductiblePercentage;
    }

    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function isActive(): bool { return $this->active; }
    public function getReportingType(): string { return $this->reportingType; }
    public function getVatDeductiblePercentage(): int { return $this->vatDeductiblePercentage; }
}
