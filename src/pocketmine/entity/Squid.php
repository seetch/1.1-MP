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

namespace pocketmine\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;

class Squid extends WaterAnimal{
	const NETWORK_ID = 17;

	public $width = 0.95;
	public $height = 0.95;

	/** @var Vector3 */
	public $swimDirection = \null;
	public $swimSpeed = 0.1;

	private $switchDirectionTicker = 0;

	public function initEntity(){
		$this->setMaxHealth(10);
		parent::initEntity();
	}

	public function getName() : string{
		return "Squid";
	}

	public function attack(EntityDamageEvent $source){
		parent::attack($source);
		if($source->isCancelled()){
			return;
		}

		if($source instanceof EntityDamageByEntityEvent){
			$this->swimSpeed = \mt_rand(150, 350) / 2000;
			$e = $source->getDamager();
			if($e !== \null){
				$this->swimDirection = (new Vector3($this->x - $e->x, $this->y - $e->y, $this->z - $e->z))->normalize();
			}

			$pk = new EntityEventPacket();
			$pk->entityRuntimeId = $this->getId();
			$pk->event = EntityEventPacket::SQUID_INK_CLOUD;
			$this->server->broadcastPacket($this->hasSpawned, $pk);
		}
	}

	private function generateRandomDirection() : Vector3{
		return new Vector3(\mt_rand(-1000, 1000) / 1000, \mt_rand(-500, 500) / 1000, \mt_rand(-1000, 1000) / 1000);
	}


	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed !== \false){
			return \false;
		}

		if(++$this->switchDirectionTicker === 100 or $this->isCollided){
			$this->switchDirectionTicker = 0;
			if(\mt_rand(0, 100) < 50){
				$this->swimDirection = \null;
			}
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->isAlive()){

			if($this->y > 62 and $this->swimDirection !== \null){
				$this->swimDirection->y = -0.5;
			}

			$inWater = $this->isInsideOfWater();
			if(!$inWater){
				$this->swimDirection = \null;
			}elseif($this->swimDirection !== \null){
				if($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2 <= $this->swimDirection->lengthSquared()){
					$this->motionX = $this->swimDirection->x * $this->swimSpeed;
					$this->motionY = $this->swimDirection->y * $this->swimSpeed;
					$this->motionZ = $this->swimDirection->z * $this->swimSpeed;
				}
			}else{
				$this->swimDirection = $this->generateRandomDirection();
				$this->swimSpeed = \mt_rand(50, 100) / 2000;
			}

			$f = \sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
			$this->yaw = (-\atan2($this->motionX, $this->motionZ) * 180 / M_PI);
			$this->pitch = (-\atan2($f, $this->motionY) * 180 / M_PI);
		}

		return $hasUpdate;
	}

	protected function applyGravity(){
		if(!$this->isInsideOfWater()){
			parent::applyGravity();
		}
	}


	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->type = Squid::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(ItemItem::DYE, 0, \mt_rand(1, 3))
		];
	}
}
