<?php
/**
 * Functions for processing XML data and retrieving product information.
 *
 * This set of functions includes methods to process XML descriptions by removing CDATA tags
 * and to fetch products list and categories from an XML file URL.
 *
 * @package SalesDriveToWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Process XML description data by removing CDATA tags.
 *
 * @param string $description The XML description string to process.
 * @return string The processed description string without CDATA tags.
 */
function processDescription($description) {
    // Del "<description><!--[CDATA[" and "]]</description>" 
    $description = str_replace('<description><!--[CDATA[', '', $description);
    $description = str_replace('<description><![CDATA[', '', $description);
    $description = str_replace(']]></description>', '', $description);

    return $description;
}

/**
 * Get products list and categories from an XML file URL.
 *
 * This function retrieves data from an XML file located at the specified URL,
 * converts it to an array, and processes product descriptions.
 *
 * @param string $file_url The URL of the XML file to fetch data from.
 * @return array|false An array containing products and categories if successful, false on failure.
 */
 function getProductsList($file_url) {

    // Getting data using the file_get_contents function
    $data = file_get_contents($file_url);

    // Checking if data retrieval was successful
    if ($data === false) {
        echo __("Failed to load file.", "salesdrive_integ");
        echo '<br><button onclick="window.history.back();">' . __("Go Back", "salesdrive_integ") . '</button>';
        
        return false;
    } else {

        // Creating DOMDocument to handle XML data
        $dom = new DOMDocument;
        $dom->loadXML($data);

        // Converting XML to an array
        $json = json_encode(simplexml_import_dom($dom));
        $array = json_decode($json, true);

        // Getting a list of all offers
        $offers = $array['shop']['offers']['offer'];

        $categories = $array['shop']['categories']['category'];

        // For each offer, adding description from DOMDocument
        foreach ($offers as &$offer) {
            $offer['description'] = processDescription($dom->saveXML($dom->getElementsByTagName('description')->item(0)));
        }

        // return $offers;

        return array(
            'products' => $offers,
            'categories' => $categories,
        );
    }
}