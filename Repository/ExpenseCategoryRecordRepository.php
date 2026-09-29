<?php
namespace Flexgrid\Modules\AdminExpense\Repository;
use Flexgrid\Modules\AdminExpense\Entity\ExpenseCategoryRecord;
use Repository\Repository;
class ExpenseCategoryRecordRepository extends Repository { public function getEntity() { return new ExpenseCategoryRecord(); } }
