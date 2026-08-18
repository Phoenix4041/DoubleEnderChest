<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\storage\task;

use pocketmine\scheduler\AsyncTask;
use Phoenix4041\DoubleEnderChest\Loader;

final class SaveInventoryTask extends AsyncTask{

	private ?string $error = null;

	public function __construct(
		private readonly string $databasePath,
		private readonly string $uuid,
		private readonly string $data,
		private readonly int $updatedAt
	){
	}

	public function onRun() : void{
		try{
			$db = new \SQLite3($this->databasePath);
			$stmt = $db->prepare(
				"INSERT INTO enderchests (uuid, data, updated_at) VALUES (:uuid, :data, :updated_at)
				ON CONFLICT(uuid) DO UPDATE SET data = :data, updated_at = :updated_at"
			);
			$stmt->bindValue(":uuid", $this->uuid, SQLITE3_TEXT);
			$stmt->bindValue(":data", $this->data, SQLITE3_BLOB);
			$stmt->bindValue(":updated_at", $this->updatedAt, SQLITE3_INTEGER);
			$stmt->execute();
			$stmt->close();
			$db->close();
		}catch(\Throwable $e){
			$this->error = $e->getMessage();
		}
	}

	public function onCompletion() : void{
		if($this->error === null){
			return;
		}
		$loader = Loader::getInstanceOrNull();
		$loader?->getLogger()->warning("Failed to save ender chest for {$this->uuid}: {$this->error}");
	}
}
