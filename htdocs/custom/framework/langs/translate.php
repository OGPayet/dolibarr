<?php

header('Content-type: text/plain; charset=utf-8');


define('file_lang_name', 'edi.lang');
define('DEV_KEY', 'AIzaSyBHgg6XWZJFZVZeIygIqwiOGVvQZ8cfhaw');
define('commentaire', '#');
define('correspondance', '=');
define('encapsulation', "");

if (isset($_GET['src']) && isset($_GET['dst'])) {
    $src = $_GET['src'];
    $dst = $_GET['dst'];
    if (!is_dir($dst)) { // création du répertoire de sortie
        mkdir($dst);
    }
    $handleSrc = fopen($src . DIRECTORY_SEPARATOR . file_lang_name, "r");
    $handleDst = fopen($dst . DIRECTORY_SEPARATOR . file_lang_name, "w");
    if ($handleSrc && $handleDst) {
        $gt = new LanguageTranslator();
        //var_dump($gt);
        while (($line = fgets($handleSrc)) !== false) {
            $checkCom = trim($line);
            if ($checkCom[0] == commentaire || strpos($line, correspondance) === FALSE) { // pas besoin de traduire
                $newLine = str_ireplace($src, $dst, $line);
            } else { // on traduit
                list($key, $srcTrad) = explode(correspondance, $line, 2); // on coup la ligne en deux à partir de =
                $srcTrad = trim(trim($srcTrad), encapsulation); //on enlève les espaces et les encapsulations
                try {
                    $dstTrad = $gt->translate($srcTrad, $dst, $src); // on traduit
                } catch (Exception $exc) {
                    //echo 'Exception reçue : ',  $exc->getMessage(), "\n";
                    // traduction impossible
                    $dstTrad = $srcTrad;
                }
                $newLine = $key . correspondance . encapsulation . $dstTrad . encapsulation . "\n"; // on réassemble
            }
            echo $newLine;
            fputs($handleDst, $newLine);
        }
        fclose($handleSrc);
        fclose($handleDst);
    } else {
        echo "error opening the files.";
    }
} else {
    echo 'usage: ?src=fr_FR&dst=de_DE';
}

class LanguageTranslator {

    // this is the API endpoint, as specified by Google
    const ENDPOINT = 'https://www.googleapis.com/language/translate/v2';

    // holder for you API key, specified when an instance is created
    protected $_apiKey;

    // constructor, accepts Google API key as its only argument
    public function __construct() {
        $this->_apiKey = DEV_KEY;
    }

    // translate the text/html in $data. Translates to the language
    // in $target. Can optionally specify the source language
    public function translate($data, $target, $source = '') {
        // this is the form data to be included with the request
        $values = array(
            'key' => $this->_apiKey,
            'target' => substr($target, 0, 2),
            'q' => $data
        );

        // only include the source data if it's been specified
        if (strlen($source) > 0) {
            $values['source'] = substr($source, 0, 2);
        }

        // turn the form data array into raw format so it can be used with cURL
        $formData = http_build_query($values);

        // create a connection to the API endpoint
        $ch = curl_init(self::ENDPOINT);

        // tell cURL to return the response rather than outputting it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // write the form data to the request in the post body
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);

        // include the header to make Google treat this post request as a get request
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));

        // execute the HTTP request
        $json = curl_exec($ch);
        curl_close($ch);

        // decode the response data
        $data = json_decode($json, true);

//        print_r($values);
//        print_r($data);

        // ensure the returned data is valid
        if (!is_array($data) || !array_key_exists('data', $data)) {
            throw new Exception('Unable to find data key');
        }

        // ensure the returned data is valid
        if (!array_key_exists('translations', $data['data'])) {
            throw new Exception('Unable to find translations key');
        }

        if (!is_array($data['data']['translations'])) {
            throw new Exception('Expected array for translations');
        }

        // loop over the translations and return the first one.
        // if you wanted to handle multiple translations in a single call
        // you would need to modify how this returns data
        foreach ($data['data']['translations'] as $translation) {
            return $translation['translatedText'];
        }

        // assume failure since success would've returned just above
        throw new Exception('Translation failed');
    }

}
