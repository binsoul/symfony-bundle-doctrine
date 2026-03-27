<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\Behavior {
    /**
     * Override number_format to allow triggering failures in DateTime::createFromFormat.
     */
    function number_format(float $num, int $decimals = 0, ?string $decimal_separator = '.', ?string $thousands_separator = ','): string
    {
        return \BinSoul\Test\Symfony\Bundle\Doctrine\Behavior\TimestampableTraitTestHelper::getNumberFormat($num, $decimals, $decimal_separator, $thousands_separator);
    }

    /**
     * Override date_default_timezone_get to allow testing invalid timezones.
     */
    function date_default_timezone_get(): string
    {
        return \BinSoul\Test\Symfony\Bundle\Doctrine\Behavior\TimestampableTraitTestHelper::getTimezone();
    }
}

namespace BinSoul\Test\Symfony\Bundle\Doctrine\Behavior {
    use BinSoul\Symfony\Bundle\Doctrine\Behavior\TimestampableTrait;
    use PHPUnit\Framework\TestCase;

    class TimestampableTraitTest extends TestCase
    {
        protected function tearDown(): void
        {
            TimestampableTraitTestHelper::reset();
        }

        /**
         * Test if the timestamp is correctly generated.
         */
        public function test_get_timestamp_returns_datetime(): void
        {
            $subject = new TimestampableEntity();
            $timestamp = $subject->callGetTimestamp();
            $this->assertInstanceOf(\DateTimeInterface::class, $timestamp);

            // Compare with current time (tolerance 1 second)
            $now = new \DateTime();
            $this->assertLessThanOrEqual(1, abs($timestamp->getTimestamp() - $now->getTimestamp()));
            $this->assertEquals(\date_default_timezone_get(), $timestamp->getTimezone()->getName());
        }

        /**
         * Test that a configured timezone is correctly used.
         */
        public function test_get_timestamp_uses_configured_timezone(): void
        {
            $timezone = 'Indian/Mauritius';
            TimestampableTraitTestHelper::$timezoneOverride = $timezone;

            $subject = new TimestampableEntity();
            $timestamp = $subject->callGetTimestamp();
            $this->assertSame($timezone, $timestamp->getTimezone()->getName());
        }

        /**
         * Test that an invalid timezone triggers the UTC fallback.
         */
        public function test_get_timestamp_uses_utc_on_invalid_timezone(): void
        {
            TimestampableTraitTestHelper::$timezoneOverride = 'Invalid/Timezone';

            $subject = new TimestampableEntity();
            $timestamp = $subject->callGetTimestamp();
            $this->assertSame('UTC', $timestamp->getTimezone()->getName());
        }

        /**
         * Test that a RuntimeException is thrown if DateTime creation fails.
         */
        public function test_get_timestamp_throws_on_create_from_format_failure(): void
        {
            TimestampableTraitTestHelper::$numberFormatOverride = 'invalid-format';

            $subject = new TimestampableEntity();
            $this->expectException(\RuntimeException::class);
            $subject->callGetTimestamp();
        }
    }

    /**
     * Helper class to manage function overrides.
     */
    class TimestampableTraitTestHelper
    {
        public static ?string $numberFormatOverride = null;

        public static ?string $timezoneOverride = null;

        /**
         * Returns the overridden number format or calls the PHP default.
         */
        public static function getNumberFormat(float $num, int $decimals = 0, ?string $decimal_separator = '.', ?string $thousands_separator = ','): string
        {
            return self::$numberFormatOverride ?? \number_format($num, $decimals, $decimal_separator, $thousands_separator);
        }

        /**
         * Returns the overridden timezone or calls the PHP default.
         */
        public static function getTimezone(): string
        {
            return self::$timezoneOverride ?? \date_default_timezone_get();
        }

        public static function reset(): void
        {
            self::$numberFormatOverride = null;
            self::$timezoneOverride = null;
        }
    }

    /**
     * Concrete class using the trait for testing.
     */
    class TimestampableEntity
    {
        use TimestampableTrait;

        public function callGetTimestamp(): \DateTimeInterface
        {
            return $this->getTimestamp();
        }
    }
}
