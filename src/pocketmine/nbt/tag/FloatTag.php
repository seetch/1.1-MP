<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\nbt\tag;

use pocketmine\nbt\NBT;

use pocketmine\utils\Binary;

class FloatTag extends NamedTag{

	/**
	 * FloatTag constructor.
	 *
	 * @param string $name
	 * @param float  $value
	 */
	public function __construct(string $name = "", float $value = 0.0){
		parent::__construct($name, $value);
	}

	public function getType(){
		return NBT::TAG_Float;
	}

	public function read(NBT $nbt, bool $network = \false){
		$this->value = ($nbt->endianness === 1 ? (\ENDIANNESS === 0 ? \unpack("f", $nbt->get(4))[1] : \unpack("f", \strrev($nbt->get(4)))[1]) : (\ENDIANNESS === 0 ? \unpack("f", \strrev($nbt->get(4)))[1] : \unpack("f", $nbt->get(4))[1]));
	}

	public function write(NBT $nbt, bool $network = \false){
		($nbt->buffer .= $nbt->endianness === 1 ? (\ENDIANNESS === 0 ? \pack("f", $this->value) : \strrev(\pack("f", $this->value))) : (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->value)) : \pack("f", $this->value)));
	}

	/**
	 * @return float
	 */
	public function &getValue() : float{
		return parent::getValue();
	}

	public function setValue($value){
		if(!\is_float($value) and !\is_int($value)){
			throw new \TypeError("FloatTag value must be of type float, " . \gettype($value) . " given");
		}
		parent::setValue((float) $value);
	}
}
