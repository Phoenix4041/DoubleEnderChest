<?php

/**
 * Local .phar packager for DoubleEnderChest.
 * Bundles the plugin source together with the InvMenu virion source
 * (mirroring what Poggit CI's virion merge step would produce),
 * since there is no Poggit build available in this environment.
 *
 * Usage: php -d phar.readonly=0 build.php
 */

declare(strict_types=1);

$root = __DIR__;
$buildDir = $root . DIRECTORY_SEPARATOR . "build";
$pharPath = $buildDir . DIRECTORY_SEPARATOR . "DoubleEnderChest.phar";

if (!is_dir($buildDir)) {
	mkdir($buildDir, 0777, true);
}
if (file_exists($pharPath)) {
	unlink($pharPath);
}

$phar = new Phar($pharPath);
$phar->startBuffering();
$phar->setStub("<?php __HALT_COMPILER();");

$phar->addFile($root . "/plugin.yml", "plugin.yml");
$phar->addFile($root . "/LICENSE", "LICENSE");

$addTree = static function (string $sourceDir, string $pharPrefix) use ($phar): void {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS)
	);
	foreach ($iterator as $file) {
		if ($file->getExtension() !== "php") {
			continue;
		}
		$relative = substr($file->getPathname(), strlen($sourceDir) + 1);
		$relative = str_replace(DIRECTORY_SEPARATOR, "/", $relative);
		$phar->addFile($file->getPathname(), $pharPrefix . "/" . $relative);
	}
};

$addTree($root . "/src", "src");
$addTree($root . "/vendor/muqsit/invmenu/src", "src");

$phar->stopBuffering();

echo "Built: {$pharPath} (" . round(filesize($pharPath) / 1024, 1) . " KB)" . PHP_EOL;
