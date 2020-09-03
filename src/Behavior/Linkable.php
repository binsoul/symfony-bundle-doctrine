<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Behavior;

interface Linkable
{
    /**
     * Returns the linked object which will be notified about changes.
     */
    public function getLinkedObject(): LinkedObject;
}
