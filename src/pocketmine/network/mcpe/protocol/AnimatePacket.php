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

class AnimatePacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::ANIMATE_PACKET;

	const ACTION_SWING_ARM = 1;

	const ACTION_STOP_SLEEP = 3;
	const ACTION_CRITICAL_HIT = 4;

	public $action;
	public $entityRuntimeId;
	public $float = 0.0; //TODO (Boat rowing time?)

	public function decodePayload(){
		$this->action = $this->getVarInt();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if($this->action & 0x80){
			$this->float = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		}
	}

	public function encodePayload(){
		$this->putVarInt($this->action);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if($this->action & 0x80){
			($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->float)) : \pack("f", $this->float)));
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAnimate($this);
	}

}
