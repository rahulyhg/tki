<?php declare(strict_types = 1);
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
// File: classes/PlanetProduction.php

namespace Tki;

class PlanetProduction
{
    public static function productionChange(\PDO $pdo_db, $db, array $langvars, array $prodpercentarray, Reg $tkireg): void
    {
        //  Declare default production values from the config.php file
        //
        //  We need to track what the player_id is and what team they belong to if they belong to a team,
        //    these two values are not passed in as arrays
        //    ship_id = the owner of the planet          ($ship_id = $prodpercentarray['ship_id'])
        //    team_id = the team creators ship_id ($team_id = $prodpercentarray['team_id'])
        //
        //  First we generate a list of values based on the commodity
        //    (ore, organics, goods, energy, fighters, torps, team, sells)
        //
        //  Second we generate a second list of values based on the planet_id
        //  Because team and ship_id are not arrays we do not pass them through the second list command.
        //  When we write the ore production percent we also clear the selling and team values out of the db
        //  When we pass through the team array we set the value to $team we grabbed out of the array.
        //  in the sells and team the prodpercent = the planet_id.
        //
        //  We run through the database checking to see if any planet production is greater than 100, or possibly negative
        //    if so we set the planet to the default values and report it to the player.
        //
        //  There has got to be a better way, but at this time I am not sure how to do it.
        //  Off the top of my head if we could sort the data passed in, in order of planets we could check before we do the writes
        //  This would save us from having to run through the database a second time checking our work.

        $result = $db->Execute("SELECT ship_id, team FROM {$db->prefix}ships WHERE email = ?;", array($_SESSION['username']));
        \Tki\Db::logDbErrors($pdo_db, $result, __LINE__, __FILE__);
        $ship_id = $result->fields['ship_id'];

        echo str_replace("[here]", "<a href='planet_report.php?preptype=2'>" . $langvars['l_here'] . "</a>", $langvars['l_pr_click_return_prod']);
        echo "<br><br>";

        foreach($prodpercentarray as $commod_type => $valarray)
        {
            if ($commod_type != "team_id" && $commod_type != "ship_id")
            {
                foreach($valarray as $planet_id => $prodpercent)
                {
                    if ($commod_type == "prod_ore" || $commod_type == "prod_organics" || $commod_type == "prod_goods" || $commod_type == "prod_energy" || $commod_type == "prod_fighters" || $commod_type == "prod_torp")
                    {
                        $res = $db->Execute("SELECT COUNT(*) AS owned_planet FROM {$db->prefix}planets WHERE planet_id = ? AND owner = ?;", array($planet_id, $ship_id));
                        \Tki\Db::logDbErrors($pdo_db, $res, __LINE__, __FILE__);

                        $resx = $db->Execute("UPDATE {$db->prefix}planets SET {$commod_type} = ? WHERE planet_id = ? AND owner = ?;", array($prodpercent, $planet_id, $ship_id));
                        \Tki\Db::logDbErrors($pdo_db, $resx, __LINE__, __FILE__);

                        $resy = $db->Execute("UPDATE {$db->prefix}planets SET sells='N' WHERE planet_id = ? AND owner = ?;", array($planet_id, $ship_id));
                        \Tki\Db::logDbErrors($pdo_db, $resy, __LINE__, __FILE__);

                        $resz = $db->Execute("UPDATE {$db->prefix}planets SET team=0 WHERE planet_id = ? AND owner = ?;", array($planet_id, $ship_id));
                        \Tki\Db::logDbErrors($pdo_db, $resz, __LINE__, __FILE__);
                    }
                    elseif ($commod_type == "sells")
                    {
                        $resx = $db->Execute("UPDATE {$db->prefix}planets SET sells='Y' WHERE planet_id = ? AND owner = ?;", array($prodpercent, $ship_id));
                        \Tki\Db::logDbErrors($pdo_db, $resx, __LINE__, __FILE__);
                    }
                    elseif ($commod_type == "team")
                    {
                        // Compare entered team_id and one in the db, if different then use one from db
                        $res = $db->Execute("SELECT {$db->prefix}ships.team as owner FROM {$db->prefix}ships, {$db->prefix}planets WHERE ( {$db->prefix}ships.ship_id = {$db->prefix}planets.owner ) AND ( {$db->prefix}planets.planet_id = ?);", array($prodpercent));
                        \Tki\Db::logDbErrors($pdo_db, $res, __LINE__, __FILE__);
                        if ($res)
                        {
                            $team_id = $res->fields['owner'];
                        }
                        else
                        {
                            $team_id = 0;
                        }

                        $resx = $db->Execute("UPDATE {$db->prefix}planets SET team = ? WHERE planet_id = ? AND owner = ?;", array($team_id, $prodpercent, $ship_id));
                        \Tki\Db::logDbErrors($pdo_db, $resx, __LINE__, __FILE__);
                    }
                }
            }
        }

        echo "<br>";
        echo $langvars['l_pr_prod_updated'] . "<br><br>";
        echo $langvars['l_pr_checking_values'] . "<br><br>";

        $res = $db->Execute("SELECT * FROM {$db->prefix}planets WHERE owner = ? ORDER BY sector_id;", array($ship_id));
        \Tki\Db::logDbErrors($pdo_db, $res, __LINE__, __FILE__);
        $i = 0;
        $planet = array();
        $planets = array();
        if ($res)
        {
            while (!$res->EOF)
            {
                $planets[$i] = $res->fields;
                $i++;
                $res->MoveNext();
            }

            foreach ($planets as $planet)
            {
                if (empty($planet['name']))
                {
                    $planet['name'] = $langvars['l_unnamed'];
                }

                if ($planet['prod_ore'] < 0)
                {
                    $planet['prod_ore'] = 110;
                }

                if ($planet['prod_organics'] < 0)
                {
                    $planet['prod_organics'] = 110;
                }

                if ($planet['prod_goods'] < 0)
                {
                    $planet['prod_goods'] = 110;
                }

                if ($planet['prod_energy'] < 0)
                {
                    $planet['prod_energy'] = 110;
                }

                if ($planet['prod_fighters'] < 0)
                {
                    $planet['prod_fighters'] = 110;
                }

                if ($planet['prod_torp'] < 0)
                {
                    $planet['prod_torp'] = 110;
                }

                if ($planet['prod_ore'] + $planet['prod_organics'] + $planet['prod_goods'] + $planet['prod_energy'] + $planet['prod_fighters'] + $planet['prod_torp'] > 100)
                {
                    $temp1 = str_replace("[planet_name]", $planet['name'], $langvars['l_pr_value_reset']);
                    $temp2 = str_replace("[sector_id]", $planet['sector_id'], $temp1);
                    echo $temp2 . "<br>";

                    $resa = $db->Execute("UPDATE {$db->prefix}planets SET prod_ore = ? WHERE planet_id = ?;", array($tkireg->default_prod_ore, $planet['planet_id']));
                    \Tki\Db::logDbErrors($pdo_db, $resa, __LINE__, __FILE__);

                    $resb = $db->Execute("UPDATE {$db->prefix}planets SET prod_organics = ? WHERE planet_id = ?;", array($tkireg->default_prod_organics, $planet['planet_id']));
                    \Tki\Db::logDbErrors($pdo_db, $resb, __LINE__, __FILE__);

                    $resc = $db->Execute("UPDATE {$db->prefix}planets SET prod_goods = ? WHERE planet_id = ?;", array($tkireg->default_prod_goods, $planet['planet_id']));
                    \Tki\Db::logDbErrors($pdo_db, $resc, __LINE__, __FILE__);

                    $resd = $db->Execute("UPDATE {$db->prefix}planets SET prod_energy = ? WHERE planet_id = ?;", array($tkireg->default_prod_energy, $planet['planet_id']));
                    \Tki\Db::logDbErrors($pdo_db, $resd, __LINE__, __FILE__);

                    $rese = $db->Execute("UPDATE {$db->prefix}planets SET prod_fighters = ? WHERE planet_id = ?;", array($tkireg->default_prod_fighters, $planet['planet_id']));
                    \Tki\Db::logDbErrors($pdo_db, $rese, __LINE__, __FILE__);

                    $resf = $db->Execute("UPDATE {$db->prefix}planets SET prod_torp = ? WHERE planet_id = ?;", array($tkireg->default_prod_torp, $planet['planet_id']));
                    \Tki\Db::logDbErrors($pdo_db, $resf, __LINE__, __FILE__);
                }
            }
        }
    }
}
