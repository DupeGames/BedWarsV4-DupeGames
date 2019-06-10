<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11.12.2018
 * Time: 12:55
 */

namespace Mastercoding\BedWars\Arena;
use Mastercoding\BedWars\Main;
use Mastercoding\BedWars\Task\Cooldown;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Server;
use pocketmine\tile\Sign;

class InteractSign implements Listener {
    protected $pl;


    public function __construct(Main $pl)
    {
        $this->pl = $pl;
    }

    public function onInteract(PlayerInteractEvent $ev)
    {
        $p = $ev->getPlayer();
        $n = $p->getName();
        $i = $ev->getItem();
        $b = $ev->getBlock();

        if (!in_array($n, $this->pl->incooldown)) {
          if (!$ev->isCancelled()) {
              if ($p->getLevel()->getTile($b->asVector3()) instanceof Sign) {
                  $tile = $p->getLevel()->getTile($b->asVector3());
                  if ($tile instanceof Sign) {
                      $this->pl->getScheduler()->scheduleDelayedTask(new Cooldown($this->pl, $p), 80);
                      Main::getInstance()->incooldown[$n] = $n;
                      $text = $tile->getText();
                      if ($text[0] == "§cBedWars") {
                          $max = explode("/", $text[3]);
                          $name = substr($text[1], 3);

                          if (isset(Main::getInstance()->signs[$name])) {
                              $info = Main::getInstance()->signs[$name];
                              $lobbyname = $info["Lobby"];
                              $levelname = $info["Level"];

                              if (!Server::getInstance()->isLevelLoaded($lobbyname)) {
                                  Server::getInstance()->loadLevel($lobbyname);
                                  $lobby = Server::getInstance()->getLevelByName($lobbyname);
                                  if (Server::getInstance()->isLevelLoaded($lobbyname)){
                                      $lobby->setTime(1200);
                                      $lobby->stopTime();
                                      $lobby->setAutoSave(false);
                                  }
                              }

                              $lobby = Server::getInstance()->getLevelByName($lobbyname);

                              $counter = count($lobby->getPlayers());

                              if ($text[2] !== "§0[§6Lobby§0]" and $text[2] !== "§0[§4Ingame§0]" and $counter < $max[1]) {
                                  Main::getInstance()->getLobby()->teleportLobby($p, $lobby, $levelname, $info["Name"], $info["Art"]);
                                  if ($counter == 0) {
                                      Main::getInstance()->getArenalistener()->resetArena($levelname, $lobbyname);
                                      var_dump("ResetArenaTime");
                                  }

                              } elseif ($text[2] == "§0[§6Lobby§0]" and $p->hasPermission("joinall.server.player")) {
                                  #Main::getInstance()->getLobby()->teleportLobby($p, $lobby, $levelname);

                              } else if ($text[2] == "§0[§6Lobby§0]" and !$p->hasPermission("joinall.server.player")) {

                                  $p->sendMessage(Main::prefix . "§7Du brauchst den §bM-Duper§7-Rang um volle Spiele beitreten zu können");

                              } else if ($text[2] == "§0[§4Ingame§0]") {

                                  $p->sendMessage(Main::prefix . "§cDiese Runde hat schon begonnen!");

                              }
                          } else {
                              $p->sendMessage(Main::prefix . "§4Fehler: §c>>§4getSignInfofromArray§c<< §4bitte in bugreports melden");
                          }
                      }
                  }
              }
          }
        }else{
            $ev->setCancelled();
        }
    }
}