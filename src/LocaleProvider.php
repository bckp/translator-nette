<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette;

use Bckp\Translator\Locale;
use Bckp\Translator\Nette\Resolvers\LocaleResolver;
use Bckp\Translator\StringList;
use InvalidArgumentException;
use stdClass;

final class LocaleProvider
{
	private readonly StringList $allowed;

	private stdClass $resolvers;

	private ?string $locale = null;

	public function __construct(StringList $allowed, LocaleResolver ...$resolvers)
	{
		$this->allowed = new StringList();
		$allowed->each(function (string $locale): void {
			$this->allowed->add(Locale::normalize($locale));
		});

		if ($this->allowed->isEmpty()) {
			throw new InvalidArgumentException('At least one locale must be configured.');
		}
		$this->resolvers = new stdClass();
		foreach ($resolvers as $index => $resolver) {
			$this->resolvers->{(string) $index} = $resolver;
		}
	}

	public function resolve(): string
	{
		if ($this->locale !== null) {
			return $this->locale;
		}
		foreach (get_object_vars($this->resolvers) as $resolver) {
			if (!$resolver instanceof LocaleResolver) {
				continue;
			}
			$locale = $resolver->resolve(clone $this->allowed);

			if ($locale === null) {
				continue;
			}

			try {
				$locale = Locale::normalize($locale);
			} catch (InvalidArgumentException) {
				continue;
			}

			if ($this->allowed->contains($locale)) {
				return $this->locale = $locale;
			}
		}

		return $this->locale = $this->allowed->first()
			?? throw new InvalidArgumentException('No locales are available.');
	}
}
