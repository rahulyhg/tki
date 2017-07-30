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
// File: classes/News/NewsGateway.php

namespace Tki\News; // Domain Entity organization pattern, Players objects

class NewsGateway // Gateway for SQL calls related to Players
{
    /** @var \PDO **/
    protected $pdo_db; // This will hold a protected version of the pdo_db variable

    public function __construct(\PDO $pdo_db) // Create the this->pdo_db object
    {
        $this->pdo_db = $pdo_db;
    }

    public function selectNewsByDay(string $day)
    {
        // SQL call that selects all of the news items between the start date beginning of day, and the end of day.
        $sql = "SELECT * FROM ::prefix::news WHERE date > :start AND date < :end ORDER BY news_id";
        $stmt = $this->pdo_db->prepare($sql);
        $stmt->bindValue(':start', $day . ' 00:00:00', \PDO::PARAM_STR);
        $stmt->bindValue(':end', $day . ' 23:59:59', \PDO::PARAM_STR);
        $stmt->execute();
        \Tki\Db::logDbErrors($this->pdo_db, $sql, __LINE__, __FILE__); // Log errors, if there are any
        $return_value = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $return_value;
    }
}
