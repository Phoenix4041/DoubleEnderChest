<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest;

use pocketmine\plugin\PluginBase;
use Phoenix4041\DoubleEnderChest\listener\EnderChestListener;
use Phoenix4041\DoubleEnderChest\storage\EnderChestRepository;
use Phoenix4041\DoubleEnderChest\storage\task\CreateSchemaTask;

final class Loader extends PluginBase{

	private static ?self $instance = null;

	private EnderChestRepository $repository;

	public static function getInstanceOrNull() : ?self{
		return self::$instance;
	}

	protected function onEnable() : void{
		self::$instance = $this;

		$databasePath = $this->getDataFolder() . "enderchests.sqlite";

		// pinned to a single worker so CreateSchemaTask always runs before any load/save on that worker
		$worker = $this->getServer()->getAsyncPool()->submitTask(new CreateSchemaTask($databasePath));
		$this->repository = new EnderChestRepository($this, $databasePath, $worker);

		$this->getServer()->getPluginManager()->registerEvents(new EnderChestListener($this->repository), $this);
	}

	protected function onDisable() : void{
		$this->repository->saveAll();
		self::$instance = null;
	}

	public function getRepository() : EnderChestRepository{
		return $this->repository;
	}
}
