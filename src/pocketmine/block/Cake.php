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

namespace pocketmine\block;

use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityEatBlockEvent;
use pocketmine\item\FoodSource;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Cake extends Transparent implements FoodSource{
	protected $id = self::CAKE_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 0.5;
	}

	public function getName() : string{
		return "Cake Block";
	}

	protected function recalculateBoundingBox(){
		$f = $this->getDamage() * 0.125; //1 slice width

		return new AxisAlignedBB(
			$this->x + 0.0625 + $f,
			$this->y,
			$this->z + 0.0625,
			$this->x + 1 - 0.0625,
			$this->y + 0.5,
			$this->z + 1 - 0.0625
		);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $facePos, Player $player = \null) : bool{
		$down = $this->getSide(Vector3::SIDE_DOWN);
		if($down->getId() !== self::AIR){
			$this->getLevel()->setBlock($blockReplace, $this, \true, \true);

			return \true;
		}

		return \false;
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR){ //Replace with common break method
				$this->getLevel()->setBlock($this, BlockFactory::get(Block::AIR), \true);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return \false;
	}

	public function getDrops(Item $item) : array{
		return [];
	}

	public function onActivate(Item $item, Player $player = \null) : bool{
		//TODO: refactor this into generic food handling
		if($player instanceof Player and $player->getFood() < $player->getMaxFood()){
			$player->getServer()->getPluginManager()->callEvent($ev = new EntityEatBlockEvent($player, $this));

			if(!$ev->isCancelled()){
				$player->addFood($ev->getFoodRestore());
				$player->addSaturation($ev->getSaturationRestore());
				foreach($ev->getAdditionalEffects() as $effect){
					$player->addEffect($effect);
				}

				$this->getLevel()->setBlock($this, $ev->getResidue());
				return \true;
			}
		}

		return \false;
	}

	public function getFoodRestore() : int{
		return 2;
	}

	public function getSaturationRestore() : float{
		return 0.4;
	}

	public function getResidue(){
		$clone = clone $this;
		$clone->meta++;
		if($clone->meta > 0x06){
			$clone = BlockFactory::get(Block::AIR);
		}
		return $clone;
	}

	/**
	 * @return Effect[]
	 */
	public function getAdditionalEffects() : array{
		return [];
	}
}
