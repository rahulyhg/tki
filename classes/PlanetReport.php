<?php
declare(strict_types = 1);
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
// File: classes/PlanetReport.php

namespace Tki;

class PlanetReport
{
    public static function baseBuildCheck($langvars, Reg $tkireg, $planet, int $i): string
    {
        if($planet[$i]['base'] == 'Y')
        {
            return $langvars['l_yes'];
        }
        elseif($planet[$i]['ore'] >= $tkireg->base_ore && $planet[$i]['organics'] >= $tkireg->base_organics && $planet[$i]['goods'] >= $tkireg->base_goods && $planet[$i]['credits'] >= $tkireg->base_credits)
        {
            return "<a href=planet-report-ce.php?buildp=" . $planet[$i]['planet_id'] . "&builds=" . $planet[$i]['sector_id'] . ">Build</a>";
        }
        else
        {
            return $langvars['l_no'];
        }
    }

    public static function planetReportMenu(array $playerinfo, array $langvars): void
    {
        echo "<div style='width:90%; margin:auto; font-size:14px;'>\n";
        echo "<strong><a href=\"planet_report.php?preptype=1\" name=\"Planet Status\">Planet Status</a></strong><br>" .
             "Displays the number of each Commodity on the planet (Ore, Organics, Goods, Energy, Colonists, Credits, Fighters, and Torpedoes)<br>" .
             "<br>" .
             "<strong><a href=\"planet_report.php?preptype=2\" name=\"Planet Status\">Change Production</a></strong> &nbsp;&nbsp; <strong>Base Required</strong> on Planet<br>" .
             "This Report allows you to change the rate of production of commondits on planets that have a base<br>" .
             "-- You must travel to the planet to build a base set the planet to coporate or change the name (celebrations and such)<br>";

        if ($playerinfo['team'] > 0)
        {
            echo "<br><strong><a href=team_planets.php>" . $langvars['l_pr_teamlink'] . "</a></strong><br> " .
                 "Commondity Report (like Planet Status) for planets marked Team by you and/or your fellow team member<br><br>";
        }

        echo "</div>\n";
    }

    public static function teamPlanetCheckboxes(int $planet, $i) : string
    {
        if ($planet[$i]['team'] <= 0)
        {
            return "<input type='checkbox' name='team[" . $i . "]' value='" . $planet[$i]['planet_id'] ."' />";
        }
        elseif ($planet[$i]['team'] > 0)
        {
            return "<input type='checkbox' name='team[" . $i . "]' value='{" . $planet[$i]['planet_id'] . "' checked />";
        }
    }

    public static function sellingCheckboxes(int $planet, $i) : string
    {
        if ($planet[$i]['sells'] != 'Y')
        {
            return "<input type='checkbox' name='sells[" . $i . "]' value='" . $planet[$i]['planet_id'] . "' />";
        }
        elseif ($planet[$i]['sells'] == 'Y')
        {
            return "<input type='checkbox' name='sells[" . $i . "]' value='" . $planet[$i]['planet_id'] . "' checked />";
        }
    }

