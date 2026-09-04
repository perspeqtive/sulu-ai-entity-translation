<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks;

use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

final class MockDomainEventDispatcher implements DomainEventDispatcherInterface
{
    /**
     * @var list<DomainEvent>
     */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): DomainEvent
    {
        $this->dispatched[] = $event;

        return $event;
    }
}
