<?php

declare(strict_types=1);

namespace Phoenix4041\DoubleEnderChest\storage\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

final class SaveAllInventoriesTask extends AsyncTask{

	private ?string $error = null;

	private readonly string $serializedEntries;

	/**
	 * @param array<string, string> $entries uuid => nbt blob
	 */
	public function __construct(
		private readonly string $databasePath,
		array $entries,
		private readonly int $updatedAt
	){
		// AsyncTask properties must be thread-safe; a plain array isn't, so it crosses
		// the thread boundary as an igbinary-serialized string and is restored in onRun().
		if(count($entries) === 0){
			$this->serializedEntries = "";
		}else{
			$serialized = igbinary_serialize($entries);
			if($serialized === null){
				throw new \RuntimeException("Failed to serialize ender chest batch for async transfer");
			}
			$this->serializedEntries = $serialized;
		}
	}

	public function onRun() : void{
		if($this->serializedEntries === ""){
			return;
		}
		try{
			/** @var array<string, string> $entries */
			$entries = igbinary_unserialize($this->serializedEntries);
			$db = new \SQLite3($this->databasePath);
			$db->exec("BEGIN");
			$stmt = $db->prepare(
				"INSERT INTO enderchests (uuid, data, updated_at) VALUES (:uuid, :data, :updated_at)
				ON CONFLICT(uuid) DO UPDATE SET data = :data, updated_at = :updated_at"
			);
			if($stmt === false){
				throw new \RuntimeException("Failed to prepare UPSERT statement: " . $db->lastErrorMsg());
			}
			foreach($entries as $uuid => $data){
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
