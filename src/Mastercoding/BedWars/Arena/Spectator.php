<?php
/**
 * Created by PhpStorm.
 * User: chr1s
 * Date: 24.01.2019
 * Time: 14:56
 */
namespace Mastercoding\BedWars\Arena;

use Mastercoding\BedWars\Main;
use Mastercoding\BedWars\Utils\BColor;
use Mastercoding\BedWars\Utils\Scoreboard;
use muqsit\invmenu\InvMenu;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Skull;

class Spectator implements Listener {
    public function __construct(Main $pl)
    {
    }

    /*public function onItemUse(PlayerItemUseEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $event->getItem();
        if ($item->getCustomName() == "§7Teleporter"){
            $this->specTateTeam($player);
        }elseif ($item->getCustomName() == "§6Lobby"){

            Scoreboard::rmScoreboard($player , $player->getLevel()->getFolderName());

            Main::getInstance()->getSaveTp()->saveTeleport($player, Server::getInstance()->getDefaultLevel());
            $player->setGamemode(0);
            $player->spawnToAll();
            $player->getInventory()->clearAll();
            $player->getCraftingGrid()->clearAll();

        }
    }*/

    /*public function onDamageEvent(EntityDamageEvent $event){

    }*/

    public function onDamage(EntityDamageByEntityEvent $event){
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if ($damager instanceof Player and $entity instanceof Player){
            if ($damager->getGamemode() !== 0 and $damager->getGamemode() !== 1) {
                $event->setCancelled();
                var_dump("Cancell");

        }

        }elseif (!$entity instanceof Player){
            $event->setCancelled();
            var_dump("Cancell");

        }
    }

    public function onPickUp(InventoryPickupItemEvent $event){
        $player = $event->getInventory()->getHolder();
        if ($player instanceof Player){
            if ($player->getGamemode() !== 0 and  $player->getGamemode() !== 1){
                $event->setCancelled();
            }
        }
    }

    public function onDrop(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        if ($player->getGamemode() !== 0){
            $event->setCancelled();
        }elseif ($player->getLevel()->getFolderName() === Server::getInstance()->getDefaultLevel()->getFolderName()){
            $event->setCancelled();
            var_dump("Cancell");
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($block instanceof Block){
            if ($player->getGamemode() !== 0 and $player->getGamemode() !== 1) {
                $event->setCancelled();
                var_dump("Cancell");
            }
        }
    }


    public function specTateTeam(Player $player){
        $name = $player->getName();
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);

        $menu->readOnly();
        $menu->setName("§7Teleporter");

        $inv = $menu->getInventory();
        
        if ($player->getLevel()->getFolderName() === Main::getInstance()->player[$player->getName()]["Level"]) {

            $levelname = Main::getInstance()->player[$name]["Level"];

            $signkey = Main::getInstance()->getSignInfoFromLevel(Main::getInstance()->player[$player->getName()]["Level"]);

            if ($signkey["Level"] == $player->getLevel()->getFolderName()) {
                $art = $signkey["Art"];
                $zahl = explode("x", $art);
                $zahl = $zahl[0] * $zahl[1];

                $config = Main::getInstance()->getArenalistener()->onConfig($levelname);

                $slot = 0;
                foreach ($config->getAll() as $teams => $info){
                    if ($teams !== "Settings"){
                        if (Main::getInstance()->getArenalistener()->isLeben($levelname, $teams)) {
                            if (Main::getInstance()->getArenalistener()->countTeamPlayers($levelname, $teams) > 0) {
                                $teamarray = Main::getInstance()->getArenalistener()->getTeamArray($levelname, $teams);

                                $color = BColor::teamToColorString($teams);

                                foreach ($teamarray as $teamnames){
                                    $inv->setItem($slot, Item::get(Item::PAPER, 0, 1)->setCustomName("{$color}{$teamnames}"));
                                    $slot++;
                                }
                            }else{
                                Main::getInstance()->getLogger()->debug("Team ist Tot Spectator 134");
                            }
                        }
                    }
                }
            }
        }

        $menu->send($player);

        $menu->setListener(function(Player $p, Item $ito, Item $ipi, SlotChangeAction $e) : bool {

            $playername = substr($ito->getCustomName(), 3);

            foreach ($e->getInventory()->getContents() as $slot => $item){
                if ($ito->getCustomName() == $item->getCustomName()){
                    $playertp = Server::getInstance()->getPlayerExact($playername);
                    if ($playertp !== NULL){
                        $p->teleport($playertp);
                        $color = BColor::teamToColorString(Main::getInstance()->getArenalistener()->getPlayerTeam($playertp));
                        $p->sendMessage(Main::prefix . "§7Du wurdest zu {$color}{$playertp->getName()}§7 Teleportiert");
                    }
                }
            }

            return true;
        });
    }
}