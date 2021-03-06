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
// File: classes/Planets/PlanetsGateway.php

namespace Tki\Planets; // Domain Entity organization pattern, Planets objects

class PlanetsGateway // Gateway for SQL calls related to Planets
{
    /** @var \PDO **/
    protected $pdo_db; // This will hold a protected version of the pdo_db variable

    public function __construct(\PDO $pdo_db) // Create the this->pdo_db object
    {
        $this->pdo_db = $pdo_db;
    }

    public function selectPlanetInfo(int $sector_id)
    {
        $sql = "SELECT * FROM ::prefix::planets WHERE sector_id = :sector_id";
        $stmt = $this->pdo_db->prepare($sql);
        $stmt->bindParam(':sector_id', $sector_id, \PDO::PARAM_INT);
        $stmt->execute();
        \Tki\Db::logDbErrors($this->pdo_db, $sql, __LINE__, __FILE__); // Log any errors, if there are any

        // A little magic here. If it couldn't select a planet in the sector, the following call will return false - which is what we want for "no planet found".
        $planetinfo = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $planetinfo; // FUTURE: Eventually we want this to return a planet object instead, for now, planetinfo array or false for no planet found.
    }
}
