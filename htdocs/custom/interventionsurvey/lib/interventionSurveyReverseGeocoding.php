<?php
/**
	 * Reverse geocoding an address (lat,lng -> address)
	 *
	 * @param  string  $latitude        lat coord
     * @param  string  $longitude       lng coord
	 * @return string  $return          address
*/
function interventionSurveyReverseGeocoding($latitude, $longitude) {
    $url = "https://nominatim.openstreetmap.org/reverse?lat={$latitude}&lon={$longitude}&format=json";

    // Create a curl handle to a non-existing location
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

    $result = '';
    $json = curl_exec($ch);
    if ($json !== false && !curl_errno($ch)) {
        $resp = json_decode($json, true);
        $result = $resp['display_name'];
    }

    // Close handle
    curl_close($ch);

    return $result;
}
?>