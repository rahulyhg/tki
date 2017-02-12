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
// File: warpedit2.php

require_once './common.php';

Tki\Login::checkLogin($pdo_db, $lang, $tkireg, $template);

$title = $langvars['l_warp_title'];
Tki\Header::display($pdo_db, $lang, $template, $title);

// Database driven language entries
$langvars = Tki\Translate::load($pdo_db, $lang, array('warpedit', 'common', 'global_includes', 'global_funcs', 'footer', 'news'));
echo "<h1>" . $title . "</h1>\n";

// Detect if this variable exists, and filter it. Returns false if anything wasn't right.
$oneway = null;
$oneway = filter_input(INPUT_POST, 'oneway', FILTER_SANITIZE_STRING);
if (mb_strlen(trim($oneway)) === 0)
{
    $oneway = false;
}

// Detect if this variable exists, and filter it. Returns false if anything wasn't right.
$target_sector = null;
$target_sector = (int) filter_input(INPUT_POST, 'target_sector', FILTER_SANITIZE_NUMBER_INT);
if (mb_strlen(trim($target_sector)) === 0)
{
    $target_sector = false;
}

// Get playerinfo from database
$sql = "SELECT * FROM ::prefix::ships WHERE email=:email LIMIT 1";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':email', $_SESSION['username']);
$stmt->execute();
$playerinfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($playerinfo['turns'] < 1)
{
    echo $langvars['l_warp_turn'] . "<br><br>";
    Tki\Text::gotoMain($pdo_db, $lang);
    Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
    die();
}

if ($playerinfo['dev_warpedit'] < 1)
{
    echo $langvars['l_warp_none'] . "<br><br>";
    Tki\Text::gotoMain($pdo_db, $lang);
    Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
    die();
}

if ($target_sector === null)
{
    // This is the best that I can do without adding a new language variable.
    $langvars['l_warp_twoerror'] = str_replace('[target_sector]', $langvars['l_unknown'], $langvars['l_warp_twoerror']);
    echo $langvars['l_warp_twoerror'] . "<br><br>";
    Tki\Text::gotoMain($pdo_db, $lang);
    die();
}

$sql = "SELECT allow_warpedit,::prefix::universe.zone_id FROM  FROM ::prefix::zones, ::prefix::universe WHERE sector_id=:sector_id AND ::prefix::universe.zone_id = ::prefix::zones.zone_id ";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':email', $playerinfo['sector']);
$stmt->execute();
$zoneinfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($zoneinfo['allow_warpedit'] == 'N')
{
    echo $langvars['l_warp_forbid'] . "<br><br>";
    Tki\Text::gotoMain($pdo_db, $lang);
    Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
    die();
}

$target_sector = round($target_sector);

$sql = "SELECT * FROM ::prefix::ships WHERE email=:email LIMIT 1";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':email', $_SESSION['username']);
$stmt->execute();
$playerinfo = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM ::prefix::universe WHERE sector_id=:sector_id LIMIT 1";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':sector_id', $target_sector);
$stmt->execute();
$sectorinfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sectorinfo)
{
    echo $langvars['l_warp_nosector'] . "<br><br>";
    Tki\Text::gotoMain($pdo_db, $lang);
    die();
}

$sql = "SELECT allow_warpedit,::prefix::universe.zone_id FROM ::prefix::zones, ::prefix::universe WHERE sector_id=:sector_id AND ::prefix::universe.zone_id = ::prefix::zones.zone_id";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':sector_id', $target_sector);
$stmt->execute();
$zoneinfo = $stmt->fetch(PDO::FETCH_ASSOC);
if ($zoneinfo['allow_warpedit'] == 'N' && !$oneway)
{
    $langvars['l_warp_twoerror'] = str_replace("[target_sector]", $target_sector, $langvars['l_warp_twoerror']);
    echo $langvars['l_warp_twoerror'] . "<br><br>";
    Tki\Text::gotoMain($pdo_db, $lang);
    Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
    die();
}

$sql = "SELECT COUNT(*) as count FROM ::prefix::links WHERE link_start=:link_start";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':link_start', $playerinfo['sector']);
$stmt->execute();
$tmp_link_info = $stmt->fetch(PDO::FETCH_ASSOC);
$numlink_start = $tmp_link_info['count'];

if ($numlink_start >= $max_links)
{
    $langvars['l_warp_sectex'] = str_replace("[link_max]", $max_links, $langvars['l_warp_sectex']);
    echo $langvars['l_warp_sectex'] . "<br><br>";
    Tki\Text::gotoMain($pdo_db, $lang);
    Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
    die();
}

$sql = "SELECT * FROM ::prefix::links WHERE link_start=:sector LIMIT 1";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':email', $playerinfo['sector']);
$stmt->execute();
$linkinfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($linkinfo)
{
    $flag = 0;
    foreach ($linkinfo as $tmp_linkinfo)
    {
        if ($target_sector == $tmp_linkinfo['link_dest'])
        {
            $flag = 1;
        }
    }

    if ($flag == 1)
    {
        $langvars['l_warp_linked'] = str_replace("[target_sector]", $target_sector, $langvars['l_warp_linked']);
        echo $langvars['l_warp_linked'] . "<br><br>";
    }
    elseif ($playerinfo['sector'] == $target_sector)
    {
        echo $langvars['l_warp_cantsame'];
    }
    else
    {
        $sql = "INSERT INTO ::prefix::links SET link_start=:link_start, link_dest=:link_dest";
        $stmt = $pdo_db->prepare($sql);
        $stmt->bindParam(':link_start', $playerinfo['sector']);
        $stmt->bindParam(':link_dest', $target_sector);
        $stmt->execute();

        $sql = "UPDATE ::prefix::ships SET dev_warpedit = dev_warpedit - 1, turns = turns - 1, turns_used = turns_used + 1 WHERE ship_id = :ship_id";
        $stmt = $pdo_db->prepare($sql);
        $stmt->bindParam(':ship_id', $playerinfo['sector']);
        $stmt->execute();

        if ($oneway !== null)
        {
            echo $langvars['l_warp_coneway'] . " " . $target_sector . " " . "<br><br>";
        }
        else
        {
            $sql = "SELECT * FROM ::prefix::links WHERE link_start=:link_start";
            $stmt = $pdo_db->prepare($sql);
            $stmt->bindParam(':link_start', $target_sector);
            $stmt->execute();
            $linkinfo2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($linkinfo2 !== false)
            {
                $flag2 = 0;
                foreach ($linkinfo2 as $tmp_link)
                {
                    if ($playerinfo['sector'] == $tmp_link['link_dest'])
                    {
                        $flag2 = 1;
                    }
                }
            }

            if ($flag2 != 1)
            {
                $sql = "INSERT INTO ::prefix::links SET link_start=:link_start, link_dest=:link_dest";
                $stmt = $pdo_db->prepare($sql);
                $stmt->bindParam(':link_start', $target_sector);
                $stmt->bindParam(':link_dest', $playerinfo['sector']);
                $stmt->execute();
            }

            echo $langvars['l_warp_ctwoway'] . " " . $target_sector . ".<br><br>";
        }
    }
}

Tki\Text::gotoMain($pdo_db, $lang);
Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
