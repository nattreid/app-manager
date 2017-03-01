<?php

declare(strict_types = 1);

namespace NAttreid\AppManager\Helpers\Deploy;

use InvalidArgumentException;
use Nette\Http\Request;
use Tracy\Debugger;

/**
 * Gitlab
 *
 * @author Attreid <attreid@gmail.com>
 */
class Gitlab extends Deploy
{

	/** @var string */
	private $path;

	public function __construct(string $appDir, string $url, string $ip, Request $request)
	{
		parent::__construct($url, $ip, $request);
		$this->path = $appDir . '/..';
	}

	/**
	 * Aktualizuje z gitlabu
	 */
	public function update()
	{
		$commands = [
			"cd $this->path",
			'echo $PWD',
			'whoami',
			'git pull',
			'git status',
			'git submodule sync',
			'git submodule update',
			'git submodule status',
			'rm temp/cache/* -rf',
			'rm www/webtemp/* -rf'
		];
		foreach ($commands AS $command) {
			$output = shell_exec("$command 2>&1");
			if ($output !== null) {
				Debugger::log($output, 'gitlab');
			}
		}
	}

	/**
	 * Akutalizuje z gitlabu, pokud je pristup z povolene adresy
	 *
	 * @throws InvalidArgumentException
	 */
	public function authorizedUpdate()
	{
		if ($this->checkAccess()) {
			$this->update();
		}
	}

}
