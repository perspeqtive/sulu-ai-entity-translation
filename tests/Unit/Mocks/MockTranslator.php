<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Tests\Unit\Mocks;

use LogicException;
use Sulu\Bundle\AiBundle\Expert\Translator\TranslatorInterface;
use Sulu\Bundle\AiBundle\Expert\Translator\TranslatorResponse;

final class MockTranslator implements TranslatorInterface
{
    public function translate(
        string $translateId,
        array $texts,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        bool $html = true,
        ?string $webspaceKey = null,
    ): TranslatorResponse {
        throw new LogicException('The unit tests never reach the translator.');
    }
}
