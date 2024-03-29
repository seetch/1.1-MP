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

namespace pocketmine\inventory\recipe;

use pocketmine\item\Item;
use pocketmine\utils\UUID;

abstract class CraftingRecipe implements Recipe{
	/** @var UUID|null */
	protected $id = null;

	/**
	 * @return UUID|null
	 */
	public function getId() : ?UUID{
		return $this->id;
	}

	/**
	 * @param UUID $id
	 */
	public function setId(UUID $id) : void{
		if($this->id !== null){
			throw new \InvalidStateException("Id is already set");
		}

		$this->id = $id;
	}

	/**
	 * Returns a list of items needed to craft this recipe. This MUST NOT include Air items or items with a zero count.
	 *
	 * @return Item[]
	 */
	abstract public function getIngredientList() : array;

	/**
	 * Returns a list of items created by crafting this recipe.
	 *
	 * @return Item[]
	 */
	abstract public function getResults() : array;
}
