<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 10.12.2018
 * Time: 20:27
 */

namespace Mastercoding\BedWars\Arena;

use Mastercoding\BedWars\db;
use Mastercoding\BedWars\Main;
use Mastercoding\BedWars\Utils\BColor;
use Mastercoding\BedWars\Utils\Scoreboard;
use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\Thin;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\level\LevelSaveEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\sound\GenericSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\utils\Color;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class ArenaListener implements Listener
{
    public $pl;

    public $teamarray = [];

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
    }

    public function onPlayerTakeItem(InventoryPickupItemEvent $event)
    {
        if ($event->getItem()->getItem()->getCustomName() === "BRICK" or $event->getItem()->getItem()->getCustomName() === "IRON" or
            $event->getItem()->getItem()->getCustomName() === "GOLD") {
            $pos = $event->getItem()->getPosition()->asVector3();
            $pos->y -= 2;
            $tile = $event->getItem()->getLevel()->getTile($pos);
            if ($tile instanceof Sign) {
                if (in_array($event->getItem()->getLevel()->getFolderName(), Main::getInstance()->getLevels())) {
                    $levelname = $event->getItem()->getLevel()->getFolderName();
                    $pos = $tile->asVector3();
                    $id = $pos->x . $pos->y . $pos->z;
                    if (isset(Main::getInstance()->drops_count[$levelname][$id])) {
                        while (Main::getInstance()->drops_count[$levelname][$id] > 64) {
                                Main::getInstance()->drops_count[$levelname][$id] -= 64;

                                $event->getItem()->getLevel()->addSound(new GenericSound($event->getItem()->asVector3(), LevelSoundEventPacket::SOUND_POP, 2));

                                $event->getInventory()->addItem(Item::get($event->getItem()->getItem()->getId(), 0, 64));
                        }

                        $event->getInventory()->addItem(Item::get($event->getItem()->getItem()->getId(), 0, Main::getInstance()->drops_count[$levelname][$id]));
                        Main::getInstance()->drops_count[$levelname][$id] = 0;
                        $event->setCancelled(TRUE);
                        $event->getItem()->getLevel()->addSound(new GenericSound($event->getItem()->asVector3(), LevelSoundEventPacket::SOUND_POP, 1));
                        $event->getItem()->getLevel()->dropItem($event->getItem()->asVector3(), $event->getItem()->getItem(), new Vector3(0, 0, 0));
                        $event->getItem()->kill();
                    }else{
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function onLogin(PlayerLoginEvent $ev)
    {
        $p = $ev->getPlayer();
        $n = $p->getName();

        $p->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
    }

    public function onConfigGame(string $level)
    {
        $config = new Config(Main::getInstance()->getDataFolder() . "/arenas/" . $level . ".json", Config::JSON);
        $config->reload();
        return $config;
    }

    public function onConfig(string $level): Config
    {
        $config = new Config(Main::getInstance()->getDataFolder() . "/levels/" . $level . ".json", Config::JSON);
        $config->reload();
        return $config;
    }

    public function getPlayerTeam(Player $player)
    {
        $n = $player->getName();
        return Main::getInstance()->player[$n]["Team"];
    }

    public function setPlayerTeam(Player $player, string $team)
    {
        $n = $player->getName();
        Main::getInstance()->player[$n]["Team"] = $team;
    }

    public function getTeamArray(string $level, string $team) : array
    {
        $config = $this->onConfigGame($level);
        $array = $config->get($level);

        $team = $array[$team];
        return $team;
    }

    public function isLeben(string $level, string $team)
    {
        $config = $this->onConfig($level);
        $infos = $config->get($team);
        return $infos["TeamLeben"];
    }

    public function isBettLeben(string $level, string $team)
    {
        $config = $this->onConfig($level);
        $infos = $config->get($team);
        return $infos["Leben"];
    }

    public function resetArena(string $level, string $lobby = "")
    {
        $config = Main::getInstance()->getArenalistener()->onConfigGame($level);

        $config->set($level, array("Rot" => [], "Blau" => [], "Grün" => [], "Gelb" => []));
        $config->save();

        $config = Main::getInstance()->getArenalistener()->onConfig($level);
        $info = $config->get("Settings");


        Main::getInstance()->arena[$lobby] = array("WaitTime" => $info["WaitTime"],
            "Level" => $level,
            "PlayersNeed" => $info["PlayersNeed"],
            "PlayerPerTeam" => $info["PlayerPerTeam"],
            "Sorte" => $info["Sorte"],
            "Status" => $info["Status"]);

        Main::getInstance()->drops_count[$level] = [];
        Main::getInstance()->blocks[$level] = [];

        foreach ($config->getAll() as $key => $infos) {
            if ($key !== "Settings") {
                if (isset($infos["Leben"])) {
                    $config->setNested($key . ".Leben", false);
                    $config->setNested($key . ".TeamLeben", false);
                    $config->save();
                }
            }
        }
    }

    public function setArenaStatus(string $level, string $lobby, string $status = "Ingame")
    {
        $config = $this->onConfig($level);
        $config->setNested("Settings" . ".Status", $status);
        $config->save();
    }


    public function setTeamArray(string $level, Player $player, string $team, string $art)
    {

        $config = $this->onConfigGame($level);
        $array = $config->get($level);

        array_push($array[$team], $player->getName());
        $config->set($level, $array);
        $config->save();

        $this->setPlayerTeam($player, $team);
        $this->setBedWarsRang($player, $team);
    }

    public function removePlayerFromTeamArray(string $level, $player)
    {
        if ($player instanceof Player) {
            $name = $player->getName();
            $team = $this->getPlayerTeam($player);
            $config = $this->onConfigGame($level);
            $array = $config->get($level);

            $serach = array_search($name, $array[$team]);
            unset($array[$team][$serach]);
            $config->set($level, $array);
            $config->save();
            $this->setBedWarsRang($player, "n");
        } else {
            $name = $player;
        }
    }

    public function resetPlayer(Player $player, string $team)
    {
        $name = $player->getName();
        $player->spawnToAll();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setGamemode(0);

        if (Main::getInstance()->player[$name]["Level"] !== "~") {

            Scoreboard::rmScoreboard($player, Main::getInstance()->player[$name]["Level"]);


            if (Main::getInstance()->player[$name]["Team"] !== "~" and Main::getInstance()->player[$name]["Team"] !== "Spectator") {
                $this->removePlayerFromTeamArray(Main::getInstance()->player[$name]["SignKey"]["Level"], $player);

                Main::getInstance()->player[$name] = array("State" => "~", "Level" => "~", "Team" => "~", "Menu" => "~", "SignKey" => "~", "Art" => "~", "Leben" => false);
            }
        }
    }


    public function onDamgeByEntity(EntityDamageByEntityEvent $ev)
    {
        $e = $ev->getEntity();
        $d = $ev->getDamager();

        if ($e instanceof Player and $d instanceof Player or $e instanceof Player and $d instanceof Arrow) {
            if ($this->getPlayerTeam($d) == $this->getPlayerTeam($e)) {
                $ev->setCancelled();
            } else {
                Main::getInstance()->lasthit[$e->getName()] = $d->getName();
                if ($ev->getFinalDamage() >= $e->getHealth()) {
                    $ev->setCancelled();
                    $this->onPlayerRespawn($e);
                    #mp("s");
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();

        Main::getInstance()->player[$name]["Leben"] = false;

        $levelname = Main::getInstance()->player[$name]["Level"];
        if ($levelname !== "~" and $this->getPlayerTeam($player) !== "~" and $this->getPlayerTeam($player) !== "Spectator") {
            ##mp($levelname);
            if ($levelname !== "~") {
                ##mp($levelname);
                $team = $this->getPlayerTeam($player);

                $bettleben = $this->isBettLeben(Main::getInstance()->player[$name]["Level"], $this->getPlayerTeam($player));
                $teamarray = $this->getTeamArray(Main::getInstance()->player[$name]["Level"], $this->getPlayerTeam($player));

                $config = $this->onConfig($levelname);

                $count = 0;
                $all = count($teamarray) - 1;
                foreach ($teamarray as $teamnames) {
                    if (Main::getInstance()->player[$teamnames]["Leben"] == false) {
                        $count = $count + 1;
                        if ($count > $all) {
                            $config->setNested($team . ".TeamLeben", false);
                            $config->setNested($team . ".Leben", false);
                            $config->save();
                            break;
                        }
                    }
                }
            }
        }
    }

    public function onExhaust(PlayerExhaustEvent $event)
    {
        $event->getPlayer()->setFood(20);
        #$event->setCancelled();
    }


    public function onPlayerRespawn(Player $player, string $reason = "~")
    {
        $name = $player->getName();
        $player->doCloseInventory();
        $player->getCraftingGrid()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();

        if ($player->getGamemode() == 0) {

            Main::getInstance()->getStats()->addDeath($player);

            $player->setHealth(20);
            $hiter = Main::getInstance()->lasthit[$name];
            if ($hiter !== "~") {
                $angreifer = Server::getInstance()->getPlayerExact($hiter);
                if ($angreifer !== NULL) {
                    $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->player[$name]["Level"]);
                    $playercolor = BColor::teamToColorString($this->getPlayerTeam($player));
                    $angreifercolor = BColor::teamToColorString($this->getPlayerTeam($angreifer));

                    Main::getInstance()->getStats()->addKill($angreifer);

                    $this->teleportPlayerSpawn($player, $this->getPlayerTeam($player));
                    foreach ($gamelevel->getPlayers() as $levelplayer) {
                        $levelplayer->sendMessage(Main::prefix . "{$playercolor}{$player->getDisplayName()}§7 wurde von {$angreifercolor}{$angreifer->getDisplayName()}§7 getötet");
                    }
                    $player->sendMessage(Main::prefix . "§7Du wurdest von {$angreifercolor}{$angreifer->getDisplayName()}§7 getötet");
                    #Todo Stats

                }
            } else {
                if (Main::getInstance()->player[$player->getName()]["Level"] !== "~") {
                    $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->player[$name]["Level"]);
                    $playercolor = BColor::teamToColorString($this->getPlayerTeam($player));

                    foreach ($gamelevel->getPlayers() as $levelplayer) {
                        $levelplayer->sendMessage(Main::prefix . "{$playercolor}{$player->getDisplayName()}§7 ist gestorben");
                    }
                    $this->teleportPlayerSpawn($player, $this->getPlayerTeam($player));
                    $player->sendMessage(Main::prefix . "§7Du bist gestorben");
                }

            }

            $levelname = Main::getInstance()->player[$name]["Level"];
            $team = $this->getPlayerTeam($player);


            if (Main::getInstance()->player[$name]["Level"] !== "~" and $this->getPlayerTeam($player) !== "~") {
                $bettleben = $this->isBettLeben(Main::getInstance()->player[$name]["Level"], $this->getPlayerTeam($player));
                $teamarray = $this->getTeamArray(Main::getInstance()->player[$name]["Level"], $this->getPlayerTeam($player));


                if ($bettleben === false) {
                    $gamelevel = Server::getInstance()->getLevelByName(Main::getInstance()->player[$name]["Level"]);
                    Main::getInstance()->player[$name]["Leben"] = false;
                    $this->spectateSpiel($player, $gamelevel);
                    $player->sendMessage(Main::prefix . "§7Du bist nun Zuschauer");
                }

                $config = $this->onConfig($levelname);
                #$playerperteam = $config->getNested("Settings" . ".PlayerPerTeam");

                $count = 0;
                $all = count($teamarray) - 1;
                #mp($all);

                foreach ($teamarray as $teamnames) {
                    #mp($teamnames);
                    if (Main::getInstance()->player[$teamnames]["Leben"] == false) {
                        #mp("IST TOT");
                        $count = $count + 1;
                        if ($count > $all) {
                            $config->setNested($team . ".TeamLeben", false);
                            $config->save();
                            break;
                        }
                    }
                }
            }
            Main::getInstance()->lasthit[$name] = "~";
        }
    }

    public function onExPlode(EntityExplodeEvent $event)
    {
        $entity = $event->getEntity();
        $level = $entity->getLevel();
        $blocklist = $event->getBlockList();
        $block = Main::getInstance()->blocks[$level->getFolderName()];
        $entity->getLevel()->addParticle(new ExplodeParticle($entity->asVector3()));
        $event->setCancelled();

        foreach ($blocklist as $list) {
            if (!empty($block)) {
                if (in_array($list->asVector3(), $block)) {
                    $level->setBlock($list->asVector3(), Block::get(Block::AIR));
                    $search = array_search($list->asVector3(), $block);
                    unset(Main::getInstance()->blocks[$level->getFolderName()][$search]);
                }
            }
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $cause = $event->getCause();
            if (!$event->isCancelled()) {
                if ($entity->getLevel()->getFolderName() == Server::getInstance()->getDefaultLevel()->getFolderName() or in_array($entity->getLevel()->getFolderName(), Main::getInstance()->getLobbys())) {
                    $event->setCancelled();
                } else {
                    if ($cause === EntityDamageEvent::CAUSE_FALL) {
                        if ($event->getFinalDamage() >= $entity->getHealth()) {
                            $event->setCancelled(true);
                            $entity->setHealth(20);
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new Teleport($this, $entity), 1);
                        }
                    } elseif ($cause === EntityDamageEvent::CAUSE_VOID or $cause === EntityDamageEvent::CAUSE_SUFFOCATION or $cause === EntityDamageEvent::CAUSE_FIRE or $cause === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION or $cause === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION) {
                        if ($event->getFinalDamage() >= $entity->getHealth()) {
                            if ($entity->getGamemode() == 0) {
                                $event->setCancelled(true);
                                $entity->setHealth(20);
                                $this->onPlayerRespawn($entity);
                            }elseif ($entity->getGamemode() == 2){
                                $event->setCancelled();
                            }
                        }
                    }
                }
            }
        }
    }


    public function onChat(PlayerChatEvent $ev)
    {
        $p = $ev->getPlayer();
        $n = $p->getName();
        $msg = $ev->getMessage();

        $array = explode(" ", $msg);



        #mp($msg);

        if (in_array($p->getLevel()->getFolderName(), Main::getInstance()->getLobbys())) {
            $ev->setCancelled();
            foreach ($p->getLevel()->getPlayers() as $player) {
                $team = $this->getPlayerTeam($player);
                $teamcolor = BColor::teamToColorString($team);
                $player->sendMessage("{$teamcolor}{$p->getDisplayName()} §8» §7{$msg}");
            }

        } else if (in_array($p->getLevel()->getFolderName(), Main::getInstance()->getLevels())) {

            if ($this->isBedWars($p)) {
                if (Main::getInstance()->player[$n]["Team"] !== "~" and Main::getInstance()->player[$n]["Team"] !== "Spectator") {
                    $players = $p->getLevel()->getPlayers();
                    $team = $this->getPlayerTeam($p);
                    $teamcolor = BColor::teamToColorString($team);
                    $ev->setCancelled();

                    foreach ($players as $player) {
                        if ($array[0] !== "@a" and $this->getPlayerTeam($player) == $team) {
                            $player->sendMessage("{$teamcolor}{$p->getDisplayName()} §8» §7{$msg}");
                        }
                    }

                    if ($array[0] == "@a" and isset($array[1])) {
                        unset($array[0]);
                        $mss = implode(" ", $array);
                        foreach ($players as $player) {
                            if ($player === $p) {

                                $player->sendMessage("§0[§7@all§0]§r{$teamcolor} {$p->getDisplayName()} §8» §7{$mss}");
                            } else {
                                $player->sendMessage("{$teamcolor}{$p->getDisplayName()} §8» §7{$mss}");
                            }
                        }
                    }
                }elseif (Main::getInstance()->player[$n]["Team"] == "Spectator"){
                    foreach ($p->getLevel()->getPlayers() as $player) {
                        $team = $this->getPlayerTeam($p);
                        $teamcolor = BColor::teamToColorString($team);
                        if ($this->getPlayerTeam($player) == $team) {
                            $player->sendMessage("{$teamcolor}{$p->getDisplayName()} §8» §7{$msg}");
                        }
                    }
                }
            }
        }
    }

    public function getBedWarsRang(Player $p)
    {
        $n = $p->getName();
        $db = new db();
        $con = $db->connect();
        $result = $con->query("SELECT * FROM crang WHERE Name = '$n'");
        $data = mysqli_fetch_array($result);
        $nick = $data["BedWarsRang"];
        $con->close();
        return $nick;
    }

    public function setBedWarsRang(Player $p, string $team)
    {
        $n = $p->getName();
        $db = new db();
        $con = $db->connect();
        $con->query("UPDATE crang SET BedWarsRang = '$team' WHERE Name = '$n'");
        $con->close();

        Main::getInstance()->getGroup()->getEventListener()->updateScreen($p);
    }

    public function isBedWars(Player $p)
    {
        $nick = $this->getBedWarsRang($p);
        if ($nick !== 'n' and $nick !== "") {
            return true;
        } else {
            return false;
        }
    }

    public function countTeamPlayers(string $level, string $team): int
    {
        $config = $this->onConfigGame($level);
        $info = $config->get($level);
        if (isset($info[$team])) {
            return count($info[$team]);
        } else {
            Main::getInstance()->getLogger()->alert("§4Achtung Sofort Fixxen");
            Server::getInstance()->broadcastMessage("§4Error in Runde {$level} §lBitte sofort in Bugreport");
        }
        return true;
    }

    public function teleportPlayerSpawn(Player $player, string $team)
    {


        $name = $player->getName();
        $level = Main::getInstance()->player[$name]["Level"];

        $config = $this->onConfig($level);

        foreach ($config->getAll() as $key => $infos) {
            #mp($level);
            if ($key !== "Settings") {
                if ($key == $team) {
                    $v = unserialize($config->getNested($team . ".Spawn"));
                    $x = $v->x;
                    $y = $v->y;
                    $z = $v->z;

                    /**
                     * onPlayerRespawn
                     */

                    #TODO Wenn der Spieler Tod ist nicht Respawnen

                    $player->teleport(new Vector3($x, $y + 1, $z));
                }
            }
        }
    }

    public function spectateSpiel(Player $player, Level $level)
    {
        $inv = $player->getInventory();
        $inv->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setGamemode(3);
        $name = $player->getName();

        #$inv->setItem(0, Item::get(Item::COMPASS)->setCustomName("§7Teleporter"));
        #$inv->setItem(8, Item::get(Item::MAGMA_CREAM)->setCustomName("§6Lobby"));

        Main::getInstance()->getSaveTp()->saveTeleport($player, $level);

    }

    public function onPleace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();

        $block = $event->getBlock();
        if (in_array($player->getLevel()->getFolderName(), Main::getInstance()->getLobbys())){
            $event->setCancelled();
            $player->sendMessage(Main::prefix . "§7Du kannst keinen Block auf den Block §e{$block->getName()}§7 setzen");
        }
        if (Main::getInstance()->player[$name]["Level"] !== "~") {
            $levelname = Main::getInstance()->player[$name]["Level"];
            if (Main::getInstance()->player[$name]["Level"] == $player->getLevel()->getFolderName()) {
                array_push(Main::getInstance()->blocks[$levelname], $block->asVector3());
            }
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();

        if (in_array($player->getLevel()->getFolderName(), Main::getInstance()->getLobbys())) {
            if ($player->getGamemode() !== 1) {
                $player->sendMessage(Main::prefix . "§7Du kannst den Block §e{$block->getName()}§7 nicht Abbauen");
                $event->setCancelled();
            }
        } else {
            if (Main::getInstance()->player[$name]["Level"] !== "~") {
                $levelname = Main::getInstance()->player[$name]["Level"];
                if (Main::getInstance()->player[$name]["Level"] == $player->getLevel()->getFolderName()) {
                    $bx = $block->getX();
                    $bz = $block->getZ();
                    $by = $block->getY();

                    if (in_array($block->asVector3(), Main::getInstance()->blocks[$levelname])) {

                    } else if ($block instanceof Bed) {
                        $config = $this->onConfig($levelname);
                        $team = $this->getPlayerTeam($player);

                        $level = Server::getInstance()->getLevelByName(Main::getInstance()->player[$name]["Level"]);

                        foreach ($config->getAll() as $key => $infos) {
                            if ($key !== "Settings") {
                                if ($this->pl->makemap[$event->getPlayer()->getName()]["State"] == false) {
                                    $vector = unserialize($infos["Bett"]);
                                    $x = $vector->x;
                                    $y = $vector->y;
                                    $z = $vector->z;

                                    if (($x == $bx && $y == $by && $z == $bz) ||
                                        ($x == $bx + 1 && $y == $by && $z == $bz) ||
                                        ($x == $bx && $y == $by && $z == $bz - 1) ||
                                        ($x == $bx && $y == $by && $z == $bz + 1) ||
                                        ($x == $bx - 1 && $y == $by && $z == $bz)) {
                                        if ($block instanceof Bed) {
                                            if ($infos["Leben"] == true and $team !== $key) {
                                                $team = $key;

                                                $config->setNested($key . ".Leben", false);
                                                $config->save();
                                                $event->setDrops([]);

                                                Main::getInstance()->getStats()->addBettBreak($player);

                                                $color = BColor::teamToColorString(Main::getInstance()->getArenalistener()->getPlayerTeam($player));
                                                $colors = BColor::teamToColor($team);

                                                foreach ($level->getPlayers() as $players) {
                                                    $players->sendMessage(Main::prefix . "§7Das Bett von Team {$colors} §7wurde von {$color}{$player->getDisplayName()} §7zerstört");
                                                }
                                            } else if ($team == $key) {
                                                $player->sendMessage(Main::prefix . "§7Du kannst dein Bett nicht zerstören");
                                                $event->setCancelled();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $player->sendMessage(Main::prefix . "§7Du kannst den Block §e{$block->getName()}§7 nicht Abbauen");
                        $event->setCancelled();
                    }
                }
            }
        }
    }
}

class Teleport extends Task {
    protected $pl;
    protected $player;

    public function __construct(ArenaListener $pl, Player $player)
    {
        $this->player = $player;
    }

    public function onRun(int $currentTick)
    {
        Main::getInstance()->getArenalistener()->onPlayerRespawn($this->player);
    }
}
