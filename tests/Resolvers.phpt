<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\Nette\LocaleProvider;
use Bckp\Translator\Nette\Resolvers\CookieLocaleResolver;
use Bckp\Translator\Nette\Resolvers\HeaderLocaleResolver;
use Bckp\Translator\Nette\Resolvers\LocaleResolver;
use Bckp\Translator\StringList;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Tester\Assert;

$request = new Request(new UrlScript('https://example.test/'), cookies: ['lang' => 'zz'], headers: ['accept-language' => 'cs,en;q=0.8']);
$cookie = new CookieLocaleResolver('lang', $request, new Response());
$allowed = new StringList('en', 'cs');
Assert::null($cookie->resolve($allowed));
Assert::same('cs', (new LocaleProvider($allowed, $cookie, new HeaderLocaleResolver($request)))->resolve());
$request = new Request(new UrlScript('https://example.test/'), cookies: ['lang' => 'CS_CZ']);
Assert::same('cs-cz', (new CookieLocaleResolver('lang', $request, new Response()))->resolve(new StringList('cs-cz')));
$request = new Request(new UrlScript('https://example.test/'), cookies: ['lang' => '../invalid']);
Assert::null((new CookieLocaleResolver('lang', $request, new Response()))->resolve($allowed));
Assert::same('en', (new LocaleProvider($allowed))->resolve());
Assert::exception(static fn() => new LocaleProvider(new StringList()), InvalidArgumentException::class);
$resolver = new class implements LocaleResolver {
	public int $calls = 0;

	#[\Override]
	public function resolve(StringList $allowed): ?string
	{
		++$this->calls;

		return 'CS_CZ';
	}
};
$provider = new LocaleProvider(new StringList('en', 'cs_CZ'), $resolver);
Assert::same('cs-cz', $provider->resolve());
Assert::same('cs-cz', $provider->resolve());
Assert::same(1, $resolver->calls);
