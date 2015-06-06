<?php
/*
 *
 *  * Copyright (C) 2015 eveR Vásquez.
 *  *
 *  * Licensed under the Apache License, Version 2.0 (the "License");
 *  * you may not use this file except in compliance with the License.
 *  * You may obtain a copy of the License at
 *  *
 *  *      http://www.apache.org/licenses/LICENSE-2.0
 *  *
 *  * Unless required by applicable law or agreed to in writing, software
 *  * distributed under the License is distributed on an "AS IS" BASIS,
 *  * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  * See the License for the specific language governing permissions and
 *  * limitations under the License.
 *
 */

namespace Evasquez\GeoDistance;


trait GeoDistanceTrait
{
    protected $latColumn = 'latitude';

    protected $lngColumn = 'longitude';

    protected $distance = 10;

    private static $MEASUREMENTS = [
        'miles' => 3959,
        'm' => 3959,
        'kilometers' => 6371,
        'km' => 6371,
        'meters' => 6371000,
        'feet' => 20902231,
        'nautical_miles' => 3440.06479
    ];

    public function getLatColumn()
    {
        return "{$this->getTable()}.{$this->latColumn}";
    }

    public function getLngColumn()
    {
        return "{$this->getTable()}.{$this->lngColumn}";
    }

    public function lat($lat = null)
    {
        if ($lat) {
            $this->lat = $lat;
            return $this;
        }

        return $this->lat;
    }

    public function lng($lng = null)
    {
        if ($lng) {
            $this->lng = $lng;
            return $this;
        }

        return $this->lng;
    }

    /**
     * @param string
     *
     * Grabs the earths mean radius in a specific measurment based on the key provided, throws an exception
     * if no mean readius measurement is found
     *
     * @throws InvalidMeasurementException
     * @return float
     **/

    public function resolveEarthMeanRadius($measurement = null)
    {
        $measurement = ($measurement === null) ? key(static::$MEASUREMENTS) : strtolower($measurement);

        if (array_key_exists($measurement, static::$MEASUREMENTS))
            return static::$MEASUREMENTS[$measurement];

        throw new InvalidMeasurementException('Invalid measurement');
    }

    /**
     * @param Query
     * @param integer
     * @param mixed
     * @param mixed
     *
     * @todo Use pdo paramater bindings, instead of direct variables in query
     * @return Query
     *
     * Implements a distance radius search using Haversine formula.
     * Returns a query scope.
     * credit - https://developers.google.com/maps/articles/phpsqlsearch_v3
     **/

    public function scopeWithin($q, $distance, $measurement = null, $lat = null, $lng = null, $table = 'locations')
    {
        $pdo = DB::connection()->getPdo();

        $latColumn = $this->getLatColumn();
        $lngColumn = $this->getLngColumn();

        $lat = ($lat === null) ? $this->lat() : $lat;
        $lng = ($lng === null) ? $this->lng() : $lng;

        $meanRadius = $this->resolveEarthMeanRadius($measurement);

        $distance = intval($distance);

        $lat = $pdo->quote(floatval($lat));

        $lng = $pdo->quote(floatval($lng));

        $meanRadius = $pdo->quote(floatval($meanRadius));

        // Paramater bindings havent been used as it would need to be within a DB::select which would run straight away and return its result, which we dont want as it will break the query builder.
        // This method should work okay as our values have been cooerced into correct types and quoted with pdo.

        $query = "SELECT id, ( " . $meanRadius . " * acos( cos( radians(" . $lat . ") ) * cos( radians('" . $latColumn . "') ) * cos( radians( '" . $lngColumn . "' ) - radians(" . $lng . ") ) + sin( radians(" . $lat . ") ) * sin( radians( '" . $latColumn . "' ) ) ) ) AS distance
        FROM " . $table . " HAVING distance < " . $distance;

        return $q->select(\DB::raw($query));

    }

    public function scopeOutside($q, $distance, $measurement = null, $lat = null, $lng = null)
    {
        $pdo = DB::connection()->getPdo();

        $latColumn = $this->getLatColumn();
        $lngColumn = $this->getLngColumn();

        $lat = ($lat === null) ? $this->lat() : $lat;
        $lng = ($lng === null) ? $this->lng() : $lng;

        $meanRadius = $this->resolveEarthMeanRadius($measurement);
        $distance = intval($distance);

        $lat = $pdo->quote(floatval($lat));
        $lng = $pdo->quote(floatval($lng));
        $meanRadius = $pdo->quote(floatval($meanRadius));

        return $q->select(DB::raw("*, ( $meanRadius * acos( cos( radians($lat) ) * cos( radians( $latColumn ) ) * cos( radians( $lngColumn ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( $latColumn ) ) ) ) AS distance"))
            ->having('distance', '>=', $distance)
            ->orderby('distance', 'ASC');
    }
}