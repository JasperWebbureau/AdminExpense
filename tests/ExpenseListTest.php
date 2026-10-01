<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

use Flexgrid\Modules\AdminExpense\Application\Query\ExpenseListQuery;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseListItem;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseListResult;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseOverviewSummary;
use Flexgrid\Modules\AdminExpense\Service\ExpenseOverviewPresenter;

$query=new ExpenseListQuery('hosting','category-1',2026,'gross_total','desc',1,25);$result=new ExpenseListResult([new ExpenseListItem('expense-list-1','Hosting','Hosting BV','2026-09-19','Software','INV-42',12100,'EUR',1)],1,1,25);$view=(new ExpenseOverviewPresenter())->present(['query'=>$query,'result'=>$result,'summaries'=>[new ExpenseOverviewSummary(1,10000,2100,12100,'EUR')],'years'=>[2026],'categories'=>[]],'ajax-expense-refresh','/Flexgrid/AdminExpense/edit');
adminExpenseAssert($view['table']['rows'][0]['url']==='/Flexgrid/AdminExpense/edit/expense-list-1','Uitgavenrij moet naar de editor linken.');adminExpenseAssert($view['table']['rows'][0]['cells']['gross_total']==='€ 121,00','Uitgavenbedrag moet exact en leesbaar worden getoond.');adminExpenseAssert(count($view['summaryCards'])===4,'Uitgavenoverzicht moet vier samenvattingskaarten tonen.');adminExpenseAssert($query->getPerPage()===25&&$query->getCategoryPublicId()==='category-1','Uitgavenquery moet filters en paginering bewaren.');adminExpenseAssertThrows(InvalidArgumentException::class,function(){new ExpenseListQuery('','',null,'onbekend');},'Onbekende sorteerkolom moet worden geweigerd.');
adminExpenseAssert((new ExpenseListQuery('', '', 2026, 'expense_date', 'desc', 1, 10, 3))->getQuarter() === 3, 'Uitgavenquery moet kwartaal bewaren.');
adminExpenseAssertThrows(InvalidArgumentException::class,function(){new ExpenseListQuery('', '', 2026, 'expense_date', 'desc', 1, 10, 0);},'Uitgavenquery moet ongeldig kwartaal weigeren.');
echo "AdminExpense list tests passed.\n";
