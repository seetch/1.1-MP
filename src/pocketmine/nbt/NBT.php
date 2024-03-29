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

/**
 * Named Binary Tag handling classes
 */
namespace pocketmine\nbt;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\EndTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;



use pocketmine\utils\Binary;

/**
 * Named Binary Tag encoder/decoder
 */
class NBT{

	const LITTLE_ENDIAN = 0;
	const BIG_ENDIAN = 1;
	const TAG_End = 0;
	const TAG_Byte = 1;
	const TAG_Short = 2;
	const TAG_Int = 3;
	const TAG_Long = 4;
	const TAG_Float = 5;
	const TAG_Double = 6;
	const TAG_ByteArray = 7;
	const TAG_String = 8;
	const TAG_List = 9;
	const TAG_Compound = 10;
	const TAG_IntArray = 11;

	public $buffer;
	public $offset;
	public $endianness;
	private $data;

	/**
	 * @param int $type
	 *
	 * @return Tag
	 */
	public static function createTag(int $type){
		switch($type){
			case self::TAG_End:
				return new EndTag();
			case self::TAG_Byte:
				return new ByteTag();
			case self::TAG_Short:
				return new ShortTag();
			case self::TAG_Int:
				return new IntTag();
			case self::TAG_Long:
				return new LongTag();
			case self::TAG_Float:
				return new FloatTag();
			case self::TAG_Double:
				return new DoubleTag();
			case self::TAG_ByteArray:
				return new ByteArrayTag();
			case self::TAG_String:
				return new StringTag();
			case self::TAG_List:
				return new ListTag();
			case self::TAG_Compound:
				return new CompoundTag();
			case self::TAG_IntArray:
				return new IntArrayTag();
			default:
				throw new \InvalidArgumentException("Unknown NBT tag type $type");
		}
	}

	public static function matchList(ListTag $tag1, ListTag $tag2) : bool{
		if($tag1->getName() !== $tag2->getName() or $tag1->getCount() !== $tag2->getCount()){
			return \false;
		}

		foreach($tag1 as $k => $v){
			if(!($v instanceof Tag)){
				continue;
			}

			if(!isset($tag2->{$k}) or !($tag2->{$k} instanceof $v)){
				return \false;
			}

			if($v instanceof CompoundTag){
				if(!self::matchTree($v, $tag2->{$k})){
					return \false;
				}
			}elseif($v instanceof ListTag){
				if(!self::matchList($v, $tag2->{$k})){
					return \false;
				}
			}else{
				if($v->getValue() !== $tag2->{$k}->getValue()){
					return \false;
				}
			}
		}

		return \true;
	}

	public static function matchTree(CompoundTag $tag1, CompoundTag $tag2) : bool{
		if($tag1->getName() !== $tag2->getName() or $tag1->getCount() !== $tag2->getCount()){
			return \false;
		}

		foreach($tag1 as $k => $v){
			if(!($v instanceof Tag)){
				continue;
			}

			if(!isset($tag2->{$k}) or !($tag2->{$k} instanceof $v)){
				return \false;
			}

			if($v instanceof CompoundTag){
				if(!self::matchTree($v, $tag2->{$k})){
					return \false;
				}
			}elseif($v instanceof ListTag){
				if(!self::matchList($v, $tag2->{$k})){
					return \false;
				}
			}else{
				if($v->getValue() !== $tag2->{$k}->getValue()){
					return \false;
				}
			}
		}

		return \true;
	}