    public static function standardReport(\PDO $pdo_db, array $langvars, array $playerinfo, $sort, Reg $tkireg): void
    {
        echo "<div style='width:90%; margin:auto; font-size:14px;'>\n";

        echo "Planetary report descriptions and <strong><a href=\"planet_report.php?preptype=0\">menu</a></strong><br><br>" .
             "<strong><a href=\"planet_report.php?preptype=2\">Change Production</a></strong> &nbsp;&nbsp; <strong>Base Required</strong> on Planet<br>";

        if ($playerinfo['team'] > 0)
        {
            echo "<br><strong><a href=team_planets.php>" . $langvars['l_pr_teamlink'] . "</a></strong><br> <br>";
        }

        $sql = "SELECT * FROM ::prefix::planets WHERE owner=:owner";

        if ($sort !== null)
        {
            $sql .= " ORDER BY";
            if ($sort == "name")
            {
                $sql .= " name ASC";
            }
            elseif ($sort == "organics" || $sort == "ore" || $sort == "goods" || $sort == "energy" || $sort == "colonists" || $sort == "credits" || $sort == "fighters")
            {
                $sql .= " $sort DESC, sector_id ASC";
            }
            elseif ($sort == "torp")
            {
                $sql .= " torps DESC, sector_id ASC";
            }
            else
            {
                $sql .= " sector_id ASC";
            }
        }
        else
        {
            $sql .= " ORDER BY sector_id ASC";
        }

        $stmt = $pdo_db->prepare($sql);
        $stmt->bindParam(':owner', $playerinfo['ship_id']);
        $stmt->execute();
        $planet_owner_present = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $i = 0;
        $planet = array();
        if ($planet_owner_present !== null)
        {
            foreach ($planet_owner_present as $tmp_owner)
            {
                $planet[$i] = $tmp_owner;
                $i++;
            }
        }

        $num_planets = $i;
        if ($num_planets < 1)
        {
            echo "<br>" . $langvars['l_pr_noplanet'];
        }
        else
        {
            echo "<br>";
            echo "<form accept-charset='utf-8' action=planet_report_ce.php method=post>";

            // Next block of echo 's creates the header of the table
            echo $langvars['l_pr_clicktosort'] . "<br><br>";
            echo "<strong>WARNING:</strong> \"Build\" and \"Take Credits\" will cause your ship to move. <br><br>";
            echo "<table width=\"100%\" border=0 cellspacing=0 cellpadding=2>";
            echo "<tr bgcolor=\"$tkireg->color_header\" valign=bottom>";
            echo "<td><strong><a href=\"planet_report.php?preptype=1&amp;sort=sector_id\">" . $langvars['l_sector'] . "</a></strong></td>";
            echo "<td><strong><a href=\"planet_report.php?preptype=1&amp;sort=name\">" . $langvars['l_name'] . "</a></strong></td>";
            echo "<td><strong><a href=\"planet_report.php?preptype=1&amp;sort=ore\">" . $langvars['l_ore'] . "</a></strong></td>";
            echo "<td><strong><a href=\"planet_report.php?preptype=1&amp;sort=organics\">" . $langvars['l_organics'] . "</a></strong></td>";
            echo "<td><strong><a href=\"planet_report.php?preptype=1&amp;sort=goods\">" . $langvars['l_goods'] . "</a></strong></td>";
            echo "<td><strong><a href=\"planet_report.php?preptype=1&amp;sort=energy\">" . $langvars['l_energy'] . "</a></strong></td>";
            echo "<td align=center><strong><a href=\"planet_report.php?preptype=1&amp;sort=colonists\">" . $langvars['l_colonists'] . "</a></strong></td>";
            echo "<td align=center><strong><a href=\"planet_report.php?preptype=1&amp;sort=credits\">" . $langvars['l_credits'] . "</a></strong></td>";
            echo "<td align=center><strong>Take<br>Credits</strong></td>";
            echo "<td align=center><strong><a href=\"planet_report.php?preptype=1&amp;sort=fighters\">" . $langvars['l_fighters'] . "</a></strong></td>";
            echo "<td align=center><strong><a href=\"planet_report.php?preptype=1&amp;sort=torp\">" . $langvars['l_torps'] . "</a></strong></td>";
            echo "<td align=right><strong>" . $langvars['l_base'] . "?</strong></td>";
            if ($playerinfo['team'] > 0)
            {
                echo "<td align=right><strong>Team?</strong></td>";
            }

            echo "<td align=right><strong>" . $langvars['l_selling'] . "?</strong></td>";

            // Next block of echo 's fils the table and calculates the totals of all the commoditites as well as counting the bases and selling planets
            echo "</tr>";
            $total_organics = 0;
            $total_ore = 0;
            $total_goods = 0;
            $total_energy = 0;
            $total_colonists = 0;
            $total_credits = 0;
            $total_fighters = 0;
            $total_torp = 0;
            $total_base = 0;
            $total_team = 0;
            $total_selling = 0;
            $color = $tkireg->color_line1;
            for ($i = 0; $i < $num_planets; $i++)
            {
                $total_organics += $planet[$i]['organics'];
                $total_ore += $planet[$i]['ore'];
                $total_goods += $planet[$i]['goods'];
                $total_energy += $planet[$i]['energy'];
                $total_colonists += $planet[$i]['colonists'];
                $total_credits += $planet[$i]['credits'];
                $total_fighters += $planet[$i]['fighters'];
                $total_torp += $planet[$i]['torps'];
                if ($planet[$i]['base'] == "Y")
                {
                    $total_base++;
                }

                if ($planet[$i]['team'] > 0)
                {
                    $total_team++;
                }

                if ($planet[$i]['sells'] == "Y")
                {
                    $total_selling++;
                }

                if (empty ($planet[$i]['name']))
                {
                    $planet[$i]['name'] = $langvars['l_unnamed'];
                }

                echo "<tr bgcolor=\"$color\">";
                echo "<td><a href=rsmove.php?engage=1&destination=". $planet[$i]['sector_id'] . ">". $planet[$i]['sector_id'] . "</a></td>";
                echo "<td>" . $planet[$i]['name'] . "</td>";
                echo "<td>" . number_format($planet[$i]['ore'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td>" . number_format($planet[$i]['organics'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td>" . number_format($planet[$i]['goods'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td>" . number_format($planet[$i]['energy'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td align=right>" . number_format($planet[$i]['colonists'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td align=right>" . number_format($planet[$i]['credits'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td align=center>" . "<input type=checkbox name=TPCreds[] value=\"" . $planet[$i]['planet_id'] . "\">" . "</td>";
                echo "<td align=right>"  . number_format($planet[$i]['fighters'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td align=right>"  . number_format($planet[$i]['torps'], 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
                echo "<td align=center>" . self::baseBuildCheck($langvars, $tkireg, $planet, $i) . "</td>";

                if ($playerinfo['team'] > 0)
                {
                    echo "<td align=center>" . ($planet[$i]['team'] > 0 ? $langvars['l_yes'] : $langvars['l_no']) . "</td>";
                }

                echo "<td align=center>" . ($planet[$i]['sells'] == 'Y' ? $langvars['l_yes'] : $langvars['l_no']) . "</td>";
                echo "</tr>";

                if ($color == $tkireg->color_line1)
                {
                    $color = $tkireg->color_line2;
                }
                else
                {
                    $color = $tkireg->color_line1;
                }
            }

            // the next block displays the totals
            echo "<tr bgcolor=$color>";
            echo "<td COLSPAN=2 align=center>" . $langvars['l_pr_totals'] . "</td>";
            echo "<td>" . number_format($total_ore, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td>" . number_format($total_organics, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td>" . number_format($total_goods, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td>" . number_format($total_energy, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td align=right>" . number_format($total_colonists, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td align=right>" . number_format($total_credits, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td></td>";
            echo "<td align=right>"  . number_format($total_fighters, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td align=right>"  . number_format($total_torp, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "<td align=center>" . number_format($total_base, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            if ($playerinfo['team'] > 0)
            {
                echo "<td align=center>" . number_format($total_team, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            }

            echo "<td align=center>" . number_format($total_selling, 0, $langvars['local_number_dec_point'], $langvars['local_number_thousands_sep']) . "</td>";
            echo "</tr>";
            echo "</table>";
            echo "<br>";
            echo "<input type=submit value=\"Collect Credits\">  <input type=reset value=reset>";
            echo "</form>";
        }

        echo "</div>\n";
    }
}
