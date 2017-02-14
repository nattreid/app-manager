<?php

namespace NAttreid\AppManager\Helpers\Database;

use Nextras\Dbal\Connection;
use Nextras\Dbal\Utils\FileImporter;

/**
 * Class NextrasDbal
 *
 * @author Attreid <attreid@gmail.com>
 */
class NextrasDbal implements IDriver
{
	/** @var Connection */
	private $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Vrati nazvy tabulek
	 * @return string[]
	 */
	public function getTables()
	{
		$tables = $this->connection->getPlatform()->getTables();
		foreach ($tables as $table) {
			yield $table['name'];
		}
	}

	/**
	 * Vrati DDL tabulky
	 * @param string $table
	 * @return string
	 */
	public function getCreateTable($table)
	{
		return $this->connection->query('SHOW CREATE TABLE %table', $table)->fetch()->{'Create Table'};
	}

	/**
	 * @param string $table
	 * @return string[][]
	 */
	public function getRows($table)
	{
		$rows = $this->connection->query('SELECT * FROM %table', $table);
		foreach ($rows as $row) {
			yield $row->toArray();
		}
	}

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase()
	{
		$tables = $this->getTables();
		if (!empty($tables)) {
			$this->connection->query('SET foreign_key_checks = 0');
			foreach ($tables as $table) {
				$this->connection->query('DROP TABLE %table', $table);
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