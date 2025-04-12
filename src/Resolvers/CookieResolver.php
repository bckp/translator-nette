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
use Nette\Http\Response;
use Throwable;

/**
 * @api
 */
readonly class CookieResolver implements Resolver
{
	public function __construct(
		protected string $parameterName,
		protected Request $httpRequest,
		protected Response $httpResponse,
	) {}

	/**
	 * @param string[] $allowed
	 */
	#[\Override]
	public function resolve(array $allowed): ?string
	{
		return $this->httpRequest->getCookie($this->parameterName);
	}

	public function set(string $lang): bool
	{
		try {
			$this->httpResponse->setCookie(
				$this->parameterName,
				$lang,
				null,
			);
			return true;
		} catch (Throwable) {
			return false;
		}
	}
}
