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
// File: mailto.php

require_once './common.php';

Tki\Login::checkLogin($pdo_db, $lang, $tkireg, $template);

$body_class = 'tki';
$include_ckeditor = true;

// Database driven language entries
$langvars = Tki\Translate::load($pdo_db, $lang, array('mailto', 'common', 'global_includes', 'global_funcs', 'footer', 'planet_report'));
$title = $langvars['l_sendm_title'];
$header = new Tki\Header;
$header->display($pdo_db, $lang, $template, $title, $body_class, $include_ckeditor);

// Filter to the FILTER_SANITIZE_STRING ruleset, because we need to allow spaces for names & subject (FILTER_SANITIZE_URL doesn't allow spaces)
// $name, $to, and $subject are all sent both via post and get, so we have to do a filter input for each
// filter_input doesn't support INPUT_REQUEST, and also doesn't support the format INPUT_POST | INPUT_GET - I tried.

$name = null;
if (array_key_exists('name', $_POST))
{
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
}
elseif (array_key_exists('name', $_GET))
{
    $name = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_STRING);
}

$to = null;
if (array_key_exists('to', $_POST))
{
    $to = filter_input(INPUT_POST, 'to', FILTER_SANITIZE_STRING);
}
elseif (array_key_exists('to', $_GET))
{
    $to = filter_input(INPUT_GET, 'to', FILTER_SANITIZE_STRING);
}

$subject = null;
if (array_key_exists('subject', $_POST))
{
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
}

if (array_key_exists('subject', $_GET))
{
    $subject = filter_input(INPUT_GET, 'subject', FILTER_SANITIZE_STRING);
}

// Allow rich-text codes (dirty) in, we will filter them using html purifier
$dirtycontent = filter_input(INPUT_POST, 'content', FILTER_UNSAFE_RAW);

// Include HTML purifier, set its config, use the 4.01 doctype (since they don't do HTML5 yet)
$html_purifier_config = HTMLPurifier_Config::createDefault();
$html_purifier_config->set('HTML.Doctype', 'HTML 4.01 Transitional');
$purifier = new HTMLPurifier($html_purifier_config);

// Filter the submitted content to ensure that it doesn't have exploits
$content = $purifier->purify($dirtycontent);

// Filter the submitted subject to ensure that it doesn't have exploits either
if ($subject !== null)
{
    $subject = $purifier->purify($subject);
}

// Get playerinfo from database
$sql = "SELECT * FROM ::prefix::ships WHERE email=:email LIMIT 1";
$stmt = $pdo_db->prepare($sql);
$stmt->bindParam(':email', $_SESSION['username'], PDO::PARAM_STR);
$stmt->execute();
$playerinfo = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h1>" . $title . "</h1>\n";

if (empty($content))
{
    $res = $db->Execute("SELECT character_name FROM {$db->prefix}ships WHERE email NOT LIKE '%@Kabal' AND ship_id <> ? ORDER BY character_name ASC;", array($playerinfo['ship_id']));
    Tki\Db::logDbErrors($pdo_db, $res, __LINE__, __FILE__);
    $res2 = $db->Execute("SELECT team_name FROM {$db->prefix}teams WHERE admin ='N' ORDER BY team_name ASC;");
    Tki\Db::logDbErrors($pdo_db, $res2, __LINE__, __FILE__);
    echo "<form accept-charset='utf-8' action=mailto.php method=post>\n";
    echo "  <table>\n";
    echo "    <tr>\n";
    echo "      <td>" . $langvars['l_sendm_from'] . ":</td>\n";
    echo "      <td><input disabled type='text' name='dummy' size='40' maxlength='40' value=\"$playerinfo[character_name]\"></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>" . $langvars['l_sendm_to'] . ":</td>\n";
    echo "      <td>\n";
    echo "        <select name='to' style='width:200px;'>\n";

    // Add self to list.
    echo "          <option" . (($playerinfo['character_name'] == $name) ? " selected" : "") . ">{$playerinfo['character_name']}</option>\n";

    while (!$res->EOF)
    {
        $row = $res->fields;
        echo "          <option" . (($row['character_name'] == $to) ? " selected" : "") . ">{$row['character_name']}</option>\n";
        $res->MoveNext();
    }

    while (!$res2->EOF && $res2->fields !== null)
    {
        $row2 = $res2->fields;
        echo "          <option>" . $langvars['l_sendm_ally'] . " " . $row2['team_name'] . "</option>\n";
        $res2->MoveNext();
    }

    echo "        </select>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    if ($subject !== null)
    {
        $subject = "RE: " . $subject;
    }
    else
    {
        $subject = null;
    }

    echo "    <tr>\n";
    echo "      <td>" . $langvars['l_sendm_subj'] . ":</td>\n";
    echo "      <td><input type=text name=subject size=40 maxlength=40 value=\"$subject\"></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>" . $langvars['l_sendm_mess'] . ":</td>\n";
    echo "      <td><textarea id=richeditor name=content rows=6 cols=80></textarea></td>\n";
    echo "    </tr>";
    echo "    <tr>\n";
    echo "      <td></td>\n";
    echo "      <td><input type=submit value=" . $langvars['l_sendm_send'] . "><input type=reset value=" . $langvars['l_reset'] . "></td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "<script>CKEDITOR.replace('richeditor');</script>";
    echo "</form>\n";
}
else
{
    if (mb_strpos($to, $langvars['l_sendm_ally']) === false)
    {
        $timestamp = date("Y\-m\-d H\:i\:s");
        $res = $db->Execute("SELECT ship_id FROM {$db->prefix}ships WHERE character_name = ?;", array($to));
        Tki\Db::logDbErrors($pdo_db, $res, __LINE__, __FILE__);
        $target_info = $res->fields;
        $resx = $db->Execute("INSERT INTO {$db->prefix}messages (sender_id, recp_id, sent, subject, message) VALUES (?, ?, ?, ?, ?);", array($playerinfo['ship_id'], $target_info['ship_id'], $timestamp, $subject, $content));
        Tki\Db::logDbErrors($pdo_db, $resx, __LINE__, __FILE__);
        if ($db->ErrorNo() != 0)
        {
            echo "Message failed to send: " . $db->ErrorMsg() . "<br>\n";
        }
        else
        {
            echo $langvars['l_sendm_sent'] . "<br><br>";
        }
    }
    else
    {
        $timestamp = date("Y\-m\-d H\:i\:s");
        $to = str_replace($langvars['l_sendm_ally'], "", $to);
        $to = trim($to);
        $to = addslashes($to);
        $res = $db->Execute("SELECT id FROM {$db->prefix}teams WHERE team_name = ?;", array($to));
        Tki\Db::logDbErrors($pdo_db, $res, __LINE__, __FILE__);
        $row = $res->fields;

        $res2 = $db->Execute("SELECT ship_id FROM {$db->prefix}ships WHERE team = ?;", array($row['id']));
        Tki\Db::logDbErrors($pdo_db, $res2, __LINE__, __FILE__);

        while (!$res2->EOF)
        {
            $row2 = $res2->fields;
            $resx = $db->Execute("INSERT INTO {$db->prefix}messages (sender_id, recp_id, sent, subject, message) VALUES (?, ?, ?, ?, ?);", array($playerinfo['ship_id'], $row2['ship_id'], $timestamp, $subject, $content));
            Tki\Db::logDbErrors($pdo_db, $resx, __LINE__, __FILE__);
            $res2->MoveNext();
        }
    }
}

Tki\Text::gotoMain($pdo_db, $lang);

$footer = new Tki\Footer;
$footer->display($pdo_db, $lang, $tkireg, $template);
