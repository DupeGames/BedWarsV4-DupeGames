<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 03.12.2018
 * Time: 19:30
 */

namespace Mastercoding\BedWars;
use Mastercoding\BedWars\Arena\ArenaListener;
use Mastercoding\BedWars\Arena\InteractSign;
use Mastercoding\BedWars\Arena\Spectator;
use Mastercoding\BedWars\Commands\BedWarsCommand;
use Mastercoding\BedWars\Commands\SpectateEntity;
use Mastercoding\BedWars\Commands\Start;
use Mastercoding\BedWars\Lobby\FloatingStats;
use Mastercoding\BedWars\Lobby\Lobby;
use Mastercoding\BedWars\Lobby\Stats;
use Mastercoding\BedWars\Task\CountDown;
use Mastercoding\BedWars\Task\SignUpdate;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat as Color;

class Main extends PluginBase implements Listener {

    public const prefix = "§cBedWars§7│";

    public static $savetp;

    public $makemap;

    public static $instance;

    public $signs = [];

    public $teamplayers = [];

    public $incooldown = [];

    public $arena;

    public $arenalistener;
    public $lobby;

    public $player;
    public $lasthit;
    public $blocks;

    public static $group;
    public static $stats;

    public $drops_count;

    public function onEnable()
    {
        self::$instance = $this;

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        self::$group = Server::getInstance()->getPluginManager()->getPlugin("Cloud-Group");


        $this->getServer()->getCommandMap()->register("stats", new Stats($this));
        $this->getServer()->getPluginManager()->registerEvents(new Stats($this), $this);

        $this->getServer()->getPluginManager()->registerEvents(new FloatingStats($this), $this);
        $this->getServer()->getCommandMap()->register("floatingstats", new FloatingStats($this));

        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("kill"));

        $this->getLevelsFolder();
        $this->getSigns();

        $this->registerCommands();
        $this->registerEvents();
        $this->registerScheduler();

        $s = $this->getScheduler();
        $s->scheduleRepeatingTask(new SignUpdate($this), 20);
        $s->scheduleRepeatingTask(new CountDown($this), 20);

        self::$stats = new Stats($this);

        @mkdir("/var/www/Cloud/BedWars/");

        if (!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

    }

    public function onLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        $this->player[$name] = array("State"=>"~","Level"=>"~","Team"=>"~","Menu"=>"~","SignKey"=>"~","Art"=>"~","Leben"=>false);
        $this->lasthit[$name] = "~";

        $player->setGamemode(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();

        #dump($this->player[$name]);
    }

    public function onDisable()
    {
        $dir = $this->getDataFolder() . "levels";
        if (is_dir($dir)) {
            $t = @scandir($dir);
            if ($t !== NULL) {
                for ($i = 0; $i <= count($t); $i++) {
                    if ($i !== 0 && $i !== 1) {
                        if ($i < count($t)) {
                            $m = new Config($this->getDataFolder() . "levels/" . $t[$i], Config::JSON);
                            $name = explode(".", $t[$i]);
                            $cc = $m->get("Settings");
                            if ($cc["Status"] == "Ingame"){
                                $m->setNested("Settings" . ".Status", "Lobby");
                                $m->save();
                            }
                        }
                    }
                }
            }
        }
    }

    public function onLevelsConfig() : array {
        $dir = @scandir($this->getDataFolder() . "levels/");
        $m[] = 0;
        for ($i = 0; $i < count($dir); $i++) {
            if ($i != 0 && $i != 1) {
                if ($i < count($dir)) {
                    $m[] = new Config($this->getDataFolder() . "levels/" . $dir[$i], Config::JSON);
                }
            }
        }
        return $m;
    }

    /**
     * @return mixed
     */
    public function getArenalistener() : ArenaListener
    {
        return $this->arenalistener;

    }

    public function getLobby() : Lobby
    {
        return $this->lobby;
    }



    public function onLevelConfig(string $levelname){
        if (is_file($this->getDataFolder() . "levels/" . $levelname . ".json")){
            $c = new Config($this->getDataFolder() . "levels/". $levelname . ".json", Config::JSON);
            $c->reload();
            return $c;
        }
    }

    public function getSignInfofromArray(string $key, string $search){
        foreach ($this->signs as $sign){
            if ($sign[$key] == $search){
                #dump($sign);
                return $sign;
            }
        }
    }

    public function getSignKey(string $lobbyname)
    {
        foreach ($this->signs as $sign => $info) {
            return $sign[$lobbyname];
        }
    }

    public function getSignInfo(string $wartelobby){
        #dump($this->signs);
        foreach ($this->signs as $sign => $info){
            #dump($wartelobby);
            if ($info["Lobby"] == $wartelobby){
                $infos = $this->signs[$info["Name"]];
                #dump($infos);
                return $infos;
            }
        }
    }

    public function getLobbyNameFromLevel(string $levelname){
        #dump($this->signs);
        foreach ($this->signs as $sign => $info){
            #dump($levelname);
            if ($info["Level"] == $levelname){
                $infos = $this->signs[$info["Lobby"]];
                #dump($infos);
                return $infos;
            }
        }
    }

    public function getSignInfoFromLevel(string $levelname){
        #dump($this->signs);
        foreach ($this->signs as $sign => $info){
            #dump($levelname);
            if ($info["Level"] == $levelname){
                $infos = $this->signs[$info["Name"]];
                #dump($infos);
                return $infos;
            }
        }
    }

