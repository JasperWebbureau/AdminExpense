<?php
namespace Flexgrid\Modules\AdminExpense\Repository;
use Flexgrid\Modules\AdminExpense\Entity\ExpenseAttachmentRecord;
use Repository\Repository;
class ExpenseAttachmentRecordRepository extends Repository { public function getEntity() { return new ExpenseAttachmentRecord(); } }
