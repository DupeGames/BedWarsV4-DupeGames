<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 10.12.2018
 * Time: 13:21
 */

namespace Mastercoding\BedWars\Commands;

use Mastercoding\BedWars\Main;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;

class BedWarsCommand extends Command implements Listener
{

    protected $pl;

    public function __construct(Main $pl, string $name, string $description = "", string $usageMessage = null, $aliases = [], array $overloads = null)
    {
        $this->pl = $pl;
        parent::__construct($name, $description, $usageMessage, $aliases, $overloads);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender->hasPermission("bedwars.admin")) {
            if ($sender instanceof Player) {
                if (!isset($args[0])) {
                    $sender->sendMessage(Main::prefix . "§a/bw addmap (sorte) bsp: 4x2 map name");
                } else {
                    switch ($args[0]) {
                        case "addmap":
                            if (isset($args[1])) {
                                if (isset($args[2])) {
                                    if (Server::getInstance()->isLevelGenerated($args[2])) {
                                        switch ($args[1]) {
                                            case "4x2":

                                                var_dump('1');

                                                if (!Server::getInstance()->isLevelLoaded($args[2])){
                                                    Server::getInstance()->loadLevel($args[2]);
                                                    var_dump('2');
                                                }



                                                var_dump('3');

                                                $level = Server::getInstance()->getLevelByName($args[2]);

                                                var_dump('33');

                                                $this->pl->getSaveTp()->saveTeleport($sender, $level);

                                                var_dump('4');


                                                $this->pl->makemap[$sender->getName()];

                                                $this->pl->makemap[$sender->getName()] = array("Sorte"=>$args[1],"State"=>true,"Mode"=>(int)0,"Level"=>$args[2]);

                                                break;
                                            case "2x4":
                                                var_dump('1');

                                                if (!Server::getInstance()->isLevelLoaded($args[2])){
                                                    Server::getInstance()->loadLevel($args[2]);
                                                    var_dump('2');
                                                }



                                                var_dump('3');

                                                $level = Server::getInstance()->getLevelByName($args[2]);

                                                var_dump('33');

                                                $this->pl->getSaveTp()->saveTeleport($sender, $level);

                                                var_dump('4');


                                                $this->pl->makemap[$sender->getName()];

                                                $this->pl->makemap[$sender->getName()] = array("Sorte"=>$args[1],"State"=>true,"Mode"=>(int)0,"Level"=>$args[2]);

                                                break;
                                        }
                                    } else {
                                        $sender->sendMessage(Main::prefix . "§4Dieses Level gibt es nicht!");
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }
    }

    public function levelSetup(BlockBreakEvent $ev)
    {
        if (isset($this->pl->makemap[$ev->getPlayer()->getName()])) {
            var_dump('sss');
            if ($this->pl->makemap[$ev->getPlayer()->getName()]["State"] == true) {
                var_dump('s');
                $p = $ev->getPlayer();
                $n = $p->getName();
                $ev->setCancelled();

                switch ($this->pl->makemap[$ev->getPlayer()->getName()]["Sorte"]) {
                    case "4x2":
                        switch ($this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]){
                            case 0:

                                $sorte = explode("x", $this->pl->makemap[$ev->getPlayer()->getName()]["Sorte"]);
                                $zahl = $sorte[0] * $sorte[1];
                                $playerperteam = $sorte[1];
                                $playersneed = $sorte[1] + 1;

                                $config = new Config($this->pl->getDataFolder() . "levels/" . $this->pl->makemap[$ev->getPlayer()->getName()]["Level"] . ".json", Config::JSON);
                                $config->set("Settings", array(
                                    "Name"=>$this->pl->makemap[$ev->getPlayer()->getName()]["Level"],
                                    "Level"=>$this->pl->makemap[$ev->getPlayer()->getName()]["Level"],
                                    "Status"=>"Lobby",
                                    "Lobby"=>"~",
                                    "Schild"=>"~",
                                    "Sorte"=>"4x2",
                                    "WaitTime"=>60,
                                    "PlayersNeed"=>$playersneed,
                                    "PlayerPerTeam"=>$playerperteam
                                ));

                                $config->set("Rot",array(
                                    "Bett"=>"~",
                                    "Spawn"=>"~",
                                    "Leben"=>false,
                                    "TeamLeben"=>false
                                ));
                                $config->set("Blau",array(
                                    "Bett"=>"~",
                                    "Spawn"=>"~",
                                    "Leben"=>false,
                                    "TeamLeben"=>false
                                ));
                                $config->set("Gelb",array(
                                    "Bett"=>"~",
                                    "Spawn"=>"~",
                                    "Leben"=>false,
                                    "TeamLeben"=>false
                                ));
                                $config->set("Grün",array(
                                    "Bett"=>"~",
                                    "Spawn"=>"~",
                                    "Leben"=>false,
                                    "TeamLeben"=>false
                                ));

                                $config->save();


                                foreach ($p->getLevel()->getTiles() as $tile){
                                    if ($tile instanceof Sign){
                                        $blockabovesign = $p->getLevel()->getBlock(new Vector3($tile->x, $tile->y + 1, $tile->z));
                                        if ($blockabovesign->getId() == Block::get(Block::HARDENED_CLAY)->getId()){
                                            $tile->setLine(0, "bronze");
                                            var_dump("Bronze");
                                        }elseif ($blockabovesign->getId() == Block::get(Block::GOLD_BLOCK)->getId()){
                                            $tile->setLine(0, "gold");
                                            var_dump("Gold");
                                        }elseif ($blockabovesign->getId() == Block::get(Block::IRON_BLOCK)->getId()){
                                            $tile->setLine(0, "iron");
                                            var_dump("Eisen");
                                        }
                                    }
                                }



                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;
                                $p->sendMessage(Main::prefix . "§4Baue das Bett von Team §cRot §4ab.");
                                break;
                            case 1:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Rot" . ".Bett", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§4Baue den Spawn von Team §cRot §4ab.");
                                break;
                            case 2:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Rot" . ".Spawn", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§9Baue das Bett von Team §1Blau §9ab.");
                                break;
                            case 3:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Blau" . ".Bett", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§9Baue den Spawn von Team §1Blau §9ab.");
                                break;
                            case 4:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Blau" . ".Spawn", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§eBaue das Bett von Team §6Gelb §eab.");
                                break;
                            case 5:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Gelb" . ".Bett", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§eBaue den Spawn von Team §6Gelb §eab.");
                                break;
                            case 6:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Gelb" . ".Spawn", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§aBaue das Bett von Team §2Grün §aab.");
                                break;
                            case 7:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Grün" . ".Bett", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§aBaue den Spawn von Team §2Grün §aab.");
                                break;
                            case 8:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Grün" . ".Spawn", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§fGebe nun den Namen der Gewünschten Welt ein!");

                                break;
                            case 11:
                                $tile = $ev->getBlock()->getLevel()->getTile($ev->getBlock()->asVector3());
                                if ($tile instanceof Sign) {
                                    $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                    $levelname = $this->pl->makemap[$n]["Level"];
                                    $config->setNested("Settings" . ".Schild", serialize($ev->getBlock()->asVector3()));
                                    $config->save();

                                    $this->pl->makemap[$ev->getPlayer()->getName()]["State"] = false;

                                    Main::getInstance()->getSigns();
                                    $p->sendMessage(Main::prefix . "§aDie Runde ist nun Spielbereit");
                                }
                                break;
                        }
                        break;
                    case "2x4":
                        switch ($this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]) {
                            case 0:

                                $sorte = explode("x", $this->pl->makemap[$ev->getPlayer()->getName()]["Sorte"]);
                                $zahl = $sorte[0] * $sorte[1];
                                $playerperteam = $sorte[1];
                                $playersneed = $sorte[1] + 1;

                                $config = new Config($this->pl->getDataFolder() . "levels/" . $this->pl->makemap[$ev->getPlayer()->getName()]["Level"] . ".json", Config::JSON);
                                $config->set("Settings", array(
                                    "Name" => $this->pl->makemap[$ev->getPlayer()->getName()]["Level"],
                                    "Level" => $this->pl->makemap[$ev->getPlayer()->getName()]["Level"],
                                    "Status" => "Lobby",
                                    "Lobby" => "~",
                                    "Schild" => "~",
                                    "Sorte" => "2x4",
                                    "WaitTime" => 60,
                                    "PlayersNeed" => $playersneed,
                                    "PlayerPerTeam" => $playerperteam
                                ));

                                $config->set("Rot", array(
                                    "Bett" => "~",
                                    "Spawn" => "~",
                                    "Leben" => false,
                                    "TeamLeben" => false
                                ));
                                $config->set("Blau", array(
                                    "Bett" => "~",
                                    "Spawn" => "~",
                                    "Leben" => false,
                                    "TeamLeben" => false
                                ));

                                $config->save();

                                foreach ($p->getLevel()->getTiles() as $tile){
                                    if ($tile instanceof Sign){
                                        $blockabovesign = $p->getLevel()->getBlock(new Vector3($tile->x, $tile->y + 1, $tile->z));
                                        if ($blockabovesign->getId() == Block::get(Block::HARDENED_CLAY)->getId()){
                                            $tile->setLine(0, "bronze");
                                            var_dump("Bronze");
                                        }elseif ($blockabovesign->getId() == Block::get(Block::GOLD_BLOCK)->getId()){
                                            $tile->setLine(0, "gold");
                                            var_dump("Gold");
                                        }elseif ($blockabovesign->getId() == Block::get(Block::IRON_BLOCK)->getId()){
                                            $tile->setLine(0, "iron");
                                            var_dump("Eisen");
                                        }
                                    }
                                }
                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;
                                $p->sendMessage(Main::prefix . "§4Baue das Bett von Team §cRot §4ab.");
                                break;
                            case 1:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Rot" . ".Bett", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§4Baue den Spawn von Team §cRot §4ab.");
                                break;
                            case 2:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Rot" . ".Spawn", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§9Baue das Bett von Team §1Blau §9ab.");
                                break;
                            case 3:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Blau" . ".Bett", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§9Baue den Spawn von Team §1Blau §9ab.");
                                break;
                            case 4:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                $config->setNested("Blau" . ".Spawn", serialize($ev->getBlock()->asVector3()));
                                $config->save();

                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                $p->sendMessage(Main::prefix . "§fGebe nun den Namen der Gewünschten Welt ein!");

                                break;
                            case 7:
                                $tile = $ev->getBlock()->getLevel()->getTile($ev->getBlock()->asVector3());
                                if ($tile instanceof Sign) {
                                    $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                    $levelname = $this->pl->makemap[$n]["Level"];
                                    $config->setNested("Settings" . ".Schild", serialize($ev->getBlock()->asVector3()));
                                    $config->save();

                                    $this->pl->makemap[$ev->getPlayer()->getName()]["State"] = false;

                                    Main::getInstance()->getSigns();
                                    $p->sendMessage(Main::prefix . "§aDie Runde ist nun Spielbereit");
                                }
                                break;

                        }
                        break;
                }
            }
        }
    }


    public function onChat(PlayerChatEvent $ev){
        $p = $ev->getPlayer();
        $n = $p->getName();
        $msg = $ev->getMessage();
        if (isset($this->pl->makemap[$ev->getPlayer()->getName()])) {
            if ($this->pl->makemap[$ev->getPlayer()->getName()]["State"] == true) {
                $p = $ev->getPlayer();
                $n = $p->getName();

                switch ($this->pl->makemap[$ev->getPlayer()->getName()]["Sorte"]) {
                    case "4x2":
                        switch ($this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]) {
                            case 9:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];

                                    $config->setNested("Settings" . ".Name", $msg);
                                    $config->save();


                                    $p->sendMessage(Main::prefix . "§fGebe nun im Chat die Warte Lobby ein.");
                                    $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;
                                    $ev->setCancelled();

                                break;
                            case 10:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                if (Server::getInstance()->isLevelGenerated($msg)) {
                                    $config->setNested("Settings" . ".Lobby", $msg);
                                    $config->save();

                                    $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                    $p->sendMessage(Main::prefix . "§fBaue nun das Schild ein");
                                    $ev->setCancelled();
                                    Main::getInstance()->getSaveTp()->saveTeleport($p, Server::getInstance()->getDefaultLevel());
                                }else{
                                    $p->sendMessage(Main::prefix . "§4Dieses Level gibt es nicht!");
                                    $ev->setCancelled();
                                }
                                break;
                        }
                        break;
                    case "2x4":
                        switch ($this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]) {
                            case 5:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];

                                $config->setNested("Settings" . ".Name", $msg);
                                $config->save();


                                $p->sendMessage(Main::prefix . "§fGebe nun im Chat die Warte Lobby ein.");
                                $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;
                                $ev->setCancelled();

                                break;
                            case 6:
                                $config = $this->pl->onLevelConfig($this->pl->makemap[$n]["Level"]);
                                $levelname = $this->pl->makemap[$n]["Level"];
                                if (Server::getInstance()->isLevelGenerated($msg)) {
                                    $config->setNested("Settings" . ".Lobby", $msg);
                                    $config->save();

                                    $this->pl->makemap[$ev->getPlayer()->getName()]["Mode"]++;

                                    $p->sendMessage(Main::prefix . "§fBaue nun das Schild ein");
                                    $ev->setCancelled();
                                    Main::getInstance()->getSaveTp()->saveTeleport($p, Server::getInstance()->getDefaultLevel());
                                }else{
                                    $p->sendMessage(Main::prefix . "§4Dieses Level gibt es nicht!");
                                    $ev->setCancelled();
                                }
                                break;
                        }
                        break;
                }
            }
        }
    }
}

