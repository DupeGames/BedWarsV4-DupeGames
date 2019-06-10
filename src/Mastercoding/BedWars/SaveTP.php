<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 10.11.2018
 * Time: 19:11
 */

namespace Mastercoding\BedWars;


use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class SaveTP implements Listener
{
    public $pl;

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
        $worlds = scandir("worlds");

        if (!is_numeric($worlds[array_search("transfare", $worlds)])) {
            $this->pl->getServer()->generateLevel("transfare");
        }
        $this->pl->getServer()->loadLevel("transfare");
    }

    public function saveTeleport(Player $player, Level $level, bool $back = FALSE)
    {
        $player->doCloseInventory();
        $player->teleport($this->pl->getServer()->getLevelByName("transfare")->getSafeSpawn());
        $pk = new ChangeDimensionPacket();
        $pk->position = $this->pl->getServer()->getLevelByName("transfare")->getSafeSpawn();
        $pk->dimension = DimensionIds::THE_END;
        $pk->respawn = true;
        $player->sendDataPacket($pk);
        $this->pl->getScheduler()->scheduleDelayedTask(new removeLoadingScreen($this, $player, $level->getFolderName()), 20);
    }

}
// ========== removeLoadingScreen.php ==================


class removeLoadingScreen extends Task {

    protected $player;
    protected $lvl;
    protected $pl;
    protected $x;
    protected $y;
    protected $spawn;

    public function __construct(SaveTP $pl, Player $player,$lvl = "547834798359")
    {
        $this->player = $player;
        $this->lvl = $lvl;
        $this->pl = $pl;
    }

    public function onRun(int $currentTick)
    {
        $pk = new PlayStatusPacket();
        $pk->status = 3;
        $this->player->sendDataPacket($pk);
        $level = $this->pl->pl->getServer()->getLevelByName($this->lvl);
        if($level instanceof Level) {
            $spawn = $level->getSafeSpawn();
            $this->player->teleport($spawn);
            $pk = new ChangeDimensionPacket();
            $pk->position = $level->getSafeSpawn();
            $pk->dimension = DimensionIds::OVERWORLD;
            $pk->respawn = true;
            $this->player->sendDataPacket($pk);
            $this->pl->pl->getScheduler()->scheduleDelayedTask(new removeLoadingScreen($this->pl, $this->player), 40);
        }

    }

}