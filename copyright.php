<?php
// The Kabal Invasion - A web-based 4X space game
// Copyright © 2017 The Kabal Invasion development team, Ron Harwood, and the BNT development team
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
// File: copyright.php

$index_page = true;
require_once './common.php';

$link = null;

// Database driven language entries
$langvars = Tki\Translate::load($pdo_db, $lang, array('main', 'login', 'logout', 'index', 'common','regional', 'footer','global_includes'));

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
$template->display('copyright.tpl');

Tki\Footer::display($pdo_db, $lang, $tkireg, $template);
