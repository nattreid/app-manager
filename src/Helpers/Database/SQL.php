<?php

declare(strict_types=1);

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

	/** @var int|null */
	private $maxRows;

	/** @var string[] */
	private $excludeTables;

	public function __construct(?int $maxRows, array $excludeTables, Nextras $nextras = null, Nette $nette = null)
	{
		if ($nextras !== null) {
			$this->driver = new NextrasDbal($nextras);
		} elseif ($nette !== null) {
			$this->driver = new NetteDatabase($nette);
		} else {
			$this->isSupported = false;
		}
		$this->maxRows = $maxRows;
		$this->excludeTables = $excludeTables;
	}

	/**
	 * @throws NotSupportedException
	 */
	private function check(): void
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
	public function backupDatabase(): TempFile
	{
		$this->check();
		$backup = new TempFile('database.sql', true);
		$tables = $this->driver->getTables();

		$backup->write("SET NAMES utf8;\n");
		$backup->write("SET time_zone = '+00:00';\n");
		$backup->write("SET foreign_key_checks = 0;\n");
		$backup->write("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n\n");

		foreach ($tables as $table) {
			if (in_array($table, $this->excludeTables)) {
				continue;
			}
			$backup->write("DROP TABLE IF EXISTS `$table`;\n");

			$createTable = $this->driver->getCreateTable($table);
			$backup->write("$createTable;\n\n");

			$rows = $this->driver->getRows($table);
			$insert = [];
			$columns = null;
			$counter = 0;
			foreach ($rows as $row) {
				$counter++;
				if ($columns === null) {
					$colName = [];
					foreach ($row as $key => $value) {
						$colName[] = "`$key`";
					}
					$columns = implode(', ', $colName);
				}
				$cols = [];
				foreach ($row as $column) {
					if (is_string($column)) {
						$column = addslashes($column);
						$column = preg_replace("/\n/", "\\n", $column);
					} elseif ($column === null) {
						$cols[] = 'NULL';
						continue;
					}
					$cols[] = '"' . $column . '"';
				}
				$insert[] = implode(', ', $cols);

				if ($this->maxRows && $counter >= $this->maxRows) {
					$this->writeInsert($backup, $table, $columns, $insert);
					$counter = 0;
					$insert = [];
				}
			}
			$this->writeInsert($backup, $table, $columns, $insert);
			$backup->write("\n\n");
		}

		return $backup;
	}

	private function writeInsert(TempFile $backup, string $table, ?string $columns, array $insert): void
	{
		if (!empty($insert) && $columns !== null) {
			$backup->write("INSERT INTO `$table` ($columns) VALUES\n(" . implode("),\n(", $insert) . ");\n");
		}
	}

	/**
	 * Vrati zabalenou zalohu databaze
	 * @param TempFile|null $archive
	 * @return TempFile
	 */
	public function compressBackupDatabase(TempFile $archive = null): TempFile
	{
		$this->check();
		if ($archive === null) {
			$archive = new TempFile('databaze.zip');
		}
		File::zip($this->backupDatabase(), (string) $archive);

		return $archive;
	}

	/**
	 * Smaze vsechny tabulky v databazi
	 * @throws NotSupportedException
	 */
	public function dropDatabase(): void
	{
		$this->check();
		$this->driver->dropDatabase();
	}

	/**
	 * Nahraje databazi
	 * @param string $file
	 * @throws NotSupportedException
	 */
	public function loadDatabase(string $file): void
	{
		$this->check();
		$this->driver->loadDatabase($file);
	}
}