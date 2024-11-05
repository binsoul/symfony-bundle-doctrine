<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Behavior;

use DateTimeInterface;

interface Archivable
{
    /**
     * Returns the date and time of the archival of this object.
     */
    public function getArchivedAt(): ?DateTimeInterface;

    /**
     * Sets the date and time of the archival of this object.
     */
    public function setArchivedAt(?DateTimeInterface $archivedAt): void;

    /**
     * Will be called if the object is archived.
     */
    public function archive(): void;
}
