<?php
class amoCRM {
    function ResponseCRM() {
        $subdomain='soporte01'; #Our account is a subdomain
        #Generate a link for the request
        $link='https://'.$subdomain.'.amocrm.com/private/api/v2/webhooks';
        #We need to initiate a request to the server. We use the cURL library (supplied as part of PHP). More about working with this library you can read in the manual.
        $curl=curl_init(); #Save the cURL session descriptor
        #Set the necessary options for the cURL session
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Accept: application/json']);
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
        
        $out=curl_exec($curl); #Initiate a request to the API and save the response to a variable
        $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        #Now we can process the response, received from the server. This is an example. You can process the data in your own way.
        
        $code=(int)$code;
        $errors=array(
            301=>'Moved permanently',
            400=>'Bad request',
            401=>'Unauthorized',
            403=>'Forbidden',
            404=>'Not found',
            500=>'Internal server error',
            502=>'Bad gateway',
            503=>'Service unavailable'
            );
        try
        {
            #If the response code is not 200 or 204, we return an error message
            if($code!=200 && $code!=204)
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
        }
        catch(Exception $E)
        {
            die('Error: '.$E->getMessage().PHP_EOL.'Error code: '.$E->getCode());
        }
    }
}
