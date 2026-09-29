<?php
namespace Flexgrid\Modules\AdminExpense\Repository;
use Flexgrid\Modules\AdminExpense\Entity\ExpenseRecord;
use Repository\Repository;
class ExpenseRecordRepository extends Repository { public function getEntity() { return new ExpenseRecord(); } }
