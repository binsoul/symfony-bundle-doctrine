<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Behavior;

interface LinkedObject
{
    /**
     * Will be called if a linked object is created.
     */
    public function linkedObjectCreated(Linkable $object): void;

    /**
     * Will be called if a linked object is updated.
     */
    public function linkedObjectUpdated(Linkable $object): void;

    /**
     * Will be called if a linked object is deleted.
     */
    public function linkedObjectDeleted(Linkable $object): void;
}
