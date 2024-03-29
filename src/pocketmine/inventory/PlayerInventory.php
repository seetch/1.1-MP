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

namespace pocketmine\inventory;

use pocketmine\entity\Human;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;

class PlayerInventory extends BaseInventory{
	/** @var Human */
	protected $holder;
	/** @var int */
	protected $itemInHandIndex = 0;
	/** @var int[] */
	protected $hotbar;

	public function __construct(Human $player){
		$this->holder = $player;
		$this->resetHotbar(false);
		parent::__construct();
	}

	public function getName() : string{
		return "Player";
	}

	public function getDefaultSize() : int{
		return 36;
	}

	/**
	 * Called when a client equips a hotbar slot. This method should not be used by plugins.
	 * This method will call PlayerItemHeldEvent.
	 *
	 * @param int      $hotbarSlot Number of the hotbar slot to equip.
	 * @param int|null $inventorySlot Inventory slot to map to the specified hotbar slot. Supply null to make no change to the link.
	 *
	 * @return bool if the equipment change was successful, false if not.
	 */
	public function equipItem(int $hotbarSlot, $inventorySlot = null) : bool{
		if($inventorySlot === null){
			$inventorySlot = $this->getHotbarSlotIndex($hotbarSlot);
		}

		if($hotbarSlot < 0 or $hotbarSlot >= $this->getHotbarSize() or $inventorySlot < -1 or $inventorySlot >= $this->getSize()){
			$this->sendContents($this->getHolder());
			return false;
		}

		if($inventorySlot === -1){
			$item = ItemFactory::get(Item::AIR, 0, 0);
		}else{
			$item = $this->getItem($inventorySlot);
		}

		$this->getHolder()->getLevel()->getServer()->getPluginManager()->callEvent($ev = new PlayerItemHeldEvent($this->getHolder(), $item, $inventorySlot, $hotbarSlot));

		if($ev->isCancelled()){
			$this->sendContents($this->getHolder());
			return false;
		}

		$this->setHotbarSlotIndex($hotbarSlot, $inventorySlot);
		$this->setHeldItemIndex($hotbarSlot, false);

		return true;
	}

	/**
	 * Returns the index of the inventory slot mapped to the specified hotbar slot, or -1 if the hotbar slot does not exist.
	 * @param int $index
	 *
	 * @return int
	 */
	public function getHotbarSlotIndex($index) : int{
		return $this->hotbar[$index] ?? -1;
	}

	/**
	 * Links a hotbar slot to the specified slot in the main inventory. -1 links to no slot and will clear the hotbar slot.
	 * This method is intended for use in network interaction with clients only.
	 *
	 * NOTE: Do not change hotbar slot mapping with plugins, this will cause myriad client-sided bugs, especially with desktop GUI clients.
	 *
	 * @param int $hotbarSlot
	 * @param int $inventorySlot
	 */
	public function setHotbarSlotIndex($hotbarSlot, $inventorySlot) : void{
		if($hotbarSlot < 0 or $hotbarSlot >= $this->getHotbarSize()){
			throw new \InvalidArgumentException("Hotbar slot index \"$hotbarSlot\" is out of range");
		}elseif($inventorySlot < -1 or $inventorySlot >= $this->getSize()){
			throw new \InvalidArgumentException("Inventory slot index \"$inventorySlot\" is out of range");
		}

		if($inventorySlot !== -1 and ($alreadyEquippedIndex = array_search($inventorySlot, $this->hotbar)) !== false){
			/* Swap the slots
			 * This assumes that the equipped slot can only be equipped in one other slot
			 * it will not account for ancient bugs where the same slot ended up linked to several hotbar slots.
			 * Such bugs will require a hotbar reset to default.
			 */
			$this->hotbar[$alreadyEquippedIndex] = $this->hotbar[$hotbarSlot];
		}

		$this->hotbar[$hotbarSlot] = $inventorySlot;
	}

	/**
	 * Returns the item in the slot linked to the specified hotbar slot, or Air if the slot is not linked to any hotbar slot.
	 * @param int $hotbarSlotIndex
	 *
	 * @return Item
	 */
	public function getHotbarSlotItem(int $hotbarSlotIndex) : Item{
		$inventorySlot = $this->getHotbarSlotIndex($hotbarSlotIndex);
		if($inventorySlot !== -1){
			return $this->getItem($inventorySlot);
		}else{
			return ItemFactory::get(Item::AIR, 0, 0);
		}
	}

	/**
	 * Resets hotbar links to their original defaults.
	 * @param bool $send Whether to send changes to the holder.
	 */
	public function resetHotbar(bool $send = true) : void{
		$this->hotbar = range(0, $this->getHotbarSize() - 1, 1);
		if($send){
			$this->sendContents($this->getHolder());
		}
	}

	/**
	 * Returns the hotbar slot number the holder is currently holding.
	 * @return int
	 */
	public function getHeldItemIndex() : int{
		return $this->itemInHandIndex;
	}

