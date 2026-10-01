<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Integration\Banking;

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;

final class ExistingSuppliers
{
    private $connection;

    public function __construct(?\PDO $connection = null)
    {
        $this->connection = $connection ?: Connection::getConnections();
    }

    public function list(TenantId $tenant): array
    {
        $statement = $this->connection->prepare("SELECT `public_id`, `supplier_snapshot` FROM `admin_expense` WHERE `tenant_id`=:tenant AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`supplier_snapshot`,'$.name')),'')<>'' ORDER BY `id` DESC");
        $statement->execute([':tenant' => $tenant->toString()]);
        $options = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $snapshot = json_decode((string)$row['supplier_snapshot'], true);
            $name = is_array($snapshot) ? trim((string)($snapshot['name'] ?? '')) : '';
            if ($name === '') {
                continue;
            }
            $key = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
            if (!isset($options[$key])) {
                $options[$key] = ['expense_public_id' => (string)$row['public_id'], 'name' => $name];
            }
        }
        return array_values($options);
    }
}
