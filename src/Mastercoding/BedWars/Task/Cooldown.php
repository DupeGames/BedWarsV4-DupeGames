<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11.12.2018
 * Time: 13:16
 */
namespace Mastercoding\BedWars\Task;

use Mastercoding\BedWars\Main;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class Cooldown extends Task {

    protected $pl;
    protected $p;

    public function __construct(Main $pl, Player $player)
    {
        $this->pl = $pl;
        $this->p = $player;
    }

    public function onRun(int $currentTick)
    {
        $p = $this->p;

        if (in_array($p->getName(), $this->pl->incooldown)){

            $remove = array_search($p->getName(), $this->pl->incooldown);
            unset($this->pl->incooldown[$remove]);
        }else{
            $p->sendMessage(Main::prefix . "Not In CoolDown");
        }
    }
}