# Bckp Translator for Nette 3.0

Nette integration for the versioned, typed translator core. Requires PHP 8.4+, Nette 3.2 and core 3.x. Latte and Tracy are optional.

This is the `nextgen` development branch and requires the matching core branch. During development, clone `translator-core`, [`translator-neon`](https://github.com/bckp/translator-neon) and `translator-nette` from their `nextgen` branches as sibling directories. The adapter's path repositories link the core and its plugins locally.

```sh
cd translator-core
COMPOSER_ROOT_VERSION=3.0.x-dev composer install
cd ../translator-nette
composer install
composer tests
composer phpstan
composer phpcs
```

The PHP-cache plugin lives in the core repository's `packages/php-cache` directory and is required by the adapter. NEON has its own repository; it is an optional source plugin and a development dependency used by the integration tests.

## Configuration

```neon
extensions:
    translator: Bckp\Translator\Nette\Bridges\TranslatorDI\TranslatorExtension(%tempDir%/translator)

translator:
    languages: [cs, en]
    sources:
        files: Bckp\Translator\Neon\NeonSource(%appDir%/Locale)
        database: @applicationTranslations

    checkProbability: 0.01
    injectLatte: true
    resolvers:
        - Bckp\Translator\Nette\Resolvers\CookieLocaleResolver(locale)
        - Bckp\Translator\Nette\Resolvers\HeaderLocaleResolver()
```

Omit `database` if only file translations are needed. An application source service must implement `Bckp\Translator\Interfaces\TranslationSource` and return `MessageCatalogue` plus a mandatory string version. Named source IDs form part of cache identity; later sources override earlier messages.

`debugMode` inherits the application's `%debugMode%` parameter. Debug mode checks every initialized locale once per request, regardless of probability. `debugger` defaults to the resolved debug mode and controls the Tracy panel independently:

```neon
translator:
    languages: [cs, en]
    sources:
        files: Bckp\Translator\Neon\NeonSource(%appDir%/Locale)
    debugMode: false
    debugger: true
    checkProbability: 0.01
```

Missing cache is always built. A warm production catalogue checks source versions with the configured probability once on first use. With an unchanged version it keeps the compiled data. A skipped check instantiates no source and loads no source data. Individual translations never check versions or sample probability.

DI compilation does not scan translation directories. Only the requested locale is initialized. Source services are created lazily when a version or data is actually needed; constructors should avoid IO.

Optional `storage` accepts a DI factory or a service reference implementing `CatalogueStorage`. The default is `PhpCatalogueStorage` in the directory passed to the extension. `pluralProvider` accepts a DI factory or service reference and defaults to the core `PluralProvider`.

## Translation

Inject Nette's interface for ordinary translation:

```php
<?php

declare(strict_types=1);

use Nette\Application\UI\Presenter;
use Nette\DI\Attributes\Inject;
use Nette\Localization\Translator;

final class HomepagePresenter extends Presenter
{
    #[Inject]
    public Translator $translator;

    public function renderDefault(): void
    {
        $this->template->welcome = $this->translator->translate('messages.hello', 'Ada');
    }
}
```

Latte's translator extension is added automatically when `injectLatte` is enabled and the standard `latte.latteFactory` service exists:

```latte
<p>{_'messages.hello', 'Ada'}</p>
```

Messages use `sprintf` formatting. Core parameters are strictly `string|int|float`. The Nette adapter accepts scalar values, null and `Stringable` objects as required by Nette's interface and converts them appropriately; arrays and other objects throw.

## Manual recompilation

Inject the adapter's extended interface to refresh after editing translations:

```php
<?php

declare(strict_types=1);

use Bckp\Translator\Nette\Interfaces\Translator;

final class TranslationRefresh
{
    public function __construct(private readonly Translator $translator) {}

    public function refresh(): void
    {
        $this->translator->forceRecompile();
    }

    public function refreshLocale(string $locale): void
    {
        $this->translator->forceRecompile($locale);
    }
}
```

`forceRecompile()` reloads all sources for the current locale even if their versions are identical. Passing a locale refreshes that locale without switching the current request's language.

`TranslatorProvider::forceRecompile($locale)` provides the same operation when working directly with the provider. The provider updates its existing core translator object, so existing Nette, Latte, form and core-translator references immediately see the replacement. A failed load leaves those references on their previous catalogue.

In long-running workers, create a fresh container/provider scope per job or explicitly force a refresh. The probabilistic decision is made at catalogue initialization, not on every translation in a persistent process.

## Locale resolution

`languages` is required and must contain at least one locale. Locales are normalized to lowercase with hyphens: `CS_CZ` becomes `cs-cz`.

Resolvers are tried in configuration order. Invalid or unsupported results are skipped; the first configured locale is the fallback. The chosen locale is held for the provider's lifetime.

Custom resolvers implement this native contract:

```php
interface LocaleResolver
{
    public function resolve(\Bckp\Translator\StringList $allowed): ?string;
}
```

The built-in cookie resolver uses the `locale` cookie; the header resolver uses Nette's `Accept-Language` detection. Resolvers receive a copy of the typed allowed-language list.

## NEON source

Files follow `{resource}.{locale}.neon`. For `messages.cs.neon`:

```neon
hello: 'Ahoj %s'
people:
    zero: 'žádný člověk'
    one: '%d člověk'
    few: '%d lidé'
    other: '%d lidí'
counter: '0'
hidden: ''
```

Keys become `messages.hello` and `messages.people`. Values must be strings or known plural variants containing strings. Quote numeric and boolean text. The plugin's automatic version hashes contents and the file set, so changes with identical modification times are detected.

For immutable release files, pass a fixed version:

```neon
sources:
    files: Bckp\Translator\Neon\NeonSource(%appDir%/Locale, release-42)
```

Update that string on release or force a rebuild when its files change.

## Tracy and migration

The Tracy panel shows used catalogues, their stored versions, whether they were checked/recompiled, untranslated messages and plural warnings. Rendering the panel does not trigger version checks or source loading.

The 3.0 adapter replaces `path` with the named `sources` map and uses typed factories and `StringList` resolver inputs. Upgrade it together with core 3.x. See the core's `MIGRATION.md` for the removed 2.x file, array and event API.

PHPStan level 8 and integration tests run against real Nette DI containers, Latte engines and HTTP resolvers. Source code uses strict native types and no docblocks. PHP CS Fixer uses the PER Coding Style 3.0 preset with tab indentation and PHP 8.4 migration rules. EditorConfig also selects tabs for PHP files.

## Quality tooling

GitHub Actions runs Nette Tester on PHP 8.4 and 8.5, PHPStan level 8, PER 3 with tabs, and `composer audit`. Existing coverage checks remain in place.

### API compatibility

Roave Backward Compatibility Check has isolated dependencies:

```sh
composer bc-install
composer bc-check
```

The check compares committed HEAD against the highest stable `3.x.y` or `v3.x.y` tag. Prereleases and older majors are excluded. Until the first stable 3.x release, it explicitly reports that no baseline exists. CI also compares HEAD with itself to verify the checker and local dependency resolution before that release.

Use `composer bc-check -- <tag-or-commit>` for an explicit baseline. Keep sibling repositories available as described above; they are linked into the checker's temporary directory. Temporary comparisons are stored under the ignored `temp/` directory. Uncommitted changes are not checked.

### Dependency updates

`renovate.json` groups development dependencies and GitHub Actions updates, checks the default branch and `nextgen`, and schedules updates before 07:00 Europe/Prague on Mondays. Automatic merging is disabled. CI validates this configuration using the official Renovate validator.

The hosted Renovate GitHub App must be enabled for the repository. It discovers configuration on the default branch, so this file must also be present there before activation while `nextgen` is a separate development branch.

### Mutation testing

Infection is deferred because it does not directly support Nette Tester. Adoption will be considered together with a possible move to PHPUnit.
