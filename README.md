Bckp\Translator
====================

[![Downloads this Month](https://img.shields.io/packagist/dm/bckp/translator-nette.svg)](https://packagist.org/packages/bckp/translator-nette)
[![Tests](https://github.com/bckp/translator-nette/actions/workflows/tests.yaml/badge.svg)](https://github.com/bckp/translator-nette/actions/workflows/tests.yaml)
[![Coverage Status](https://coveralls.io/repos/github/bckp/translator-nette/badge.svg?branch=main)](https://coveralls.io/github/bckp/translator-nette?branch=main)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bckp/translator-nette/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/bckp/translator-nette/?branch=main)
[![Latest Stable Version](https://poser.pugx.org/bckp/translator-nette/v/stable)](https://packagist.org/packages/bckp/translator-nette)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/application/blob/master/license.md)

Simple and fast PHP translator

Usage
-----

The best way to install bckp/translator-nette is using [Composer](http://getcomposer.org/):
```sh
$ composer require bckp/translator-nette
```

Configuration
-----

In you nette config, add extension with temporary dir
```neon
extensions:
	translator: Bckp\Translator\Nette\Bridges\TranslatorExtension(%tempDir%/translator)
```
and set translator itself
```neon
translator:
	languages:
		- cs #Language codes
	path:
		- %appDir%/Locale # Where to search for language file
	pluralProvider: Bckp\Translator\PluralProvider # Optional - default is PluralProvider from bckp/translator-core
	debugger: %debugMode% # Optional - default is %debugMode%
	injectLatte: true # Optional - default is true, this will autoregister translator into latte
	resolvers:
	    - Bckp\Translator\Nette\Resolvers\HeaderLocaleResolver() # Any class implements Resolver, will be used to resolve language, if more then one used, extension will cyclu thru all of them to find first that can be used
```

Translator will find all files in path and make map to the DI. If debugger is on, it will on each request check, if any file is modified and rebuild all language file (only needed ones).

Naming convention
-----

All language files should be in Neon format, and proper naming is: {module}.{langCode}.neon

Translation file format
-----------------------

Translation files are written in NEON format. Plural strings are in ARRAY, otherwise STRING.
```neon
welcome: 'Vítejte'
withArgs: 'Ahoj, já jsem %s, přeji krásné %s'
withArgsRev: 'Krásné %2$s, já jsem %1$s'
plural:
	zero: 'žádný člověk'
	one: 'jeden člověk'
	few: '%d lidé'
	other: '%d lidí'
next: 'This is next translation'
```

Translatings
-----

For translator to be in Presenter, simply inject it or get in in constructor

```php
<?php
use Nette\DI\Attributes\Inject;

class Presenter extends Nette\Application\UI\Presenter {
    #[Inject]
	public Nette\Localization\Translator $translator;
}
```

Translating in Presenters
-------------------------

```php
	$changes = $this->model->doSomeChanges();
	$this->flashMessage($this->translator->translate('messages.flash.success', $changes));
	
	$form->addError($this->translator->translate('messages.error.form.empty'));
```

If you want do shortcut, you can define own translate method, like this
```php
class Presenter extends Nette\Application\UI\Presenter {
	#[Inject]
	public Nette\Localization\Translator $translator;

	public function translate($message, ...$params){
		$this->translator->translate($message, ...$params);
	}

	public function renderTest(){
		$message = $this->translate('messages.test');
	}
}
```

Translating in Templates
------------------------

If you set injectLatte to true, translator will autoregister itself to Latte, so you can use it in templates

```html
	<div>{_'messages.test'}</div>
```

Every parameter you pass to the translate will be passed into sprintf in Bckp\Translator internally, so you can add order in neon translation format.

See (https://latte.nette.org/en/tags#toc-translation) for more informations about how to translate

See (https://github.com/bckp/translator-core) for more informations about Bckp\Translate-core
