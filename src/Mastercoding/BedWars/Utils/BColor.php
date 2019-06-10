<?php
/**
 * Created by PhpStorm.
 * User: chr1s
 * Date: 24.01.2019
 * Time: 12:33
 */

namespace Mastercoding\BedWars\Utils;

use pocketmine\item\Item;

class BColor {
    
    public static function teamToItem(string $team){
        switch ($team){
            case "Rot":
                return Item::get(Item::BED, 14, 1);
                break;
            case "Blau":
                return Item::get(Item::BED, 11, 1);
                break;
            case "Grün":
                return Item::get(Item::BED, 13, 1);
                break;
            case "Gelb":
                return Item::get(Item::BED, 4, 1);
                break;
            case "Pink":
                return Item::get(Item::BED, 6, 1);
                break;
            case "Orange":
                return Item::get(Item::BED, 1, 1);
                break;
            case "Violett":
                return Item::get(Item::BED, 10, 1);
                break;
            case "Weiß":
                return Item::get(Item::BED, 0, 1);
                break;
            default:
                return Item::get(Item::BED, 14, 1);
                break;
        }
    }

    public static function intToItemName(string $team){
        switch ($team){
            case 0:
                return "Rot";
                break;
            case 1:
                return "Blau";
                break;
            case 2:
                return "Grün";
                break;
            case 3:
                return "Gelb";
                break;
            case 4:
                return "Pink";
                break;
            case 5:
                return "Orange";
                break;
            case 6:
                return "Violett";
                break;
            case 7:
                return "Weiß";
                break;
            default:
                return "???";
                break;
        }
    }

    public static function teamToColor(string $team){
        switch ($team){
            case "Rot":
                return "§4Rot";
                break;
            case "Blau":
                return "§9Blau";
                break;
            case "Grün":
                return "§2Grün";
                break;
            case "Gelb":
                return "§eGelb";
                break;
            case "Pink":
                return "§dPink";
                break;
            case "Orange":
                return "§6Orange";
                break;
            case "Violett":
                return "§5Violett";
                break;
            case "Weiß":
                return "§fWeiß";
                break;
            default:
                return "???";
                break;
        }
    }

    public static function teamToColorString(string $team){
        switch ($team){
            case "Rot":
                return "§4";
                break;
            case "Blau":
                return "§9";
                break;
            case "Grün":
                return "§2";
                break;
            case "Gelb":
                return "§e";
                break;
            case "Pink":
                return "§d";
                break;
            case "Orange":
                return "§6";
                break;
            case "Violett":
                return "§5";
                break;
            case "Weiß":
                return "§f";
                break;
            default:
                return "???";
                break;
        }
    }
}