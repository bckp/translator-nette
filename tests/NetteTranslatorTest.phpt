<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Tests;

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\Nette\LocaleProvider;
use Bckp\Translator\Nette\Resolvers\LocaleResolver;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

class LocaleProviderTest extends TestCase
{
	public function testResolveWithSingleResolver(): void
	{
		$resolver = Mockery::mock(LocaleResolver::class);
		$resolver->shouldReceive('resolve')
		         ->once()
		         ->with(['en', 'cs', 'de'])
		         ->andReturn('cs');

		$provider = new LocaleProvider(['en', 'cs', 'de'], $resolver);

		$result = $provider->resolve();
		Assert::same('cs', $result);

		// Druhé volání by mělo vrátit cachovanou hodnotu bez opětovného volání resolveru
		$result2 = $provider->resolve();
		Assert::same('cs', $result2);
	}

	public function testResolveWithMultipleResolvers(): void
	{
		$resolver1 = Mockery::mock(LocaleResolver::class);
		$resolver1->shouldReceive('resolve')
		          ->once()
		          ->with(['en', 'cs', 'de'])
		          ->andReturn(null);

		$resolver2 = Mockery::mock(LocaleResolver::class);
		$resolver2->shouldReceive('resolve')
		          ->once()
		          ->with(['en', 'cs', 'de'])
		          ->andReturn('de');

		$resolver3 = Mockery::mock(LocaleResolver::class);
		$resolver3->shouldReceive('resolve')
		          ->never();

		$provider = new LocaleProvider(['en', 'cs', 'de'], $resolver1, $resolver2, $resolver3);

		$result = $provider->resolve();
		Assert::same('de', $result);
	}

	public function testResolveWithNoValidResolvers(): void
	{
		$resolver1 = Mockery::mock(LocaleResolver::class);
		$resolver1->shouldReceive('resolve')
		          ->once()
		          ->with(['en', 'cs', 'de'])
		          ->andReturn(null);

		$resolver2 = Mockery::mock(LocaleResolver::class);
		$resolver2->shouldReceive('resolve')
		          ->once()
		          ->with(['en', 'cs', 'de'])
		          ->andReturn(null);

		$provider = new LocaleProvider(['en', 'cs', 'de'], $resolver1, $resolver2);

		$result = $provider->resolve();
		Assert::same('en', $result); // Vrátí první povolenou lokalizaci
	}

	public function testResolveWithNoResolvers(): void
	{
		$provider = new LocaleProvider(['en', 'cs', 'de']);

		$result = $provider->resolve();
		Assert::same('en', $result); // Vrátí první povolenou lokalizaci
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

// Spuštění testu
(new LocaleProviderTest())->run();
