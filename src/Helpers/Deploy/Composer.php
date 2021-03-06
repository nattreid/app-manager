<?php

declare(strict_types=1);

namespace NAttreid\AppManager\Helpers\Deploy;

use Nette\Http\Request;
use Tracy\Debugger;

/**
 * Composer
 *
 * @author attreid
 */
class Composer extends Deploy
{

	/** @var string */
	private $composerDir = '/composer';

	/** @var string */
	private $composer = '/composer.phar';

	/** @var string */
	private $path, $tempDir;

	public function __construct(string $appDir, string $tempDir, array $options, Request $request)
	{
		parent::__construct($options, $request);
		$this->path = $appDir . '/..';
		$this->tempDir = $tempDir;
	}

	/**
	 * Vrati composer
	 * @return string
	 */
	private function getComposer(): string
	{
		$composer = $this->tempDir . $this->composer;

		$temp = $this->tempDir . $this->composerDir;
		if (!file_exists($temp)) {
			mkdir($temp);
		}

		$home = 'COMPOSER_HOME="' . $temp . '" ';

		if (!file_exists($composer)) {
			$expectedSignature = trim(file_get_contents('https://composer.github.io/installer.sig'));
			copy('https://getcomposer.org/installer', $this->tempDir . '/composer-setup.php');
			$actualSignature = hash_file('SHA384', $this->tempDir . '/composer-setup.php');
			if ($expectedSignature === $actualSignature) {
				$command = 'cd ' . $this->tempDir . ';'
					. $home . 'php composer-setup.php;';
				exec($command, $output);
				foreach ($output as $str) {
					if (!empty($str)) {
						Debugger::log($str, 'composer');
					}
				}
				unlink($this->tempDir . '/composer-setup.php');
			} else {
				Debugger::log('Invalid installer signature', 'composer');
			}
		}
		return $home . $composer;
	}

	/**
	 * Aktualizuje composer
	 */
	public function update(): void
	{
		$composer = $this->getComposer();

		$command = 'cd ' . $this->path . ';'
			. $composer . ' self-update 2>&1;'
			. $composer . ' update 2>&1;';
		exec($command, $output);
		foreach ($output as $str) {
			if (!empty($str)) {
				Debugger::log($str, 'composer');
			}
		}
	}
}
