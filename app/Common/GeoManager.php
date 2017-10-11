<?php

namespace App\Common;


class GeoManager
{
    public static $accurate = [
        'locality',
        'postal_code',
        'administrative_area_level_2',
        'administrative_area_level_1',
        'country'
    ];

    public static function parseAddressComponent($address_components){
        $newArray=[];
        foreach ($address_components as $item){
            $newArray[$item->types[0]] = $item->short_name;
        }
        return $newArray;
    }

    public static function getAccurateSearch($address_components){
        //$search = json_decode($request->searchPlace);
        $newArray = self::parseAddressComponent($address_components);

        foreach (self::$accurate as $key){
            if(key_exists($key, $newArray)){
                return [
                    $key => $newArray[$key]
                ];
                break;
            }
        }
        return null;
    }

    public static function getCodeAdress($address) {
        $base_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
        $address_url = urlencode($address);
        $key_url = '&key=' . env('GOOGLE_MAP_SERVER_GEOCODING_KEY');

        $url= $base_url . $address_url . $key_url;
        $json_response = file_get_contents($url);

        return $json_response;
    }

}