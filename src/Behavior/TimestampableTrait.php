<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Behavior;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;

trait TimestampableTrait
{
    protected function getTimestamp(): DateTimeInterface
    {
        $dateTime = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));

        if ($dateTime === false) {
            throw new RuntimeException('Could not generate \DateTime.');
        }

        $dateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return $dateTime;
    }
}
