<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\storage\task;

use pocketmine\scheduler\AsyncTask;
use Phoenix4041\DoubleEnderChest\Loader;

final class LoadInventoryTask extends AsyncTask{

	private ?string $error = null;
	private ?string $blob = null;

	public function __construct(
		private readonly string $databasePath,
		private readonly string $uuid
	){
	}

	public function onRun() : void{
		try{
			$db = new \SQLite3($this->databasePath, SQLITE3_OPEN_READONLY);
			$stmt = $db->prepare("SELECT data FROM enderchests WHERE uuid = :uuid");
			if($stmt === false){
				throw new \RuntimeException("Failed to prepare SELECT statement: " . $db->lastErrorMsg());
			}
			$stmt->bindValue(":uuid", $this->uuid, SQLITE3_TEXT);
			$result = $stmt->execute();
			if($result === false){
				throw new \RuntimeException("Failed to execute SELECT statement: " . $db->lastErrorMsg());
			}
			$row = $result->fetchArray(SQLITE3_ASSOC);
			$stmt->close();
			$db->close();
			$this->blob = $row !== false ? $row["data"] : null;
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
			$loader->getLogger()->warning("Failed to load ender chest for {$this->uuid}: {$this->error}");
			return;
		}
		$loader->getRepository()->applyLoadedBlob($this->uuid, $this->blob);
	}
}
