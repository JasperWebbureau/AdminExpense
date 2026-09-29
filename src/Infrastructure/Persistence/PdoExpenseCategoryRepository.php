<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Infrastructure\Persistence;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseCategoryRepositoryInterface;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;

final class PdoExpenseCategoryRepository implements ExpenseCategoryRepositoryInterface
{
    private const TABLE = 'admin_expense_category';
    /** @var \PDO */ private $connection;

    public function __construct(\PDO $connection) { $this->connection = $connection; }

    public function insert(ExpenseCategory $category, int $createdAt): void
    {
        $this->assertTransaction();
        $statement = $this->connection->prepare('INSERT INTO `' . self::TABLE . '` (`tenant_id`,`public_id`,`code`,`name`,`description`,`is_active`,`reporting_type`,`vat_deductible_percentage`,`created_at`,`updated_at`) VALUES (:tenant,:public,:code,:name,:description,:active,:reporting_type,:vat_percentage,:created,:updated)');
        $statement->execute([':tenant' => $category->getTenantId()->toString(), ':public' => $category->getPublicId(), ':code' => $category->getCode(), ':name' => $category->getName(), ':description' => $this->nullable($category->getDescription()), ':active' => $category->isActive() ? 1 : 0, ':reporting_type'=>$category->getReportingType(),':vat_percentage'=>$category->getVatDeductiblePercentage(), ':created' => $createdAt, ':updated' => $createdAt]);
    }

    public function updateAccounting(ExpenseCategory$category,int$updatedAt):void
    {
        $this->assertTransaction();$statement=$this->connection->prepare('UPDATE `'.self::TABLE.'` SET `reporting_type`=:reporting_type,`vat_deductible_percentage`=:vat_percentage,`updated_at`=:updated WHERE `tenant_id`=:tenant AND `public_id`=:public');$statement->execute([':reporting_type'=>$category->getReportingType(),':vat_percentage'=>$category->getVatDeductiblePercentage(),':updated'=>$updatedAt,':tenant'=>$category->getTenantId()->toString(),':public'=>$category->getPublicId()]);if($statement->rowCount()===0&&$this->findByPublicId($category->getTenantId(),$category->getPublicId())===null){throw new \RuntimeException('Uitgavencategorie kon niet worden bijgewerkt.');}
    }

    public function findByPublicId(TenantId $tenantId, string $publicId): ?ExpenseCategory
    {
        if (trim($publicId) === '') { throw new \InvalidArgumentException('Publieke categorie-id is verplicht.'); }
        return $this->findOne('`tenant_id`=:tenant AND `public_id`=:value', $tenantId, trim($publicId));
    }

    public function findByCode(TenantId $tenantId, string $code): ?ExpenseCategory
    {
        if (trim($code) === '') { throw new \InvalidArgumentException('Categoriecode is verplicht.'); }
        return $this->findOne('`tenant_id`=:tenant AND `code`=:value', $tenantId, strtolower(trim($code)));
    }

    public function findAll(TenantId $tenantId, bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM `' . self::TABLE . '` WHERE `tenant_id`=:tenant';
        if (!$includeInactive) { $sql .= ' AND `is_active`=1'; }
        $sql .= ' ORDER BY `name`,`code`';
        $statement = $this->connection->prepare($sql); $statement->execute([':tenant' => $tenantId->toString()]);
        $categories = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) { $categories[] = $this->hydrate($row); }
        return $categories;
    }

    private function findOne(string $where, TenantId $tenantId, string $value): ?ExpenseCategory
    {
        $statement = $this->connection->prepare('SELECT * FROM `' . self::TABLE . '` WHERE ' . $where . ' LIMIT 1');
        $statement->execute([':tenant' => $tenantId->toString(), ':value' => $value]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): ExpenseCategory { $type=trim((string)($row['reporting_type']??''))?:ExpenseCategory::OPERATING_COST;$percentage=!array_key_exists('vat_deductible_percentage',$row)||$row['vat_deductible_percentage']===null?100:(int)$row['vat_deductible_percentage'];return new ExpenseCategory((string)$row['public_id'], new TenantId((string)$row['tenant_id']), (string)$row['code'], (string)$row['name'], (string)($row['description'] ?? ''), (bool)$row['is_active'],$type,$percentage); }

    private function assertTransaction(): void { if (!$this->connection->inTransaction()) { throw new \LogicException('Opslag van een uitgavencategorie vereist een actieve transactie.'); } }
    private function nullable(string $value) { return $value === '' ? null : $value; }
}
