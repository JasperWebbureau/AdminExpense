<?php
declare(strict_types=1);

namespace Flexgrid\Modules\AdminExpense\Entity;

use Repository\RepositoryEntity;

/**
 * @FG\Entity[name=admin_expense_attachment,repository=Flexgrid\Modules\AdminExpense\Repository\ExpenseAttachmentRecordRepository,type=Module,in_menu=false]
 * @FG\Index::tenant_public[columns={tenantId,publicId},unique=true]
 * @FG\Index::tenant_expense_sha[columns={tenantId,expensePublicId,sha256},unique=true]
 * @FG\Index::tenant_expense[columns={tenantId,expensePublicId}]
 */
final class ExpenseAttachmentRecord extends RepositoryEntity
{
    /** @FG\Column[type=primary] */ protected $id;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $tenantId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $publicId;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $expensePublicId;
    /** @FG\Column[type=varchar,length=255,required=true] */ protected $fileReference;
    /** @FG\Column[type=varchar,length=255,required=true] */ protected $originalName;
    /** @FG\Column[type=varchar,length=128,required=true] */ protected $mediaType;
    /** @FG\Column[type=bigint,required=true] */ protected $sizeBytes;
    /** @FG\Column[type=varchar,length=64,required=true] */ protected $sha256;
    /** @FG\Column[type=bigint,required=true] */ protected $createdAt;


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
     * Get the value of expensePublicId
     */
    public function getExpensePublicId()
    {
        return $this->expensePublicId;
    }

    /**
     * Set the value of expensePublicId
     *
     * @param mixed $value
     * @return $this
     */
    public function setExpensePublicId($value)
    {
        $this->expensePublicId = $value;
        return $this;
    }

    /**
     * Get the value of fileReference
     */
    public function getFileReference()
    {
        return $this->fileReference;
    }

    /**
     * Set the value of fileReference
     *
     * @param mixed $value
     * @return $this
     */
    public function setFileReference($value)
    {
        $this->fileReference = $value;
        return $this;
    }

    /**
     * Get the value of originalName
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Set the value of originalName
     *
     * @param mixed $value
     * @return $this
     */
    public function setOriginalName($value)
    {
        $this->originalName = $value;
        return $this;
    }

    /**
     * Get the value of mediaType
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    /**
     * Set the value of mediaType
     *
     * @param mixed $value
     * @return $this
     */
    public function setMediaType($value)
    {
        $this->mediaType = $value;
        return $this;
    }

    /**
     * Get the value of sizeBytes
     */
    public function getSizeBytes()
    {
        return $this->sizeBytes;
    }

    /**
     * Set the value of sizeBytes
     *
     * @param mixed $value
     * @return $this
     */
    public function setSizeBytes($value)
    {
        $this->sizeBytes = $value;
        return $this;
    }

    /**
     * Get the value of sha256
     */
    public function getSha256()
    {
        return $this->sha256;
    }

    /**
     * Set the value of sha256
     *
     * @param mixed $value
     * @return $this
     */
    public function setSha256($value)
    {
        $this->sha256 = $value;
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
