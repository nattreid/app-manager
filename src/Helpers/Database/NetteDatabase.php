<?php

declare(strict_types=1);

namespace NAttreid\AppManager\Helpers\Database;

use Nette\Database\Context;
use Nette\Database\Helpers;
use Nette\Database\Table\ActiveRow;

/**
 * Class NetteDatabase
 *
 * @author Attreid <attreid@gmail.com>
 */
class NetteDatabase implements IDriver
{

	/** @var Context */
	private $context;

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	/**
	 * Vrati nazvy tabulek
	 * @return string[]
	 */
	public function getTables()
	{
		$result = [];
		$tables = $this->context->getStructure()->getTables();
		foreach ($tables as $table) {
			$result[] = $table['name'];
		}
		return $result;
	}

	/**
	 * Vrati DDL tabulky
	 * @param string $table
	 * @return string
	 */
	public function getCreateTable(string $table): string
	{
		return (string) $this->context->query('SHOW CREATE TABLE ' . $table)->fetch()->{'Create Table'};
	}

	/**
	 * @param string $table
	 * @return string[][]
	 */
	public function getRows(string $table)
	{
		$result = [];
		$rows = $this->context->table($table);
		/* @var $row ActiveRow */
		foreach ($rows as $row) {
			$result[] = $row->toArray();
		}
		return $result;
	}

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase()
	{
		$tables = $this->getTables();
		if (!empty($tables)) {
			$this->context->query('SET foreign_key_checks = 0');
			foreach ($tables as $table) {
				$this->context->query('DROP TABLE ' . $table);
			}
			$this->context->query('SET foreign_key_checks = 1');
		}
	}

	/**
	 * Nahraje databazi
	 * @param string $file
	 */
	public function loadDatabase(string $file)
	{
		Helpers::loadFromFile($this->context->getConnection(), $file);
	}
}