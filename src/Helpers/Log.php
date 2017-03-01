<?php

declare(strict_types = 1);

namespace NAttreid\AppManager\Helpers;

use NAttreid\Utils\Date;
use NAttreid\Utils\Number;
use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * Class Log
 *
 * @property-read string $id
 * @property-read string $name
 * @property-read int $size
 * @property-read string $formatedSize
 * @property-read DateTime $changed
 * @property-read string $formatedChanged
 *
 * @author Attreid <attreid@gmail.com>
 */
class Log
{
	use SmartObject;

	/** @var string */
	private $id;

	/** @var string */
	private $name;

	/** @var int */
	private $size;

	/** @var DateTime */
	private $changed;

	public function __construct(string $id, string $path, string $file)
	{
		$this->id = $id;
		$this->name = $file;
		$this->size = filesize($path . $file);
		$this->changed = DateTime::createFromFormat('U', filemtime($path . $file));
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getSize(): int
	{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getFormatedSize(): string
	{
		return Number::size($this->size);
	}

	/**
	 * @return DateTime
	 */
	public function getChanged(): DateTime
	{
		return $this->changed;
	}

	/**
	 * @return string
	 */
	public function getFormatedChanged(): string
	{
		return Date::getDateTime($this->changed);
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}
}