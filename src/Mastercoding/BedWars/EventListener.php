<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 10.12.2018
 * Time: 20:26
 */

namespace Mastercoding\BedWars;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\Server;

class EventListener implements Listener {
    public $pl;

    public function __construct(Main $pl)
    {
        $this->pl = $pl;
    }

    public function onBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        $block = $event->getBlock();
        $level = $player->getLevel()->getFolderName();

        if ($level === Server::getInstance()->getDefaultLevel()->getFolderName()){
            if ($player->getGamemode() !== 1){
                $event->setCancelled();
            }
        }
    }

    public function onCraft(CraftItemEvent $event){
        $player = $event->getPlayer();
        $event->setCancelled();
    }


}