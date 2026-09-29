<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Entity;

use Repository\RepositoryEntity;

/**
 * @FG\Entity[name=admin_expense,repository=Flexgrid\Modules\AdminExpense\Repository\ExpenseRecordRepository,type=Module,in_menu=false]
 * @FG\Index::tenant_public[columns={tenantId,publicId},unique=true]
 * @FG\Index::tenant_source_external[columns={tenantId,source,externalId},unique=true]
 * @FG\Index::tenant_date[columns={tenantId,expenseDate}]
 * @FG\Index::tenant_category_date[columns={tenantId,categoryPublicId,expenseDate}]
 */
final class ExpenseRecord extends RepositoryEntity
{
    /** @FG\Column[type=primary] */ protected $id;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $tenantId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $publicId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $categoryPublicId;
    /** @FG\Column[type=varchar,length=3,required=true] */ protected $currency;
    /** @FG\Column[type=varchar,length=10,required=true] */ protected $expenseDate;
    /** @FG\Column[type=varchar,length=255,required=true] */ protected $title;
    /** @FG\Column[type=text] */ protected $description;
    /** @FG\Column[type=varchar,length=128] */ protected $reference;
    /** @FG\Column[type=text,required=true] */ protected $supplierSnapshot;
    /** @FG\Column[type=bigint,required=true] */ protected $netAmountMinor;
    /** @FG\Column[type=bigint,required=true] */ protected $taxAmountMinor;
    /** @FG\Column[type=bigint,required=true] */ protected $grossAmountMinor;
    /** @FG\Column[type=varchar,length=64] */ protected $source;
    /** @FG\Column[type=varchar,length=128] */ protected $externalId;
    /** @FG\Column[type=bigint,required=true] */ protected $createdAt;
    /** @FG\Column[type=bigint,required=true] */ protected $updatedAt;


    // --- Auto-generated getters and setters ---

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @param mixed $value
     * @return $this
     */
    public function setId($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Get the value of tenantId
     */
    public function getTenantId()
    {
        return $this->tenantId;
    }

    /**
     * Set the value of tenantId
     *
     * @param mixed $value
     * @return $this
     */
    public function setTenantId($value)
    {
        $this->tenantId = $value;
        return $this;
    }

    /**
     * Get the value of publicId
     */
    public function getPublicId()
    {
        return $this->publicId;
    }

    /**
     * Set the value of publicId
     *
     * @param mixed $value
     * @return $this
     */
    public function setPublicId($value)
    {
        $this->publicId = $value;
        return $this;
    }

    /**
     * Get the value of categoryPublicId
     */
    public function getCategoryPublicId()
    {
        return $this->categoryPublicId;
    }

    /**
     * Set the value of categoryPublicId
     *
     * @param mixed $value
     * @return $this
     */
    public function setCategoryPublicId($value)
    {
        $this->categoryPublicId = $value;
        return $this;
    }

    /**
     * Get the value of currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set the value of currency
     *
     * @param mixed $value
     * @return $this
     */
    public function setCurrency($value)
    {
        $this->currency = $value;
        return $this;
    }

    /**
     * Get the value of expenseDate
     */
    public function getExpenseDate()
    {
        return $this->expenseDate;
    }

    /**
     * Set the value of expenseDate
     *
     * @param mixed $value
     * @return $this
     */
    public function setExpenseDate($value)
    {
        $this->expenseDate = $value;
        return $this;
    }

    /**
     * Get the value of title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @param mixed $value
     * @return $this
     */
    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * Get the value of description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of description
     *
     * @param mixed $value
     * @return $this
     */
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * Get the value of reference
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set the value of reference
     *
     * @param mixed $value
     * @return $this
     */
    public function setReference($value)
    {
        $this->reference = $value;
        return $this;
    }

    /**
     * Get the value of supplierSnapshot
     */
    public function getSupplierSnapshot()
    {
        return $this->supplierSnapshot;
    }

    /**
     * Set the value of supplierSnapshot
     *
     * @param mixed $value
     * @return $this
     */
    public function setSupplierSnapshot($value)
    {
        $this->supplierSnapshot = $value;
        return $this;
    }

    /**
     * Get the value of netAmountMinor
     */
    public function getNetAmountMinor()
    {
        return $this->netAmountMinor;
    }

    /**
     * Set the value of netAmountMinor
     *
     * @param mixed $value
     * @return $this
     */
    public function setNetAmountMinor($value)
    {
        $this->netAmountMinor = $value;
        return $this;
    }

    /**
     * Get the value of taxAmountMinor
     */
    public function getTaxAmountMinor()
    {
        return $this->taxAmountMinor;
    }

    /**
     * Set the value of taxAmountMinor
     *
     * @param mixed $value
     * @return $this
     */
    public function setTaxAmountMinor($value)
    {
        $this->taxAmountMinor = $value;
        return $this;
    }

    /**
     * Get the value of grossAmountMinor
     */
    public function getGrossAmountMinor()
    {
        return $this->grossAmountMinor;
    }

    /**
     * Set the value of grossAmountMinor
     *
     * @param mixed $value
     * @return $this
     */
    public function setGrossAmountMinor($value)
    {
        $this->grossAmountMinor = $value;
        return $this;
    }

    /**
     * Get the value of source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the value of source
     *
     * @param mixed $value
     * @return $this
     */
    public function setSource($value)
    {
        $this->source = $value;
        return $this;
    }

    /**
     * Get the value of externalId
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set the value of externalId
     *
     * @param mixed $value
     * @return $this
     */
    public function setExternalId($value)
    {
        $this->externalId = $value;
        return $this;
    }

    /**
     * Get the value of createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt
     *
     * @param mixed $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->createdAt = $value;
        return $this;
    }

    /**
     * Get the value of updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the value of updatedAt
     *
     * @param mixed $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        $this->updatedAt = $value;
        return $this;
    }

    /**
     * Get the value of makeTime
     */
    public function getMakeTime()
    {
        return $this->makeTime;
    }

    /**
     * Set the value of makeTime
     *
     * @param mixed $value
     * @return $this
     */
    public function setMakeTime($value)
    {
        $this->makeTime = $value;
        return $this;
    }

}
