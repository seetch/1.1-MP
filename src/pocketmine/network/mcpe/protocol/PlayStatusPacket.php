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

class PlayStatusPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::PLAY_STATUS_PACKET;

	const LOGIN_SUCCESS = 0;
	const LOGIN_FAILED_CLIENT = 1;
	const LOGIN_FAILED_SERVER = 2;
	const PLAYER_SPAWN = 3;
	const LOGIN_FAILED_INVALID_TENANT = 4;
	const LOGIN_FAILED_VANILLA_EDU = 5;
	const LOGIN_FAILED_EDU_VANILLA = 6;

	public $status;

	public function decodePayload(){
		$this->status = ((\unpack("N", $this->get(4))[1] << 32 >> 32));
	}

	public function canBeSentBeforeLogin() : bool{
		return \true;
	}

	public function encodePayload(){
		($this->buffer .= (\pack("N", $this->status)));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayStatus($this);
	}

}
