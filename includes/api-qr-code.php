<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class API{

    // call API get list bank, return options bin - name
    public function get_info_banks() {
        $api_url = 'https://api.vietqr.io/v2/banks';
        $response = wp_remote_get($api_url);
    
        if (is_wp_error($response)) {
            return array();
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $options = array('default' => 'Select bank', );
        foreach ($data['data'] as $method) {
            $options[$method['bin']] = $method['shortName'] . " - " . $method['name'];
        }
    
        return $options;
    }

    // call API generate QR Code
    public function get_qr_code($data, $client_id, $api_key){
        $curl = curl_init();
        $options = array(
            CURLOPT_URL => 'https://api.vietqr.io/v2/generate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_HTTPHEADER => array(
                'x-client-id:' . $client_id,
                'x-api-key:' . $api_key,
                'Content-Type: application/json',
            )
        );

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        
        curl_close($curl);

        return $response;
    }

}