	public function get($len){
		if($len < 0){
			$this->offset = \strlen($this->buffer) - 1;
			return "";
		}elseif($len === \true){
			return \substr($this->buffer, $this->offset);
		}

		return $len === 1 ? $this->buffer{$this->offset++} : \substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	public function put($v){
		$this->buffer .= $v;
	}

	public function feof() : bool{
		return !isset($this->buffer{$this->offset});
	}

	public function __construct($endianness = self::LITTLE_ENDIAN){
		$this->offset = 0;
		$this->endianness = $endianness & 0x01;
	}

	public function read($buffer, $doMultiple = \false, bool $network = \false){
		$this->offset = 0;
		$this->buffer = $buffer;
		$this->data = $this->readTag($network);
		if($doMultiple and $this->offset < \strlen($this->buffer)){
			$this->data = [$this->data];
			do{
				$this->data[] = $this->readTag($network);
			}while($this->offset < \strlen($this->buffer));
		}
		$this->buffer = "";
	}

	public function readCompressed($buffer){
		$this->read(\zlib_decode($buffer));
	}

	/**
	 * @param bool $network
	 *
	 * @return string|bool
	 */
	public function write(bool $network = \false){
		$this->offset = 0;
		$this->buffer = "";

		if($this->data instanceof CompoundTag){
			$this->writeTag($this->data, $network);

			return $this->buffer;
		}elseif(\is_array($this->data)){
			foreach($this->data as $tag){
				$this->writeTag($tag, $network);
			}
			return $this->buffer;
		}

		return \false;
	}

	public function writeCompressed($compression = ZLIB_ENCODING_GZIP, $level = 7){
		if(($write = $this->write()) !== \false){
			return \zlib_encode($write, $compression, $level);
		}

		return \false;
	}

	public function readTag(bool $network = \false){
		if($this->feof()){
			return new EndTag();
		}

		$tagType = (\ord($this->get(1)));
		$tag = self::createTag($tagType);

		if($tag instanceof NamedTag){
			$tag->setName($this->getString($network));
			$tag->read($this, $network);
		}

		return $tag;
	}

	public function writeTag(Tag $tag, bool $network = \false){
		($this->buffer .= \chr($tag->getType()));
		if($tag instanceof NamedTag){
			$this->putString($tag->getName(), $network);
		}
		$tag->write($this, $network);
	}

	public function getByte() : int{
		return (\ord($this->get(1)));
	}

	public function getSignedByte() : int{
		return (\ord($this->get(1)) << 56 >> 56);
	}

	public function putByte($v){
		$this->buffer .= (\chr($v));
	}

	public function getShort() : int{
		return $this->endianness === self::BIG_ENDIAN ? (\unpack("n", $this->get(2))[1]) : (\unpack("v", $this->get(2))[1]);
	}

	public function getSignedShort() : int{
		return $this->endianness === self::BIG_ENDIAN ? (\unpack("n", $this->get(2))[1] << 48 >> 48) : (\unpack("v", $this->get(2))[1] << 48 >> 48);
	}

	public function putShort($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? (\pack("n", $v)) : (\pack("v", $v));
	}

	public function getInt(bool $network = \false) : int{
		if($network === \true){
			return Binary::readVarInt($this->buffer, $this->offset);
		}
		return $this->endianness === self::BIG_ENDIAN ? (\unpack("N", $this->get(4))[1] << 32 >> 32) : (\unpack("V", $this->get(4))[1] << 32 >> 32);
	}

	public function putInt($v, bool $network = \false){
		if($network === \true){
			$this->buffer .= Binary::writeVarInt($v);
		}else{
			$this->buffer .= $this->endianness === self::BIG_ENDIAN ? (\pack("N", $v)) : (\pack("V", $v));
		}
	}

	public function getLong(bool $network = \false) : int{
		if($network){
			return Binary::readVarLong($this->buffer, $this->offset);
		}
		return $this->endianness === self::BIG_ENDIAN ? Binary::readLong($this->get(8)) : Binary::readLLong($this->get(8));
	}

	public function putLong($v, bool $network = \false){
		if($network){
			$this->buffer .= Binary::writeVarLong($v);
		}else{
			$this->buffer .= $this->endianness === self::BIG_ENDIAN ? (\pack("NN", $v >> 32, $v & 0xFFFFFFFF)) : (\pack("VV", $v & 0xFFFFFFFF, $v >> 32));
		}
	}

	public function getFloat() : float{
		return $this->endianness === self::BIG_ENDIAN ? (\ENDIANNESS === 0 ? \unpack("f", $this->get(4))[1] : \unpack("f", \strrev($this->get(4)))[1]) : (\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]);
	}

