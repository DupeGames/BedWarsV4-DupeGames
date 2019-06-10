<?php
/**
 * Created by PhpStorm.
 * User: chr1s
 * Date: 25.01.2019
 * Time: 14:34
 */
namespace Mastercoding\BedWars\Commands;

use Mastercoding\BedWars\Main;
use Mastercoding\BedWars\Utils\BColor;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\lang\TextContainer;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;

class SpectateEntity extends Command implements Listener {

    public function __construct(Main $pl, string $name, string $description = "", string $usageMessage = null, $aliases = [], array $overloads = null)
    {
        parent::__construct($name, $description, $usageMessage, $aliases, $overloads);
        $this->setPermission("spectate.perm");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            if ($this->testPermission($sender)) {
                $config = $this->onConfig();
                $config->set("Pitch", $sender->getPitch());
                $config->set("Yaw", $sender->getYaw());
                $config->set("Vector", serialize($sender->asVector3()));
                $config->set("Level", $sender->getLevel()->getFolderName());
                $config->save();
                $sender->sendMessage(Main::prefix . "§7Du hast den Spawn für den Spectator gesetzt");
                $this->spawnEntity();
            }
        }
    }

    public function onConfig() : Config {
        $config = new Config(Main::getInstance()->getDataFolder() . "spectate.json", Config::JSON);
        $config->reload();
        return $config;
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $this->spawnEntity();
    }

    public function spawnEntity()
    {
        $config = $this->onConfig();
        if ($config->exists("Vector")) {
            if ($config->get("Level") !== "~") {
                if (Server::getInstance()->isLevelLoaded($config->get("Level"))) {
                    $level = Server::getInstance()->getLevelByName($config->get("Level"));
                    foreach ($level->getEntities() as $entity) {
                        if ($entity instanceof Villager) {
                            if ($entity->getNameTag() == "§7Spectate") {
                                $entity->despawnFromAll();
                                $entity->kill();
                            }
                        }
                    }
                }
            }

            if ($config->get("Vector") !== "~") {
                $vector = unserialize($config->get("Vector"));
                $nbt = Entity::createBaseNBT($vector, new Vector3(0, 0, 0), $config->get("Yaw"), $config->get("Pitch"));
                if (Server::getInstance()->isLevelLoaded($config->get("Level"))) {
                    $level = Server::getInstance()->getLevelByName($config->get("Level"));
                    $villager = new Villager($level, $nbt);
                    $villager->setNameTag("§7Spectate");
                    $villager->setNameTagAlwaysVisible(true);
                    $villager->spawnToAll();

                }
            }
        }
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event){
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if ($entity instanceof Villager){
            if ($entity->getNameTag() == "§7Spectate"){
                if ($damager instanceof Player) {
                    #$this->selectGameLevel($damager);
                }
            }
        }
    }

    /*public function selectGameLevel(Player $player){
        $name = $player->getName();

        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName("§7Spectate");
        $menu->readOnly();

        $inv = $menu->getInventory();

        $inv = $menu->getInventory();

        $config = Main::getInstance()->getLevelsFolder();

        $count[$name] = 0;
        foreach ($config as $info){
            if ($info["Status"] == "Ingame"){
                $configs = Main::getInstance()->getArenalistener()->onConfig($info["Level"]);
                foreach ($configs->getAll() as $teams => $infos){
                    if (Server::getInstance()->isLevelLoaded($info["Level"])){
                        var_dump($teams);
                        $array = [];
                        if ($teams !== "Settings"){
                            $level = Server::getInstance()->getLevelByName($info["Level"]);

                            foreach ($level->getPlayers() as $player) {
                                if ($player->getGamemode() == 0) {
                                    $team = Main::getInstance()->getArenalistener()->getPlayerTeam($player);
                                    $teamcolor = BColor::teamToColorString($team);
                                    $array[] = "{$teamcolor}{$player->getDisplayName()}";
                                }
                            }

                            $inv->setItem($count[$name], Item::get(Item::PAPER, 0, 1)->setCustomName("{$info["Name"]}")->setLore($array));
                        }
                    }else{
                        var_dump("Level ist nicht geladen");

                        #Main::getInstance()->getLogger()->debug("Level {$info["Level"]} ist nicht Geladen SpectateEntity 146");
                    }

                }
                $count[$name]++;
            }else{
                var_dump("Nicht in game");
                #Main::getInstance()->getLogger()->debug("Nicht Ingame");
            }
        }

        $menu->send($player);

        $menu->setListener(function(Player $p, Item $ito, Item $ipi, SlotChangeAction $e) : bool {

            if (Main::getInstance()->getSignInfofromArray("Name", $ito->getCustomName())) {
                $info = Main::getInstance()->getSignInfofromArray("Name", $ito->getCustomName());
                var_dump("f");
                #$e->getInventory()->clearAll();

                $e->getInventory()->clearAll();
                $p->getInventory()->close($p);

                $levelname = $info["Level"];
                $level = Server::getInstance()->getLevelByName($levelname);

                $pk = new ContainerClosePacket();
                $pk->windowId = 0;
                $p->sendDataPacket($pk);

                Main::getInstance()->getScheduler()->scheduleDelayedTask(new Waitt($this, $p, $level), 10);
                Main::getInstance()->player[$p->getName()]["Level"] = $levelname;
                Main::getInstance()->player[$p->getName()]["Team"] = "Spectator";
            }

            $inv = $p->getInventory();

            $inv->clearAll();
            $inv->setItem(0, Item::get(Item::COMPASS)->setCustomName("§7Teleporter"));
            $inv->setItem(8, Item::get(Item::MAGMA_CREAM)->setCustomName("§6Lobby"));
            #$p->getInventory()->sendContents($p);
            $p->setGamemode(2);
            return false;
        });
    }*/
}

class Waitt extends Task {

    protected $pl;
    protected $player;
    protected $level;

    public function __construct(SpectateEntity $pl, Player $player, Level $level)
    {
        $this->pl = $pl;
        $this->player = $player;
        $this->level = $level;
    }

    public function onRun(int $currentTick)
    {
        Main::getInstance()->getSaveTp()->saveTeleport($this->player, $this->level);
    }
}