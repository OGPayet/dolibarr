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

    // Create a curl handle to a non-existing location
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    $json = '';
    if( ($json = curl_exec($ch) ) !== false)
    {
        $json = curl_exec($ch);
        $resp = json_decode($json, true);
        $return = $resp['display_name'];
    }

    // Close handle
    curl_close($ch);

    return $return;
}
?>