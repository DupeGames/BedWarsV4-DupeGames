<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 06.12.2018
 * Time: 17:56
 */

namespace Mastercoding\BedWars\Lobby;


use Mastercoding\BedWars\Main;
use MongoDB\Driver\ReadConcern;
use pocketmine\block\Thin;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;

class FloatingStats extends PluginCommand implements Listener {
    public $pl;
    public $floating;

    protected const prefix = "§9FloatingSats§7│";

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
        parent::__construct("floatingstats", $pl);
        $this->setDescription("Admin Command");
        $this->setPermission("admin.floatingstats");
        $this->setAliases(["fst"]);
    }

    public function onConfig() :Config {
        $config = new Config($this->pl->getDataFolder() . "floatingstats.json", Config::JSON);
        $config->reload();
        return $config;
    }

    public function iniFloating(Player $p){
        $name = $p->getName();
        $config = $this->onConfig();
        if ($config->exists("stats")) {
            $config = $config->get("stats");
            $vector = unserialize($config["vec"]);
            $vector = new Vector3($vector->x, $vector->y + 0.5, $vector->z);

            $wins = Main::getInstance()->getStats()->getWins($name);
            $loses = Main::getInstance()->getStats()->getLose($name);


            $kills = Main::getInstance()->getStats()->getKills($name);
            $deaths = Main::getInstance()->getStats()->getDeaths($name);
            $betten = Main::getInstance()->getStats()->getBreaks($name);
            $spiele = Main::getInstance()->getStats()->getPlayedGames($name);
            $leaved = Main::getInstance()->getStats()->getleftGames($name);

            $level = Server::getInstance()->getLevelByName($config["Level"]);

            $platz = Main::getInstance()->getStats()->getRank($name);
            $this->floating[$name] = new FloatingTextParticle($vector,"§7Wins {$wins}\n§7Lose {$loses}\n§7Kills §7{$kills}\n§7Deaths§f: §7{$deaths}\n§7Betten §{$betten}\n", "§2Stats-§3{$name}§7#§9{$platz}");

        }
    }

    public function onChat(PlayerChatEvent $ev){
        $p = $ev->getPlayer();
        $n = $p->getName();

    }

    public function onLogin(PlayerLoginEvent $ev){
        $p = $ev->getPlayer();
        $n = $p->getName();

        $this->iniFloating($p);

    }

    public function onJoin(PlayerJoinEvent $ev){
        $p = $ev->getPlayer();
        $n = $p->getName();

        $config = $this->onConfig();
        $config = $config->get("stats");

        $level = Server::getInstance()->getLevelByName($config["Level"]);

        $particle = $this->floating[$n];

        $config = $this->onConfig();
        if ($config->exists("stats")) {
            $config = $config->get("stats");
            $vector = unserialize($config["vec"]);
            $vector = new Vector3($vector->x, $vector->y + 0.5, $vector->z);

            if ($particle instanceof FloatingTextParticle) {

                $level->addParticle($particle, [$p]);
            }
        }
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            if ($this->testPermission($sender)){
                if (empty($args[0])){
                    $sender->sendMessage(self::prefix . "§2/fst §3set");
                }elseif (!empty($args[0])){
                    if ($args[0] == "set"){
                        $config = $this->onConfig();
                        $sender->sendMessage(self::prefix . "§2Du hast den Spawn für die Floating-Stats erfolgreich gesetzt!");
                        $config->set("stats", array("vec"=>serialize($sender->asVector3()), "Level"=>$sender->getLevel()->getFolderName()));
                        $config->save();
                    }
                }
            }
        }
        return true;
    }

    public function spawnStats(){

    }


    public function onMapSwitch(EntityLevelChangeEvent $ev) {
        $e = $ev->getEntity();

        $config = $this->onConfig();
        if ($config->exists("stats")){
            $config = $config->get("stats");
            if ($ev->getTarget()->getFolderName() == $config["Level"]) {
                if ($e instanceof Player) {
                    #ump($ev->getTarget()->getName());
                    $this->iniFloating($e);
                    $this->pl->getScheduler()->scheduleDelayedTask(new Spawn($this, $e->getName()), 20);
                }
            }else{
                $particle = $this->floating[$e->getName()];
                if ($particle instanceof FloatingTextParticle){
                    $particle->setInvisible(true);
                }
            }

        }
    }
}

class Spawn extends Task {
    protected $pl;
    protected $n;

    public function __construct(FloatingStats $pl, string $n)
    {
        $this->pl = $pl;
        $this->n = $n;
    }

    public function onRun(int $currentTick)
    {
        $pro = $this->pl->floating[$this->n];
        if ($pro instanceof FloatingTextParticle){
            $config = $this->pl->onConfig();
            $config = $config->get("stats");

            $level = Server::getInstance()->getLevelByName($config["Level"]);

            $particle = $this->pl->floating[$this->n];

            $p = $this->pl->pl->getServer()->getPlayerExact($this->n);

            $config = $this->pl->onConfig();
            if ($config->exists("stats")) {
                $config = $config->get("stats");
                $vector = unserialize($config["vec"]);
                $vector = new Vector3($vector->x, $vector->y + 0.5, $vector->z);

                if ($particle instanceof FloatingTextParticle) {
                    $level->addParticle($particle, [$p]);
                    #ump('s');
                }
            }
        }

    }
}