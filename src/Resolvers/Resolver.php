<?php declare(strict_types=1);

namespace Bckp\Translator\Nette\Resolvers;

interface Resolver
{
	/**
	 * @api
	 * @param string[] $allowed
	 */
	public function resolve(array $allowed): ?string;
}
