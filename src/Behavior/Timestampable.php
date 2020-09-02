<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Behavior;

interface Timestampable
{
    public function updateTimestamps(): void;
}
