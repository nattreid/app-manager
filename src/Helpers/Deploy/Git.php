<?php

declare(strict_types=1);

namespace NAttreid\AppManager\Helpers\Deploy;

use InvalidArgumentException;
use Nette\Http\Request;
use Tracy\Debugger;

/**
 * Gitlab
 *
 * @author Attreid <attreid@gmail.com>
 */
class Git extends Deploy
{

	/** @var string */
	private $path;

	public function __construct(string $appDir, array $options, Request $request)
	{
		parent::__construct($options, $request);
		$this->path = $appDir . '/..';
	}

	/**
	 * Aktualizuje z gitlabu
	 */
	public function update()
	{
		$path = getcwd();
		chdir($this->path);

		$commands = [
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
				Debugger::log($output, 'git');
			}
		}

		chdir($path);
	}

	/**
	 * Akutalizuje z gitlabu, pokud je pristup z povolene adresy
	 *
	 * @throws InvalidArgumentException
	 */
	public function authorizedUpdate()
	{
		if ($this->authentication()) {
			$this->update();
		}
	}

}
