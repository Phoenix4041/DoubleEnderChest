<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\storage;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use Phoenix4041\DoubleEnderChest\Loader;
use Phoenix4041\DoubleEnderChest\storage\task\LoadInventoryTask;
use Phoenix4041\DoubleEnderChest\storage\task\SaveAllInventoriesTask;
use Phoenix4041\DoubleEnderChest\storage\task\SaveInventoryTask;

final class EnderChestRepository{

	public const SLOTS = 54;

	/** @var array<string, string|null> uuid => nbt blob, null means confirmed empty */
	private array $cache = [];

	public function __construct(
		private readonly Loader $plugin,
		private readonly string $databasePath,
		private readonly int $workerId
	){
	}

	public function isLoaded(Player $player) : bool{
		return array_key_exists($player->getUniqueId()->toString(), $this->cache);
	}

	/**
	 * @return Item[] indexed by slot 0..SLOTS-1
	 */
	public function getItems(Player $player) : array{
		$blob = $this->cache[$player->getUniqueId()->toString()] ?? null;
		return self::decode($blob);
	}

	public function load(Player $player) : void{
		$this->plugin->getServer()->getAsyncPool()->submitTaskToWorker(
			new LoadInventoryTask($this->databasePath, $player->getUniqueId()->toString()),
			$this->workerId
		);
	}

	public function applyLoadedBlob(string $uuid, ?string $blob) : void{
		$this->cache[$uuid] = $blob;
	}

	public function unload(Player $player) : void{
		unset($this->cache[$player->getUniqueId()->toString()]);
	}

	/**
	 * @param Item[] $contents
	 */
	public function save(Player $player, array $contents) : void{
		$uuid = $player->getUniqueId()->toString();
		$blob = self::encode($contents);
		$this->cache[$uuid] = $blob;
		$this->plugin->getServer()->getAsyncPool()->submitTaskToWorker(
			new SaveInventoryTask($this->databasePath, $uuid, $blob, time()),
			$this->workerId
		);
	}

	public function saveAll() : void{
		$entries = [];
		foreach($this->cache as $uuid => $blob){
			if($blob !== null){
				$entries[$uuid] = $blob;
			}
		}
		$this->plugin->getServer()->getAsyncPool()->submitTaskToWorker(
			new SaveAllInventoriesTask($this->databasePath, $entries, time()),
			$this->workerId
		);
	}

	/**
	 * @param Item[] $contents indexed by slot 0..SLOTS-1
	 */
	private static function encode(array $contents) : string{
		$items = new ListTag();
		foreach($contents as $slot => $item){
			if($item->isNull()){
				continue;
			}
			$items->push($item->nbtSerialize($slot));
		}
		$root = CompoundTag::create()->setTag("Items", $items);
		return (new LittleEndianNbtSerializer())->write(new TreeRoot($root));
	}

	/**
	 * @return Item[] indexed by slot 0..SLOTS-1
	 */
	private static function decode(?string $blob) : array{
		$contents = array_fill(0, self::SLOTS, VanillaItems::AIR());
		if($blob === null || $blob === ""){
			return $contents;
		}
		try{
			$root = (new LittleEndianNbtSerializer())->read($blob)->mustGetCompoundTag();
		}catch(NbtDataException){
			return $contents;
		}
		$items = $root->getListTag("Items");
		if($items === null){
			return $contents;
		}
		foreach($items as $tag){
			if(!$tag instanceof CompoundTag){
				continue;
			}
			try{
				$slot = $tag->getByte("Slot", -1);
			}catch(\Throwable){
				continue;
			}
			if($slot < 0 || $slot >= self::SLOTS){
				continue;
			}
			$contents[$slot] = Item::safeNbtDeserialize($tag, "double ender chest slot $slot");
		}
		return $contents;
	}
}
