<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Infrastructure\Persistence;

use Flexgrid\Modules\AdminCore\ValueObject\Currency;
use Flexgrid\Modules\AdminCore\ValueObject\Money;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\Expense;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\ExpenseDate;
use Flexgrid\Modules\AdminExpense\Domain\ValueObject\SupplierSnapshot;
use Flexgrid\Modules\AdminExpense\Exception\DuplicateExpenseExternalReferenceException;

final class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    private const TABLE = 'admin_expense';
    private const ATTACHMENT_TABLE = 'admin_expense_attachment';
    /** @var \PDO */ private $connection;

    public function __construct(\PDO $connection) { $this->connection = $connection; }

    public function insert(Expense $expense, int $createdAt): void
    {
        $this->assertTransaction();
        $statement = $this->connection->prepare('INSERT INTO `' . self::TABLE . '` (`tenant_id`,`public_id`,`category_public_id`,`currency`,`expense_date`,`title`,`description`,`reference`,`supplier_snapshot`,`net_amount_minor`,`tax_amount_minor`,`gross_amount_minor`,`source`,`external_id`,`created_at`,`updated_at`) VALUES (:tenant,:public,:category,:currency,:expense_date,:title,:description,:reference,:supplier,:net,:tax,:gross,:source,:external,:created,:updated)');
        try {
            $statement->execute([':tenant' => $expense->getTenantId()->toString(), ':public' => $expense->getPublicId(), ':category' => $expense->getCategoryPublicId(), ':currency' => $expense->getCurrency()->getCode(), ':expense_date' => $expense->getExpenseDate()->getValue(), ':title' => $expense->getTitle(), ':description' => $this->nullable($expense->getDescription()), ':reference' => $this->nullable($expense->getReference()), ':supplier' => $this->json($expense->getSupplierSnapshot()->toArray()), ':net' => $expense->getNet()->getMinorUnits(), ':tax' => $expense->getTax()->getMinorUnits(), ':gross' => $expense->getGross()->getMinorUnits(), ':source' => $this->nullable($expense->getSource()), ':external' => $this->nullable($expense->getExternalId()), ':created' => $createdAt, ':updated' => $createdAt]);
        } catch (\PDOException $exception) {
            if ($expense->getSource() !== '' && (string)$exception->getCode() === '23000') { throw new DuplicateExpenseExternalReferenceException('Deze externe uitgave is al verwerkt.', 0, $exception); }
            throw $exception;
        }
        $attachment = $this->connection->prepare('INSERT INTO `' . self::ATTACHMENT_TABLE . '` (`tenant_id`,`public_id`,`expense_public_id`,`file_reference`,`original_name`,`media_type`,`size_bytes`,`sha256`,`created_at`) VALUES (:tenant,:public,:expense,:reference,:name,:media,:size,:sha,:created)');
        foreach ($expense->getAttachments() as $item) {
            $attachment->execute([':tenant' => $expense->getTenantId()->toString(), ':public' => $item->getPublicId(), ':expense' => $expense->getPublicId(), ':reference' => $item->getFileReference(), ':name' => $item->getOriginalName(), ':media' => $item->getMediaType(), ':size' => $item->getSizeBytes(), ':sha' => $item->getSha256(), ':created' => $createdAt]);
        }
    }

    public function findByPublicId(TenantId $tenantId, string $publicId): ?Expense
    {
        $this->assertText($publicId, 'Publieke uitgave-id');
        return $this->findOne('`tenant_id`=:tenant AND `public_id`=:public', [':tenant' => $tenantId->toString(), ':public' => trim($publicId)]);
    }

    public function findByPublicIdForUpdate(TenantId $tenantId, string $publicId): ?Expense
    {
        $this->assertTransaction(); $this->assertText($publicId, 'Publieke uitgave-id');
        $statement = $this->connection->prepare('SELECT * FROM `' . self::TABLE . '` WHERE `tenant_id`=:tenant AND `public_id`=:public LIMIT 1 FOR UPDATE');
        $statement->execute([':tenant' => $tenantId->toString(), ':public' => trim($publicId)]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findByExternalReference(TenantId $tenantId, string $source, string $externalId): ?Expense
    {
        $this->assertText($source, 'Uitgavebron'); $this->assertText($externalId, 'Externe uitgave-id');
        return $this->findOne('`tenant_id`=:tenant AND `source`=:source AND `external_id`=:external', [':tenant' => $tenantId->toString(), ':source' => strtolower(trim($source)), ':external' => trim($externalId)]);
    }

    public function update(Expense $expense, int $updatedAt): void
    {
        $this->assertTransaction();
        $statement = $this->connection->prepare('UPDATE `' . self::TABLE . '` SET `category_public_id`=:category,`expense_date`=:expense_date,`title`=:title,`description`=:description,`reference`=:reference,`supplier_snapshot`=:supplier,`net_amount_minor`=:net,`tax_amount_minor`=:tax,`gross_amount_minor`=:gross,`updated_at`=:updated WHERE `tenant_id`=:tenant AND `public_id`=:public');
        $statement->execute([':category' => $expense->getCategoryPublicId(), ':expense_date' => $expense->getExpenseDate()->getValue(), ':title' => $expense->getTitle(), ':description' => $this->nullable($expense->getDescription()), ':reference' => $this->nullable($expense->getReference()), ':supplier' => $this->json($expense->getSupplierSnapshot()->toArray()), ':net' => $expense->getNet()->getMinorUnits(), ':tax' => $expense->getTax()->getMinorUnits(), ':gross' => $expense->getGross()->getMinorUnits(), ':updated' => $updatedAt, ':tenant' => $expense->getTenantId()->toString(), ':public' => $expense->getPublicId()]);
        if ($statement->rowCount() < 1 && $this->findByPublicId($expense->getTenantId(), $expense->getPublicId()) === null) { throw new \RuntimeException('Uitgave bestaat niet meer.'); }
    }

    public function insertAttachment(TenantId $tenantId, string $expensePublicId, ExpenseAttachment $attachment, int $createdAt): void
    {
        $this->assertTransaction();$parent=$this->connection->prepare('SELECT 1 FROM `'.self::TABLE.'` WHERE `tenant_id`=:tenant AND `public_id`=:expense LIMIT 1');$parent->execute([':tenant'=>$tenantId->toString(),':expense'=>$expensePublicId]);if($parent->fetchColumn()===false){throw new \RuntimeException('Uitgave bestaat niet meer binnen de actieve administratie.');}$statement=$this->connection->prepare('INSERT INTO `'.self::ATTACHMENT_TABLE.'` (`tenant_id`,`public_id`,`expense_public_id`,`file_reference`,`original_name`,`media_type`,`size_bytes`,`sha256`,`created_at`) VALUES (:tenant,:public,:expense,:reference,:name,:media,:size,:sha,:created)');$statement->execute([':tenant'=>$tenantId->toString(),':public'=>$attachment->getPublicId(),':expense'=>$expensePublicId,':reference'=>$attachment->getFileReference(),':name'=>$attachment->getOriginalName(),':media'=>$attachment->getMediaType(),':size'=>$attachment->getSizeBytes(),':sha'=>$attachment->getSha256(),':created'=>$createdAt]);
    }

    public function removeAttachment(TenantId $tenantId, string $expensePublicId, string $attachmentPublicId): bool
    {
        $this->assertTransaction();$statement=$this->connection->prepare('DELETE FROM `'.self::ATTACHMENT_TABLE.'` WHERE `tenant_id`=:tenant AND `expense_public_id`=:expense AND `public_id`=:public');$statement->execute([':tenant'=>$tenantId->toString(),':expense'=>$expensePublicId,':public'=>$attachmentPublicId]);return$statement->rowCount()===1;
    }

    private function findOne(string $where, array $parameters): ?Expense
    {
        $statement = $this->connection->prepare('SELECT * FROM `' . self::TABLE . '` WHERE ' . $where . ' LIMIT 1');
        $statement->execute($parameters);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): Expense
    {
        $tenant = new TenantId((string)$row['tenant_id']); $currency = new Currency((string)$row['currency']);
        $expense = new Expense((string)$row['public_id'], $tenant, (string)$row['category_public_id'], $currency, SupplierSnapshot::fromArray($this->decode((string)$row['supplier_snapshot'])), new ExpenseDate((string)$row['expense_date']), (string)$row['title'], new Money((int)$row['net_amount_minor'], $currency), new Money((int)$row['tax_amount_minor'], $currency), (string)($row['description'] ?? ''), (string)($row['reference'] ?? ''), (string)($row['source'] ?? ''), (string)($row['external_id'] ?? ''));
        $statement = $this->connection->prepare('SELECT * FROM `' . self::ATTACHMENT_TABLE . '` WHERE `tenant_id`=:tenant AND `expense_public_id`=:expense ORDER BY `id`');
        $statement->execute([':tenant' => $tenant->toString(), ':expense' => $expense->getPublicId()]);
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $attachment) {
            $expense->addAttachment(new ExpenseAttachment((string)$attachment['public_id'], (string)$attachment['file_reference'], (string)$attachment['original_name'], (string)$attachment['media_type'], (int)$attachment['size_bytes'], (string)$attachment['sha256']));
        }
        if ($expense->getGross()->getMinorUnits() !== (int)$row['gross_amount_minor']) { throw new \UnexpectedValueException('Opgeslagen uitgaventotaal is inconsistent.'); }
        return $expense;
    }

    private function assertTransaction(): void { if (!$this->connection->inTransaction()) { throw new \LogicException('Uitgavenopslag vereist een actieve transactie.'); } }
    private function assertText(string $value, string $label): void { if (trim($value) === '' || strlen(trim($value)) > 128) { throw new \InvalidArgumentException($label . ' is verplicht.'); } }
    private function nullable(string $value) { return $value === '' ? null : $value; }
    private function json(array $value): string { $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); if ($json === false) { throw new \UnexpectedValueException('Leverancierssnapshot kon niet worden gecodeerd.'); } return $json; }
    private function decode(string $json): array { $value = json_decode($json, true); if (!is_array($value) || json_last_error() !== JSON_ERROR_NONE) { throw new \UnexpectedValueException('Ongeldige leverancierssnapshot.'); } return $value; }
}
