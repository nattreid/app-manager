<?php

declare(strict_types=1);

namespace NAttreid\AppManager\Helpers\Database;

use Generator;

interface IDriver
{
	/**
	 * Vrati nazvy tabulek
	 * @return string[]
	 */
	public function getTables(): array;

	/**
	 * Vrati DDL tabulky
	 * @param string $table
	 * @return string
	 */
	public function getCreateTable(string $table): string;

	/**
	 * @param string $table
	 * @return Generator|string[][]
	 */
	public function getRows(string $table): Generator;

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase(): void;

	/**
	 * Nahraje databazi
	 * @param string $file
	 */
	public function loadDatabase(string $file): void;
}