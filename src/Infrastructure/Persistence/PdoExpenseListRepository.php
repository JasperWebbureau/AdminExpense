<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Infrastructure\Persistence;

use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Application\Query\ExpenseListQuery;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseListItem;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseListResult;
use Flexgrid\Modules\AdminExpense\Application\ReadModel\ExpenseOverviewSummary;
use Flexgrid\Modules\AdminExpense\Contract\ExpenseListRepositoryInterface;

final class PdoExpenseListRepository implements ExpenseListRepositoryInterface
{
    private $connection;
    public function __construct(\PDO$connection){$this->connection=$connection;}

    public function search(TenantId$tenantId,ExpenseListQuery$query):ExpenseListResult
    {
        list($where,$parameters)=$this->where($tenantId,$query);$sort=['title'=>'e.`title`','supplier'=>'supplier_name','expense_date'=>'e.`expense_date`','category'=>'category_name','gross_total'=>'e.`gross_amount_minor`'][$query->getSort()];
        $count=$this->connection->prepare('SELECT COUNT(*) FROM `admin_expense` e LEFT JOIN `admin_expense_category` c ON c.`tenant_id`=e.`tenant_id` AND c.`public_id`=e.`category_public_id` '.$where);$count->execute($parameters);$total=(int)$count->fetchColumn();
        $sql='SELECT e.*,COALESCE(c.`name`,\'Onbekend\') AS category_name,COALESCE(NULLIF(c.`reporting_type`,\'\'),\'operating_cost\') AS reporting_type,COALESCE(c.`vat_deductible_percentage`,100) AS vat_percentage,COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.`supplier_snapshot`,\'$.name\')),\'\') AS supplier_name,(SELECT COUNT(*) FROM `admin_expense_attachment` a WHERE a.`tenant_id`=e.`tenant_id` AND a.`expense_public_id`=e.`public_id`) AS attachment_count FROM `admin_expense` e LEFT JOIN `admin_expense_category` c ON c.`tenant_id`=e.`tenant_id` AND c.`public_id`=e.`category_public_id` '.$where.' ORDER BY '.$sort.' '.strtoupper($query->getDirection()).',e.`id` DESC LIMIT '.(int)$query->getPerPage().' OFFSET '.(int)(($query->getPage()-1)*$query->getPerPage());
        $statement=$this->connection->prepare($sql);$statement->execute($parameters);$items=[];
        foreach($statement->fetchAll(\PDO::FETCH_ASSOC)as$row){$items[]=new ExpenseListItem((string)$row['public_id'],(string)$row['title'],(string)$row['supplier_name'],(string)$row['expense_date'],(string)$row['category_name'],(string)($row['reference']??''),(int)$row['gross_amount_minor'],(string)$row['currency'],(int)$row['attachment_count'],(string)$row['reporting_type'],(int)$row['vat_percentage']);}
        return new ExpenseListResult($items,$total,$query->getPage(),$query->getPerPage());
    }

    public function getSummaries(TenantId$tenantId,ExpenseListQuery$query):array
    {
        list($where,$parameters)=$this->where($tenantId,$query);$type="COALESCE(NULLIF(c.`reporting_type`,''),'operating_cost')";$percentage="CASE WHEN ".$type."='operating_cost' THEN COALESCE(c.`vat_deductible_percentage`,100) ELSE 0 END";$statement=$this->connection->prepare('SELECT e.`currency`,COUNT(*) AS expense_count,COALESCE(SUM(CASE WHEN '.$type." IN ('operating_cost','payroll_cost') THEN e.`net_amount_minor`+e.`tax_amount_minor`-ROUND(e.`tax_amount_minor`*(".$percentage.')/100) ELSE 0 END),0) AS net_minor,COALESCE(SUM(CASE WHEN '.$type." IN ('operating_cost','payroll_cost') THEN ROUND(e.`tax_amount_minor`*(".$percentage.')/100) ELSE 0 END),0) AS tax_minor,COALESCE(SUM(e.`gross_amount_minor`),0) AS gross_minor FROM `admin_expense` e LEFT JOIN `admin_expense_category` c ON c.`tenant_id`=e.`tenant_id` AND c.`public_id`=e.`category_public_id` '.$where.' GROUP BY e.`currency` ORDER BY e.`currency`');$statement->execute($parameters);$result=[];
        foreach($statement->fetchAll(\PDO::FETCH_ASSOC)as$row){$result[]=new ExpenseOverviewSummary((int)$row['expense_count'],(int)$row['net_minor'],(int)$row['tax_minor'],(int)$row['gross_minor'],(string)$row['currency']);}return$result;
    }

    public function getYears(TenantId$tenantId):array{$statement=$this->connection->prepare('SELECT DISTINCT LEFT(`expense_date`,4) AS expense_year FROM `admin_expense` WHERE `tenant_id`=:tenant ORDER BY expense_year DESC');$statement->execute([':tenant'=>$tenantId->toString()]);return array_map('intval',$statement->fetchAll(\PDO::FETCH_COLUMN));}

    private function where(TenantId$tenantId,ExpenseListQuery$query):array
    {
        $parts=['e.`tenant_id`=:tenant'];$parameters=[':tenant'=>$tenantId->toString()];
        if($query->getSearch()!==''){$parts[]="CONCAT_WS(' ',e.`title`,COALESCE(e.`description`,''),COALESCE(e.`reference`,''),e.`supplier_snapshot`) LIKE :search";$parameters[':search']='%'.$query->getSearch().'%';}
        if($query->getCategoryPublicId()!==''){$parts[]='e.`category_public_id`=:category';$parameters[':category']=$query->getCategoryPublicId();}
        if($query->getYear()!==null){$parts[]='e.`expense_date` LIKE :year';$parameters[':year']=(string)$query->getYear().'-%';}
        return['WHERE '.implode(' AND ',$parts),$parameters];
    }
}
