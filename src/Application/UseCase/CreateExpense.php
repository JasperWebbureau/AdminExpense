<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\UseCase;

use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Contract\PublicIdGeneratorInterface;
use Flexgrid\Modules\AdminCore\Contract\TransactionManagerInterface;
use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminExpense\Application\Command\CreateExpenseCommand;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\ExpenseDate;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\SupplierSnapshot;
use Flexgrid\Modules\AdminExpense\Exception\DuplicateExpenseExternalReferenceException;
use Flexgrid\Utils\_Time;

final class CreateExpense
{
    private $tenant, $ids, $transactions, $expenses, $categories, $clock;

    public function __construct(TenantContext $tenant, PublicIdGeneratorInterface $ids, TransactionManagerInterface $transactions, ExpenseRepositoryInterface $expenses, ExpenseCategoryRepositoryInterface $categories, _Time $clock)
    {
        $this->tenant = $tenant; $this->ids = $ids; $this->transactions = $transactions; $this->expenses = $expenses; $this->categories = $categories; $this->clock = $clock;
    }

    public function execute(CreateExpenseCommand $command): Expense
    {
        $tenantId = $this->tenant->getTenantId();
        try {
            return $this->transactions->transactional(function () use ($command, $tenantId): Expense {
                if ($command->getSource() !== '') {
                    $existing = $this->expenses->findByExternalReference($tenantId, $command->getSource(), $command->getExternalId());
                    if ($existing !== null) { return $existing; }
                }
                $category = $this->categories->findByPublicId($tenantId, $command->getCategoryPublicId());
                if ($category === null) { throw new \DomainException('Uitgavencategorie bestaat niet binnen de actieve administratie.'); }
                if (!$category->isActive()) { throw new \DomainException('Uitgavencategorie is niet actief.'); }
                $currency = new Currency($command->getCurrency());
                $supplier = $command->getSupplier();
                $expense = new Expense(
                    $this->ids->generate(), $tenantId, $category->getPublicId(), $currency,
                    new SupplierSnapshot($this->text($supplier, 'name'), $this->text($supplier, 'contact_name'), $this->text($supplier, 'email'), $this->text($supplier, 'registration_number'), $this->text($supplier, 'tax_number'), $this->text($supplier, 'country_code', false, 'NL'), $this->text($supplier, 'source_public_id')),
                    new ExpenseDate($command->getExpenseDate()), $command->getTitle(),
                    Money::fromDecimal($command->getNetAmount(), $currency), Money::fromDecimal($command->getTaxAmount(), $currency),
                    $command->getDescription(), $command->getReference(), $command->getSource(), $command->getExternalId()
                );
                foreach ($command->getAttachments() as $attachment) {
                    if (!is_array($attachment)) { throw new \InvalidArgumentException('Iedere uitgavenbijlage moet een array zijn.'); }
                    $expense->addAttachment(new ExpenseAttachment($this->ids->generate(), $this->text($attachment, 'file_reference', true), $this->text($attachment, 'original_name', true), $this->text($attachment, 'media_type', true), $this->integer($attachment, 'size_bytes'), $this->text($attachment, 'sha256', true)));
                }
                $this->expenses->insert($expense, (int)$this->clock->get());
                return $expense;
            });
        } catch (DuplicateExpenseExternalReferenceException $exception) {
            if ($command->getSource() !== '') {
                $existing = $this->expenses->findByExternalReference($tenantId, $command->getSource(), $command->getExternalId());
                if ($existing !== null) { return $existing; }
            }
            throw $exception;
        }
    }

    private function text(array $data, string $key, bool $required = false, string $default = ''): string
    {
        $value = array_key_exists($key, $data) ? $data[$key] : $default;
        if (!is_string($value) && !is_int($value)) { throw new \InvalidArgumentException('Veld ' . $key . ' moet tekst bevatten.'); }
        $value = trim((string)$value);
        if ($required && $value === '') { throw new \InvalidArgumentException('Veld ' . $key . ' is verplicht.'); }
        return $value;
    }

    private function integer(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        if (is_int($value)) { return $value; }
        if (!is_string($value) || preg_match('/^[0-9]+$/D', $value) !== 1) { throw new \InvalidArgumentException('Veld ' . $key . ' moet een niet-negatief geheel getal zijn.'); }
        return (int)$value;
    }
}
