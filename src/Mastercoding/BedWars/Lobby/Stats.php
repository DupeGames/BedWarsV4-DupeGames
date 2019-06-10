<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 29.11.2018
 * Time: 19:06
 */

namespace Mastercoding\BedWars\Lobby;


use Mastercoding\BedWars\Main;
use pocketmine\block\Thin;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Monster;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Stats extends PluginCommand implements Listener {
    public $pl;
    const setup = "§4Setup§7│";

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
        parent::__construct("stats", $pl);
        $this->setDescription("");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            if (!isset($args[0])) {

            } elseif (isset($args[0]) and $sender->hasPermission("bw.admin") and !isset($args[1])) {
                $sender->sendMessage(self::setup . "\n"
                    . "§2/stats §3add §a(int)");

            } else if (isset($args[1]) and $sender->hasPermission("bw.admin")) {
                switch ($args[0]) {
                    case "add":
                        if (isset($args[1])) {
                            if (is_numeric($args[1])) {
                                if ($args[1] > 0 and $args[1] < 4) {
                                    $config = $this->onConfig();
                                    $config->set($args[1], array("Vector" => serialize($sender->asVector3()), "Yaw"=>$sender->getYaw(), "Pitch"=>$sender->getPitch(),"Level"=>$sender->getLevel()->getFolderName()));
                                    $config->save();
                                    $sender->sendMessage(self::setup . "§2Du hast den Platz für §a{$args[1]} §agesetzt");
                                } else {
                                    $sender->sendMessage(self::setup . "§4Bitte gebe eine Zahl zwischen 1 und 4 ein");
                                }
                            } else {
                                $sender->sendMessage(self::setup . "§4Syntax error at Parameter §c>>§4{$args[1]}§c<< §4please use numbers!");
                            }
                        } else {
                            $sender->sendMessage(self::setup . "§4/stats add (int)");
                        }
                }
            } #TODO
        }
    }


    public function onConfig() : Config {
        $config = new Config($this->pl->getDataFolder() . "statsconfig.json", Config::JSON);
        $config->reload();
        return $config;
    }

    public function onPlayerConfig() : Config {
        $config = new Config("/var/www/Cloud/BedWars/stats.json",Config::JSON);
        $config->reload();
        return $config;
    }

    public function skins() : Config {
        $config = new Config("/root/Cloud/GlobalPlugins/Cloud-Group/skins.json",Config::JSON);
        $config->reload();
        return $config;
    }

    public function onBettenConfig() : Config {
        $config = new Config("/var/www/Cloud/BedWars/wins.json",Config::JSON);
        $config->reload();
        return $config;
    }

    public function spawnEntity(string $name, int $platz = 1)
    {
        $skinconfig = $this->skins();
        $config = $this->onConfig();

        $bc = $this->onBettenConfig();
        if ($bc->get($name) !== 0) {
            if ($config->exists($platz)) {
                $config = $config->get($platz);
                if (isset($config["Vector"])) {
                    $vector = unserialize($config["Vector"]);
                    $x = $vector->x;
                    $y = $vector->y;
                    $z = $vector->z;

                    $level = $this->pl->getServer()->getLevelByName($config["Level"]);

                    $c = $this->onPlayerConfig();
                    $c = $c->get($name);


                    $skin = 0;
                    if ($skinconfig->exists($name)) {
                        $skinc = base64_decode($skinconfig->get($name));
                        $skin = new Skin("Stabdart_Custom", $skinc);
                    } else {
                        $skinc = base64_decode($skinconfig->get("Mastercoding"));
                        $skin = new Skin("Stabdart_Custom", $skinc);
                    }

                    $nbt = new CompoundTag("", ["Pos" =>
                        new ListTag("Pos", [
                            new DoubleTag("", $x),
                            new DoubleTag("", $y),
                            new DoubleTag("", $z)]),
                        "Motion" =>
                            new ListTag("Motion", [
                                new DoubleTag("", 0),
                                new DoubleTag("", 0.2),
                                new DoubleTag("", 0)]),
                        "Rotation" =>
                            new ListTag("Rotation", [
                                new FloatTag("", $config["Yaw"]), #YAW
                                new FloatTag("", $config["Pitch"]), #PITCH
                            ]),
                        "Skin" =>
                            new CompoundTag("Skin", [
                                new StringTag("Data", $skin->getSkinData()),
                                new StringTag("Name", $skin->getSkinId())
                            ]),
                    ]);

                    $human = new Human($level, $nbt);
                    $human->spawnToAll();


                    switch ($platz) {
                        case 1:
                            $human->setScale(1.2);
                            $human->setNameTag("§6{$name}");
                            break;
                        case 2:
                            $human->setScale(1);
                            $human->setNameTag("§f{$name}");
                            break;
                        case 3:
                            $human->setScale(0.7);
                            $human->setNameTag("§7{$name}");
                            break;
                    }
                }
            }
        } else {
            $config = $config->get($platz);
            $vector = unserialize($config["Vector"]);
            if (isset($config["Vector"])) {
                $x = $vector->x;
                $y = $vector->y;
                $z = $vector->z;

                $level = $this->pl->getServer()->getLevelByName($config["Level"]);


                $c = $this->onPlayerConfig();
                $c = $c->get($name);

                $skin = 0;
                if ($skinconfig->exists($name)) {
                    $skinc = base64_decode($skinconfig->get($name));
                    $skin = new Skin("Stabdart_Custom", $skinc);
                } else {
                    $skinc = base64_decode($skinconfig->get("Mastercoding"));
                    $skin = new Skin("Stabdart_Custom", $skinc);
                }

                $nbt = new CompoundTag("", ["Pos" =>
                    new ListTag("Pos", [
                        new DoubleTag("", $x),
                        new DoubleTag("", $y),
                        new DoubleTag("", $z)]),
                    "Motion" =>
                        new ListTag("Motion", [
                            new DoubleTag("", 0),
                            new DoubleTag("", 0.2),
                            new DoubleTag("", 0)]),
                    "Rotation" =>
                        new ListTag("Rotation", [
                            new FloatTag("", $config["Yaw"]), #YAW
                            new FloatTag("", $config["Pitch"]), #PITCH
                        ]),
                    "Skin" =>
                        new CompoundTag("Skin", [
                            new StringTag("Data", $skin->getSkinData()),
                            new StringTag("Name", $skin->getSkinId())
                        ]),
                ]);

                $human = new Human($level, $nbt);
                $human->spawnToAll();
                $human->setNameTag("???");
            }
        }
    }

    public function getStatsPlayers(){
        $this->killEntity();

        $config = $this->onBettenConfig();

        $a = $config->getAll();
        arsort($a);
        $s = $a;

        $i = 1;
        foreach ($s as $name => $werte){
            if ($i < 4){
                $this->spawnEntity($name, $i);
                if ($i == 1){
                    $skinconfig = $this->skins();
                    $skin = 0;
                    if ($skinconfig->exists($name)) {
                        $skinc = base64_decode($skinconfig->get($name));
                        $skin = new Skin("Standart_Custom", $skinc);
                    }else{
                        $skinc = base64_decode($skinconfig->get("Mastercoding"));
                        $skin = new Skin("Standart_Custom", $skinc);
                    }
                    #$this->spawnEntity($skin);
                }
                #ump("GG");
                $i++;
            }
        }


        $o = 1;
        foreach ($s as $name => $werte){
            if ($o < 2){
                if ($o == 1){
                    $skinconfig = $this->skins();
                    $skin = 0;
                    if ($skinconfig->exists($name)) {
                        $skinc = base64_decode($skinconfig->get($name));
                        $skin = new Skin("Standart_Custom", $skinc);
                    }else{
                        $skinc = base64_decode($skinconfig->get("Mastercoding"));
                        $skin = new Skin("Standart_Custom", $skinc);
                    }
                    #$this->spawnEntity($skin);
                }
                $o++;
            }
        }
    }

    public function getRank(string $n){
        $config = $this->onBettenConfig();

        $a = $config->getAll();
        arsort($a);
        $s = $a;

        #ump($n);

        $i = 1;
        foreach ($s as $name => $werte){
            if ($i < count($s)){
                #ump('s');
                if ($name == $n){
                    #ump($i);
                    return $i;
                }
                $i++;
            }
        }
    }

    public function killEntity()
    {
        $c = $this->onConfig();
        if ($c->exists(1)) {
            $c = $c->get(1);

            $this->pl->getServer()->loadLevel($c["Level"]);
            $lvl = $this->pl->getServer()->getLevelByName($c["Level"]);

            $entitys = $lvl->getEntities();
            foreach ($entitys as $e) {
                if ($e instanceof Human and !$e instanceof Player and !$e instanceof Villager) {
                        $e->despawnFromAll();
                        $e->kill();
                }
            }
        }
    }

    public function onChat(PlayerChatEvent $ev){
        $p = $ev->getPlayer();
        $msg = $ev->getMessage();

        if ($msg == "stats"){
            $this->getStatsPlayers();
        }elseif ($msg == "spawn"){
        }
    }

    public function onLogin(PlayerLoginEvent $ev){
        $p = $ev->getPlayer();
        $n = $p->getName();
        $config = $this->onPlayerConfig();
        if (!$config->exists($n)){
            $config->set($n, array("Betten"=>0,"Deaths"=>0,"Kills"=>0,"Spielegespielt"=>0, "Spielerverlassen"=>0,"Minuten"=>0,"Wins"=>0,"Lose"=>0));
            $config->save();
        }

        $c2 = $this->onBettenConfig();
        if (!$c2->exists($n)){
            $c2->set($n, (int)0);
            $c2->save();
        }



        #$this->getStatsPlayers();
    }

    public function onJoin(PlayerJoinEvent $ev){
        $p = $ev->getPlayer();
        $this->getStatsPlayers();
    }

    public function addKill(Player $p){
        $n = $p->getName();
        $config = $this->onPlayerConfig();
        $config->setNested($n . ".Kills", $config->getNested($n . ".Kills") + 1);
        $config->save();
    }

    public function getKills(string $n){
        $config = $this->onPlayerConfig();
        return $config->getNested($n . ".Kills");
    }

    public function addDeath(Player $p){
        $n = $p->getName();
        $config = $this->onPlayerConfig();
        $config->setNested($n . ".Deaths", $config->getNested($n . ".Deaths") + 1);
        $config->save();
    }

    public function getDeaths(string $n){
        $config = $this->onPlayerConfig();
        return $config->getNested($n . ".Deaths");
    }

    public function addBettBreak(Player $p){
        $n = $p->getName();
        $config = $this->onPlayerConfig();
        $config->setNested($n . ".Betten", $config->getNested($n . ".Betten") + 1);
        $config->save();

        $config = $this->onBettenConfig();
        $config->set($n, $config->get($n) + 1);
        $config->save();
    }

    public function getBreaks(string $n){
        $config = $this->onPlayerConfig();
        return $config->getNested($n . ".Betten");
    }

    public function addPlayedGames(Player $p){
        $n = $p->getName();
        $config = $this->onPlayerConfig();
        $config->setNested($n . ".Spielegespielt", $config->getNested($n . ".Spielegespielt") + 1);
        $config->save();
    }

    public function getPlayedGames(string $n){
        $config = $this->onPlayerConfig();
        return $config->getNested($n . ".Spielegespielt");
    }

    public function addleftGames(Player $p){
        $n = $p->getName();
        $config = $this->onPlayerConfig();
        $config->setNested($n . ".Spieleverlassen", $config->getNested($n . ".Spieleverlassen") + 1);
        $config->save();
    }

    public function getleftGames(string $n){
        $config = $this->onPlayerConfig();
        return $config->getNested($n . ".Spielerverlassen");
    }

    public function addWin(string $name){
        $config = $this->onPlayerConfig();
        $config->setNested($name . ".Wins", $config->getNested($name . ".Wins") + 1);
        $config->save();

        $config = $this->onBettenConfig();
        $config->set($name, $config->get($name) + 1);
        $config->save();

        var_dump($name);
    }

    public function getWins(string $name){
        $config = $this->onPlayerConfig();
        return $config->getNested($name . ".Wins");
    }

    public function addLose(string $name){
        $config = $this->onPlayerConfig();
        $config->setNested($name . ".Lose", $config->getNested($name . ".Lose") + 1);
        $config->save();
    }

    public function getLose(string $name){
        $config = $this->onPlayerConfig();
        return $config->getNested($name . ".Lose");
    }
}