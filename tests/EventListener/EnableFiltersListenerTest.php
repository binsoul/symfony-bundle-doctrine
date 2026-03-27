<?php

declare(strict_types=1);

namespace BinSoul\Test\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\EventListener\EnableFiltersListener;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class EnableFiltersListenerTest extends TestCase
{
    /**
     * Test that the expected events are subscribed.
     */
    public function test_get_subscribed_events(): void
    {
        $events = EnableFiltersListener::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
    }

    /**
     * Test that filters are registered and enabled on kernel request.
     */
    public function test_on_kernel_request_enables_filters(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->atLeastOnce())->method('addFilter');

        $filterCollection = $this->createMock(FilterCollection::class);
        $filterCollection->expects($this->atLeastOnce())->method('enable');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);
        $entityManager->method('getFilters')->willReturn($filterCollection);

        $listener = new EnableFiltersListener($entityManager);
        $listener->onKernelRequest();
    }
}