    public function getSigns(){
        $dir = $this->getDataFolder() . "levels";
        if (is_dir($dir)) {
            $t = @scandir($dir);
            $this->signs = [];
            if ($t !== NULL) {
                for ($i = 0; $i <= count($t); $i++) {
                    if ($i !== 0 && $i !== 1) {
                        if ($i < count($t)) {
                            $m = new Config($this->getDataFolder() . "levels/" . $t[$i], Config::JSON);
                            $name = explode(".", $t[$i]);
                            $cc = $m->get("Settings");
                            $sorte = $cc["Sorte"];
                            $ppteam = explode("x", $sorte);
                            $cord = $m->getNested("Settings" . ".Schild");
                            $cord = unserialize($cord);
                            $this->signs[$m->getNested("Settings" . ".Name")] = array("Name" => $m->getNested("Settings" . ".Name"),
                                "Cord" => $cord,
                                "Art" => $m->getNested("Settings" . ".Sorte"),
                                "Level" => $name[0],
                                "Status" => $m->getNested("Settings" . ".Status"),
                                "Lobby" => $m->getNested("Settings" . ".Lobby"),
                                "PlayerPerTeam" => $ppteam[1]
                            );
                        }
                    }
                }
            }
        }
    }

    public function getSign(string $level){
        $m = new Config($this->getDataFolder() . "levels/" . "{$level}.json", Config::JSON);
        $cc = $m->get("Settings");
        $sorte = $cc["Sorte"];
        $ppteam = explode("x", $sorte);
        $cord = $m->getNested("Settings" . ".Schild");
        $cord = unserialize($cord);
        $this->signs[$m->getNested("Settings" . ".Name")] = array("Name" => $m->getNested("Settings" . ".Name"),
            "Cord" => $cord,
            "Art" => $m->getNested("Settings" . ".Sorte"),
            "Level" => $level,
            "Status" => $m->getNested("Settings" . ".Status"),
            "Lobby" => $m->getNested("Settings" . ".Lobby"),
            "PlayerPerTeam" => $ppteam[1]
        );
    }

    public function getLevelsFolder() : array {
        $dir = $this->getDataFolder() . "levels";
        if (is_dir($dir)) {
            $t = @scandir($dir);
            unset($array);
            $array = [];
            if ($t !== NULL) {
                for ($i = 0; $i <= count($t); $i++) {
                    if ($i !== 0 && $i !== 1) {
                        if ($i < count($t)) {
                            $m = new Config($this->getDataFolder() . "levels/" . $t[$i], Config::JSON);
                            $name = explode(".", $t[$i]);
                            $cc = $m->get("Settings");

                            if ($cc["Schild"] !== "~") {

                                $sorte = $cc["Sorte"];
                                $ppteam = explode("x", $sorte);
                                $cord = $m->getNested("Settings" . ".Schild");
                                $cord = unserialize($cord);
                                $array[$m->getNested("Settings" . ".Name")] = array("Name" => $m->getNested("Settings" . ".Name"),
                                    "Cord" => $cord,
                                    "Art" => $m->getNested("Settings" . ".Sorte"),
                                    "Level" => $name[0],
                                    "Status" => $m->getNested("Settings" . ".Status"),
                                    "Lobby" => $m->getNested("Settings" . ".Lobby"),
                                    "PlayerPerTeam" => $ppteam[1]
                                );
                            }
                        }
                    }
                }
            }
            return $array;
        }
    }

    public function getLobbys() : array {
        $lobbys = [];
        foreach ($this->signs as $sign => $info) {
            $lobbys[]= $info["Lobby"];
        }

        return $lobbys;
    }

    public function getLevels() : array {
        $lobbys = [];
        foreach ($this->signs as $sign => $info) {
            $lobbys[]= $info["Level"];
        }

        return $lobbys;
    }


    public function registerCommands(){
        $map = $this->getServer()->getCommandMap();
        $map->register("bedwars", new BedWarsCommand($this, "bedwars", "BedWars Main Command!", "/bedwars", ["bw"]));
        $map->register("spectate", new SpectateEntity($this, "spec", "Admin Spec!", "/spec", ["spec"]));
        $map->register("start", new Start($this, "start", "Starte", "/start"));
    }

    public function registerEvents(){

        $plugin = $this->getServer()->getPluginManager();

        $plugin->registerEvents(new SaveTP($this), $this);

        $plugin->registerEvents(new BedWarsCommand($this,"bedwars", "BedWars Main Command!", "/bedwars", ["bw"]), $this);
        $plugin->registerEvents(new SpectateEntity($this, "spec"), $this);

        $arenalistener = new ArenaListener($this);
        $plugin->registerEvents($arenalistener,$this);
        $this->arenalistener = $arenalistener;

        $plugin->registerEvents(new InteractSign($this), $this);

        $lobby = new Lobby($this);
        $plugin->registerEvents($lobby, $this);
        $this->lobby = $lobby;

        $plugin->registerEvents(new Spectator($this), $this);

        $plugin->registerEvents(new EventListener($this), $this);

        self::$savetp = new SaveTP($this);
    }

    public function registerScheduler() {
    }

    public function getStats() : Stats {
        return self::$stats;
    }

    public function getSaveTp() : SaveTP{
        return self::$savetp;
    }

    public function getGroup(){
        return self::$group;
    }

    public static function getInstance() : Main {
        return self::$instance;
    }

}