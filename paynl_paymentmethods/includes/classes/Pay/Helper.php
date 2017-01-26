<?php

class Pay_Helper {

    /**
     * Get the status by statusId
     * 
     * @param int $statusId
     * @return string The status
     */
    public static function getStateText($stateId) {
        switch ($stateId) {
            case -70:
            case -71:
                return 'CHARGEBACK';
            case -51:
                return 'PAID CHECKAMOUNT';
            case -81:
                return 'REFUND';
            case -82:
                return 'PARTIAL REFUND';
            case 20:
            case 25:
            case 50:
                return 'PENDING';
            case 60:
                return 'OPEN';
            case 75:
            case 76:
                return 'CONFIRMED';
            case 80:
                return 'PARTIAL PAYMENT';
            case 100:
                return 'PAID';
            default:
                if ($stateId < 0) {
                    return 'CANCEL';
                } else {
                    return 'UNKNOWN';
                }
        }
    }

    //remove all empty nodes in an array
    public static function filterArrayRecursive($array) {
        $newArray = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::filterArrayRecursive($value);
            }
            if (!empty($value)) {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }

    /**
     * Find out if the connection is secure
     * 
     * @return boolean Secure
     */
    public static function isSecure() {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $isSecure = true;
        }
        return $isSecure;
    }
    /**
     * Get the uri of the current script without the filename.
     * We use this to generate the return- and exchangeurl
     * 
     * @return string The uri
     */
    public static function getUri(){        
        if(self::isSecure()){
            $uri='https://';
        } else {
            $uri='http://';
        }
        
        $uri .= $_SERVER['SERVER_NAME'];
        
        if(!empty($_SERVER['REQUEST_URI'])){
            $uri .= $_SERVER['REQUEST_URI'];
            $uriDir = $uri;
            if(substr($uri, -4) == '.php'){
                $uriDir = dirname($uri);
            }
            
            
            if($uriDir != 'http:' && $uriDir != 'https:'){
                $uri = $uriDir;
            }
        }
        
        return $uri.'/';
    }
    public static function splitAddress($strAddress) {
        $strAddress = trim($strAddress);

        $a = preg_split('/([0-9]+)/', $strAddress, 2, PREG_SPLIT_DELIM_CAPTURE);
        $strStreetName = trim(array_shift($a));
        $strStreetNumber = trim(implode('', $a));

        if (empty($strStreetName)) { // American address notation
            $a = preg_split('/([a-zA-Z]{2,})/', $strAddress, 2, PREG_SPLIT_DELIM_CAPTURE);

            $strStreetNumber = trim(array_shift($a));
            $strStreetName = implode(' ',$a);
        }

        return array($strStreetName, $strStreetNumber);
    }

    /**
     * Determine the tax class to send to Pay.nl
     *
     * @param int|float $amountInclTax
     * @param int|float $taxAmount
     * @return string The tax class (N, L or H)
     */
    public static function calculateTaxClass($amountInclTax, $taxAmount)
    {
        $taxClasses = array(
            0 => 'N',
            6 => 'L',
            21 => 'H'
        );
        // return 0 if amount or tax is 0
        if ($taxAmount == 0 || $amountInclTax == 0) {
            return $taxClasses[0];
        }
        $amountExclTax = $amountInclTax - $taxAmount;
        $taxRate = ($taxAmount / $amountExclTax) * 100;
        $nearestTaxRate = self::nearest($taxRate, array_keys($taxClasses));
        return ($taxClasses[$nearestTaxRate]);
    }
    /**
     * Get the nearest number
     *
     * @param int $number
     * @param array $numbers
     * @return int|bool nearest number false on error
     */
    private static function nearest($number, $numbers)
    {
        $output = FALSE;
        $number = intval($number);
        if (is_array($numbers) && count($numbers) >= 1) {
            $NDat = array();
            foreach ($numbers as $n) {
                $NDat[abs($number - $n)] = $n;
            }
            ksort($NDat);
            $NDat = array_values($NDat);
            $output = $NDat[0];
        }
        return $output;
    }
    
    /**
     * Sort the paymentoptions by name
     * 
     * @param array $paymentOptions
     * @return array
     */
    public static function sortPaymentOptions($paymentOptions){
        uasort($paymentOptions, 'sortPaymentOptions');
        return $paymentOptions;
    }   
}
function sortPaymentOptions($a,$b){
    return strcmp($a['name'], $b['name']);
}