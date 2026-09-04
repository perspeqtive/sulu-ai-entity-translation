# Sulu AI Entity Translation Bundle

Enables Sulu AI's *full content translation* for **custom Sulu entities**.

Sulu's `sulu/ai-platform-bundle` translates content when an editor copies a locale and ticks the
translate checkbox — but only for pages, articles and snippets. The checkbox is added to the core
`copy_locale` form for every resource, so it also appears for custom entities, where nothing
happens.

Sulu documents how to close that gap: extend `AbstractFullContentTranslationSubscriber`, write a
`ContentAdapterInterface` implementation, add a repository, register the service — **per entity**.
This bundle does it once, generically. Install it and every custom entity built on
`sulu/content-bundle` is translated on copy-locale, with no per-entity code.

## Requirements

- PHP 8.2+
- Sulu 2.6 or 3.x
- A Sulu AI subscription. `sulu/ai-platform-bundle` is proprietary and distributed through Sulu's
  private Composer endpoint.

## Installation

Configure Sulu's private Composer repository first (see the Sulu AI documentation), then:

```bash
composer require perspeqtive/sulu-ai-entity-translation
```

Register the bundles in `config/bundles.php`. **The platform bundle has to come before the AI
bundle**, otherwise booting fails with an explicit error:

```php
return [
    /* ... */
    Symfony\AI\AiBundle\AiBundle::class => ['all' => true],
    Sulu\Bundle\AiPlatformBundle\SuluAiPlatformBundle::class => ['all' => true],
    Sulu\Bundle\AiBundle\SuluAiBundle::class => ['all' => true],
    PERSPEQTIVE\SuluAiEntityTranslationBundle\SuluAiEntityTranslationBundle::class => ['all' => true],
];
```

## What your entity has to provide

Almost nothing. Entities are discovered from Doctrine metadata: anything implementing
`ContentRichEntityInterface` with an association to a dimension content is picked up
automatically. No configuration, no interface to implement, no tag in the template.

There is exactly one requirement, and it is something a well behaved Sulu entity does anyway:

**The copy-locale action must collect a domain event whose `getResourceLocale()` returns the
target locale.**

`DomainEvent` already forces `getResourceKey()` and `getResourceId()` — they are abstract.
`getResourceLocale()` is concrete and returns `null` by default, so your event has to override it:

```php
class GlossaryTranslationCopiedEvent extends DomainEvent
{
    public function __construct(
        private readonly Glossary $glossary,
        private readonly string $locale,       // the destination locale
        private readonly string $sourceLocale,
        /* ... */
    ) {
        parent::__construct();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getEventContext(): array
    {
        return ['sourceLocale' => $this->sourceLocale];
    }
}
```

The source locale is optional: when `getEventContext()['sourceLocale']` is absent, the bundle
falls back to the `src` query parameter that Sulu's admin sends.

Your controller collects it as usual:

```php
$this->domainEventCollector->collect(
    new GlossaryTranslationCopiedEvent($glossary, $destLocale, $srcLocale, /* ... */),
);
```

Nothing else changes. Your entity keeps no reference to this bundle.

## Which fields get translated

Derived from the entity's form metadata, using Sulu's own logic:

| Property type | Treated as |
| --- | --- |
| `text_line`, `text_area` | plain text |
| `text_editor` | HTML |
| blocks | traversed recursively, including global blocks |
| everything else | left untouched |

## Pages, articles and snippets

Those three are translated by `sulu/ai-platform-bundle` itself and this bundle leaves them alone.
It matters on Sulu 3, where they are Doctrine entities implementing `ContentRichEntityInterface`
and therefore indistinguishable from a custom entity to any generic discovery. Translating them
again would cost a second platform request per copy-locale and replace the resource locator Sulu
generated from the `route_schema` with a plain slug.

The list comes from the `sulu_ai_platform.built_in_resource_keys` parameter, so it stays correct
when Sulu AI grows another built-in resource.

## Behaviour on failure

If the translation service fails, the locale copy is still created and persisted. The failure is
logged and recorded in Sulu's activity log as an `ai_translation_failed` event. The editor sees
the untranslated copy and can translate manually.

The one exception is a failure that leaves the EntityManager closed, a failed flush being the
usual cause: writing the activity log needs an open one, so only the log entry is written. The
copy still survives, because Sulu AI reports the failure instead of letting it escape the
Doctrine `postFlush` the domain events are dispatched from.

**No error message appears in the admin UI.** Sulu's `CopyLocaleToolbarAction` attaches no error
handler to the copy-locale request, so a failed request produces no snackbar — this affects pages
just as much as custom entities. Surfacing it would require overriding Sulu's admin JavaScript,
which is out of scope here.

## Development

The bundle supports two Sulu generations whose content model lives in different namespaces, and
only one can be installed at a time. Version specific code sits in `Sulu26` and `Sulu3`
namespaces; tests for the generation that is not installed skip themselves.

```bash
# Sulu 2.6
ddev composer update "sulu/sulu:^2.6" "sulu/content-bundle:^0.8" --with-all-dependencies
ddev exec vendor/bin/phpunit
ddev exec vendor/bin/phpstan analyse -c phpstan-sulu26.neon

# Sulu 3
ddev composer update "sulu/sulu:^3.0" --with-all-dependencies
ddev exec vendor/bin/phpunit
ddev exec vendor/bin/phpstan analyse -c phpstan-sulu3.neon
```

## License

MIT
