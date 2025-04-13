<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Tests\Resolvers;

require __DIR__ . '/../bootstrap.php';

use Bckp\Translator\Nette\Resolvers\HeaderLocaleResolver;
use Mockery;
use Nette\Http\Request;
use Tester\Assert;
use Tester\TestCase;

class HeaderResolverTest extends TestCase
{
	public function testResolveWithLanguageDetected(): void
	{
		$httpRequest = Mockery::mock(Request::class);
		$httpRequest->shouldReceive('detectLanguage')
		            ->once()
		            ->with(['en', 'cs', 'de'])
		            ->andReturn('cs');

		$resolver = new HeaderLocaleResolver($httpRequest);

		$result = $resolver->resolve(['en', 'cs', 'de']);
		Assert::same('cs', $result);
	}

	public function testResolveWithNoLanguageDetected(): void
	{
		$httpRequest = Mockery::mock(Request::class);
		$httpRequest->shouldReceive('detectLanguage')
		            ->once()
		            ->with(['en', 'cs', 'de'])
		            ->andReturn(null);

		$resolver = new HeaderLocaleResolver($httpRequest);

		$result = $resolver->resolve(['en', 'cs', 'de']);
		Assert::null($result);
	}

	public function testResolveWithEmptyAllowedLanguages(): void
	{
		$httpRequest = Mockery::mock(Request::class);
		$httpRequest->shouldReceive('detectLanguage')
		            ->once()
		            ->with([])
		            ->andReturn(null);

		$resolver = new HeaderLocaleResolver($httpRequest);

		$result = $resolver->resolve([]);
		Assert::null($result);
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

// Spuštění testu
(new HeaderResolverTest())->run();
