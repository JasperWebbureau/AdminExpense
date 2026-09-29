<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Contract;

use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseFileDownload;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\StoredExpenseFile;

interface ExpenseFileStorageInterface
{
    public function storeUploadedFile(array $uploadedFile, string $context): StoredExpenseFile;
    public function read(string $reference, string $fileName, string $mediaType): ExpenseFileDownload;
    public function discard(string $reference, string $context): void;
}
