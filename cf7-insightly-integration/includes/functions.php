<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

/*
 * This is a function that integrate form.
 * $cf7 variable return current form data.
 */
if ( ! function_exists( 'cf7_insightly_integration' ) ) {
    add_action( 'wpcf7_before_send_mail', 'cf7_insightly_integration', 20, 1 );
    function cf7_insightly_integration( $cf7 ) {
        
        $submission = WPCF7_Submission::get_instance();
        if ( $submission ) {
          $request = $submission->get_posted_data();     
        }
        
        $form_id = 0;
        if ( isset( $request['_wpcf7'] ) ) {
            $form_id = (int) $request['_wpcf7'];
        } else if ( isset( $_POST['_wpcf7'] ) ) {
            $form_id = (int) $_POST['_wpcf7'];
        } else {
            //
        }
        
        if ( $form_id ) {
            $cf7_insightly = get_post_meta( $form_id, 'cf7_insightly', true );
            if ( $cf7_insightly ) {
                $cf7_insightly_fields = get_post_meta( $form_id, 'cf7_insightly_fields', true );
                if ( $cf7_insightly_fields != null ) {
                    $data = array();
                    $email = '';
                    foreach ( $cf7_insightly_fields as $cf7_insightly_field_key => $cf7_insightly_field ) {
                        if ( isset( $cf7_insightly_field['key'] ) && $cf7_insightly_field['key'] ) {
                            if ( is_array( $request[$cf7_insightly_field_key] ) ) {
                                $request[$cf7_insightly_field_key] = implode( ', ', $request[$cf7_insightly_field_key] );
                            }
                            
                            if ( strpos( $cf7_insightly_field['key'], '###' ) !== false ) {
                                $cf7_insightly_field_data = explode( '###', $cf7_insightly_field['key'] );
                                if ( $cf7_insightly_field_data[0] == 'emails' ) {
                                    $data['emails'][] = array(
                                        'email' => strip_tags( $request[$cf7_insightly_field_key] ),
                                        'type'  => $cf7_insightly_field_data[1],
                                    );
                                    $email = strip_tags( $request[$cf7_insightly_field_key] );
                                } else if ( $cf7_insightly_field_data[0] == 'telephones' ) {
                                    $data['telephones'][] = array(
                                        'number'    => strip_tags( $request[$cf7_insightly_field_key] ),
                                        'type'      => $cf7_insightly_field_data[1],
                                    );
                                } else if ( $cf7_insightly_field_data[0] == 'addresses' ) {
                                    $data['addresses'][$cf7_insightly_field_data[1]]['type'] = $cf7_insightly_field_data[1];
                                    $data['addresses'][$cf7_insightly_field_data[1]]['address'][$cf7_insightly_field_data[2]] = strip_tags( $request[$cf7_insightly_field_key] );
                                } else if ( $cf7_insightly_field_data[0] == 'custom_fields' ) {
                                    $data['custom_fields'][] = array(
                                        'value' => strip_tags( $request[$cf7_insightly_field_key] ),
                                        'id'    => $cf7_insightly_field_data[1],
                                    );
                                }
                            } else {
                                $data[$cf7_insightly_field['key']] = strip_tags( $request[$cf7_insightly_field_key] );
                            }
                        }
                    }
                    
                    if ( isset( $data['addresses'] ) ) {
                        $addresses_data = array();
                        foreach ( $data['addresses'] as $addresses ) {
                            $addresses_data[] = $addresses;
                        }

                        $data['addresses'] = $addresses_data;
                    }
                    
                    if ( isset( $data['tags'] ) && $data['tags'] ) {
                        $tags = explode( ',', $data['tags'] );
                        if ( $tags != null ) {
                            $data['tags'] = $tags;
                        }
                    }
                    
                    if ( $data != null ) {
                        $client_id = get_option( 'cf7_insightly_client_id' );
                        $client_secret = get_option( 'cf7_insightly_client_secret' );
                        $insightly = new CF7_INSIGHTLY_API( 'https://app.insightly.eu', $client_id, $client_secret );
                        $token = get_option( 'cf7_insightly' );
                        $insightly->getRefreshToken( $token );
                        $token = get_option( 'cf7_insightly' );
                        $module = get_post_meta( $form_id, 'cf7_insightly_module', true );
                        $action = get_option( 'cf7_insightly_action_'.$form_id );
                        if ( ! $action ) {
                            $action = 'create';
                        }
                        
                        if ( $action == 'create' ) {
                            $insightly->addRecord( $token->access_token, $module, $data, $form_id );
                        } else if ( $action == 'create_or_update' ) {
                            if ( $email ) {
                                $records = $insightly->getRecords( $token->access_token, $module, $email );
                                if ( isset( $records->data ) && $records->data != null ) {
                                    foreach ( $records->data as $record ) {
                                        $record_id = $record->id;
                                        $insightly->updateRecord( $token->access_token, $module, $data, $record_id, $form_id );
                                    }
                                } else {
                                    $insightly->addRecord( $token->access_token, $module, $data, $form_id );
                                }
                            } else {
                                $insightly->addRecord( $token->access_token, $module, $data, $form_id );
                            }
                        } else {
                            // nothing
                        }
                    }
                }
            }
        }
    }
}