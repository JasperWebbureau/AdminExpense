<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Application\Query;

final class ExpenseListQuery
{
    private const SORTS = ['title', 'supplier', 'expense_date', 'category', 'gross_total'];
    private const PAGE_SIZES = [10, 25, 50];
    private $search, $categoryPublicId, $year, $sort, $direction, $page, $perPage, $quarter;

    public function __construct(string $search = '', string $categoryPublicId = '', ?int $year = null, string $sort = 'expense_date', string $direction = 'desc', int $page = 1, int $perPage = 10, ?int $quarter = null)
    {
        $search = trim($search); $categoryPublicId = trim($categoryPublicId); $sort = strtolower(trim($sort)); $direction = strtolower(trim($direction));
        if (strlen($search) > 120) { throw new \InvalidArgumentException('Zoekterm mag maximaal 120 tekens bevatten.'); }
        if (strlen($categoryPublicId) > 64) { throw new \InvalidArgumentException('Ongeldige categorie-id.'); }
        if ($year !== null && ($year < 1900 || $year > 2200)) { throw new \InvalidArgumentException('Ongeldig uitgavenjaar.'); }
        if ($quarter !== null && ($quarter < 1 || $quarter > 4 || $year === null)) { throw new \InvalidArgumentException('Ongeldig uitgavenkwartaal.'); }
        if (!in_array($sort, self::SORTS, true) || !in_array($direction, ['asc', 'desc'], true)) { throw new \InvalidArgumentException('Ongeldige sortering.'); }
        if ($page < 1 || !in_array($perPage, self::PAGE_SIZES, true)) { throw new \InvalidArgumentException('Ongeldige paginering.'); }
        $this->search = $search; $this->categoryPublicId = $categoryPublicId; $this->year = $year; $this->sort = $sort; $this->direction = $direction; $this->page = $page; $this->perPage = $perPage; $this->quarter = $quarter;
    }

    public function getSearch(): string { return $this->search; }
    public function getCategoryPublicId(): string { return $this->categoryPublicId; }
    public function getYear(): ?int { return $this->year; }
    public function getQuarter(): ?int { return $this->quarter; }
    public function getSort(): string { return $this->sort; }
    public function getDirection(): string { return $this->direction; }
    public function getPage(): int { return $this->page; }
    public function getPerPage(): int { return $this->perPage; }
    public static function sorts(): array { return self::SORTS; }
    public static function pageSizes(): array { return self::PAGE_SIZES; }
}
