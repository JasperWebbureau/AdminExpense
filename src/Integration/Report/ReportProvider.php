<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Integration\Report;

use Flexgrid\Database\Connection;
use Flexgrid\Modules\AdminCore\ValueObject\TenantId;
use Flexgrid\Modules\AdminExpense\Domain\Model\ExpenseCategory;
use Flexgrid\Modules\AdminReport\Contract\ReportProviderInterface;
use Flexgrid\Modules\AdminReport\Contract\ReportPeriodListProviderInterface;
use Flexgrid\Modules\AdminReport\ValueObject\ReportPeriod;

final class ReportProvider implements ReportProviderInterface, ReportPeriodListProviderInterface
{
    private$tenant,$connection;
    public function __construct(TenantId$tenant,?\PDO$connection=null){$this->tenant=$tenant;$this->connection=$connection?:Connection::getConnections();}

    public function getReportContribution(ReportPeriod$period, bool $includeTransactions = true):array
    {
        $statement=$this->connection->prepare("SELECT e.`public_id`,e.`expense_date`,e.`title`,e.`supplier_snapshot`,e.`net_amount_minor`,e.`tax_amount_minor`,e.`gross_amount_minor`,e.`currency`,COALESCE(c.`name`,'Overig') category_name,COALESCE(NULLIF(c.`reporting_type`,''),'operating_cost') reporting_type,COALESCE(c.`vat_deductible_percentage`,100) vat_percentage FROM `admin_expense` e LEFT JOIN `admin_expense_category` c ON c.`tenant_id`=e.`tenant_id` AND c.`public_id`=e.`category_public_id` WHERE e.`tenant_id`=:tenant AND e.`expense_date` BETWEEN :start AND :end ORDER BY e.`expense_date` DESC,e.`id` DESC");
        $statement->execute([':tenant'=>$this->tenant->toString(),':start'=>$period->getStartDate(),':end'=>$period->getEndDate()]);
        $currencies=[];$transactions=[];
        foreach($statement->fetchAll(\PDO::FETCH_ASSOC)as$row){
            $currency=(string)$row['currency'];$month=substr((string)$row['expense_date'],0,7);if(!isset($currencies[$currency])){$currencies[$currency]=$this->emptyCurrency();}if(!isset($currencies[$currency]['months'][$month])){$currencies[$currency]['months'][$month]=$this->emptyMetrics();}
            $type=$this->type((string)$row['reporting_type']);$percentage=$type===ExpenseCategory::OPERATING_COST?max(0,min(100,(int)$row['vat_percentage'])):0;$tax=(int)$row['tax_amount_minor'];$deductibleTax=$this->percentage($tax,$percentage);$affectsResult=in_array($type,[ExpenseCategory::OPERATING_COST,ExpenseCategory::PAYROLL_COST],true);$amount=$affectsResult?(int)$row['net_amount_minor']+$tax-$deductibleTax:(int)$row['gross_amount_minor'];$category=(string)$row['category_name'];
            if($affectsResult){$currencies[$currency]['costs_net_minor']+=$amount;$currencies[$currency]['input_tax_minor']+=$deductibleTax;$currencies[$currency]['months'][$month]['costs_net_minor']+=$amount;$currencies[$currency]['months'][$month]['input_tax_minor']+=$deductibleTax;if($type===ExpenseCategory::PAYROLL_COST){$currencies[$currency]['payroll_net_minor']+=$amount;$currencies[$currency]['months'][$month]['payroll_net_minor']+=$amount;}else{$currencies[$currency]['categories'][$category]=($currencies[$currency]['categories'][$category]??0)+$amount;}}
            else{$currencies[$currency]['excluded_minor']+=$amount;$currencies[$currency]['months'][$month]['excluded_minor']+=$amount;$key=$this->label($type).' · '.$category;$currencies[$currency]['excluded_categories'][$key]=($currencies[$currency]['excluded_categories'][$key]??0)+$amount;}
            if($includeTransactions&&count($transactions)<20){$transactions[]=['id'=>'expense-'.$row['public_id'],'date'=>(string)$row['expense_date'],'title'=>(string)$row['title'],'description'=>$this->name($row['supplier_snapshot']),'type'=>$this->label($type),'tone'=>$affectsResult?'danger':'neutral','amount_minor'=>$amount,'net_minor'=>$amount,'tax_minor'=>$deductibleTax,'currency'=>$currency,'href'=>$this->url((string)$row['public_id']),'timestamp'=>(int)strtotime((string)$row['expense_date'].' 12:00:00'),'affects_result'=>$affectsResult,'reporting_label'=>$affectsResult?'Telt mee':'Niet in resultaat'];}
        }
        $years=$this->connection->prepare("SELECT DISTINCT LEFT(`expense_date`,4) report_year FROM `admin_expense` WHERE `tenant_id`=:tenant ORDER BY report_year DESC");$years->execute([':tenant'=>$this->tenant->toString()]);
        return['currencies'=>$currencies,'transactions'=>$transactions,'years'=>array_map('intval',$years->fetchAll(\PDO::FETCH_COLUMN))];
    }

