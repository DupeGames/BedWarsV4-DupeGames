<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 10.12.2018
 * Time: 17:20
 */

namespace Mastercoding\BedWars\Task;

use Mastercoding\BedWars\Main;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Sign;

class SignUpdate extends Task {
    protected $pl;

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
    }

    public function onRun(int $currentTick)
    {
        $signs = Main::getInstance()->getLevelsFolder();
        $level = Server::getInstance()->getDefaultLevel();
        foreach ($signs as $sign => $info){

            if($level->getTile($info["Cord"]) instanceof Sign){
                $tile = $level->getTile($info["Cord"]);
                if ($tile instanceof Sign){

                    $max = "???";

                    $teams = explode("x", $info["Art"]);
                    $max = $teams[0] * $teams[1];

                    $status = "???";

                    $players = 0;
                    if (Server::getInstance()->isLevelLoaded($info["Lobby"])) {
                        $lobby = Server::getInstance()->getLevelByName($info["Lobby"]);
                        if ($info["Status"] !== "Ingame") {
                            $players = count($lobby->getPlayers());
                        }
                    }

                    if ($info["Status"] == "Ingame") {
                        if (Server::getInstance()->isLevelLoaded($info["Level"])) {
                            $gamelevel = Server::getInstance()->getLevelByName($info["Level"]);
                            $players = count($gamelevel->getPlayers());
                            #dump($players);
                        }
                    }

                    switch ($info["Status"]){
                        case "Lobby":
                            if ($players < $max) {
                                $status = "§2Lobby";
                            }elseif ($players == $max){
                                $status = "§6Lobby";
                            }
                            break;
                        case "Ingame":
                            $status = "§4Ingame";
                            break;
                    }

                    $tile->setLine(0, "§cBedWars");
                    $tile->setLine(1, "§0{$info["Name"]}");
                    $tile->setLine(2, "§0[{$status}§0]");
                    $tile->setLine(3, "§2{$players}§7/{$max}");
                }
            }
        }
    }
}