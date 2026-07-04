<?php
    class Api {
        private string $NS_API_Key;

        public function __construct() {
            $this->NS_API_Key = json_decode(file_get_contents(__DIR__ . "/../../project.json"), true)["NS_API_KEY"];
        }

        public function getResponse(string $url) {
            // Starts the api request and sets the headers
            $api_request = curl_init($url);
            $headers = [
                'Cache-Control: no-cache',
                'Ocp-Apim-Subscription-Key: ' . $this->NS_API_Key
            ];

            //Set the header and response
            curl_setopt($api_request, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($api_request, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($api_request, CURLOPT_RETURNTRANSFER, true);

            //Actually execute the api call abd return the data
            return curl_exec($api_request);
        }
    }
?>