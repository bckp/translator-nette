Bckp\Translator
====================

[![Downloads this Month](https://img.shields.io/packagist/dm/bckp/translator-nette.svg)](https://packagist.org/packages/bckp/translator-nette)
[![Build Status](https://travis-ci.org/bckp/translator-nette.svg?branch=master)](https://travis-ci.org/bckp/translator-nette)
[![Coverage Status](https://coveralls.io/repos/github/bckp/translator-nette/badge.svg?branch=master)](https://coveralls.io/github/bckp/translator-nette?branch=master)
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
	resolver: true # Use resolver to get proper language?
	debugger: %debugMode%
```

Translator will find all files in path and make map to the DI. If debugger is on, it will on each request check, if any file is modified and rebuild all language file (only needed ones).

Naming convention
-----

All language files should be in Neon format, and proper naming is: <module>.<langCode>.neon

Translatings
-----

Translator will auto-register into all presnters, that uses TranslatorAwareTrait, during onStartup part. If you want to use them in templates, you must push them into latte by yourself (as onRender is not yet implemented in nette), and onStartup is bad place to create template
```php
<?php
class Presenter extends Nette\Application\UI\Presenter {
	use TranslatorAwarePresenter; // this comes with translator extension
}
```


