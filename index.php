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
// File: index.php

$index_page = true;
require_once './common.php';

$link = null;

if (!Tki\Db::isActive($pdo_db))
{
    // If DB is not active, redirect to create universe to run install
    header('Location: create_universe.php');
    die();
}

// Database driven language entries
$langvars = Tki\Translate::load($pdo_db, $lang, array('regional', 'admin', 'attack', 'beacon', 'bounty', 'check_fighters', 'check_mines', 'combat', 'common', 'team', 'create_universe', 'defense_report', 'device', 'dump', 'emerwarp', 'error', 'faq', 'feedback', 'footer', 'galaxy', 'genesis', 'ibank', 'index', 'log', 'login', 'logout', 'lrscan', 'mail', 'mailto', 'main', 'mines', 'modify_defenses', 'move', 'navcomp', 'new', 'new_player_guide', 'news', 'option2', 'options', 'planet', 'planet_report', 'port', 'presets', 'pwreset', 'ranking', 'readmail', 'report', 'rsmove', 'scan', 'scheduler', 'sector_fighters', 'self_destruct', 'settings', 'setup_info', 'ship', 'team_planets', 'teams', 'traderoutes', 'warpedit', 'kabal_control', 'zoneedit', 'zoneinfo', 'global_includes'));

$variables = null;
$variables['lang'] = $lang;
$variables['link'] = $link;
$variables['title'] = $langvars['l_welcome_tki'];
$variables['link_forums'] = $tkireg->link_forums;
$variables['admin_mail'] = $tkireg->admin_mail;
$variables['body_class'] = 'index';

// Get list of available languages
$variables['list_of_langs'] = Tki\Languages::listAvailable($pdo_db, $lang);

// Temporarily set the template to the default template until we have a user option
$variables['template'] = $tkireg->default_template;
Tki\Header::display($pdo_db, $lang, $template, $variables['title'], $variables['body_class']);

$template->addVariables('langvars', $langvars);
$template->addVariables('variables', $variables);
$template->display('index.tpl');

Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