	/**
	 * Sets which hotbar slot the player is currently loading.
	 *
	 * @param int  $index 0-8 index of the hotbar slot to hold
	 * @param bool $send  Whether to send updates back to the inventory holder. This should usually be true for plugin calls.
	 *                    It should only be false to prevent feedback loops of equipment packets between client and server.
	 */
	public function setHeldItemIndex($index, $send = true) : void{
		if($index >= 0 and $index < $this->getHotbarSize()){
			$this->itemInHandIndex = $index;

			if($this->getHolder() instanceof Player and $send){
				$this->sendHeldItem($this->getHolder());
			}

			$this->sendHeldItem($this->getHolder()->getViewers());
		}else{
			throw new \InvalidArgumentException("Hotbar slot index \"$index\" is out of range");
		}
	}

	/**
	 * Returns the currently-held item.
	 *
	 * @return Item
	 */
	public function getItemInHand() : Item{
		return $this->getHotbarSlotItem($this->itemInHandIndex);
	}

	/**
	 * Sets the item in the currently-held slot to the specified item.
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setItemInHand(Item $item) : bool{
		return $this->setItem($this->getHeldItemSlot(), $item);
	}

	/**
	 * Returns the hotbar slot number currently held.
	 *
	 * @return int
	 */
	public function getHeldItemSlot() : int{
		return $this->getHotbarSlotIndex($this->itemInHandIndex);
	}

	/**
	 * Sets the hotbar slot link of the currently-held hotbar slot.
	 * @deprecated Do not change hotbar slot mapping with plugins, this will cause myriad client-sided bugs, especially with desktop GUI clients.
	 *
	 * @param int $slot
	 */
	public function setHeldItemSlot($slot) : void{
		if($slot >= -1 and $slot < $this->getSize()){
			$this->setHotbarSlotIndex($this->getHeldItemIndex(), $slot);
		}
	}

	/**
	 * Sends the currently-held item to specified targets.
	 * @param Player|Player[] $target
	 */
	public function sendHeldItem($target) : void{
		$item = $this->getItemInHand();

		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getHolder()->getId();
		$pk->item = $item;
		$pk->inventorySlot = $this->getHeldItemSlot();
		$pk->hotbarSlot = $this->getHeldItemIndex();
		$pk->windowId = ContainerIds::INVENTORY;

		if(!is_array($target)){
			$target->dataPacket($pk);
			if($this->getHeldItemSlot() !== -1 and $target === $this->getHolder()){
				$this->sendSlot($this->getHeldItemSlot(), $target);
			}
		}else{
			$this->getHolder()->getLevel()->getServer()->broadcastPacket($target, $pk);
			if($this->getHeldItemSlot() !== -1 and in_array($this->getHolder(), $target, true)){
				$this->sendSlot($this->getHeldItemSlot(), $this->getHolder());
			}
		}
	}

	/**
	 * Returns the number of slots in the hotbar.
	 * @return int
	 */
	public function getHotbarSize(){
		return 9;
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendContents($target) : void{
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new ContainerSetContentPacket();
		$pk->slots = [];
		for($i = 0; $i < $this->getSize(); ++$i){
			$pk->slots[$i] = $this->getItem($i);
		}

		//Because PE is stupid and shows 9 less slots than you send it, give it 9 dummy slots so it shows all the REAL slots.
		for($i = $this->getSize(); $i < $this->getSize() + $this->getHotbarSize(); ++$i){
			$pk->slots[$i] = ItemFactory::get(Item::AIR, 0, 0);
		}

		foreach($target as $player){
			$pk->hotbar = [];
			if($player === $this->getHolder()){
				for($i = 0; $i < $this->getHotbarSize(); ++$i){
					$index = $this->getHotbarSlotIndex($i);
					$pk->hotbar[] = $index <= -1 ? -1 : $index + $this->getHotbarSize();
				}
			}
			if(($id = $player->getWindowId($this)) === -1 or $player->spawned !== true){
				$this->close($player);
				continue;
			}
			$pk->windowid = $id;
			$pk->targetEid = $player->getId(); //TODO: check if this is correct
			$player->dataPacket(clone $pk);
		}
	}

	public function sendCreativeContents(){
		$pk = new ContainerSetContentPacket();
		$pk->windowid = ContainerIds::CREATIVE;
		if($this->getHolder()->getGamemode() === Player::CREATIVE){
			foreach(Item::getCreativeItems() as $i => $item){
				$pk->slots[$i] = clone $item;
			}
		}
		$pk->targetEid = $this->getHolder()->getId();
		$this->getHolder()->dataPacket($pk);
	}

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendSlot($index, $target) : void{
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new ContainerSetSlotPacket();
		$pk->slot = $index;
		$pk->item = clone $this->getItem($index);

		foreach($target as $player){
			if($player === $this->getHolder()){
				$pk->windowid = 0;
				$player->dataPacket(clone $pk);
			}else{
				if(($id = $player->getWindowId($this)) === -1){
					$this->close($player);
					continue;
				}
				$pk->windowid = $id;
				$player->dataPacket(clone $pk);
			}
		}
	}

	/**
	 * @return Human|Player
	 */
	public function getHolder(){
		return $this->holder;
	}
}
