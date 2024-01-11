<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

/*
 * This is a function that creates admin menu.
 */
if ( ! function_exists( 'cf7_insightly_main_menu' ) ) {
    add_action( 'admin_menu', 'cf7_insightly_main_menu' );
    function cf7_insightly_main_menu() {
        
        add_menu_page( esc_html__( 'Contact Form 7 - Insightly CRM Integration', 'cf7_insightly' ), esc_html__( 'CF7 - Insightly', 'cf7_insightly' ), 'manage_options', 'cf7_insightly_integration', 'cf7_insightly_integration_callback', 'dashicons-migrate' );
        add_submenu_page( 'cf7_insightly_integration', esc_html__( 'CF7 - Insightly: Integration', 'cf7_insightly' ), esc_html__( 'Integration', 'cf7_insightly' ), 'manage_options', 'cf7_insightly_integration', 'cf7_insightly_integration_callback' );
        add_submenu_page( 'cf7_insightly_integration', esc_html__( 'CF7 - Insightly: Configuration', 'cf7_insightly' ), esc_html__( 'Configuration', 'cf7_insightly' ), 'manage_options', 'cf7_insightly_configuration', 'cf7_insightly_configuration_callback' );
        add_submenu_page( 'cf7_insightly_integration', esc_html__( 'CF7 - Insightly: API Error Logs', 'cf7_insightly' ), esc_html__( 'API Error Logs', 'cf7_insightly' ), 'manage_options', 'cf7_insightly_api_error_logs', 'cf7_insightly_api_error_logs_callback' );
        add_submenu_page( 'cf7_insightly_integration', esc_html__( 'CF7 - Insightly: Settings', 'cf7_insightly' ), esc_html__( 'Settings', 'cf7_insightly' ), 'manage_options', 'cf7_insightly_settings', 'cf7_insightly_settings_callback' );
            }
}

/*
 * This is a function for configuration.
 */
