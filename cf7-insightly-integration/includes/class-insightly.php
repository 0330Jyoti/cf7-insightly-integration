<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

/*
 * This is a class for insightly CRM API.
 */
if ( ! class_exists( 'CF7_INSIGHTLY_API' ) ) {
    class CF7_INSIGHTLY_API {
        
        var $url;
        var $client_id;
        var $client_secret;
        
        function __construct( $url, $client_id, $client_secret ) {
            
            $this->url = $url;
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
        }
        
        function getToken( $code, $redirect_uri ) {
            
            $data = array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirect_uri,
            );
            $data = http_build_query( $data );
            
            $url = $this->url.'/oauth2/access_token';
            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_HEADER, false );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );        
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            $json_response = curl_exec( $ch ); 
            curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close( $ch );
            
            $response = json_decode( $json_response );
            
            return $response;
        }
        
        function getRefreshToken( $token ) {
            
            $data = array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $token->refresh_token,
            );
            $data = http_build_query( $data );
            
            $url = $this->url.'/oauth2/access_token';
            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_HEADER, false );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );        
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            $json_response = curl_exec( $ch ); 
            curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close( $ch );
            
            $response = json_decode( $json_response );
            
            if ( isset( $response->access_token ) ) {
                update_option( 'cf7_insightly', $response );
            }
            
            return $response;
        }
        
        function getCustomFields( $access_token ) {
            
            $header = array(
                'Authorization: '."Bearer $access_token",
                'Content-Type: application/json',
            );
            
            $url = 'https://api.insightly.eu/customFieldDefinitions.list';
            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            $json_response = curl_exec( $ch );
            $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close( $ch );
            
            $response = json_decode( $json_response );
            
            $fields = array();
            if ( isset( $response->data ) && $response->data != null ) {
                foreach ( $response->data as $field ) {
                    if ( $field->context == 'contact' ) {
                        $fields['custom_fields###'.$field->id] = array(
                            'label'     => $field->label,
                            'type'      => $field->type,  
                            'required'  => 0,
                        );
                        
                        if ( $field->required ) {
                            $fields['custom_fields###'.$field->id]['required'] = 1;
                        }
                    }
                }
            }
            
            $response = $fields;
            
            if ( $status == 401 ) {
                $response = 0;
            }
            
            return $response;
        }
        
        function addRecord( $access_token, $module, $data, $form_id ) {
            
            $data = json_encode( $data );
            $header = array(
                'Authorization: '."Bearer $access_token",
                'Content-Type: application/json',
            );
            
            $url = 'https://api.insightly.eu/'.$module.'.add';
            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            $json_response = curl_exec( $ch );
            $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close( $ch );
            
            $response = json_decode( $json_response );
            
            if ( isset( $response->errors ) ) {
                $log = "Form ID: ".$form_id."\n";
                $log .= "Error Code: ".$status."\n";
                $log .= "Response: ".$json_response."\n";
                $log .= "Date: ".date( 'Y-m-d H:i:s' )."\n\n";

                $send_to = get_option( 'cf7_insightly_notification_send_to' );
                if ( $send_to ) {
                    $to = $send_to;
                    $subject = get_option( 'cf7_insightly_notification_subject' );
                    $body = "Form ID: ".$form_id."<br>";
                    $body .= "Error Code: ".$status."<br>";
                    $body .= "Response: ".$json_response."\n";
                    $body .= "Date: ".date( 'Y-m-d H:i:s' );
                    $headers = array(
                        'Content-Type: text/html; charset=UTF-8',
                    );
                    wp_mail( $to, $subject, $body, $headers );
                }
                
                file_put_contents( CF7_INSIGHTLY_PLUGIN_PATH.'debug.log', $log, FILE_APPEND );
            }
            
            return $response;
        }

        function updateRecord( $access_token, $module, $data, $record_id, $form_id ) {
            
            $data['id'] = $record_id;
            $data = json_encode( $data );
            $header = array(
                'Authorization: '."Bearer $access_token",
                'Content-Type: application/json',
            );
            
            $url = 'https://api.insightly.eu/'.$module.'.update';
            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            $json_response = curl_exec( $ch );
            $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close( $ch );
            
            $response = json_decode( $json_response );
            
            if ( isset( $response->errors ) ) {
                $log = "Form ID: ".$form_id."\n";
                $log .= "Error Code: ".$status."\n";
                $log .= "Response: ".$json_response."\n";
                $log .= "Date: ".date( 'Y-m-d H:i:s' )."\n\n";

                $send_to = get_option( 'cf7_insightly_notification_send_to' );
                if ( $send_to ) {
                    $to = $send_to;
                    $subject = get_option( 'cf7_insightly_notification_subject' );
                    $body = "Form ID: ".$form_id."<br>";
                    $body .= "Error Code: ".$status."<br>";
                    $body .= "Response: ".$json_response."\n";
                    $body .= "Date: ".date( 'Y-m-d H:i:s' );
                    $headers = array(
                        'Content-Type: text/html; charset=UTF-8',
                    );
                    wp_mail( $to, $subject, $body, $headers );
                }
                
                file_put_contents( CF7_INSIGHTLY_PLUGIN_PATH.'debug.log', $log, FILE_APPEND );
            }
            
            return $response;
        }
        
        function getRecords( $access_token, $module, $email ) {
            
            $data = array(
                'filter' => array(
                    'email' => array(
                        'type'  => 'primary',
                        'email' => $email,
                    ),
                ),
            );
            
            $data = json_encode( $data );
            $header = array(
                'Authorization: '."Bearer $access_token",
                'Content-Type: application/json',
            );
            
            $url = 'https://api.insightly.eu/'.$module.'.list';
            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            $json_response = curl_exec( $ch );
            $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close( $ch );
            
            $response = json_decode( $json_response );

            return $response;
        }
    }
}