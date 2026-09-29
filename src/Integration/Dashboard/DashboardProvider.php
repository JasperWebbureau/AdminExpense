<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Integration\Dashboard;

use Flexgrid\Modules\AdminDashboard\Contract\DashboardProviderInterface;
use Flexgrid\Modules\AdminDashboard\Provider\AbstractPdoDashboardProvider;

final class DashboardProvider extends AbstractPdoDashboardProvider implements DashboardProviderInterface
{
    public function getDashboardContribution(): array
    {
        $tenant = $this->tenant->toString();
        $monthStatement = $this->connection->prepare("SELECT e.`currency`,COUNT(*) expense_count,COALESCE(SUM(e.`net_amount_minor`+e.`tax_amount_minor`-ROUND(e.`tax_amount_minor`*(CASE WHEN COALESCE(NULLIF(c.`reporting_type`,''),'operating_cost')='operating_cost' THEN COALESCE(c.`vat_deductible_percentage`,100) ELSE 0 END)/100)),0) total_minor FROM `admin_expense` e LEFT JOIN `admin_expense_category` c ON c.`tenant_id`=e.`tenant_id` AND c.`public_id`=e.`category_public_id` WHERE e.`tenant_id`=:tenant AND e.`expense_date` LIKE :month AND COALESCE(NULLIF(c.`reporting_type`,''),'operating_cost') IN ('operating_cost','payroll_cost') GROUP BY e.`currency` ORDER BY e.`currency`");
        $monthStatement->execute([':tenant' => $tenant, ':month' => $this->clock->format('Y-m') . '-%']);
        $monthRows = $monthStatement->fetchAll(\PDO::FETCH_ASSOC);
        $monthCount = array_sum(array_map(function (array $row): int { return (int)$row['expense_count']; }, $monthRows));

        $missingStatement = $this->connection->prepare("SELECT COUNT(*) FROM `admin_expense` e LEFT JOIN `admin_expense_category` c ON c.`tenant_id`=e.`tenant_id` AND c.`public_id`=e.`category_public_id` WHERE e.`tenant_id`=:tenant AND COALESCE(NULLIF(c.`reporting_type`,''),'operating_cost') IN ('operating_cost','payroll_cost') AND NOT EXISTS (SELECT 1 FROM `admin_expense_attachment` a WHERE a.`tenant_id`=e.`tenant_id` AND a.`expense_public_id`=e.`public_id`)");
        $missingStatement->execute([':tenant' => $tenant]);
        $missingCount = (int)$missingStatement->fetchColumn();

        $recent = $this->connection->prepare("SELECT `public_id`,`title`,`supplier_snapshot`,`gross_amount_minor`,`currency`,`created_at` FROM `admin_expense` WHERE `tenant_id`=:tenant ORDER BY `created_at` DESC,`id` DESC LIMIT 2");
        $recent->execute([':tenant' => $tenant]);
        $activities = [];
        foreach ($recent->fetchAll(\PDO::FETCH_ASSOC) as $expense) {
            $activities[] = ['id' => 'expense-' . $expense['public_id'], 'title' => 'Uitgave toegevoegd', 'description' => (string)$expense['title'] . ' · ' . $this->money((int)$expense['gross_amount_minor'], (string)$expense['currency']), 'icon' => 'fas fa-receipt', 'tone' => 'warning', 'href' => $this->url('AdminExpense', 'edit/' . rawurlencode((string)$expense['public_id'])), 'timestamp' => (int)$expense['created_at'], 'time' => $this->activityTime((int)$expense['created_at'])];
        }

        return [
            'activities' => $activities,
            'quick_actions' => [['id' => 'create-expense', 'label' => 'Nieuwe uitgave', 'icon' => 'fas fa-receipt', 'tone' => 'warning', 'href' => $this->url('AdminExpense', 'create'), 'priority' => 40]],
            'attention' => $missingCount > 0 ? [['id' => 'expenses-without-attachment', 'title' => $missingCount . ' uitgave' . ($missingCount === 1 ? '' : 'n') . ' zonder bewijsstuk', 'description' => 'Voeg de ontbrekende bon of factuur toe.', 'icon' => 'fas fa-paperclip', 'tone' => 'warning', 'badge' => 'Aanvullen', 'badge_tone' => 'neutral', 'href' => $this->url('AdminExpense', 'expenses'), 'priority' => 40]] : [],
            'modules' => [['id' => 'admin-expense', 'title' => 'Uitgaven', 'description' => $monthCount . ' kostenboekingen · ' . $this->aggregateMoney($monthRows, 'total_minor'), 'icon' => 'fas fa-receipt', 'status' => 'Actief', 'tone' => 'warning', 'href' => $this->url('AdminExpense', 'expenses'), 'priority' => 50]],
        ];
    }
}