if ( ! function_exists( 'cf7_insightly_configuration_callback' ) ) {
    function cf7_insightly_configuration_callback() {
        
        if ( isset( $_REQUEST['submit'] ) ) {
            $client_id = $_REQUEST['cf7_insightly_client_id'];
            $client_secret = $_REQUEST['cf7_insightly_client_secret'];
            
            update_option( 'cf7_insightly_client_id', $client_id );
            update_option( 'cf7_insightly_client_secret', $client_secret );
            
            
        } else if ( isset( $_REQUEST['code'] ) ) {
            $client_id = get_option( 'cf7_insightly_client_id' );
            $client_secret = get_option( 'cf7_insightly_client_secret' );
            $code = $_REQUEST['code'];
            $redirect_uri = menu_page_url( 'cf7_insightly_configuration', 0 );
            $teamleader = new CF7_TL_API( 'https://app.teamleader.eu', $client_id, $client_secret );
            $token = $teamleader->getToken( $code, $redirect_uri );
            if ( isset( $token->errors ) ) {
                ?>
                    <div class="notice notice-error is-dismissible">
                        <p><strong><?php esc_html_e( 'Error', 'cf7_insightly' ); ?></strong>: <?php echo json_encode( $token->errors ); ?></p>
                    </div>
                <?php
            } else {
                update_option( 'cf7_insightly', $token );
                $redirect_uri = menu_page_url( 'cf7_insightly_integration', 0 );
                ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e( 'Configuration successful.', 'cf7_insightly' ); ?></p>
                    </div>
                    <script type="text/javascript">
                        jQuery( document ).ready( function( $ ) {
                            window.setTimeout(function(){
                                window.location.replace( '<?php echo $redirect_uri; ?>' );
                            }, 3000);
                        });
                    </script>
                <?php
            }
        }
        
        $client_id = get_option( 'cf7_insightly_client_id' );
        $client_secret = get_option( 'cf7_insightly_client_secret' );
        ?>
        <div class="wrap">                
            <h1><?php esc_html_e( 'Insightly CRM Configuration', 'cf7_insightly' ); ?></h1>
            <hr>
            <?php
            $licence = get_site_option( 'cf7_insightly_licence' , true );
            if ( $licence ) {
            ?>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label><?php esc_html_e( 'Client ID', 'cf7_insightly' ); ?> <span class="description">(required)</span></label></th>
                            <td>
                                <input class="regular-text" type="text" name="cf7_insightly_client_id" value="<?php echo $client_id; ?>" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php esc_html_e( 'Client Secret', 'cf7_insightly' ); ?> <span class="description">(required)</span></label></th>
                            <td>
                                <input class="regular-text" type="text" name="cf7_insightly_client_secret" value="<?php echo $client_secret; ?>" required />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p><input type='submit' class='button-primary' name="submit" value="<?php esc_html_e( 'Authorize', 'cf7_insightly' ); ?>" /></p>
            </form>
            <?php
            } else {
                ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php esc_html_e( 'Please verify purchase code.', 'cf7_insightly' ); ?></p>
                    </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
}

/*
 * This is a function for integration.
 */
if ( ! function_exists( 'cf7_insightly_integration_callback' ) ) {
    function cf7_insightly_integration_callback() {
        
        ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Insightly CRM Integration', 'cf7_insightly' ); ?></h1>
                <hr>
                <?php
                $licence = get_site_option( 'cf7_insightly_licence' , true );
                if ( $licence ) {
                    if ( isset( $_REQUEST['id'] ) ) {
                        $id = (int) $_REQUEST['id'];
                        $form_id = $id;
                        if ( isset( $_POST['submit'] ) ) {
                            update_post_meta( $id, 'cf7_insightly', $_POST['cf7_insightly'] );
                            update_post_meta( $id, 'cf7_insightly_fields', $_POST['cf7_insightly_fields'] );
                            $action = sanitize_text_field( $_POST['cf7_insightly_action'] );
                            update_option( 'cf7_insightly_action_'.$form_id, $action );
                            ?>
                                <div class="notice notice-success is-dismissible">
                                    <p><?php esc_html_e( 'Integration settings saved.', 'cf7_insightly' ); ?></p>
                                </div>
                            <?php
                        } else if ( isset( $_POST['filter'] ) ) { 
                            update_post_meta( $id, 'cf7_insightly_module', $_POST['cf7_insightly_module'] );
                        }

                        $cf7_insightly_module = get_post_meta( $id, 'cf7_insightly_module', true );
                        $cf7_insightly = get_post_meta( $id, 'cf7_insightly', true );
                        $cf7_insightly_fields = get_post_meta( $id, 'cf7_insightly_fields', true );
                        $action = get_option( 'cf7_insightly_action_'.$form_id );
                        if ( ! $action ) {
                            $action = 'create';
                        }
                        
                        ?>
                        <p style="font-size: 17px;"><strong><?php esc_html_e( 'Form Name', 'cf7_insightly' ); ?>:</strong> <?php echo get_the_title( $form_id ); ?></p>
                        <hr>
                        <form method="post">
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e( 'Module', 'cf7_insightly' ); ?></label></th>
                                        <td>
                                            <select name="cf7_insightly_module">
                                                <option value=""><?php esc_html_e( 'Select an module', 'cf7_insightly' ); ?></option>
                                                <?php
                                                    $modules = unserialize( get_option( 'cf7_insightly_modules' ) );

                                                    if ( ! is_array( $modules ) ) {
                                                          $modules = array(); 
                                                      }
                                                    foreach ( $modules as $key => $value ) {
                                                        $selected = '';
                                                        if ( $key == $cf7_insightly_module ) {
                                                            $selected = ' selected="selected"';
                                                        }
                                                        ?>
                                                            <option value="<?php echo $key; ?>"<?php echo $selected; ?>><?php echo $value; ?></option>
                                                        <?php
                                                    }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Filter module fields', 'cf7_insightly' ); ?></th>
                                        <td><button type="submit" name="filter" class='button-secondary'><?php esc_html_e( 'Filter', 'cf7_insightly' ); ?></button></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e( 'Insightly CRM Integration?', 'cf7_insightly' ); ?></label></th>
                                        <td>
                                            <input type="hidden" name="cf7_insightly" value="0" />
                                            <input type="checkbox" name="cf7_insightly" value="1"<?php echo ( $cf7_insightly ? ' checked' : '' ); ?> />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e( 'Action Event', 'cf7_insightly' ); ?></label></th>
                                        <td>
                                            <fieldset>
                                                <label><input type="radio" name="cf7_insightly_action" value="create"<?php echo ( $action == 'create' ? ' checked="checked"' : '' ); ?> /> <?php esc_html_e( 'Create Module Record', 'cf7_insightly' ); ?></label>&nbsp;&nbsp;
                                                <label><input type="radio" name="cf7_insightly_action" value="create_or_update"<?php echo ( $action == 'create_or_update' ? ' checked="checked"' : '' ); ?> /> <?php esc_html_e( 'Create/Update Module Record', 'cf7_insightly' ); ?></label>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <?php
                                $_form = get_post_meta( $id, '_form', true );
                                if ( $_form ) {
                                    preg_match_all( '#\[(.*?)\]#', $_form, $matches );
                                    $cf7_fields = array();
                                    if ( $matches != null ) {
                                        foreach ( $matches[1] as $match ) {
                                            $match_explode = explode( ' ', $match );
                                            $field_type = str_replace( '*', '', $match_explode[0] );
                                            if ( $field_type != 'submit' ) {
                                                if ( isset( $match_explode[1] ) ) {
                                                    $cf7_fields[$match_explode[1]] = array(
                                                        'key'   => $match_explode[1],
                                                        'type'  => $field_type,
                                                    );
                                                }
                                            }
                                        }

                                        if ( $cf7_fields != null ) {
                                            ?>
                                                <table class="widefat striped">
                                                    <thead>
                                                        <tr>
                                                            <th><?php esc_html_e( 'Contact Form 7 Form Field', 'cf7_insightly' ); ?></th>
                                                            <th><?php esc_html_e( 'Insightly CRM Module Field', 'cf7_insightly' ); ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tfoot>
                                                        <tr>
                                                            <th><?php esc_html_e( 'Contact Form 7 Form Field', 'cf7_insightly' ); ?></th>
                                                            <th><?php esc_html_e( 'Insightly CRM Module Field', 'cf7_insightly' ); ?></th>
                                                        </tr>
                                                    </tfoot>
                                                    <tbody>
                                                        <?php
                                                            $cf7_insightly_modules_fields = get_option( 'cf7_insightly_modules_fields' );
                                                            if ( $cf7_insightly_modules_fields ) {
                                                                $cf7_insightly_modules_fields = unserialize( $cf7_insightly_modules_fields );
                                                            }
                                                            
                                                            $fields = ( isset( $cf7_insightly_modules_fields[$cf7_insightly_module] ) ? $cf7_insightly_modules_fields[$cf7_insightly_module] : array() );
                                                            if ( ! is_array( $fields ) ) {
                                                                $fields = array();
                                                            } else {
                                                                $fields['addresses###primary###line_1']['label'] = 'Primary Address Street';
                                                                $fields['addresses###invoicing###line_1']['label'] = 'Invoicing Address Street';
                                                                $fields['addresses###delivery###line_1']['label'] = 'Delivery Address Street';
                                                                $fields['addresses###visiting###line_1']['label'] = 'Visiting Address Street';
                                                                unset( $fields['addresses###invoicing###addressee'] );
                                                                unset( $fields['addresses###delivery###addressee'] );
                                                                unset( $fields['addresses###visiting###addressee'] );
                                                                asort( $fields );
                                                            }
                                                            
                                                            foreach ( $cf7_fields as $cf7_field_key => $cf7_field_value ) {
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $cf7_field_key; ?></td>
                                                                        <td>
                                                                            <select name="cf7_insightly_fields[<?php echo $cf7_field_key; ?>][key]">
                                                                                <option value=""><?php esc_html_e( 'Select a field', 'cf7_insightly' ); ?></option>
                                                                                <?php
                                                                                    $type = '';
                                                                                    if ( $fields != null ) {
                                                                                        foreach ( $fields as $field_key => $field_value ) {
                                                                                            $selected = '';
                                                                                            if ( isset( $cf7_insightly_fields[$cf7_field_key]['key'] ) && $cf7_insightly_fields[$cf7_field_key]['key'] == $field_key ) {
                                                                                                $selected = ' selected="selected"';
                                                                                                $type = $field_value['type'];
                                                                                            }
                                                                                            ?><option value="<?php echo $field_key; ?>"<?php echo $selected; ?>> <?php echo $field_value['label']; ?>(<?php esc_html_e( 'Data Type:', 'cf7_insightly' ); ?><?php echo isset($field_value['type']) ? $field_value['type'] : ''; ?><?php echo (isset($field_value['required']) && $field_value['required']) ? esc_html__( ' and Field: required', 'cf7_insightly' ) : ''; ?>)</option><?php
                                                                                        }
                                                                                    }
                                                                                ?>
                                                                            </select>
                                                                            <input type="hidden" name="cf7_insightly_fields[<?php echo $cf7_field_key; ?>][type]" value="<?php echo $type; ?>" />
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                            }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            <?php
                                        }
                                    }
                                }
                            ?>
                            <p>
                                <input type='submit' class='button-primary' name="submit" value="<?php esc_html_e( 'Save Changes', 'cf7_insightly' ); ?>" />
                            </p>
                        </form>
                        <?php
                    } else {
                        $client_id = get_option( 'cf7_insightly_client_id' );
                        if ( $client_id ) {
                            $client_secret = get_option( 'cf7_insightly_client_secret' );
                            $teamleader = new CF7_TL_API( 'https://app.teamleader.eu', $client_id, $client_secret );
                            $token = get_option( 'cf7_insightly' );
                            $custom_fields = $teamleader->getCustomFields( $token->access_token );
                            if ( ! $custom_fields ) {
                                $teamleader->getRefreshToken( $token );
                                $token = get_option( 'cf7_insightly' );
                                $custom_fields = $teamleader->getCustomFields( $token->access_token );
                            }
                            
                            $fields = get_option( 'cf7_insightly_modules_fields' );
                            if ( $fields ) {
                                $fields = unserialize( $fields );
                                $contact_fields = ( isset( $fields['contacts'] ) ? $fields['contacts'] : array() );
                            } else {
                                $fields = array();
                            }
                            
                            if ( $custom_fields != null ) {
                                $fields['contacts'] = array_merge( $contact_fields, $custom_fields );
                            }
                            
                            $contact_all_fields = serialize( $fields );
                            update_option( 'cf7_insightly_modules_fields', $contact_all_fields );
                        }
                        
                        ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'insightly', 'cf7_insightly' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'cf7_insightly' ); ?></th>       
                                    <th><?php esc_html_e( 'Action', 'cf7_insightly' ); ?></th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th><?php esc_html_e( 'insightly', 'cf7_insightly' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'cf7_insightly' ); ?></th>       
                                    <th><?php esc_html_e( 'Action', 'cf7_insightly' ); ?></th>
                                </tr>
                            </tfoot>
                            <tbody>
                              <?php
$args = array(
    'post_type'         => 'wpcf7_contact_form',
    'order'             => 'ASC',
    'posts_per_page'    => -1,
);

$forms = new WP_Query( $args );
if ( $forms->have_posts() ) {
    while ( $forms->have_posts() ) {
        $forms->the_post();
        ?>
        <tr>
            <td><?php echo get_the_title(); ?></td>
            <td><?php echo ( get_post_meta( get_the_ID(), 'cf7_insightly', true ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>' ); ?></td>
            <td><a href="<?php echo menu_page_url( 'cf7_insightly_integration', 0 ); ?>&id=<?php echo get_the_ID(); ?>"><span class="dashicons dashicons-edit"></span></a></td>
        </tr>
        <?php
    }
} else {
    ?>
    <tr>
        <td colspan="3"><?php esc_html_e( 'No forms found.', 'cf7_insightly' ); ?></td>
    </tr>
    <?php
}

wp_reset_postdata();
?>

                            </tbody>
                        </table>
                        <?php
                    }
                } else {
                    ?>
                        <div class="notice notice-error is-dismissible">
                            <p><?php esc_html_e( 'Please verify purchase code.', 'cf7_insightly' ); ?></p>
                        </div>
                    <?php
                }
                ?>
            </div>
        <?php
    }
}

if ( ! function_exists( 'cf7_insightly_api_error_logs_callback' ) ) {
    function cf7_insightly_api_error_logs_callback() {
        
        $file_path = CF7_INSIGHTLY_PLUGIN_PATH.'debug.log';
        if ( isset( $_POST['submit'] ) ) {
            $file = fopen( $file_path, 'w' );
            fclose( $file );
        }
        
        $licence = get_site_option( 'cf7_insightly_licence' , true );
        ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Insightly CRM API Error Logs', 'cf7_insightly' ); ?></h1>
                <hr>
                <?php
                    if ( $licence ) {
                        $file = fopen( $file_path, 'r' );
                            $file_size = filesize( $file_path );
                            if ( $file_size ) {
                                $file_data = fread( $file, $file_size );
                                if ( $file_data ) {
                                    echo '<pre style="overflow: scroll;">'; print_r( $file_data ); echo '</pre>';
                                    ?>
                                        <form method="post">
                                            <p>
                                                <input type='submit' class='button-primary' name="submit" value="<?php esc_html_e( 'Clear API Error Logs', 'cf7_insightly' ); ?>" />
                                            </p>
                                        </form>
                                    <?php
                                }
                            } else {
                                ?><p><?php esc_html_e( 'No API error logs found.', 'cf7_insightly' ); ?></p><?php
                            }
                        fclose( $file );
                    } else {
                        ?>
                            <div class="notice notice-error is-dismissible">
                                <p><?php esc_html_e( 'Please verify purchase code.', 'cf7_insightly' ); ?></p>
                            </div>
                        <?php
                    }
                ?>
            </div>
        <?php
    }
}

if ( ! function_exists( 'cf7_insightly_settings_callback' ) ) {
    function cf7_insightly_settings_callback() {
        
        if ( isset( $_POST['submit'] ) ) {
            $notification_subject = sanitize_text_field( $_POST['cf7_insightly_notification_subject'] );
            update_option( 'cf7_insightly_notification_subject', $notification_subject );
            
            $notification_send_to = sanitize_text_field( $_POST['cf7_insightly_notification_send_to'] );
            update_option( 'cf7_insightly_notification_send_to', $notification_send_to );
            
            $uninstall = (int) $_POST['cf7_insightly_uninstall'];
            update_option( 'cf7_insightly_uninstall', $uninstall );

            ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Settings saved.', 'cf7_insightly' ); ?></p>
                </div>
            <?php
        }
        
        $notification_subject = get_option( 'cf7_insightly_notification_subject' );
        if ( ! $notification_subject ) {
            $notification_subject = esc_html__( 'API Error Notification', 'cf7_insightly' );
        }
        $notification_send_to = get_option( 'cf7_insightly_notification_send_to' );
        $uninstall = get_option( 'cf7_insightly_uninstall' );
        $licence = get_site_option( 'cf7_insightly_licence' , true );
        ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Settings', 'cf7_insightly' ); ?></h1>
                <hr>
                <?php
                    if ( $licence ) {
                        ?>
                            <form method="post">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><label><?php esc_html_e( 'API Error Notification', 'cf7_insightly' ); ?></label></th>
                                            <td>
                                                <label><?php esc_html_e( 'Subject', 'cf7_insightly' ); ?></label><br>
                                                <input class="regular-text" type="text" name="cf7_insightly_notification_subject" value="<?php echo $notification_subject; ?>" />
                                                <p class="description"><?php esc_html_e( 'Enter the subject.', 'cf7_insightly' ); ?></p><br><br>
                                                <label><?php esc_html_e( 'Send To', 'cf7_insightly' ); ?></label><br>
                                                <input class="regular-text" type="text" name="cf7_insightly_notification_send_to" value="<?php echo $notification_send_to; ?>" />
                                                <p class="description"><?php esc_html_e( 'Enter the email address. For multiple email addresses, you can add email address by comma separated.', 'cf7_insightly' ); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label><?php esc_html_e( 'Delete data on uninstall?', 'cf7_insightly' ); ?></label></th>
                                            <td>
                                                <input type="hidden" name="cf7_insightly_uninstall" value="0" />
                                                <input type="checkbox" name="cf7_insightly_uninstall" value="1"<?php echo ( $uninstall ? ' checked' : '' ); ?> />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p>
                                    <input type='submit' class='button-primary' name="submit" value="<?php esc_html_e( 'Save Changes', 'cf7_insightly' ); ?>" />
                                </p>
                            </form>
                        <?php
                    } else {
                        ?>
                            <div class="notice notice-error is-dismissible">
                                <p><?php esc_html_e( 'Please verify purchase code.', 'cf7_insightly' ); ?></p>
                            </div>
                        <?php
                    }
                ?>
            </div>
        <?php
    }
}