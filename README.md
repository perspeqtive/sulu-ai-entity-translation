# Sulu AI Entity Translation Bundle

Enables Sulu AI's *Full Content Translation* for **custom Sulu entities**.

Sulu's `sulu/ai-platform-bundle` translates content when an editor copies a locale and ticks the
translate checkbox — but only for pages, articles and snippets. The checkbox is added to the core
`copy_locale` form for every resource, so it also shows up for custom entities, where nothing
happens.

This bundle closes that gap. Install it and any custom entity built on `sulu/content-bundle` is
translated on copy-locale. No per-entity configuration.

## Requirements

- PHP 8.2+
- Sulu 2.6 or 3.x
- A Sulu AI subscription — `sulu/ai-platform-bundle` is proprietary and distributed through
  Sulu's private Composer endpoint.

## Installation

Configure Sulu's private Composer repository first (see the Sulu AI documentation), then:

```bash
composer require perspeqtive/sulu-ai-entity-translation
```

Register the bundle in `config/bundles.php`:

```php
return [
    /* ... */
    PERSPEQTIVE\SuluAiEntityTranslationBundle\SuluAiEntityTranslationBundle::class => ['all' => true],
];
```

## How it works

Translatable fields are derived from the entity's form metadata — `text_line` and `text_area` are
translated as plain text, `text_editor` as HTML, blocks recursively. This is Sulu's own logic;
the bundle only binds it to custom entities.

## Behaviour on failure

If the translation service fails, the locale copy is still created and persisted. The failure is
logged and recorded in Sulu's activity log; the editor sees the untranslated copy and can
translate manually.

Note that no error message appears in the admin UI. Sulu's `CopyLocaleToolbarAction` has no error
handler for the copy-locale request, so a failed request produces no snackbar. Surfacing it would
require overriding Sulu's admin JavaScript, which is out of scope for this bundle.

## License

MIT
