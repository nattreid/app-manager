<?php

namespace NAttreid\AppManager;

use InvalidArgumentException;
use NAttreid\AppManager\Deploy\Composer;
use NAttreid\AppManager\Deploy\Gitlab;
use NAttreid\Utils\File;
use NAttreid\Utils\TempFile;
use Nette\Utils\Finder;
use Nextras\Dbal\Connection;

/**
 * Sprava aplikace
 *
 * @author Attreid <attreid@gmail.com>
 *
 * @todo predelat repository cache
 */
class AppManager
{

	use \Nette\SmartObject;

	/** @var string */
	private $appDir, $wwwDir, $tempDir, $logDir, $sessionDir, $sessionExpiration;

	/** @var array */
	private $webLoaderDir;

	/** @var Gitlab */
	private $gitlab;

	/** @var Composer */
	private $composer;

	/** @var Connection */
	private $db;

	/** @var callable[] */
	public $onInvalidateCache = [];

	public function __construct($appDir, $wwwDir, $tempDir, $logDir, $sessionDir, $sessionExpiration, Gitlab $gitlab, Composer $composer, Connection $db, \WebLoader\Nette\LoaderFactory $loader = null)
	{
		$this->appDir = $appDir;
		$this->wwwDir = $wwwDir;
		$this->tempDir = $tempDir;
		$this->logDir = $logDir;
		$this->sessionDir = $sessionDir;

		if ($loader !== null) {
			foreach ($loader->getTempPaths() as $path) {
				$this->webLoaderDir[] = $wwwDir . '/' . $path;
			}
		}
		$this->sessionExpiration = $sessionExpiration;

		$this->gitlab = $gitlab;
		$this->composer = $composer;

		$this->db = $db;
	}

	/**
	 * Smazani cache
	 */
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
	 * Invaliduje cache
	 */
	public function invalidateCache()
	{
		foreach ($this->onInvalidateCache as $func) {
			$func();
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

	/**
	 * Zapne nebo vypne udrzbu stranek (zobrazi se stranka udrzby)
	 * @param boolean $set
	 */
	public function maintenance($set = true)
	{
		$file = $this->tempDir . '/maintenance';
		if ($set) {
			file_put_contents($file, '');
		} else {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

	/**
	 * Aktualizace zdrojovych kodu pomoci composeru
	 * @param boolean $force
	 * @throws InvalidArgumentException
	 */
	public function composerUpdate($force = false)
	{
		$this->maintenance();
		if ($force) {
			$this->composer->update();
		} else {
			$this->composer->authorizedUpdate();
		}
		$this->maintenance(false);
	}

	/**
	 * Deploy
	 * @param boolean $force
	 * @throws InvalidArgumentException
	 */
	public function gitPull($force = false)
	{
		$this->maintenance();
		if ($force) {
			$this->gitlab->update();
		} else {
			$this->gitlab->authorizedUpdate();
		}
		$this->maintenance(false);
	}

	/**
	 * Vrati zalohu databaze
	 * @return TempFile
	 */
	public function backupDatabase()
	{
		$backup = new TempFile('backup.sql', true);
		$tables = $this->db->getPlatform()->getTables();

		$backup->write("SET NAMES utf8;\n");
		$backup->write("SET time_zone = '+00:00';\n");
		$backup->write("SET foreign_key_checks = 0;\n");
		$backup->write("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n\n");

		foreach ($tables as $table) {
			$tableName = $table['name'];
			$backup->write("DROP TABLE IF EXISTS `$tableName`;\n");

			$createTable = $this->db->query("SHOW CREATE TABLE %table", $tableName)->fetch()->{'Create Table'};
			$backup->write("$createTable;\n\n");

			$rows = $this->db->query("SELECT * FROM %table", $tableName);
			$insert = [];
			$columns = null;
			foreach ($rows as $row) {
				$field = $row->toArray();

				if ($columns === null) {
					$colName = [];
					foreach ($field as $key => $value) {
						$colName[] = "`$key`";
					}
					$columns = implode(', ', $colName);
				}
				$cols = [];
				foreach ($field as $column) {
					$column = addslashes($column);
					$column = preg_replace("/\n/", "\\n", $column);
					$cols[] = '"' . $column . '"';
				}
				$insert[] = implode(', ', $cols);
			}

			if (!empty($insert)) {
				$backup->write("INSERT INTO `$tableName` ($columns) VALUES\n(" . implode("),\n(", $insert) . ");\n");
			}

			$backup->write("\n\n");
		}

		// zip
		$archive = new TempFile;
		File::zip($backup, $archive);

		return $archive;
	}

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase()
	{
		$tables = $this->db->getPlatform()->getTables();
		if (!empty($tables)) {
			$this->db->query('SET foreign_key_checks = 0');
			foreach ($tables as $table) {
				$this->db->query('DROP TABLE %table', $table['name']);
			}
			$this->db->query('SET foreign_key_checks = 1');
		}
	}

	/**
	 * Nahraje databazi
	 * @param string $file
	 * @author Jakub Vrána, Jan Tvrdík, Michael Moravec
	 * @license Apache License
	 */
	public function loadDatabase($file)
	{
		$this->db->transactional(function (Connection $db) use ($file) {
			$db->query('SET foreign_key_checks = 0');
			$query = file_get_contents($file);

			$delimiter = ';';
			$offset = 0;
			while ($query != '') {
				if (!$offset && preg_match('~^\\s*DELIMITER\\s+(.+)~i', $query, $match)) {
					$delimiter = $match[1];
					$query = substr($query, strlen($match[0]));
				} else {
					preg_match('(' . preg_quote($delimiter) . '|[\'`"]|/\\*|-- |#|$)', $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match
					$found = $match[0][0];
					$offset = $match[0][1] + strlen($found);

					if (!$found && rtrim($query) === '') {
						break;
					}

					if (!$found || $found == $delimiter) { // end of a query
						$q = substr($query, 0, $match[0][1]);

						$db->query($q);

						$query = substr($query, $offset);
						$offset = 0;
					} else { // find matching quote or comment end
						while (preg_match('~' . ($found == '/*' ? '\\*/' : (preg_match('~-- |#~', $found) ? "\n" : "$found|\\\\.")) . '|$~s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
							$s = $match[0][0];
							$offset = $match[0][1] + strlen($s);
							if ($s[0] !== '\\') {
								break;
							}
						}
					}
				}
			}
			$db->query('SET foreign_key_checks = 1');
		});
	}

}
