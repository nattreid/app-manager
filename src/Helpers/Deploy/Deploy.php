<?php

declare(strict_types=1);

namespace NAttreid\AppManager\Helpers\Deploy;

use InvalidArgumentException;
use NAttreid\Utils\Strings;
use Nette\Http\Request;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tracy\Debugger;

/**
 * Deploy
 *
 * @author Attreid <attreid@gmail.com>
 */
abstract class Deploy
{

	/** @var string */
	private $url, $secretToken, $type;

	/** @var Request */
	private $request;

	public function __construct(array $options, Request $request)
	{
		$this->url = $options['projectUrl'];
		$this->secretToken = $options['secretToken'];
		$this->type = $options['type'];
		$this->request = $request;
	}

	/**
	 * Je povolen pristup
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	protected function authentication(): bool
	{
		if ($this->url === null) {
			throw new InvalidArgumentException('Project URL is not set');
		}

		$payload = file_get_contents('php://input');

		switch ($this->type) {
			case 'gitlab':
				$authenticated = $this->authenticateGitlab($payload);
				break;

			case 'github':
				$authenticated = $this->authenticateGithub($payload);
				break;

			case 'bitbucket':
				$authenticated = $this->authenticateBitbucket($payload);
				break;

			default:
				throw new InvalidArgumentException("Unsupported type '$this->type'");
		}

		if ($authenticated) {
			return true;
		} else {
			Debugger::log('Unknown access from ' . $this->request->getRemoteHost() . '(' . $this->request->getRemoteAddress() . ')', 'deploy');
			return false;
		}
	}

	/**
	 * @param string $payload
	 * @return bool
	 * @throws JsonException
	 */
	private function authenticateGitlab(string $payload): bool
	{
		if ($this->secretToken === null) {
			throw new InvalidArgumentException('Secret Token is not set');
		}
		$secretToken = $this->request->getHeader('x-gitlab-token');
		if ($secretToken === $this->secretToken) {
			$data = Json::decode($payload);
			if ($data) {
				if (isset($data->repository->homepage)) {
					if ($this->url === (string) $data->repository->homepage) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param string $payload
	 * @return bool
	 * @throws JsonException
	 */
	private function authenticateGithub(string $payload): bool
	{
		if ($this->secretToken === null) {
			throw new InvalidArgumentException('Secret Token is not set');
		}

		$secretToken = $this->request->getHeader('X-Hub-Signature');
		$token = 'sha1=' . hash_hmac('sha1', $payload, $this->secretToken);

		if ($secretToken === $token) {
			$data = Json::decode($payload);
			if ($data) {
				if (isset($data->repository->html_url)) {
					if ($this->url === (string) $data->repository->html_url) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param string $payload
	 * @return bool
	 * @throws JsonException
	 */
	private function authenticateBitbucket(string $payload): bool
	{
		$ip = $this->request->getRemoteAddress();

		if (Strings::ipInRange($ip, '18.205.93.0/25') || Strings::ipInRange($ip, '13.52.5.0/25')) {
			$data = Json::decode($payload);
			if ($data) {
				if (isset($data->repository->links->html->href)) {
					if ($this->url === (string) $data->repository->links->html->href) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public abstract function update(): void;

	/**
	 * Akutalizuje pokud je pristup z povolene adresy
	 *
	 * @throws InvalidArgumentException
	 */
	public function authorizedUpdate(): void
	{
		if ($this->authentication()) {
			$this->update();
		}
	}
}
