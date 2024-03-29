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

namespace pocketmine\network\mcpe\protocol;

use pocketmine\utils\Binary;

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\UUID;

class AddPlayerPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	/** @var UUID */
	public $uuid;
	/** @var string */
	public $username;
	/** @var int|null */
	public $entityUniqueId = \null; //TODO
	/** @var int */
	public $entityRuntimeId;
	public $x;
	public $y;
	public $z;
	public $speedX = 0.0;
	public $speedY = 0.0;
	public $speedZ = 0.0;
	public $pitch = 0.0;
	public $headYaw = \null; //TODO
	public $yaw = 0.0;
	/** @var Item */
	public $item;
	public $metadata = [];

	public function decodePayload(){
		$this->uuid = $this->getUUID();
		$this->username = $this->getString();
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->getVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->pitch = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->headYaw = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->yaw = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->item = $this->getSlot();
		$this->metadata = $this->getEntityMetadata();
	}

	public function encodePayload(){
		$this->putUUID($this->uuid);
		$this->putString($this->username);
		$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVector3f($this->speedX, $this->speedY, $this->speedZ);
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->pitch)) : \pack("f", $this->pitch)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->headYaw ?? $this->yaw)) : \pack("f", $this->headYaw ?? $this->yaw)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->yaw)) : \pack("f", $this->yaw)));
		$this->putSlot($this->item);
		$this->putEntityMetadata($this->metadata);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPlayer($this);
	}

}
