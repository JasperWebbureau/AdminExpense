<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Service;

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminCore\Context\TenantContext;
use Flexgrid\Modules\AdminCore\Infrastructure\PdoTransactionManager;
use Flexgrid\Modules\AdminCore\Infrastructure\UuidV4Generator;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\UseCase\CreateExpense;
use Flexgrid\Modules\AdminExpense\Application\UseCase\CreateExpenseCategory;
use Flexgrid\Modules\AdminExpense\Application\UseCase\GetExpense;
use Flexgrid\Modules\AdminExpense\Application\UseCase\ListExpenseCategories;
use Flexgrid\Modules\AdminExpense\Application\UseCase\ListExpenses;
use Flexgrid\Modules\AdminExpense\Application\UseCase\UpdateExpense;
use Flexgrid\Modules\AdminExpense\Application\UseCase\UpdateExpenseCategoryAccounting;
use Flexgrid\Modules\AdminExpense\Application\UseCase\AddExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Application\UseCase\DownloadExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Application\UseCase\RemoveExpenseAttachment;
use Flexgrid\Modules\AdminExpense\Infrastructure\File\SecureExpenseFileStorage;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseCategoryRepository;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseListRepository;
use Flexgrid\Modules\AdminExpense\Infrastructure\Persistence\PdoExpenseRepository;
use Flexgrid\Utils\_Time;

final class AdminExpenseFactory
{
    public static function createListExpenses():ListExpenses{$connection=self::connection();return new ListExpenses(self::tenant(),new PdoExpenseListRepository($connection),new PdoExpenseCategoryRepository($connection));}
    public static function createOverviewPresenter():ExpenseOverviewPresenter{return new ExpenseOverviewPresenter();}
    public static function createCreateExpense():CreateExpense{$connection=self::connection();return new CreateExpense(self::tenant(),new UuidV4Generator(),new PdoTransactionManager($connection),new PdoExpenseRepository($connection),new PdoExpenseCategoryRepository($connection),new _Time());}
    public static function createGetExpense():GetExpense{return new GetExpense(self::tenant(),new PdoExpenseRepository(self::connection()));}
    public static function createUpdateExpense():UpdateExpense{$connection=self::connection();return new UpdateExpense(self::tenant(),new PdoTransactionManager($connection),new PdoExpenseRepository($connection),new PdoExpenseCategoryRepository($connection),new _Time());}
    public static function createCreateCategory():CreateExpenseCategory{$connection=self::connection();return new CreateExpenseCategory(self::tenant(),new UuidV4Generator(),new PdoTransactionManager($connection),new PdoExpenseCategoryRepository($connection),new _Time());}
    public static function createUpdateCategoryAccounting():UpdateExpenseCategoryAccounting{$connection=self::connection();return new UpdateExpenseCategoryAccounting(self::tenant(),new PdoTransactionManager($connection),new PdoExpenseCategoryRepository($connection),new _Time());}
    public static function createListCategories():ListExpenseCategories{return new ListExpenseCategories(self::tenant(),new PdoExpenseCategoryRepository(self::connection()));}
    public static function createAddAttachment():AddExpenseAttachment{$connection=self::connection();return new AddExpenseAttachment(self::tenant(),new UuidV4Generator(),new PdoTransactionManager($connection),new PdoExpenseRepository($connection),new SecureExpenseFileStorage(),new _Time());}
    public static function createDownloadAttachment():DownloadExpenseAttachment{return new DownloadExpenseAttachment(self::tenant(),new PdoExpenseRepository(self::connection()),new SecureExpenseFileStorage());}
    public static function createRemoveAttachment():RemoveExpenseAttachment{$connection=self::connection();return new RemoveExpenseAttachment(self::tenant(),new PdoTransactionManager($connection),new PdoExpenseRepository($connection),new SecureExpenseFileStorage());}
    private static function connection():\PDO{return Connection::getConnections();}
    private static function tenant():TenantContext{if(!defined('__ADMIN_TENANT_ID__')){throw new \LogicException('Definieer __ADMIN_TENANT_ID__ expliciet voor de Admin-modules.');}return new TenantContext(new TenantId((string)constant('__ADMIN_TENANT_ID__')));}
}
