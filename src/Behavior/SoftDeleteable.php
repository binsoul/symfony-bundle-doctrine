<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Behavior;

use DateTimeInterface;

interface SoftDeleteable
{
    /**
     * Returns the date and time of the deletion of this object.
     */
    public function getDeletedAt(): ?DateTimeInterface;

    /**
     * Sets the date and time of the deletion of this object.
     */
    public function setDeletedAt(?DateTimeInterface $deletedAt): void;

    /**
     * Will be called if the object is deleted.
     */
    public function deleteSoft(): void;
}
