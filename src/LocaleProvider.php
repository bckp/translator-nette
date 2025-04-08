<?php

declare(strict_types=1);

/**
 * Nette extension for bckp/translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.dev>
 *  --------------------------------------------------------------------------
 */

namespace Bckp\Translator\Nette;

use Bckp\Translator\Nette\Resolvers\Resolver;

final class LocaleProvider
{
	/**
	 * @var Resolver[]
	 */
	private readonly array $resolvers;

	private string $locale;

	public function __construct(
		public readonly array $allowed,
		Resolver ...$resolvers
	) {
		$this->resolvers = $resolvers;
	}

	/**
	 * @api
	 */
	public function resolve(): string
	{
		return $this->locale ??= $this->getLocale();
	}

	private function getLocale(): string
	{
		foreach ($this->resolvers as $resolver) {
			$locale = $resolver->resolve($this->allowed);
			if ($locale !== null) {
				return $locale;
			}
		}

		return $this->allowed[0];
	}
}
