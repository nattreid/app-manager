<?php

declare(strict_types = 1);

namespace NAttreid\AppManager\Helpers\Deploy;

use InvalidArgumentException;
use Nette\Http\Request;
use Nette\Utils\Json;
use Tracy\Debugger;

/**
 * Deploy
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class Deploy
{

	/** @var string */
	private $url, $secretToken;

	/** @var Request */
	private $request;

	public function __construct(string $url = null, string $secretToken = null, Request $request)
	{
		$this->url = $url;
		$this->secretToken = $secretToken;
		$this->request = $request;
	}

	/**
	 * Je povolen pristup
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	protected function checkAccess(): bool
	{
		if ($this->url === null || $this->secretToken === null) {
			throw new InvalidArgumentException('Deploy is not set');
		}
		$secretToken = $this->request->getHeader('x-gitlab-token');
		if ($secretToken == $this->secretToken) {
			$json = file_get_contents('php://input');
			$data = Json::decode($json);

			if ($data) {
				if (isset($data->repository->url)) {
					if ($this->url === (string)$data->repository->url) {
						return true;
					}
				}
			}
		}
		Debugger::log('Unknown access from ' . $this->request->getRemoteHost() . '(' . $this->request->getRemoteAddress() . ')', 'deploy');
		return false;
	}

}
