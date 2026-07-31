<?php

declare(strict_types=1);

namespace PERSPEQTIVE\SuluAiEntityTranslationBundle\Content;

/**
 * Exposes the template type of an adapted content.
 *
 * Sulu's ContentAdapterInterface does not carry it, because its own implementations know their
 * template type statically. For custom entities it has to be read from the dimension content,
 * and Sulu AI needs it to look up the form metadata that describes the translatable fields.
 */
interface TemplateTypeAwareInterface
{
    public function getTemplateType(): string;
}
