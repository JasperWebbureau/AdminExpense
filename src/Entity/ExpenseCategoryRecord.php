<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Entity;

use Repository\RepositoryEntity;

/**
 * @FG\Entity[name=admin_expense_category,repository=Flexgrid\Modules\AdminExpense\Repository\ExpenseCategoryRecordRepository,type=Module,in_menu=false]
 * @FG\Index::tenant_public[columns={tenantId,publicId},unique=true]
 * @FG\Index::tenant_code[columns={tenantId,code},unique=true]
 * @FG\Index::tenant_active_name[columns={tenantId,isActive,name}]
 */
final class ExpenseCategoryRecord extends RepositoryEntity
{
    /** @FG\Column[type=primary] */ protected $id;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $tenantId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $publicId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $code;
    /** @FG\Column[type=varchar,length=128,required=true] */ protected $name;
    /** @FG\Column[type=text] */ protected $description;
    /** @FG\Column[type=tinyint,required=true] */ protected $isActive;
    /** @FG\Column[type=varchar,length=32] */ protected $reportingType;
    /** @FG\Column[type=tinyint] */ protected $vatDeductiblePercentage;
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
     * Get the value of code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set the value of code
     *
     * @param mixed $value
     * @return $this
     */
    public function setCode($value)
    {
        $this->code = $value;
        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @param mixed $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
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
     * Get the value of isActive
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set the value of isActive
     *
     * @param mixed $value
     * @return $this
     */
    public function setIsActive($value)
    {
        $this->isActive = $value;
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



    // --- Auto-generated getters and setters ---

    /**
     * Get the value of reportingType
     */
    public function getReportingType()
    {
        return $this->reportingType;
    }

    /**
     * Set the value of reportingType
     *
     * @param mixed $value
     * @return $this
     */
    public function setReportingType($value)
    {
        $this->reportingType = $value;
        return $this;
    }

    /**
     * Get the value of vatDeductiblePercentage
     */
    public function getVatDeductiblePercentage()
    {
        return $this->vatDeductiblePercentage;
    }

    /**
     * Set the value of vatDeductiblePercentage
     *
     * @param mixed $value
     * @return $this
     */
    public function setVatDeductiblePercentage($value)
    {
        $this->vatDeductiblePercentage = $value;
        return $this;
    }

}
