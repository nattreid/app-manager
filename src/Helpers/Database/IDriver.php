<?php

declare(strict_types = 1);

namespace NAttreid\AppManager\Helpers\Database;

use Generator;

interface IDriver
{
	/**
	 * Vrati nazvy tabulek
	 * @return string[]|Generator
	 */
	public function getTables(): Generator;

	/**
	 * Vrati DDL tabulky
	 * @param string $table
	 * @return string
	 */
	public function getCreateTable(string $table): string;

	/**
	 * @param string $table
	 * @return string[][]|Generator
	 */
	public function getRows(string $table): Generator;

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase();

	/**
	 * Nahraje databazi
	 * @param string $file
	 */
	public function loadDatabase(string $file);
}