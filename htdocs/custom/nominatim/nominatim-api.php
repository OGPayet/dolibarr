<?php
/**
	 * Reverse geocoding an address (lat,lng -> address)
	 *
	 * @param  string  $latitude        lat coord
     * @param  string  $longitude       lng coord
	 * @return string  $return          address
*/
function reverseGeocoding($latitude, $longitude) {
    $url = "https://nominatim.openstreetmap.org/reverse?lat={$latitude}&lon={$longitude}&format=json";

    ini_set("allow_url_open", "1");
    $resp_json = file_get_contents($url);

    $resp = json_decode($resp_json, true);

    $return = $resp[0]['address']['house_number'] . ' ' . $resp[0]['address']['road'] . ', ' . $resp[0]['address']['town'];
    $return .= ', ' . $resp[0]['address']['postcode'] . ', ' . $resp[0]['address']['country'];

    return $return;
}
?>