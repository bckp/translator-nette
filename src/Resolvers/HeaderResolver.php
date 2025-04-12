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

use Nette\Http\Request;

/**
 * @api
 */
final readonly class HeaderResolver implements Resolver
{
	public function __construct(
		private Request $httpRequest
	) {}

	#[\Override]
	public function resolve(array $allowed): ?string
	{
		return $this->httpRequest->detectLanguage($allowed);
	}
}
