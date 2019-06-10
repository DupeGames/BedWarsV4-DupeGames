<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11.12.2018
 * Time: 13:53
 */

namespace Mastercoding\BedWars\Task;

use Mastercoding\BedWars\Main;
use Mastercoding\BedWars\Utils\BColor;
use Mastercoding\BedWars\Utils\Scoreboard;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\Villager;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Sign;

class CountDown extends Task
{
    protected $pl;
    protected $resettime;

    protected $status;

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
    }

    public function onRun(int $currentTick)
    {

        #$enoughtplayers =

        foreach ($this->pl->getLobbys() as $lobby) {
            if (Server::getInstance()->isLevelLoaded($lobby)) {
                if (isset($this->pl->arena[$lobby])) {
                    $level = Server::getInstance()->getLevelByName($lobby);

                    if (!isset($this->status[Main::getInstance()->arena[$lobby]["Level"]])) {
                        $this->status[Main::getInstance()->arena[$lobby]["Level"]] = false;
                        $this->resettime[$lobby] = 12;
                        var_dump("rgrg");
                    }

                    if (Main::getInstance()->arena[$lobby]["Status"] === "Lobby") {
                        $gamelevelname = Main::getInstance()->arena[$lobby]["Level"];


                        if (count($level->getPlayers()) >= Main::getInstance()->arena[$lobby]["PlayersNeed"]) {
                            Main::getInstance()->arena[$lobby]["WaitTime"]--;
                            $time = Main::getInstance()->arena[$lobby]["WaitTime"];

                            if (in_array($time, [60, 50, 30, 20, 10, 5, 4, 3, 2])) {
                                foreach ($level->getPlayers() as $playerinlobby) {
                                    $time = Main::getInstance()->arena[$lobby]["WaitTime"];
                                    $playerinlobby->sendMessage(Main::prefix . "§7Das Spiel startet in §e{$time}§7 Sekunden");
                                }
                            }

                            if ($time === 5) {
                                if (!Server::getInstance()->isLevelLoaded($gamelevelname)) {
                                    Server::getInstance()->loadLevel($gamelevelname);

                                    Main::getInstance()->getLogger()->info("$gamelevelname wurde geladen.");

                                    $gamelevel = Server::getInstance()->getLevelByName($gamelevelname);
                                    $gamelevel->setTime(0);
                                    $gamelevel->stopTime();
                                    $gamelevel->setAutoSave(false);
                                }
                            }

                            if ($time === 1) {
                                if (Server::getInstance()->isLevelLoaded($gamelevelname)) {
                                    $gamelevel = Server::getInstance()->getLevelByName($gamelevelname);

                                    foreach ($level->getPlayers() as $player) {
                                        if (Main::getInstance()->getArenalistener()->getPlayerTeam($player) === "~") {
                                            $config = Main::getInstance()->getArenalistener()->onConfigGame($gamelevel->getFolderName());
                                            foreach ($config->getAll() as $teams => $info) {
                                                foreach ($info as $team => $infos) {
                                                    if (Main::getInstance()->getArenalistener()->countTeamPlayers($gamelevel->getFolderName(), $team) < Main::getInstance()->arena[$lobby]["PlayerPerTeam"]) {
                                                        $n = $player->getName();
                                                        Main::getInstance()->getArenalistener()->setTeamArray(Main::getInstance()->player[$n]["SignKey"]["Level"], $player, $team, Main::getInstance()->player[$n]["Art"]);
                                                        $color = BColor::teamToColorString($team);
                                                        Main::getInstance()->player[$player->getName()]["Level"] = $gamelevel->getFolderName();

                                                        $player->sendMessage(Main::prefix . "§7Du wurdest dem Team {$color}{$team}§7 zugewießen");
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        if (Main::getInstance()->getArenalistener()->countTeamPlayers($gamelevel->getFolderName(), Main::getInstance()->getArenalistener()->getPlayerTeam($player)) > 0) {
                                            $config = Main::getInstance()->getArenalistener()->onConfig($gamelevel->getFolderName());
                                            $team = Main::getInstance()->getArenalistener()->getPlayerTeam($player);
                                            $config->setNested($team . ".Leben", true);
                                            $config->setNested($team . ".TeamLeben", true);
                                            $config->save();
                                        }

                                        Main::getInstance()->getSaveTp()->saveTeleport($player, $gamelevel);

                                        $player->getInventory()->clearAll();
                                        $player->getArmorInventory()->clearAll();

                                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new Wait($this, $player), 30);
                                        Main::getInstance()->getLogger()->info("Alle Spieler von $gamelevelname wurden in die Arena Teleportiert.");
                                    }
                                    $this->status[$gamelevelname] = true;
                                } else {
                                    Main::getInstance()->getLogger()->alert("$gamelevelname ist nicht geladen!");
                                }
                            }
                        } elseif ($this->status[Main::getInstance()->arena[$lobby]["Level"]] === true) {
                            Main::getInstance()->arena[$lobby]["WaitTime"]--;
                        }
                        $time = Main::getInstance()->arena[$lobby]["WaitTime"];
                        if ($time < -1) {
                            Main::getInstance()->getLogger()->info("versuche $gamelevelname zu starten...");

                            if (Server::getInstance()->isLevelLoaded($gamelevelname)) {

                                $gamelevel = Server::getInstance()->getLevelByName($gamelevelname);
                                Main::getInstance()->getArenalistener()->setArenaStatus($gamelevel->getFolderName(), $lobby, "Ingame");

                                $config = Main::getInstance()->getArenalistener()->onConfig($gamelevelname);

                                Main::getInstance()->arena[$lobby]["Status"] = "Ingame";
                                Main::getInstance()->arena[$lobby]["WaitTime"] = $config->getNested("Settings" . ".WaitTime");


                                foreach ($gamelevel->getEntities() as $entity) {
                                    if ($entity instanceof ItemEntity or $entity instanceof Sheep or $entity instanceof PrimedTNT) {
                                        $entity->despawnFromAll();
                                        $entity->kill();
                                        Main::getInstance()->getLogger()->info("Es wurden alle Entity von Level ($gamelevelname) getötet.");
                                    }
                                }

                                foreach ($gamelevel->getTiles() as $tile) {
                                    if ($tile instanceof Sign) {
                                        $blockabovesign = $gamelevel->getBlock(new Vector3($tile->x, $tile->y + 1, $tile->z));
                                        if ($blockabovesign->getId() == Block::get(Block::SANDSTONE)->getId()) {


                                            $nbt = Entity::createBaseNBT(new Vector3($tile->x + 0.5, $tile->y + 2, $tile->z + 0.5), new Vector3(0, 0, 0));

                                            $vec = new Vector3($tile->x + 0.5, $tile->y + 2, $tile->z + 0.5);
                                            foreach ($gamelevel->getEntities() as $entity) {
                                                if ($entity instanceof Villager) {
                                                    if ($vec->distance($vec) <= 6) {
                                                        $entity->despawnFromAll();
                                                        $entity->kill();
                                                    }
                                                }
                                            }
                                            $villager = new Villager($gamelevel, $nbt);
                                            $villager->spawnToAll();
                                            var_dump("SPAWN VILLAGER");
                                        }
                                    }
                                }

                                foreach ($gamelevel->getTiles() as $tile) {
                                    if ($tile instanceof Sign) {
                                        $pos = $tile->asVector3();
                                        $id = $pos->x . $pos->y . $pos->z;
                                        Main::getInstance()->drops_count[$gamelevelname][$id] = 0;
                                        $pos = $tile->asVector3();
                                        $pos->y = $pos->y + 2;
                                        $pos->x = $pos->x + 0.5;
                                        $pos->z = $pos->z + 0.5;
                                        switch ($tile->getText()[0]) {
                                            case 'bronze':
                                                $i = Item::get(Item::BRICK, 0, 1);
                                                $i->setCustomName("BRICK");
                                                $tile->getLevel()->dropItem($pos, $i, new Vector3(0, 0, 0));
                                                var_dump("fergrg");
                                                break;
                                            case 'iron':
                                                $i = Item::get(Item::IRON_INGOT, 0, 1);
                                                $i->setCustomName("IRON");
                                                $tile->getLevel()->dropItem($pos, $i, new Vector3(0, 0, 0));
                                                break;
                                            case 'gold':
                                                $i = Item::get(Item::GOLD_INGOT, 0, 1);
                                                $i->setCustomName("GOLD");
                                                $tile->getLevel()->dropItem($pos, $i, new Vector3(0, 0, 0));
                                        }
                                    }
                                }

                                Main::getInstance()->getLogger()->info("Level $gamelevelname wurde gestartet");
                            } else {
                                Main::getInstance()->getLogger()->alert("$gamelevelname ist nicht geladen");
                            }
                        }
                    }

                    if (Main::getInstance()->arena[$lobby]["Status"] === "Ingame") {
                        $gamelevelname = Main::getInstance()->arena[$lobby]["Level"];
                        if (Server::getInstance()->isLevelLoaded($gamelevelname)) {
                            $gamelevel = Server::getInstance()->getLevelByName($gamelevelname);

                            foreach ($gamelevel->getPlayers() as $player) {
                                $count = 3;
                                Scoreboard::rmScoreboard($player, $gamelevelname);
                                Scoreboard::createScoreboard($player, "§4Bed§fWars §7DG", $gamelevelname);
                                $config = Main::getInstance()->getArenalistener()->onConfig($gamelevelname);

                                Scoreboard::setScoreboardEntry($player, 0, "\00", $gamelevelname);
                                Scoreboard::setScoreboardEntry($player, 1, "§7Gold: §2Ja", $gamelevelname);
                                Scoreboard::setScoreboardEntry($player, 2, "\000\0", $gamelevelname);


                                foreach ($config->getAll() as $teams => $info) {
                                    if ($teams !== "Settings") {
                                        $teamcolor = BColor::teamToColorString($teams);
                                        $counts = Main::getInstance()->getArenalistener()->countTeamPlayers($gamelevelname, $teams);
                                        if ($info["Leben"] == true) {
                                            Scoreboard::setScoreboardEntry($player, $count, "§2✔ {$teamcolor}{$teams} §f{$counts}", $gamelevelname);

                                        } elseif ($info["Leben"] == false) {
                                            Scoreboard::setScoreboardEntry($player, $count, "§4✘ {$teamcolor}{$teams} §f{$counts}", $gamelevelname);
                                        }
                                        $count++;
                                    }
                                }

                                if ($player->getGamemode() === 0){
                                    $team = Main::getInstance()->getArenalistener()->getPlayerTeam($player);
                                    $color = BColor::teamToColorString($team);
                                    $player->sendTip("{$color}Team {$team}");

                                }else{
                                    if (Main::getInstance()->getArenalistener()->getPlayerTeam($player) === "Spectator"){
                                        $player->despawnFromAll();
                                    }
                                    #$player->despawnFromAll();
                                }
                            }

                            if ($gamelevel->getFolderName() !== "Stronghold2x4") {
                                foreach ($gamelevel->getTiles() as $tile) {
                                    if ($tile instanceof Sign) {
                                        $pos = $tile->asVector3();
                                        $id = $pos->x . $pos->y . $pos->z;
                                        if ($tile->getText()[0] === 'bronze') {
                                            Main::getInstance()->drops_count[$gamelevelname][$id]++;
                                        } else if ($tile->getText(0)[0] === 'iron' and time() % 30 === 0) {
                                            Main::getInstance()->drops_count[$gamelevelname][$id]++;
                                        } else if ($tile->getText(0)[0] === 'gold' and time() % 60 === 1) {
                                            Main::getInstance()->drops_count[$gamelevelname][$id]++;
                                        }
                                    }
                                }
                            }else {
                                foreach ($gamelevel->getTiles() as $tile) {
                                    if ($tile instanceof Sign) {
                                        $pos = $tile->asVector3();
                                        $id = $pos->x . $pos->y . $pos->z;
                                        Main::getInstance()->drops_count[$gamelevelname][$id] = 0;
                                        $pos = $tile->asVector3();
                                        $pos->y = $pos->y + 2;
                                        $pos->x = $pos->x + 0.5;
                                        $pos->z = $pos->z + 0.5;
                                        switch ($tile->getText()[0]) {
                                            case 'bronze':
                                                    $i = Item::get(Item::BRICK, 0, 1);
                                                    $i->setCustomName("BRICK");
                                                    $tile->getLevel()->dropItem($pos, $i, new Vector3(0, 0, 0));
                                                    var_dump("fergrg");
                                                break;
                                            case 'iron':
                                                if(time() % 30 === 0) {
                                                    $i = Item::get(Item::IRON_INGOT, 0, 1);
                                                    $i->setCustomName("IRON");
                                                    $tile->getLevel()->dropItem($pos, $i, new Vector3(0, 0, 0));
                                                }
                                                break;
                                            case 'gold':
                                                if (time() % 60 === 1) {
                                                    $i = Item::get(Item::GOLD_INGOT, 0, 1);
                                                    $i->setCustomName("GOLD");
                                                    $tile->getLevel()->dropItem($pos, $i, new Vector3(0, 0, 0));
                                                    break;
                                                }
                                        }
                                    }
                                }
                            }

                            $config = Main::getInstance()->getArenalistener()->onConfig($gamelevel->getFolderName());
                            $all = count($config->getAll()) - 2;
                            foreach ($gamelevel->getPlayers() as $players) {
                                if ($players->getGamemode() !== 1 or $players->getGamemode() !== 2) {
                                    $count = 0;
                                    $team = Main::getInstance()->getArenalistener()->getPlayerTeam($players);
                                    $life = Main::getInstance()->getArenalistener()->isLeben($gamelevel->getFolderName(), $team);
                                    foreach ($config->getAll() as $key => $infos) {
                                        if ($key !== "Settings") {
                                            if ($team !== $key) {
                                                $bett = Main::getInstance()->getArenalistener()->isBettLeben($gamelevel->getFolderName(), $key);
                                                $others = Main::getInstance()->getArenalistener()->isLeben($gamelevel->getFolderName(), $key);
                                                if ($life == true and $bett == false and $others == false) {
                                                    $count = $count + 1;
                                                    if ($count > $all - 1) {
                                                        Main::getInstance()->arena[$lobby]["Status"] = "Reset";
                                                        $teamnames = Main::getInstance()->getArenalistener()->getTeamArray($gamelevel->getFolderName(), $team);
                                                        foreach ($gamelevel->getPlayers() as $player) {
                                                            if (Main::getInstance()->player[$player->getName()]["Team"] !== "~") {
                                                                if (in_array($player->getName(), $teamnames)) {
                                                                    Main::getInstance()->getStats()->addWin($player->getName());
                                                                    var_dump($player->getName());
                                                                } else {
                                                                    Main::getInstance()->getStats()->addLose($player->getName());
                                                                    var_dump($player->getName());
                                                                }
                                                            }
                                                        }


                                                        foreach ($gamelevel->getPlayers() as $alllevelplayer) {
                                                            $lobbylevel = Server::getInstance()->getLevelByName($lobby);
                                                            Main::getInstance()->getSaveTp()->saveTeleport($alllevelplayer, $lobbylevel);
                                                            $color = BColor::teamToColor($team);

                                                            $alllevelplayer->spawnToAll();
                                                            $alllevelplayer->setGamemode(0);
                                                            $alllevelplayer->sendMessage(Main::prefix . "§7Team {$color}§7 hat das Spiel gewonnen");
                                                            Scoreboard::rmScoreboard($alllevelplayer, $gamelevel->getFolderName());
                                                        }
                                                        break;
                                                    } elseif ($count == 0) {
                                                        Main::getInstance()->arena[$lobby]["Status"] = "Reset";
                                                        foreach ($gamelevel->getPlayers() as $alllevelplayer) {
                                                            $lobbylevel = Server::getInstance()->getLevelByName($lobby);
                                                            Main::getInstance()->getSaveTp()->saveTeleport($alllevelplayer, $lobbylevel);

                                                            $alllevelplayer->spawnToAll();
                                                            $alllevelplayer->setGamemode(0);
                                                            $alllevelplayer->doCloseInventory();
                                                            $alllevelplayer->getInventory()->clearAll();
                                                            Scoreboard::rmScoreboard($alllevelplayer, $gamelevel->getFolderName());
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            Main::getInstance()->getLogger()->alert("Ingame $gamelevelname ist nicht geladen.");
                        }
                    }

                    if (Main::getInstance()->arena[$lobby]["Status"] === "Reset") {
                        if (isset($this->resettime[$lobby])) {
                            $gamelevelname = Main::getInstance()->arena[$lobby]["Level"];
                            $this->resettime[$lobby]--;
                            $time = $this->resettime[$lobby];
                            if ($this->resettime[$lobby] === 10) {
                                if (Server::getInstance()->isLevelLoaded($gamelevelname)) {
                                    $gamelevel = Server::getInstance()->getLevelByName($gamelevelname);

                                    foreach ($gamelevel->getPlayers() as $player) {
                                        Main::getInstance()->getSaveTp()->saveTeleport($player, $level);
                                    }
                                }
                            }

                            if (in_array($time, [10, 9, 8, 7, 6, 5, 4, 3, 2, 1])) {
                                foreach ($level->getPlayers() as $player) {
                                    $player->sendMessage(Main::prefix . "§7Das Spiel startet in {$time} Sekunden neu.");
                                }
                            }

                            if ($time === 5) {
                                if (Server::getInstance()->isLevelLoaded($gamelevelname)) {
                                    $gamelevel = Server::getInstance()->getLevelByName($gamelevelname);
                                    Server::getInstance()->unloadLevel($gamelevel);
                                    Main::getInstance()->getLogger()->info("Reset $gamelevelname wurde endladen");
                                }
                            }

                            if ($time === 1) {
                                foreach ($level->getPlayers() as $player) {
                                    Main::getInstance()->getArenalistener()->resetPlayer($player, Main::getInstance()->getArenalistener()->getPlayerTeam($player));
                                    Main::getInstance()->getSaveTp()->saveTeleport($player, Server::getInstance()->getDefaultLevel());
                                }

                                unset($this->resettime[$lobby]);
                                unset($this->status[Main::getInstance()->arena[$lobby]["Level"]]);
                                Main::getInstance()->getArenalistener()->resetArena($gamelevelname, $lobby);
                                Main::getInstance()->getArenalistener()->setArenaStatus(Main::getInstance()->arena[$lobby]["Level"], $lobby, "Lobby");
                                Main::getInstance()->arena[$lobby]["Status"] = "Lobby";
                            }
                        }
                    }
                }
            }
        }
    }
}
                /*        $playersinlobby = count($level->getPlayers());
                        if ($playersinlobby >= Main::getInstance()->arena[$lobby]["PlayersNeed"]) {
                            Main::getInstance()->arena[$lobby]["WaitTime"]--;


                            $time = Main::getInstance()->arena[$lobby]["WaitTime"];

                            if ($time === 5) {
                                if (!Server::getInstance()->isLevelLoaded(Main::getInstance()->arena[$lobby]["Level"])) {
                                    Server::getInstance()->loadLevel(Main::getInstance()->arena[$lobby]["Level"]);

                                    $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->arena[$lobby]["Level"]);
                                    $gamelevel->setTime(0);
                                    $gamelevel->stopTime();
                                    $gamelevel->setAutoSave(false);
                                }
                            }

                            if ($time === 1) {
                                var_dump("sssfefge");
                                foreach ($level->getPlayers() as $playerinlobby) {
                                    $playerinlobby->getInventory()->clearAll();

                                    $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->arena[$lobby]["Level"]);
                                    Main::getInstance()->getSaveTp()->saveTeleport($playerinlobby, $gamelevel);

                                    if (Main::getInstance()->getArenalistener()->getPlayerTeam($playerinlobby) === "~") {
                                        $config = Main::getInstance()->getArenalistener()->onConfigGame($gamelevel->getFolderName());
                                        foreach ($config->getAll() as $teams => $info) {
                                            foreach ($info as $team => $infos) {
                                                if (Main::getInstance()->getArenalistener()->countTeamPlayers($gamelevel->getFolderName(), $team) < Main::getInstance()->arena[$lobby]["PlayerPerTeam"]) {
                                                    $n = $playerinlobby->getName();
                                                    Main::getInstance()->getArenalistener()->setTeamArray(Main::getInstance()->player[$n]["SignKey"]["Level"], $playerinlobby, $team, Main::getInstance()->player[$n]["Art"]);
                                                    $color = BColor::teamToColorString($team);
                                                    Main::getInstance()->player[$playerinlobby->getName()]["Level"] = $gamelevel->getFolderName();

                                                    $playerinlobby->sendMessage(Main::prefix . "§7Du wurdest dem Team {$color}{$team}§7 zugewießen");
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    if (Main::getInstance()->getArenalistener()->countTeamPlayers($gamelevel->getFolderName(), Main::getInstance()->getArenalistener()->getPlayerTeam($playerinlobby)) > 0) {
                                        $config = Main::getInstance()->getArenalistener()->onConfig($gamelevel->getFolderName());
                                        $team = Main::getInstance()->getArenalistener()->getPlayerTeam($playerinlobby);
                                        $config->setNested($team . ".Leben", true);
                                        $config->setNested($team . ".TeamLeben", true);
                                        $config->save();
                                    }

                                    Main::getInstance()->player[$playerinlobby->getName()]["Leben"] = true;

                                    $count = 3;
                                    Scoreboard::rmScoreboard($playerinlobby, $gamelevel->getFolderName());
                                    Scoreboard::createScoreboard($playerinlobby, "§4Bed§fWars §7DG", $gamelevel->getFolderName());
                                    $config = Main::getInstance()->getArenalistener()->onConfig($gamelevel->getFolderName());

                                    Scoreboard::setScoreboardEntry($playerinlobby, 0, "\00", $gamelevel->getFolderName());
                                    Scoreboard::setScoreboardEntry($playerinlobby, 1, "§7Gold: §2Ja", $gamelevel->getFolderName());
                                    Scoreboard::setScoreboardEntry($playerinlobby, 2, "\000\0", $gamelevel->getFolderName());


                                    foreach ($config->getAll() as $teams => $info) {
                                        if ($teams !== "Settings") {
                                            $teamcolor = BColor::teamToColorString($teams);
                                            if ($info["Leben"] == true) {
                                                Scoreboard::setScoreboardEntry($playerinlobby, $count, "§2✔ {$teamcolor}{$teams}", $gamelevel->getFolderName());

                                            } elseif ($info["Leben"] == false) {
                                                Scoreboard::setScoreboardEntry($playerinlobby, $count, "§4✘ {$teamcolor}{$teams}", $gamelevel->getFolderName());
                                            }
                                            $count++;
                                        }
                                    }
                                }
                                Main::getInstance()->arena[$lobby]["WaitTime"]--;
                            }
                        }

                        $time = Main::getInstance()->arena[$lobby]["WaitTime"];
                        if ($time <= 0) {
                            var_dump("rgijrtisluhusdhtoui");
                            $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->arena[$lobby]["Level"]);
                            Main::getInstance()->getArenalistener()->setArenaStatus($gamelevel->getFolderName(), $lobby, "Ingame");

                            $config = Main::getInstance()->getArenalistener()->onConfig($gamelevel->getFolderName());

                            Main::getInstance()->arena[$lobby]["Status"] = "Ingame";
                            Main::getInstance()->arena[$lobby]["WaitTime"] = $config->getNested("Settings" . ".WaitTime");


                            foreach ($gamelevel->getEntities() as $entity) {
                                if ($entity instanceof ItemEntity or $entity instanceof Villager or $entity instanceof Sheep or $entity instanceof PrimedTNT) {
                                    $entity->despawnFromAll();
                                    $entity->kill();
                                    var_dump("Python2");
                                }
                            }

                            foreach ($gamelevel->getTiles() as $tile) {
                                if ($tile instanceof Sign) {
                                    $blockabovesign = $gamelevel->getBlock(new Vector3($tile->x, $tile->y + 1, $tile->z));
                                    if ($blockabovesign->getId() == Block::get(Block::SANDSTONE)->getId()) {
                                        $nbt = Entity::createBaseNBT(new Vector3($tile->x + 0.5, $tile->y + 2, $tile->z + 0.5), new Vector3(0, 0, 0));
                                        $villager = new Villager($gamelevel, $nbt);
                                        $villager->spawnToAll();
                                        var_dump("SPAWN VILLAGER");
                                    }
                                }
                            }
                        }
                    }
                    if (Main::getInstance()->arena[$lobby]["Status"] == "Ingame") {
                        $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->arena[$lobby]["Level"]);

                        $config = Main::getInstance()->getArenalistener()->onConfig($gamelevel->getFolderName());
                        #var_dump($config);
                        $all = count($config->getAll()) - 2;
                        foreach ($gamelevel->getPlayers() as $players) {
                            if ($players->getGamemode() !== 1 or $players->getGamemode() !== 2) {
                                $count = 0;
                                $team = Main::getInstance()->getArenalistener()->getPlayerTeam($players);
                                $life = Main::getInstance()->getArenalistener()->isLeben($gamelevel->getFolderName(), $team);
                                foreach ($config->getAll() as $key => $infos) {
                                    if ($key !== "Settings") {
                                        if ($team !== $key) {
                                            $bett = Main::getInstance()->getArenalistener()->isBettLeben($gamelevel->getFolderName(), $key);
                                            $others = Main::getInstance()->getArenalistener()->isLeben($gamelevel->getFolderName(), $key);
                                            if ($life == true and $bett == false and $others == false) {
                                                $count = $count + 1;
                                                if ($count > $all - 1) {
                                                    Main::getInstance()->arena[$lobby]["Status"] = "Reset";
                                                    $teamnames = Main::getInstance()->getArenalistener()->getTeamArray($gamelevel->getFolderName(), $team);
                                                    foreach ($gamelevel->getPlayers() as $player) {
                                                        if (Main::getInstance()->player[$player->getName()]["Team"] !== "~") {
                                                            if (in_array($player->getName(), $teamnames)) {
                                                                Main::getInstance()->getStats()->addWin($player->getName());
                                                                var_dump($player->getName());
                                                            } else {
                                                                Main::getInstance()->getStats()->addLose($player->getName());
                                                                var_dump($player->getName());
                                                            }
                                                        }
                                                    }


                                                    foreach ($gamelevel->getPlayers() as $alllevelplayer) {
                                                        $lobbylevel = Server::getInstance()->getLevelByName($lobby);
                                                        Main::getInstance()->getSaveTp()->saveTeleport($alllevelplayer, $lobbylevel);
                                                        $color = BColor::teamToColor($team);

                                                        $alllevelplayer->spawnToAll();
                                                        $alllevelplayer->setGamemode(0);
                                                        $alllevelplayer->sendMessage(Main::prefix . "§7Team {$color}§7 hat das Spiel gewonnen");
                                                        Scoreboard::rmScoreboard($alllevelplayer, $gamelevel->getFolderName());
                                                    }
                                                    break;
                                                } elseif ($count == 0) {
                                                    Main::getInstance()->arena[$lobby]["Status"] = "Reset";
                                                    foreach ($gamelevel->getPlayers() as $alllevelplayer) {
                                                        $lobbylevel = Server::getInstance()->getLevelByName($lobby);
                                                        Main::getInstance()->getSaveTp()->saveTeleport($alllevelplayer, $lobbylevel);

                                                        $alllevelplayer->spawnToAll();
                                                        $alllevelplayer->setGamemode(0);
                                                        $alllevelplayer->doCloseInventory();
                                                        $alllevelplayer->getInventory()->clearAll();
                                                        Scoreboard::rmScoreboard($alllevelplayer, $gamelevel->getFolderName());
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }


                        $players = $gamelevel->getPlayers();


                        $tiles = $gamelevel->getTiles();
                        if (((int)date("U") % 1) == 0) {
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign and $tile->getLine(0) == "bronze") {
                                    $entities = $tile->getLevel()->getNearbyEntities(new AxisAlignedBB($tile->x - 1, $tile->y - 1, $tile->z - 1, $tile->x + 1, $tile->y + 3, $tile->z + 1));
                                    array_pop($entities);
                                    #var_dump(count($entities));
                                    if (count($entities) < 81) {
                                        $dropos = new Vector3($tile->x + 0.5, $tile->y + 2, $tile->z + 0.5);
                                        $gamelevel->dropItem($dropos, Item::get(Item::BRICK, 0, 1), new Vector3(0, 0.1, 0));
                                    }
                                    foreach ($players as $nearby) {
                                        if ($nearby->distance($tile) < 3) {
                                            $nearby->getInventory()->addItem(Item::get(Item::BRICK, 0, 1));
                                        }
                                    }
                                }
                            }
                        }

                        if (((int)date("U") % 30) == 0) {
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign and $tile->getLine(0) == "iron") {
                                    $dropos = new Vector3($tile->x + 0.5, $tile->y + 2, $tile->z + 0.5);
                                    $gamelevel->dropItem($dropos, Item::get(Item::IRON_INGOT, 0, 1), new Vector3(0, 0.1, 0));
                                }
                            }
                        }

                        if (((int)date("U") % 60) == 0) {
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign and $tile->getLine(0) == "gold") {
                                    $dropos = new Vector3($tile->x + 0.5, $tile->y + 2, $tile->z + 0.5);
                                    $gamelevel->dropItem($dropos, Item::get(Item::GOLD_INGOT, 0, 1), new Vector3(0, 0.1, 0));
                                }
                            }
                        }
                        foreach ($gamelevel->getPlayers() as $player) {
                            if ($player->getGamemode() == 1 or $player->getGamemode() == 2) {
                                if (!$player->isOp()) {
                                    $player->setGamemode(2);
                                } else {
                                    foreach ($gamelevel->getPlayers() as $players) {
                                        $players->hidePlayer($player);
                                    }
                                }
                                $player->setAllowFlight(true);
                                foreach ($gamelevel->getPlayers() as $players) {
                                    $players->hidePlayer($player);
                                }
                            }
                        }


                        $count = count($gamelevel->getPlayers());

                        if ($count == 0) {
                            Server::getInstance()->unloadLevel($gamelevel);
                            Main::getInstance()->getArenalistener()->setArenaStatus(Main::getInstance()->arena[$lobby]["Level"], $lobby, "Reset");
                            Main::getInstance()->arena[$lobby]["Status"] = "Reset";
                            $this->resettime = 12;
                            break;
                        }

                        foreach ($gamelevel->getPlayers() as $player) {
                            $count = 3;
                            Scoreboard::rmScoreboard($player, $gamelevel->getFolderName());
                            Scoreboard::createScoreboard($player, "§4Bed§fWars §7DG", $gamelevel->getFolderName());
                            $config = Main::getInstance()->getArenalistener()->onConfig($gamelevel->getFolderName());

                            Scoreboard::setScoreboardEntry($player, 0, "\00", $gamelevel->getFolderName());
                            Scoreboard::setScoreboardEntry($player, 1, "§7Gold: §2Ja", $gamelevel->getFolderName());
                            Scoreboard::setScoreboardEntry($player, 2, "\000\0", $gamelevel->getFolderName());

                            $teamcolor = BColor::teamToColorString(Main::getInstance()->getArenalistener()->getPlayerTeam($player));

                            $Team = Main::getInstance()->getArenalistener()->getPlayerTeam($player);

                            $player->sendTip("{$teamcolor}Team {$Team}");

                            foreach ($config->getAll() as $teams => $info) {
                                if ($teams !== "Settings") {
                                    $teamcolor = BColor::teamToColorString($teams);
                                    if ($info["Leben"] == true) {
                                        Scoreboard::setScoreboardEntry($player, $count, "§2✔ {$teamcolor}{$teams}", $gamelevel->getFolderName());

                                    } elseif ($info["Leben"] == false) {
                                        Scoreboard::setScoreboardEntry($player, $count, "§4✘ {$teamcolor}{$teams}", $gamelevel->getFolderName());
                                    }
                                    $count = $count + 1;
                                }
                            }
                        }

                    }
                    if (Main::getInstance()->arena[$lobby]["Status"] == "Reset") {
                        $time = $this->resettime;
                        if (Server::getInstance()->isLevelLoaded(Main::getInstance()->arena[$lobby]["Level"])) {
                            $gamelevelname = Server::getInstance()->getLevelByName(Main::getInstance()->arena[$lobby]["Level"])->getFolderName();

                            $this->resettime = $this->resettime - 1;
                            if (in_array($time, [10, 5, 4, 3, 2, 1])) {
                                $lobbylevel = Server::getInstance()->getLevelByName($lobby);
                                foreach ($lobbylevel->getPlayers() as $player) {
                                    $player->setGamemode(0);
                                    $player->spawnToAll();
                                    $player->sendMessage(Main::prefix . "§7Das Spiel endet in §e{$time}§7 Sekunden");
                                }
                            }

                            if ($time == 10 or $time == 7) {
                                $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->arena[$lobby]["Level"]);
                                $lobbylevel = Server::getInstance()->getLevelByName($lobby);
                                foreach ($gamelevel->getPlayers() as $player) {
                                    Main::getInstance()->getSaveTp()->saveTeleport($player, $lobbylevel);
                                    $player->spawnToAll();
                                    $player->setGamemode(0);
                                    $player->doCloseInventory();
                                    $player->getInventory()->clearAll();

                                    foreach ($gamelevel->getPlayers() as $pplayer) {
                                        $pplayer->showPlayer($player);
                                    }

                                }
                            }
                        }

                        if ($time == 5) {
                            $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->arena[$lobby]["Level"]);
                            if (Server::getInstance()->isLevelLoaded(Main::getInstance()->arena[$lobby]["Level"])) {
                                Server::getInstance()->unloadLevel($gamelevel);
                            }
                        }

                        if ($time == 0) {
                            $lobbylevel = Server::getInstance()->getLevelByName($lobby);
                            foreach ($lobbylevel->getPlayers() as $player) {

                                foreach ($lobbylevel->getPlayers() as $pplayer) {
                                    $pplayer->showPlayer($player);
                                }

                                Main::getInstance()->getSaveTp()->saveTeleport($player, Server::getInstance()->getDefaultLevel());
                                Main::getInstance()->getArenalistener()->resetPlayer($player, Main::getInstance()->getArenalistener()->getPlayerTeam($player));
                            }
                            Main::getInstance()->getArenalistener()->setArenaStatus(Main::getInstance()->arena[$lobby]["Level"], $lobby, "Lobby");
                            Main::getInstance()->getArenalistener()->resetArena($gamelevelname, $lobby);
                            Main::getInstance()->arena[$lobby]["Status"] = "Lobby";
                            $this->resettime = 12;
                        }
                    }
                }
            }
        }
    }
}*/

class Wait extends Task
{
    protected $pl;
    protected $player;

    public function __construct(CountDown $pl, Player $player)
    {
        $this->pl = $pl;
        $this->player = $player;
    }

    public function onRun(int $currentTick)
    {
        $team = Main::getInstance()->getArenalistener()->getPlayerTeam($this->player);
        Main::getInstance()->getArenalistener()->teleportPlayerSpawn($this->player, $team);
    }
}