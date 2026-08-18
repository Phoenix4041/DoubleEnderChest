<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\storage\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

final class SaveAllInventoriesTask extends AsyncTask{

	private ?string $error = null;

	/**
	 * @param array<string, string> $entries uuid => nbt blob
	 */
	public function __construct(
		private readonly string $databasePath,
		private readonly array $entries,
		private readonly int $updatedAt
	){
	}

	public function onRun() : void{
		if(count($this->entries) === 0){
			return;
		}
		try{
			$db = new \SQLite3($this->databasePath);
			$db->exec("BEGIN");
			$stmt = $db->prepare(
				"INSERT INTO enderchests (uuid, data, updated_at) VALUES (:uuid, :data, :updated_at)
				ON CONFLICT(uuid) DO UPDATE SET data = :data, updated_at = :updated_at"
			);
			if($stmt === false){
				throw new \RuntimeException("Failed to prepare UPSERT statement: " . $db->lastErrorMsg());
			}
			foreach($this->entries as $uuid => $data){
				$stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
				$stmt->bindValue(":data", $data, SQLITE3_BLOB);
				$stmt->bindValue(":updated_at", $this->updatedAt, SQLITE3_INTEGER);
				$stmt->execute();
				$stmt->reset();
			}
			$stmt->close();
			$db->exec("COMMIT");
			$db->close();
		}catch(\Throwable $e){
			$this->error = $e->getMessage();
		}
	}

	public function onCompletion() : void{
		if($this->error !== null){
			Server::getInstance()->getLogger()->warning("Failed to batch-save ender chests: {$this->error}");
		}
	}
}
