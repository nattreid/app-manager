<?php

namespace NAttreid\AppManager\Helpers\Database;

use NAttreid\Utils\File;
use NAttreid\Utils\TempFile;
use Nette\Database\Context as Nette;
use Nette\NotSupportedException;
use Nextras\Dbal\Connection as Nextras;

/**
 * Class SQL
 *
 * @author Attreid <attreid@gmail.com>
 */
class SQL
{
	/** @var IDriver */
	private $driver;

	/** @var bool */
	private $isSupported = true;

	/**
	 * Database constructor.
	 */
	public function __construct(Nextras $nextras = null, Nette $nette = null)
	{
		if ($nextras !== null) {
			$this->driver = new NextrasDbal($nextras);
		} elseif ($nette !== null) {
			$this->driver = new NetteDatabase($nette);
		} else {
			$this->isSupported = false;
		}
	}

	/**
	 * @throws NotSupportedException
	 */
	private function check()
	{
		if (!$this->isSupported) {
			throw new NotSupportedException();
		}
	}

	/**
	 * Vrati zalohu databaze
	 * @return TempFile
	 * @throws NotSupportedException
	 */
	public function backupDatabase()
	{
		$this->check();
		$backup = new TempFile('database.sql', true);
		$tables = $this->driver->getTables();

		$backup->write("SET NAMES utf8;\n");
		$backup->write("SET time_zone = '+00:00';\n");
		$backup->write("SET foreign_key_checks = 0;\n");
		$backup->write("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n\n");

		foreach ($tables as $table) {
			$backup->write("DROP TABLE IF EXISTS `$table`;\n");

			$createTable = $this->driver->getCreateTable($table);
			$backup->write("$createTable;\n\n");

			$rows = $this->driver->getRows($table);
			$insert = [];
			$columns = null;
			foreach ($rows as $row) {

				if ($columns === null) {
					$colName = [];
					foreach ($row as $key => $value) {
						$colName[] = "`$key`";
					}
					$columns = implode(', ', $colName);
				}
				$cols = [];
				foreach ($row as $column) {
					$column = addslashes($column);
					$column = preg_replace("/\n/", "\\n", $column);
					$cols[] = '"' . $column . '"';
				}
				$insert[] = implode(', ', $cols);
			}

			if (!empty($insert)) {
				$backup->write("INSERT INTO `$table` ($columns) VALUES\n(" . implode("),\n(", $insert) . ");\n");
			}

			$backup->write("\n\n");
		}

		return $backup;
	}

	/**
	 * Vrati zabalenou zalohu databaze
	 * @return TempFile
	 * @throws NotSupportedException
	 */
	public function compressBackupDatabase()
	{
		$this->check();
		$archive = new TempFile('databaze.zip');
		File::zip($this->backupDatabase(), $archive);

		return $archive;
	}

	/**
	 * Smaze vsechny tabulky v databazi
	 * @throws NotSupportedException
	 */
	public function dropDatabase()
	{
		$this->check();
		$this->driver->dropDatabase();
	}

	/**
	 * Nahraje databazi
	 * @param string $file
	 * @throws NotSupportedException
	 */
	public function loadDatabase($file)
	{
		$this->check();
		$this->driver->loadDatabase($file);
	}
}