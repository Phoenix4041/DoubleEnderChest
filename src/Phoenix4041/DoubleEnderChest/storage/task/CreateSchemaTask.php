<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\storage\task;

use pocketmine\scheduler\AsyncTask;
use Phoenix4041\DoubleEnderChest\Loader;

final class CreateSchemaTask extends AsyncTask{

	private ?string $error = null;

	public function __construct(
		private readonly string $databasePath
	){
	}

	public function onRun() : void{
		try{
			$db = new \SQLite3($this->databasePath);
			$db->exec(
				"CREATE TABLE IF NOT EXISTS enderchests (
					uuid TEXT PRIMARY KEY,
					data BLOB NOT NULL,
					updated_at INTEGER NOT NULL
				)"
			);
			$db->close();
		}catch(\Throwable $e){
			$this->error = $e->getMessage();
		}
	}

	public function onCompletion() : void{
		$loader = Loader::getInstanceOrNull();
		if($loader === null){
			return;
		}
		if($this->error !== null){
			$loader->getLogger()->critical("Failed to create ender chest database schema: {$this->error}");
		}
	}
}
