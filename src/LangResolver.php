<?php declare(strict_types=1);
/**
 * Nette extension for bckp/translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 *  --------------------------------------------------------------------------
 */

namespace Bckp\Translator\Nette;

use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Http;

class LangResolver {

	/** @var array */
	private $allowed;

	/** @var Http\Request */
	private $httpRequest;

	/**
	 * LangResolver constructor.
	 *
	 * @param array $allowed
	 * @param Http\Request $httpRequest
	 */
	public function __construct(array $allowed, Http\Request $httpRequest) {
		$this->allowed = $allowed;
		$this->httpRequest = $httpRequest;
	}

	/**
	 * @param Application $app
	 * @param Request $request
	 */
	public function handleRequest(Application $app, Request $request) {
		$params = $request->getParameters();

		if (isset($params['lang']) && !$this->isAllowed($params['lang'])) {
			$request->setPresenterName((string) $app->errorPresenter);
			$request->setMethod($request::FORWARD);
			$request->setParameters(['exception' => new \Nette\Application\BadRequestException('Invalid language')]);
		}

		if (!isset($params['lang'])) {
			$params['lang'] = $this->resolve();
			$request->setParameters($params);
			$request->setFlag($request::VARYING);
		}
	}

	/**
	 * @param string $lang
	 * @return bool
	 */
	public function isAllowed(string $lang): bool {
		return in_array($lang, $this->allowed, true);
	}

	/**
	 * @return string
	 */
	public function resolve(): string {
		$lang = $this->httpRequest->getCookie('lang');
		if ($lang && $this->isAllowed($lang)) {
			return $lang;
		}
		return $this->httpRequest->detectLanguage($this->allowed) ?: reset($this->allowed);
	}
}
