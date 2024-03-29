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

class DisconnectPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::DISCONNECT_PACKET;

	public $hideDisconnectionScreen = \false;
	public $message;

	public function canBeSentBeforeLogin() : bool{
		return \true;
	}

	public function decodePayload(){
		$this->hideDisconnectionScreen = (($this->get(1) !== "\x00"));
		$this->message = $this->getString();
	}

	public function encodePayload(){
		($this->buffer .= ($this->hideDisconnectionScreen ? "\x01" : "\x00"));
		if(!$this->hideDisconnectionScreen){
			$this->putString($this->message);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleDisconnect($this);
	}

}
