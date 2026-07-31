<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Domain\Event;

use PERSPEQTIVE\SuluAiEntityTranslationBundle\Domain\Event\TranslationFailedEvent;
use PHPUnit\Framework\TestCase;

class TranslationFailedEventTest extends TestCase
{
    public function testDescribesTheFailedTranslation(): void
    {
        $event = new TranslationFailedEvent('glossary', '42', 'en', 'Glossary term', 'Quota exceeded');

        self::assertSame('ai_translation_failed', $event->getEventType());
        self::assertSame('glossary', $event->getResourceKey());
        self::assertSame('42', $event->getResourceId());
        self::assertSame('en', $event->getResourceLocale());
        self::assertSame('Glossary term', $event->getResourceTitle());
    }

    public function testCarriesTheFailureReasonAsContext(): void
    {
        $event = new TranslationFailedEvent('glossary', '42', 'en', null, 'Quota exceeded');

        self::assertSame(['reason' => 'Quota exceeded'], $event->getEventContext());
    }
}