    public function getSummaryContribution(ReportPeriod $period): array
    {
        return $this->getReportContribution($period, false);
    }

    private function emptyMetrics():array{return['revenue_net_minor'=>0,'costs_net_minor'=>0,'payroll_net_minor'=>0,'output_tax_minor'=>0,'input_tax_minor'=>0,'excluded_minor'=>0];}

    public function getPeriodList(ReportPeriod $period, string $kind): array
    {
        if ($kind !== 'bookings') {
            return [];
        }
        $statement = $this->connection->prepare("SELECT e.`public_id`,e.`expense_date`,e.`title`,e.`supplier_snapshot`,e.`net_amount_minor`,e.`tax_amount_minor`,e.`gross_amount_minor`,e.`currency`,COALESCE(c.`name`,'Overig') category_name,COALESCE(NULLIF(c.`reporting_type`,''),'operating_cost') reporting_type,COALESCE(c.`vat_deductible_percentage`,100) vat_percentage FROM `admin_expense` e LEFT JOIN `admin_expense_category` c ON c.`tenant_id`=e.`tenant_id` AND c.`public_id`=e.`category_public_id` WHERE e.`tenant_id`=:tenant AND e.`expense_date` BETWEEN :start AND :end ORDER BY e.`expense_date` DESC,e.`id` DESC");
        $statement->execute([':tenant' => $this->tenant->toString(), ':start' => $period->getStartDate(), ':end' => $period->getEndDate()]);
        $rows = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $type = $this->type((string)$row['reporting_type']);
            $affectsResult = in_array($type, [ExpenseCategory::OPERATING_COST, ExpenseCategory::PAYROLL_COST], true);
            $deductibleTax = $type === ExpenseCategory::OPERATING_COST
                ? $this->percentage((int)$row['tax_amount_minor'], max(0, min(100, (int)$row['vat_percentage'])))
                : 0;
            $amount = $affectsResult
                ? (int)$row['net_amount_minor'] + (int)$row['tax_amount_minor'] - $deductibleTax
                : (int)$row['gross_amount_minor'];
            $rows[] = [
                'id' => 'expense-' . $row['public_id'],
                'date' => (string)$row['expense_date'],
                'title' => (string)$row['title'],
                'description' => $this->name($row['supplier_snapshot']),
                'type' => $this->label($type),
                'tone' => $affectsResult ? 'danger' : 'neutral',
                'amount_minor' => -abs($amount),
                'tax_minor' => $deductibleTax,
                'currency' => (string)$row['currency'],
                'href' => $this->url((string)$row['public_id']),
                'reporting_label' => $affectsResult ? 'Telt mee' : 'Niet in resultaat',
            ];
        }
        return $rows;
    }
    private function emptyCurrency():array{return$this->emptyMetrics()+['months'=>[],'categories'=>[],'excluded_categories'=>[]];}
    private function type(string$type):string{return in_array($type,ExpenseCategory::reportingTypes(),true)?$type:ExpenseCategory::OPERATING_COST;}
    private function label(string$type):string{return[ExpenseCategory::OPERATING_COST=>'Zakelijke kosten',ExpenseCategory::PAYROLL_COST=>'Loonkosten',ExpenseCategory::PRIVATE_WITHDRAWAL=>'Privéopname',ExpenseCategory::BALANCE_SHEET=>'Balansbeweging'][$type]??'Zakelijke kosten';}
    private function percentage(int$amount,int$percentage):int{$scaled=$amount*$percentage;return$scaled>=0?intdiv($scaled+50,100):-intdiv(abs($scaled)+50,100);}
    private function name($snapshot):string{$data=json_decode((string)$snapshot,true);return is_array($data)&&trim((string)($data['name']??''))!==''?trim((string)$data['name']):'Onbekende leverancier';}
    private function url(string$id):string{$domain=defined('__DOMAIN__')?rtrim((string)constant('__DOMAIN__'),'/'):'';return$domain.'/Flexgrid/AdminExpense/edit/'.rawurlencode($id);}
}
