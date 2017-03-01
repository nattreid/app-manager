<?php

declare(strict_types = 1);

namespace NAttreid\AppManager\Helpers\Database;

use Generator;
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
	 * @return string[]|Generator
	 */
	public function getTables(): Generator
	{
		$tables = $this->connection->getPlatform()->getTables();
		foreach ($tables as $table) {
			yield $table['name'];
		}
		yield [];
	}

	/**
	 * Vrati DDL tabulky
	 * @param string $table
	 * @return string
	 */
	public function getCreateTable(string $table): string
	{
		return $this->connection->query('SHOW CREATE TABLE %table', $table)->fetch()->{'Create Table'};
	}

	/**
	 * @param string $table
	 * @return string[][]|Generator
	 */
	public function getRows(string $table): Generator
	{
		$rows = $this->connection->query('SELECT * FROM %table', $table);
		foreach ($rows as $row) {
			yield $row->toArray();
		}
		yield [];
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
	public function loadDatabase(string $file)
	{
		$this->connection->transactional(function (Connection $db) use ($file) {
			$db->query('SET foreign_key_checks = 0');
			FileImporter::executeFile($db, $file);
			$db->query('SET foreign_key_checks = 1');
		});
	}
}