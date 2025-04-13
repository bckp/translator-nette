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

namespace Bckp\Translator\Nette\Resolvers;

/**
 * @api
 */
interface LocaleResolver
{
	/**
	 * @api
	 * @param string[] $allowed
	 */
	public function resolve(array $allowed): ?string;
}
