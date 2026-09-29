<?php
declare(strict_types=1);

$expenseModuleRoot = dirname(__DIR__);
$modulesRoot = dirname($expenseModuleRoot);
require_once $modulesRoot . '/AdminCore/tests/bootstrap.php';

foreach ([
    'Domain/ValueObject/ExpenseDate.php',
    'Domain/ValueObject/SupplierSnapshot.php',
    'Domain/Model/ExpenseCategory.php',
    'Domain/Model/ExpenseAttachment.php',
    'Domain/Model/Expense.php',
    'Application/Command/CreateExpenseCategoryCommand.php',
    'Application/Command/UpdateExpenseCategoryAccountingCommand.php',
    'Application/Command/CreateExpenseCommand.php',
    'Application/Command/UpdateExpenseCommand.php',
    'Application/Query/ExpenseListQuery.php',
    'Application/ReadModel/ExpenseListItem.php',
    'Application/ReadModel/ExpenseListResult.php',
    'Application/ReadModel/ExpenseOverviewSummary.php',
    'Application/ReadModel/StoredExpenseFile.php',
    'Application/ReadModel/ExpenseFileDownload.php',
    'Contract/ExpenseCategoryRepositoryInterface.php',
    'Contract/ExpenseRepositoryInterface.php',
    'Contract/ExpenseListRepositoryInterface.php',
    'Contract/ExpenseFileStorageInterface.php',
    'Exception/DuplicateExpenseExternalReferenceException.php',
    'Exception/ExpenseNotFoundException.php',
    'Application/UseCase/CreateExpenseCategory.php',
    'Application/UseCase/UpdateExpenseCategoryAccounting.php',
    'Application/UseCase/CreateExpense.php',
    'Application/UseCase/GetExpense.php',
    'Application/UseCase/ListExpenseCategories.php',
    'Application/UseCase/ListExpenses.php',
    'Application/UseCase/UpdateExpense.php',
    'Application/UseCase/AddExpenseAttachment.php',
    'Application/UseCase/DownloadExpenseAttachment.php',
    'Application/UseCase/RemoveExpenseAttachment.php',
    'Infrastructure/Persistence/PdoExpenseCategoryRepository.php',
    'Infrastructure/Persistence/PdoExpenseRepository.php',
    'Infrastructure/Persistence/PdoExpenseListRepository.php',
    'Service/ExpenseOverviewPresenter.php',
] as $file) {
    require_once $expenseModuleRoot . '/src/' . $file;
}

function adminExpenseAssert($condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function adminExpenseAssertThrows(string $class, callable $callback, string $message): void
{
    try { $callback(); }
    catch (Throwable $throwable) {
        if ($throwable instanceof $class) { return; }
        throw new RuntimeException($message . ' Ontvangen: ' . get_class($throwable));
    }
    throw new RuntimeException($message . ' Er werd geen exception gegooid.');
}
