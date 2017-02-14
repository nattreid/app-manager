<?php

namespace NAttreid\AppManager\Helpers\Database;

interface IDriver
{
	/**
	 * Vrati nazvy tabulek
	 * @return string[]
	 */
	public function getTables();

	/**
	 * Vrati DDL tabulky
	 * @param string $table
	 * @return string
	 */
	public function getCreateTable($table);

	/**
	 * @param string $table
	 * @return string[][]
	 */
	public function getRows($table);

	/**
	 * Smaze vsechny tabulky v databazi
	 */
	public function dropDatabase();

	/**
	 * Nahraje databazi
	 * @param string $file
	 */
	public function loadDatabase($file);
}