<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\menu;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\world\sound\EnderChestCloseSound;
use Phoenix4041\DoubleEnderChest\storage\EnderChestRepository;

final class EnderChestMenu{

	private function __construct(){
	}

	public static function open(Player $player, EnderChestRepository $repository) : void{
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
		$menu->setName("Ender Chest");
		$menu->getInventory()->setContents($repository->getItems($player));

		$menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
			return $transaction->continue();
		});

		$menu->setInventoryCloseListener(function(Player $player, Inventory $inventory) use ($repository) : void{
			$repository->save($player, $inventory->getContents());
			$player->getWorld()->addSound($player->getPosition(), new EnderChestCloseSound());
		});

		$menu->send($player);
	}
}
