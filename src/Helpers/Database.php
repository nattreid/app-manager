<?php

namespace NAttreid\AppManager\Helpers;

use NAttreid\Utils\File;
use NAttreid\Utils\TempFile;
use Nextras\Dbal\Connection;
use Nextras\Dbal\Utils\FileImporter;

/**
 * Class Database
 *
 * @author Attreid <attreid@gmail.com>
 */
class Database
{
	/** @var Connection */
	private $connection;

	/**
	 * Database constructor.
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Vrati zalohu databaze
	 * @return TempFile
	 */
	public function backupDatabase()
	{
		$backup = new TempFile('database.sql', true);
		$tables = $this->connection->getPlatform()->getTables();

		$backup->write("SET NAMES utf8;\n");
		$backup->write("SET time_zone = '+00:00';\n");
		$backup->write("SET foreign_key_checks = 0;\n");
		$backup->write("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n\n");

		foreach ($tables as $table) {
			$tableName = $table['name'];
			$backup->write("DROP TABLE IF EXISTS `$tableName`;\n");

			$createTable = $this->connection->query("SHOW CREATE TABLE %table", $tableName)->fetch()->{'Create Table'};
			$backup->write("$createTable;\n\n");

			$rows = $this->connection->query("SELECT * FROM %table", $tableName);
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

		return $backup;
	}

	/**
	 * Vrati zabalenou zalohu databaze
	 * @return TempFile
	 */
	public function compressBackupDatabase()
	{
		$archive = new TempFile('databaze.zip');
		File::zip($this->backupDatabase(), $archive);

		return $archive;
	}

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase()
	{
		$tables = $this->connection->getPlatform()->getTables();
		if (!empty($tables)) {
			$this->connection->query('SET foreign_key_checks = 0');
			foreach ($tables as $table) {
				$this->connection->query('DROP TABLE %table', $table['name']);
			}
			$this->connection->query('SET foreign_key_checks = 1');
		}
	}

	/**
	 * Nahraje databazi
	 * @param string $file
	 */
	public function loadDatabase($file)
	{
		$this->connection->transactional(function (Connection $db) use ($file) {
			$db->query('SET foreign_key_checks = 0');
			FileImporter::executeFile($db, $file);
			$db->query('SET foreign_key_checks = 1');
		});
	}
}