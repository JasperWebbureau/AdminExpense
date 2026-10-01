<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminExpense\Application\Command\UpdateExpenseCommand;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\ExpenseDate;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\SupplierSnapshot;
use Flexgrid\Modules\AdminExpense\Exception\ExpenseNotFoundException;
use Flexgrid\Utils\_Time;

final class UpdateExpense
{
    private $tenant, $transactions, $expenses, $categories, $clock;
    public function __construct(TenantContext $tenant, TransactionManagerInterface $transactions, ExpenseRepositoryInterface $expenses, ExpenseCategoryRepositoryInterface $categories, _Time $clock) { $this->tenant = $tenant; $this->transactions = $transactions; $this->expenses = $expenses; $this->categories = $categories; $this->clock = $clock; }

    public function execute(UpdateExpenseCommand $command): Expense
    {
        $tenantId = $this->tenant->getTenantId();
        return $this->transactions->transactional(function () use ($command, $tenantId): Expense {
            $current = $this->expenses->findByPublicIdForUpdate($tenantId, $command->getPublicId());
            if ($current === null) { throw new ExpenseNotFoundException('Uitgave is niet gevonden.'); }
            $category = $this->categories->findByPublicId($tenantId, $command->getCategoryPublicId());
            if ($category === null) { throw new \DomainException('Uitgavencategorie bestaat niet binnen de actieve administratie.'); }
            if (!$category->isActive() && $category->getPublicId() !== $current->getCategoryPublicId()) { throw new \DomainException('Uitgavencategorie is niet actief.'); }
            $supplier = $command->getSupplier(); $currency = $current->getCurrency();
            $updated = new Expense($current->getPublicId(), $tenantId, $category->getPublicId(), $currency, new SupplierSnapshot($this->text($supplier, 'name'), $this->text($supplier, 'contact_name'), $this->text($supplier, 'email'), $this->text($supplier, 'registration_number'), $this->text($supplier, 'tax_number'), $this->text($supplier, 'country_code', false, 'NL'), $this->text($supplier, 'source_public_id')), new ExpenseDate($command->getExpenseDate()), $command->getTitle(), Money::fromDecimal($command->getNetAmount(), $currency), Money::fromDecimal($command->getTaxAmount(), $currency), $command->getDescription(), $command->getReference(), $current->getSource(), $current->getExternalId());
            foreach ($current->getAttachments() as $attachment) { $updated->addAttachment($attachment); }
            $this->expenses->update($updated, (int)$this->clock->get());
            return $updated;
        });
    }

    private function text(array $data, string $key, bool $required = false, string $default = ''): string
    {
        $value = array_key_exists($key, $data) ? $data[$key] : $default;
        if (!is_string($value) && !is_int($value)) { throw new \InvalidArgumentException('Veld ' . $key . ' moet tekst bevatten.'); }
        $value = trim((string)$value);
        if ($required && $value === '') { throw new \InvalidArgumentException('Veld ' . $key . ' is verplicht.'); }
        return $value;
    }
}
