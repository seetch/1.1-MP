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


use pocketmine\network\mcpe\NetworkSession;

class ContainerOpenPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::CONTAINER_OPEN_PACKET;

	public $windowid;
	public $type;
	public $x;
	public $y;
	public $z;
	public $entityUniqueId = -1;

	public function decodePayload(){
		$this->windowid = (\ord($this->get(1)));
		$this->type = (\ord($this->get(1)));
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->entityUniqueId = $this->getEntityUniqueId();
	}

	public function encodePayload(){
		($this->buffer .= \chr($this->windowid));
		($this->buffer .= \chr($this->type));
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putEntityUniqueId($this->entityUniqueId);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleContainerOpen($this);
	}

}
