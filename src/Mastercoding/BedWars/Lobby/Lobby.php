<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11.12.2018
 * Time: 13:52
 */
namespace Mastercoding\BedWars\Lobby;

use Mastercoding\BedWars\Main;
use Mastercoding\BedWars\Task\Cooldown;
use Mastercoding\BedWars\Utils\BColor;
use Mastercoding\BedWars\Utils\Scoreboard;
use muqsit\invmenu\InvMenu;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Bed;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\Player;
use pocketmine\Server;

class Lobby implements Listener {

    public $pl;

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
    }

    public function giveLobbyItems(Player $player){
        $n = $player->getName();
        $inv = $player->getInventory();
        $inv->clearAll();
        $inv->setItem(0, Item::get(Item::BED, 0, 1)->setCustomName("§fWähle ein Team"));
        $inv->setItem(8, Item::get(Item::MAGMA_CREAM, 0, 1)->setCustomName("§eLobby"));
    }

    /*public function onUseItem(PlayerItemUseEvent $ev){
        $p = $ev->getPlayer();
        $n = $p->getName();
        $i = $ev->getItem();

        if (in_array($p->getLevel()->getFolderName(), Main::getInstance()->getLobbys())) {

            if (!$ev->isCancelled()) {
                if ($i->getCustomName() == "§fWähle ein Team") {
                    $this->selectTeam($p);
                }elseif ($i->getCustomName() == "§eLobby"){
                    $p->getInventory()->clearAll();
                    Main::getInstance()->getSaveTp()->saveTeleport($p, Server::getInstance()->getDefaultLevel());
                    Main::getInstance()->getArenalistener()->resetPlayer($p, Main::getInstance()->getArenalistener()->getPlayerTeam($p));
                }
            }
        }
    }*/

    public function onDrop(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
        if (in_array($player->getLevel()->getFolderName(), Main::getInstance()->getLobbys())){
            $event->setCancelled();
        }
    }

    public function onInteractMobile(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $event->getItem();

        #if ($player->getDeviceOS() !== Player::OS_WINDOWS) {
            if (!$event->isCancelled()) {
                if (in_array($player->getLevel()->getFolderName(), Main::getInstance()->getLobbys())) {
                    if ($item->getCustomName() == "§fWähle ein Team") {
                        $this->selectTeam($player);
                    } elseif ($item->getCustomName() == "§eLobby") {
                        $player->getInventory()->clearAll();
                        Main::getInstance()->getSaveTp()->saveTeleport($player, Server::getInstance()->getDefaultLevel());
                        Main::getInstance()->getArenalistener()->resetPlayer($player, Main::getInstance()->getArenalistener()->getPlayerTeam($player));
                    }
                }
            }
        #}
    }



    public function teleportLobby(Player $player, Level $lobby, string $level, string $customname = "~", string $sorte = "~"){
        $name = $player->getName();
        $player->doCloseInventory();
        $player->getCraftingGrid()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();
        $this->giveLobbyItems($player);
        Main::getInstance()->getSaveTp()->saveTeleport($player, $lobby);
        Main::getInstance()->player[$name]["Level"] = $level;


        Scoreboard::createScoreboard($player, "§4Bed§fWars §7DG", $level);
        Scoreboard::setScoreboardEntry($player, 0, "§b{$customname} §7{$sorte}", $level);
        Scoreboard::setScoreboardEntry($player, 1, "§7Gold: §2Ja", $level);
    }



    public function onLobbyJoinLeave(EntityLevelChangeEvent $event){
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $name = $entity->getName();
            if (isset(Main::getInstance()->arena[$event->getTarget()->getFolderName()])) {
                if (Main::getInstance()->arena[$event->getTarget()->getFolderName()]["Status"] == "Lobby") {
                    if (in_array($event->getTarget()->getFolderName(), $this->pl->getLobbys())) {
                        $signkey = Main::getInstance()->getSignInfo($event->getTarget()->getFolderName());
                        if ($signkey["Lobby"] == $event->getTarget()->getFolderName()) {

                            foreach ($event->getTarget()->getPlayers() as $levelplayers) {
                                $levelplayers->sendMessage(Main::prefix . "§2{$name} §7hat das Spiel betreten");
                                $entity->showPlayer($levelplayers);
                                $levelplayers->showPlayer($entity);
                            }



                            $entity->sendMessage(Main::prefix . "§2{$name} §7hat das Spiel betreten");


                            #mp("ONJOIN");

                            $art = $signkey["Art"];
                            Main::getInstance()->player[$entity->getName()]["SignKey"] = $signkey;
                            Main::getInstance()->player[$entity->getName()]["Art"] = $art;
                            Main::getInstance()->player[$entity->getName()]["State"] = true;
                            Main::getInstance()->player[$entity->getName()]["Team"] = Main::getInstance()->getArenalistener()->getPlayerTeam($entity);
                            Main::getInstance()->player[$entity->getName()]["Leben"] = true;
                        }

                    } elseif ($event->getTarget()->getFolderName() === Server::getInstance()->getDefaultLevel()->getFolderName()) {
                        Main::getInstance()->getArenalistener()->resetPlayer($entity, Main::getInstance()->getArenalistener()->getPlayerTeam($entity));
                        Main::getInstance()->getGroup()->getEventListener()->onLoadPlayer($entity);

                        foreach (Server::getInstance()->getDefaultLevel()->getPlayers() as $player){
                            $player->showPlayer($entity);
                            $entity->showPlayer($player);
                            $player->getInventory()->clearAll();
                            $player->getArmorInventory()->clearAll();
                        }

                        $entity->spawnToAll();
                        $entity->setGamemode(0);

                        foreach ($event->getOrigin()->getPlayers() as $levelplayers) {
                            $levelplayers->sendMessage(Main::prefix . "§2{$name} §7hat das Spiel verlassen");
                        }
                        $entity->sendMessage(Main::prefix . "§2{$name} §7hat das Spiel verlassen");
                    }
                }
            }
        }
    }

    public function kickRandomPlayer(Player $player, Level $lobby, string $level, string $customname = "~", string $sorte = "~"){
        $name = $player->getName();
        unset($array);
        $array = [];
        foreach ($lobby->getPlayers() as $lobbyplayers){
            if (!$lobbyplayers->hasPermission("joinall.server.player")){
                $array[] = $lobbyplayers;
            }
        }

        $all = count($array);
        $random = mt_rand(1, $array);
        $kickplayer = $random;
        var_dump($kickplayer);
        $kickplayer = Server::getInstance()->getPlayerExact($kickplayer);
        if ($kickplayer !== NULL){
            var_dump($kickplayer->getName());
            Main::getInstance()->getSaveTp()->saveTeleport($kickplayer, Server::getInstance()->getDefaultLevel());
            Main::getInstance()->getArenalistener()->resetPlayer($kickplayer, Main::getInstance()->getArenalistener()->getPlayerTeam($kickplayer));

            $this->teleportLobby($player, $lobby, $level, $customname , $sorte);
        }
        #mp($kickplayer);

    }


    public function selectTeam(Player $player){


        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->readOnly();
        $menu->setName("§eTeams");

        $inv = $menu->getInventory();

        if (in_array($player->getLevel()->getFolderName(), $this->pl->getLobbys())) {
            $signkey = Main::getInstance()->getSignInfo($player->getLevel()->getFolderName());
            if ($signkey["Lobby"] == $player->getLevel()->getFolderName()) {
                $art = $signkey["Art"];
                $zahl = explode("x", $art)[0];

                #mp("TRUE");

                Main::getInstance()->player[$player->getName()]["Menu"] = $menu;
                Main::getInstance()->player[$player->getName()]["SignKey"] = $signkey;
                Main::getInstance()->player[$player->getName()]["Art"] = $art;
                Main::getInstance()->player[$player->getName()]["State"] = true;
                Main::getInstance()->player[$player->getName()]["Team"] = Main::getInstance()->getArenalistener()->getPlayerTeam($player);

                for ($i = 0; $i < $zahl; $i++) {
                    $teamname = BColor::intToItemName($i);
                    $teams = Main::getInstance()->getArenalistener()->getTeamArray($signkey["Level"], $teamname);

                    $names = implode("§7\n§7", $teams);

                    $inv->setItem($i, BColor::teamToItem($teamname)->setCustomName(BColor::teamToColorString($teamname) . $teamname)->setLore(["§7" . $names]));
                }
            }

        }

        $menu->send($player);

        $menu->setListener(function(Player $p, Item $ito, Item $ipi, SlotChangeAction $e) : bool {

            $team = substr($ito->getCustomName(), 3);

            if (Main::getInstance()->getArenalistener()->countTeamPlayers(Main::getInstance()->player[$p->getName()]["Level"], $team) < Main::getInstance()->player[$p->getName()]["SignKey"]["PlayerPerTeam"]) {
                $n = $p->getName();
                ##mp(Main::getInstance()->player[$n]["Team"]);
                if (Main::getInstance()->player[$n]["State"] === true and Main::getInstance()->player[$n]["Team"] !== "~") {
                    #Main::getInstance()->getArenalistener()->getPlayerTeam($p);
                    Main::getInstance()->getArenalistener()->removePlayerFromTeamArray(Main::getInstance()->player[$n]["SignKey"]["Level"], $p);
                    #mp("Remove");
                }

                foreach ($e->getInventory()->getContents() as $slot => $item) {
                    if ($ito->getCustomName() == $item->getCustomName()) {
                        $n = $p->getName();
                        Main::getInstance()->getArenalistener()->setTeamArray(Main::getInstance()->player[$n]["SignKey"]["Level"], $p, $team, Main::getInstance()->player[$n]["Art"]);
                        $color = BColor::teamToColorString($team);
                        $p->sendMessage(Main::prefix . "§7Du bist dem Team {$color}{$team}§7 beigetreten");

                        $pk = new ContainerClosePacket();
                        $pk->windowId = 0;
                        $p->sendDataPacket($pk);
                    }
                }
            }else{
                $pk = new ContainerClosePacket();
                $pk->windowId = 0;
                $p->sendDataPacket($pk);

                $p->sendMessage(Main::prefix . "§7Du kannst diesem Team nicht mehr beitreten");
            }
            return true;
        });
    }


}