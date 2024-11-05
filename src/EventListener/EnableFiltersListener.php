<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Doctrine\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\Filter\ArchivableFilter;
use BinSoul\Symfony\Bundle\Doctrine\Filter\SoftDeleteableFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class EnableFiltersListener implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    /**
     * Constructs an instance of this class.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 160],
        ];
    }

    public function onKernelRequest(): void
    {
        $em = $this->entityManager;

        $em->getConfiguration()->addFilter('binsoul-softdeleteable', SoftDeleteableFilter::class);
        $em->getConfiguration()->addFilter('binsoul-archivable', ArchivableFilter::class);

        $em->getFilters()->enable('binsoul-softdeleteable');
        $em->getFilters()->enable('binsoul-archivable');
    }
}
