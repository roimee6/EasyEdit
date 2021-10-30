<?php

namespace platz1de\EasyEdit;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use UnexpectedValueException;

class Messages
{
	private const MESSAGE_VERSION = "1.2.1";

	/**
	 * @var string[]
	 */
	private static array $messages = [];

	public static function load(): void
	{
		EasyEdit::getInstance()->saveResource("messages.yml");
		$messages = new Config(EasyEdit::getInstance()->getDataFolder() . "messages.yml", Config::YAML);

		if (($current = (string) $messages->get("message-version", "1.0")) !== self::MESSAGE_VERSION) {
			$cMajor = explode(".", $current)[0];
			$gMajor = explode(".", self::MESSAGE_VERSION)[0];

			if ($cMajor === $gMajor) {
				//Updating the config while remaining current values
				$new = EasyEdit::getInstance()->getResource("messages.yml");
				if ($new === null || ($data = stream_get_contents($new)) === false) {
					throw new UnexpectedValueException("Couldn't read update data");
				}
				fclose($new);

				//Allow different line endings
				$newConfig = preg_split("/\r\n|\n|\r/", $data);

				//We can't just use yaml_parse as we want to preserve comments
				foreach ($messages->getAll() as $key => $value) {
					if ($key === "message-version") {
						continue;
					}
					$position = array_filter($newConfig, static function (string $line) use ($key): bool {
						return str_starts_with($line, $key . ":");
					});
					if (count($position) === 1) {
						$newConfig[key($position)] = $key . ': "' . $value . '"';
					}
				}

				file_put_contents($messages->getPath(), implode(PHP_EOL, $newConfig));

				EasyEdit::getInstance()->getLogger()->notice("Your messages were updated to the newest Version");
			} else {
				//We can't update for major releases
				copy($messages->getPath(), $messages->getPath() . ".old");
				unlink($messages->getPath());
				EasyEdit::getInstance()->saveDefaultConfig();

				EasyEdit::getInstance()->getLogger()->warning("Your messages were replaced with a newer Version");
			}
			$messages->reload();
		}

		self::$messages = $messages->getAll();
	}

	/**
	 * @param string|string[]|Player|Player[] $players
	 * @param string                          $id
	 * @param string|string[]                 $replace
	 * @param bool                            $isId
	 * @param bool                            $usePrefix
	 */
	public static function send(mixed $players, string $id, mixed $replace = [], bool $isId = true, bool $usePrefix = true): void
	{
		if (is_array($players)) {
			foreach ($players as $player) {
				if ($player instanceof Player || ($player = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
					$player->sendMessage(($usePrefix ? self::translate("prefix") : "") . self::replace($id, $replace, $isId));
				}
			}
		} else {
			self::send([$players], $id, $replace, $isId, $usePrefix);
		}
	}

	/**
	 * @param string          $id
	 * @param string|string[] $replace
	 * @param bool            $isId
	 * @return string
	 */
	public static function replace(string $id, mixed $replace = [], bool $isId = true): string
	{
		if (is_array($replace)) {
			return str_replace(array_keys($replace), array_values($replace), $isId ? self::translate($id) : $id);
		}
		return str_replace("{player}", $replace, $isId ? self::translate($id) : $id);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function translate(string $id): string
	{
		return self::$messages[$id] ?? TextFormat::RED . "The message " . $id . " was not found, please try deleting your messages.yml";
	}
}