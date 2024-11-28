<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class UsersLocation extends Model
{
    protected $table = 'users_locations';

    // protected $appends = ['location_title'];

    protected $fillable = ['latitude', 'longitude', 'user_id', 'table_name', 'table_id', 'table_type', 'location_title'];


    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $url = "https://maps.googleapis.com/maps/api/geocode/json?key=" . env('MAP_KEY') . "&latlng=" . $model->latitude . "," . $model->longitude . "&sensor=false";
            $geocode = file_get_contents($url);
            $json = json_decode($geocode);
            $address = isset($json->results[0]) ? $json->results[0]->formatted_address : '';
            $model->location_title =  $address;
        });
        Relation::morphMap([
            'attendences' => 'App\Models\Attendence',
            'shops' => 'App\Models\Shop',
            'shop_visits' => 'App\Models\ShopVisit',
            'sale_orders' => 'App\Models\SaleOrder',
        ]);
    }

    public function location()
    {
        return $this->morphTo(null, 'table_name', 'table_id');
    }
}
