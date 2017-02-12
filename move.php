<?php
// The Kabal Invasion - A web-based 4X space game
// Copyright © 2014 The Kabal Invasion development team, Ron Harwood, and the BNT development team
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU Affero General Public License as
//  published by the Free Software Foundation, either version 3 of the
//  License, or (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU Affero General Public License for more details.
//
//  You should have received a copy of the GNU Affero General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// File: move.php

require_once './common.php';

Tki\Login::checkLogin($pdo_db, $lang, $tkireg, $template);

$title = $langvars['l_move_title'];
Tki\Header::display($pdo_db, $lang, $template, $title);

$sector = (int) filter_input(INPUT_GET, 'sector', FILTER_SANITIZE_NUMBER_INT);

// Database driven language entries
$langvars = Tki\Translate::load($pdo_db, $lang, array('move', 'common', 'global_includes', 'global_funcs', 'combat', 'footer', 'news'));

// Get playerinfo from database
$sql = "SELECT * FROM ::prefix::ships WHERE email=:email LIMIT 1";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':email', $_SESSION['username']);
$stmt->execute();
$playerinfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Check to see if the player has less than one turn available
// and if so return to the main menu
if ($playerinfo['turns'] < 1)
{
    echo $langvars['l_move_turn'] . '<br><br>';
    Tki\Text::gotoMain($pdo_db, $lang);
    Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
    die();
}

$sql = "SELECT * FROM ::prefix::universe WHERE sector_id=:sector_id LIMIT 1";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':sector_id', $playerinfo['sector']);
$stmt->execute();
$sectorinfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrive all the warp links out of the current sector
$result3 = $db->Execute("SELECT * FROM {$db->prefix}links WHERE link_start = ?;", array($playerinfo['sector']));
Tki\Db::LogDbErrors($pdo_db, $result3, __LINE__, __FILE__);
$i = 0;
$flag = 0;

// Loop through the available warp links to make sure it's a valid move
while (!$result3->EOF)
{
    $row = $result3->fields;
    if ($row['link_dest'] == $sector && $row['link_start'] == $playerinfo['sector'])
    {
        $flag = 1;
    }

    $i++;
    $result3->MoveNext();
}

// Check if there was a valid warp link to move to
if ($flag == 1)
{
    $ok = 1;
    $calledfrom = "move.php";
    include_once './check_fighters.php';
    if ($ok > 0)
    {
        $stamp = date("Y-m-d H:i:s");
        Tki\LogMove::writeLog($pdo_db, $playerinfo['ship_id'], $sector);
        $move_result = $db->Execute("UPDATE {$db->prefix}ships SET last_login = ?,turns = turns - 1, turns_used = turns_used + 1, sector = ? WHERE ship_id = ?;", array($stamp, $sector, $playerinfo['ship_id']));
        Tki\Db::LogDbErrors($pdo_db, $move_result, __LINE__, __FILE__);
        if (!$move_result)
        {
            // is this really STILL needed?
            $error = $db->ErrorMsg();
            mail($tkireg->admin_mail, "Move Error", "Start Sector: $sectorinfo[sector_id]\nEnd Sector: $sector\nPlayer: $playerinfo[character_name] - $playerinfo[ship_id]\n\nQuery:  $query\n\nSQL error: $error");
        }
    }

    // Enter code for checking dangers in new sector
    include_once './check_mines.php';
    if ($ok == 1)
    {
        header("Location: main.php");
    }
    else
    {
        Tki\Text::gotoMain($pdo_db, $lang);
    }
}
else
{
    echo $langvars['l_move_failed'] . '<br><br>';
    $resx = $db->Execute("UPDATE {$db->prefix}ships SET cleared_defenses=' ' WHERE ship_id = ?;", array($playerinfo['ship_id']));
    Tki\Db::LogDbErrors($pdo_db, $resx, __LINE__, __FILE__);
    Tki\Text::gotoMain($pdo_db, $lang);
}

echo "</body></html>";
