<?php
/**
 * Created by PhpStorm.
 * User: chr1s
 * Date: 24.01.2019
 * Time: 20:15
 */

namespace Mastercoding\BedWars\Utils;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;

class Scoreboard {
    public static function setScoreboardEntry(Player $player, int $score, string $msg, string $objName) {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $objName;
        $entry->type = 3;
        $entry->customName = " $msg   ";
        $entry->score = $score;
        $entry->scoreboardId = $score;
        $pk = new SetScorePacket();
        $pk->type = 0;
        $pk->entries[$score] = $entry;
        $player->sendDataPacket($pk);
    }

    public static function rmScoreboardEntry(Player $player, int $score) {
        $pk = new SetScorePacket();
        if(isset($pk->entries[$score])) {
            unset($pk->entries[$score]);
            $player->sendDataPacket($pk);
        }
    }

    public static function createScoreboard(Player $player, string $title, string $objName, string $slot = "sidebar", $order = 0) {
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = $slot;
        $pk->objectiveName = $objName;
        $pk->displayName = $title;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = $order;
        $player->sendDataPacket($pk);
    }

    public static function rmScoreboard(Player $player, string $objName) {
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objName;
        $player->sendDataPacket($pk);
    }
}