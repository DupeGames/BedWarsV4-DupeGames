<?php
/**
 * Created by PhpStorm.
 * User: chr1s
 * Date: 27.01.2019
 * Time: 11:25
 */

namespace Mastercoding\BedWars\Commands;

use Mastercoding\BedWars\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class Start extends Command {
    public function __construct(Main $pl, string $name, string $description = "", string $usageMessage = null, $aliases = [], array $overloads = null)
    {
        parent::__construct($name, $description, $usageMessage, $aliases, $overloads);
        $this->setPermission("start.bw");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($this->testPermission($sender)){
            $levelname = Main::getInstance()->player[$sender->getName()]["Level"];
            if ($levelname !== "~") {
                if ($sender instanceof Player) {
                    if (in_array($sender->getLevel()->getFolderName(), Main::getInstance()->getLobbys())) {
                        $signkey = Main::getInstance()->getSignInfo($sender->getLevel()->getFolderName());
                        $lobby = $sender->getLevel()->getFolderName();
                        if (isset(Main::getInstance()->arena[$lobby])){
                            if (Main::getInstance()->arena[$lobby]["Status"] == "Lobby") {
                                $playersinlobby = count($sender->getLevel()->getPlayers());
                                if ($playersinlobby >= Main::getInstance()->arena[$lobby]["PlayersNeed"] and Main::getInstance()->arena[$lobby]["WaitTime"] >= 10) {
                                    $time = Main::getInstance()->arena[$lobby]["WaitTime"];
                                    Main::getInstance()->arena[$lobby]["WaitTime"] = 10;
                                    $sender->sendMessage(Main::prefix . "");
                                }else{
                                    $sender->sendMessage(Main::prefix . "");
                                }
                            }
                        }
                    }
                }
            }
        }else{
            $sender->sendMessage(Main::prefix . "§7Du brauchst mindestens den M-Duper Rang um Runden starten zu können");
        }
    }
}