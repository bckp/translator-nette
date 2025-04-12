<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Tests\Resolvers;

require __DIR__ . '/../bootstrap.php';

use Bckp\Translator\Nette\Resolvers\CookieResolver;
use Mockery;
use Nette\Http\Request;
use Nette\Http\Response;
use Tester\Assert;
use Tester\TestCase;

class CookieResolverTest extends TestCase
{
	private const CookieName = 'lang';

	public function testResolveWithCookieValue(): void
	{
		$httpRequest = Mockery::mock(Request::class);
		$httpRequest->shouldReceive('getCookie')
		            ->once()
		            ->with(self::CookieName)
		            ->andReturn('cs');

		$httpResponse = Mockery::mock(Response::class);

		$resolver = new CookieResolver(self::CookieName, $httpRequest, $httpResponse);

		$result = $resolver->resolve(['en', 'cs', 'de']);
		Assert::same('cs', $result);
	}

	public function testResolveWithoutCookieValue(): void
	{
		$httpRequest = Mockery::mock(Request::class);
		$httpRequest->shouldReceive('getCookie')
		            ->once()
		            ->with(self::CookieName)
		            ->andReturn(null);

		$httpResponse = Mockery::mock(Response::class);

		$resolver = new CookieResolver(self::CookieName, $httpRequest, $httpResponse);

		$result = $resolver->resolve(['en', 'cs', 'de']);
		Assert::null($result);
	}

	public function testSetCookieSuccessfully(): void
	{
		$httpRequest = Mockery::mock(Request::class);
		$httpResponse = Mockery::mock(Response::class);
		$httpResponse->shouldReceive('setCookie')
		             ->once()
		             ->with(self::CookieName, 'cs', null)
		             ->andReturnSelf();

		$resolver = new CookieResolver(self::CookieName, $httpRequest, $httpResponse);

		$result = $resolver->set('cs');
		Assert::true($result);
	}

	public function testSetCookieWithException(): void
	{
		$httpRequest = Mockery::mock(Request::class);
		$httpResponse = Mockery::mock(Response::class);
		$httpResponse->shouldReceive('setCookie')
		             ->once()
		             ->with(self::CookieName, 'cs', null)
		             ->andThrow(new \Exception('Cookie setting failed'));

		$resolver = new CookieResolver(self::CookieName, $httpRequest, $httpResponse);

		$result = $resolver->set('cs');
		Assert::false($result);
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

// Spuštění testu
(new CookieResolverTest())->run();
