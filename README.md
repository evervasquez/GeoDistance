# GeoDistance
GeoDistance allows you to search for locations within a radius using latitude and longitude values with your eloquent models.

###Setup

Add geodistance to your composer file.
```
"evasquez/geodistance": "dev-master"
```

Add the geodistance trait to your eloquent model and latitude/longitude columns to your table.

```php
<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jackpopp\GeoDistance\GeoDistanceTrait;

class Location extends Model {

    use GeoDistanceTrait;

    protected $fillable = ['name', 'latitude', 'longitude'];
    
}
```

You can now search for locations within a distance, using miles or kilometers:

```php

$lat = 51.4833;
$lng = 3.1833;
$table = 'youtable'
$locations = Location::within(5, 'miles', $lat, $lng,$table)->get();

$locations = Location::within(5, 'kilometers', $lat, $lng,$table)->get();

```

You can also search for locations outside a certain distance:


Distances Available

Miles (miles/m)
Kilometers (kilometers/km)
Nautical Miles (nautical_miles)
Feet (feet)