	public function putFloat($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? (\ENDIANNESS === 0 ? \pack("f", $v) : \strrev(\pack("f", $v))) : (\ENDIANNESS === 0 ? \strrev(\pack("f", $v)) : \pack("f", $v));
	}

	public function getDouble() : float{
		return $this->endianness === self::BIG_ENDIAN ? (\ENDIANNESS === 0 ? \unpack("d", $this->get(8))[1] : \unpack("d", \strrev($this->get(8)))[1]) : (\ENDIANNESS === 0 ? \unpack("d", \strrev($this->get(8)))[1] : \unpack("d", $this->get(8))[1]);
	}

	public function putDouble($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? (\ENDIANNESS === 0 ? \pack("d", $v) : \strrev(\pack("d", $v))) : (\ENDIANNESS === 0 ? \strrev(\pack("d", $v)) : \pack("d", $v));
	}

	public function getString(bool $network = \false){
		$len = $network ? Binary::readUnsignedVarInt($this->buffer, $this->offset) : ($this->endianness === 1 ? (\unpack("n", $this->get(2))[1]) : (\unpack("v", $this->get(2))[1]));
		return $this->get($len);
	}

	public function putString($v, bool $network = \false){
		if($network === \true){
			($this->buffer .= Binary::writeUnsignedVarInt(\strlen($v)));
		}else{
			($this->buffer .= $this->endianness === 1 ? (\pack("n", \strlen($v))) : (\pack("v", \strlen($v))));
		}
		$this->buffer .= $v;
	}

	public function getArray() : array{
		$data = [];
		self::toArray($data, $this->data);
		return $data;
	}

	private static function toArray(array &$data, Tag $tag){
		/** @var CompoundTag[]|ListTag[]|IntArrayTag[] $tag */
		foreach($tag as $key => $value){
			if($value instanceof CompoundTag or $value instanceof ListTag or $value instanceof IntArrayTag){
				$data[$key] = [];
				self::toArray($data[$key], $value);
			}else{
				$data[$key] = $value->getValue();
			}
		}
	}

	public static function fromArrayGuesser($key, $value){
		if(\is_int($value)){
			return new IntTag($key, $value);
		}elseif(\is_float($value)){
			return new FloatTag($key, $value);
		}elseif(\is_string($value)){
			return new StringTag($key, $value);
		}elseif(\is_bool($value)){
			return new ByteTag($key, $value ? 1 : 0);
		}

		return \null;
	}

	private static function fromArray(Tag $tag, array $data, callable $guesser){
		foreach($data as $key => $value){
			if(\is_array($value)){
				$isNumeric = \true;
				$isIntArray = \true;
				foreach($value as $k => $v){
					if(!\is_numeric($k)){
						$isNumeric = \false;
						break;
					}elseif(!\is_int($v)){
						$isIntArray = \false;
					}
				}
				$tag{$key} = $isNumeric ? ($isIntArray ? new IntArrayTag($key, []) : new ListTag($key, [])) : new CompoundTag($key, []);
				self::fromArray($tag->{$key}, $value, $guesser);
			}else{
				$v = \call_user_func($guesser, $key, $value);
				if($v instanceof Tag){
					$tag{$key} = $v;
				}
			}
		}
	}

	public function setArray(array $data, callable $guesser = \null){
		$this->data = new CompoundTag("", []);
		self::fromArray($this->data, $data, $guesser ?? [self::class, "fromArrayGuesser"]);
	}

	/**
	 * @return CompoundTag|array
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * @param CompoundTag|array $data
	 */
	public function setData($data){
		$this->data = $data;
	}

}
