<?php

namespace NAttreid\AppManager\Helpers;

use NAttreid\Utils\File;
use Nette\Utils\Finder;
use WebLoader\Nette\LoaderFactory;

/**
 * Class Files
 *
 * @author Attreid <attreid@gmail.com>
 */
class Files
{

	/** @var string */
	private $appDir, $wwwDir, $tempDir, $logDir, $sessionDir, $sessionExpiration;

	/** @var array */
	private $webLoaderDir = [];

	public function __construct($appDir, $wwwDir, $tempDir, $logDir, $sessionDir, $sessionExpiration, LoaderFactory $loader = null)
	{
		$this->appDir = $appDir;
		$this->wwwDir = $wwwDir;
		$this->tempDir = $tempDir;
		$this->logDir = $logDir;
		$this->sessionDir = $sessionDir;
		$this->sessionExpiration = $sessionExpiration;

		if ($loader !== null) {
			foreach ($loader->getTempPaths() as $path) {
				$this->webLoaderDir[] = $wwwDir . '/' . $path;
			}
		}
	}

	public function clearCache()
	{
		File::removeDir($this->tempDir . '/cache', false);
		foreach ($this->webLoaderDir as $dir) {
			if (file_exists($dir)) {
				foreach (Finder::findFiles('*')
							 ->exclude('.htaccess', 'web.config')
							 ->in($dir) as $file) {
					unlink($file);
				}
			}
		}
	}

	/**
	 * Smazani expirovane session (default je nastaven na maximalni dobu expirace session)
	 * @param string $expiration format 1 minutes, 14 days atd
	 */
	public function clearSession($expiration = null)
	{
		if ($expiration === null) {
			$expiration = $this->sessionExpiration;
		}
		foreach (Finder::findFiles('*')->date('<', '- ' . $expiration)
					 ->exclude('.htaccess', 'web.config')
					 ->in($this->sessionDir) as $file) {
			unlink($file);
		}
	}

	/**
	 * Smaze temp
	 */
	public function clearTemp()
	{
		foreach (Finder::findFiles('*')
					 ->exclude('.htaccess', 'web.config')
					 ->in($this->tempDir) as $file) {
			unlink($file);
		}
		foreach (Finder::findDirectories('*')
					 ->in($this->tempDir) as $dirname) {

			foreach (Finder::findFiles('*')
						 ->exclude('.htaccess', 'web.config')
						 ->in($dirname) as $file) {
				unlink($file);
			}
			foreach (Finder::findDirectories('*')
						 ->in($dirname) as $dir) {
				File::removeDir($dir);
			}
			if (File::isDirEmpty($dirname)) {
				File::removeDir($dirname);
			}
		}
	}

	/**
	 * Smazani logu
	 */
	public function clearLog()
	{
		foreach (Finder::findFiles('*')
					 ->exclude('.htaccess', 'web.config')
					 ->in($this->logDir) as $file) {
			unlink($file);
		}
	}

	/**
	 * Smaze CSS cache
	 */
	public function clearCss()
	{
		foreach ($this->webLoaderDir as $dir) {
			if (file_exists($dir)) {
				foreach (Finder::findFiles('*.css')
							 ->exclude('.htaccess', 'web.config')
							 ->in($dir) as $file) {
					unlink($file);
				}
			}
		}
	}

	/**
	 * Smaze Javascript cache
	 */
	public function clearJs()
	{
		foreach ($this->webLoaderDir as $dir) {
			if (file_exists($dir)) {
				foreach (Finder::findFiles('*.js')
							 ->exclude('.htaccess', 'web.config')
							 ->in($dir) as $file) {
					unlink($file);
				}
			}
		}
	}
}

