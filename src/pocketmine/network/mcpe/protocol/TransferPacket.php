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

class TransferPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::TRANSFER_PACKET;

	public $address;
	public $port = 19132;

	public function decodePayload(){
		$this->address = $this->getString();
		$this->port = ((\unpack("v", $this->get(2))[1]));
	}

	public function encodePayload(){
		$this->putString($this->address);
		($this->buffer .= (\pack("v", $this->port)));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleTransfer($this);
	}

}
