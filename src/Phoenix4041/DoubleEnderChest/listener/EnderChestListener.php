<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\listener;

use pocketmine\block\EnderChest;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\math\Facing;
use pocketmine\world\sound\EnderChestOpenSound;
use Phoenix4041\DoubleEnderChest\menu\EnderChestMenu;
use Phoenix4041\DoubleEnderChest\Permissions;
use Phoenix4041\DoubleEnderChest\storage\EnderChestRepository;

final class EnderChestListener implements Listener{

	public function __construct(
		private readonly EnderChestRepository $repository
	){
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$this->repository->load($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$this->repository->unload($event->getPlayer());
	}

	public function onInteract(PlayerInteractEvent $event) : void{
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}

		$block = $event->getBlock();
		if(!$block instanceof EnderChest){
			return;
		}

		$event->cancel();
		$player = $event->getPlayer();

		if(!$player->hasPermission(Permissions::USE)){
			return;
		}

		if(!$block->getSide(Facing::UP)->isTransparent()){
			$player->sendTip("§cThe ender chest is blocked.");
			return;
		}

		if(!$this->repository->isLoaded($player)){
			$player->sendTip("§cYour ender chest is still loading.");
			return;
		}

		$player->getWorld()->addSound($block->getPosition(), new EnderChestOpenSound());
		EnderChestMenu::open($player, $this->repository);
	}
}
