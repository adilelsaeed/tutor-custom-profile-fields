<?php
/**
 * Plugin Name: TutorLMS Custom Profile Fields
 * Plugin URI: https://adilelsaeed.com/plugins/tutor-custom-profile-fields/
 * Description: Allows admins to add custom profile fields to TutorLMS profiles.
 * Author: Adil Elsaeed
 * Version: 0.1.0
 * Author URI: https://adilelsaeed.com
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 6.7
 * License: GPLv2 or later
 * Text Domain: tutor-custom-profile-fields
 *
 * @package TutorCPF
 */


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Tutor_Custom_Profile_Fields {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ], 100 );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'tutor_profile_edit_input_after', [ $this, 'display_custom_fields' ], 10 );
        add_action( 'tutor_profile_update_after', [ $this, 'save_custom_profile_fields' ], 10, 1 );
    }

    public function add_settings_page() {
        add_submenu_page(
            'tutor', // Parent slug (TutorLMS menu).
            __( 'Custom Profile Fields', 'tutor-custom-profile-fields' ), // Page title.
            __( 'Custom Profile Fields', 'tutor-custom-profile-fields' ), // Menu title.
            'manage_options', // Capability required to access.
            'tutor-custom-fields', // Menu slug.
            [ $this, 'settings_page_html' ] // Callback function to render the page.
        );
    }

    public function register_settings() {
        register_setting( 'tutor_custom_fields', 'tutor_custom_profile_fields', [
            'type' => 'array',
            'sanitize_callback' => [ $this, 'sanitize_custom_fields' ],
            'default' => [],
        ] );
    }

    public function sanitize_custom_fields( $fields ) {
        return array_map( function( $field ) {
            return [
                'label' => sanitize_text_field( $field['label'] ),
                'type'  => sanitize_text_field( $field['type'] ),
                'meta_key' => sanitize_key( $field['meta_key'] ),
            ];
        }, $fields );
    }
    

    public function settings_page_html() {
        $fields = get_option( 'tutor_custom_profile_fields', [] );
        ?>
        <div class="wrap">
            <h1><?php _e( 'Tutor Custom Profile Fields', 'tutor-custom-profile-fields' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'tutor_custom_fields' );
                do_settings_sections( 'tutor_custom_fields' );
                ?>
                <table class="form-table">
                    <thead>
                        <tr>
                            <th><?php _e( 'Field Label', 'tutor-custom-profile-fields' ); ?></th>
                            <th><?php _e( 'Field Type', 'tutor-custom-profile-fields' ); ?></th>
                            <th><?php _e( 'Meta Key (ID)', 'tutor-custom-profile-fields' ); ?></th>
                            <th><?php _e( 'Actions', 'tutor-custom-profile-fields' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="custom-fields-list">
                        <?php foreach ( $fields as $index => $field ): ?>
                            <tr>
                                <td><input type="text" name="tutor_custom_profile_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" required></td>
                                <td>
                                    <select name="tutor_custom_profile_fields[<?php echo $index; ?>][type]" required>
                                        <option value="text" <?php selected( $field['type'], 'text' ); ?>><?php _e( 'Text', 'tutor-custom-profile-fields' ); ?></option>
                                        <option value="textarea" <?php selected( $field['type'], 'textarea' ); ?>><?php _e( 'Textarea', 'tutor-custom-profile-fields' ); ?></option>
                                    </select>
                                </td>
                                <td><input type="text" name="tutor_custom_profile_fields[<?php echo $index; ?>][meta_key]" value="<?php echo esc_attr( $field['meta_key'] ); ?>" required></td>
                                <td><button type="button" class="remove-field"><?php _e( 'Remove', 'tutor-custom-profile-fields' ); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" id="add-new-field"><?php _e( 'Add New Field', 'tutor-custom-profile-fields' ); ?></button>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            (function($){
                $('#add-new-field').on('click', function() {
                    const index = $('#custom-fields-list tr').length;
                    $('#custom-fields-list').append(`
                        <tr>
                            <td><input type="text" name="tutor_custom_profile_fields[${index}][label]" required></td>
                            <td>
                                <select name="tutor_custom_profile_fields[${index}][type]" required>
                                    <option value="text"><?php _e( 'Text', 'tutor-custom-profile-fields' ); ?></option>
                                    <option value="textarea"><?php _e( 'Textarea', 'tutor-custom-profile-fields' ); ?></option>
                                </select>
                            </td>
                            <td><input type="text" name="tutor_custom_profile_fields[${index}][meta_key]" required></td>
                            <td><button type="button" class="remove-field"><?php _e( 'Remove', 'tutor-custom-profile-fields' ); ?></button></td>
                        </tr>
                    `);
                });
    
                $('#custom-fields-list').on('click', '.remove-field', function() {
                    $(this).closest('tr').remove();
                });
            })(jQuery);
        </script>
        <?php
    }
    

    public function display_custom_fields(  ) {
        $fields = get_option( 'tutor_custom_profile_fields', [] );
    
        if ( empty( $fields ) ) {
            return;
        }
        foreach ( $fields as $field ) {
            
            $meta_key = isset( $field['meta_key'] );
            $value = get_user_meta( get_current_user_id(), $field['meta_key'], true );
    
            echo '<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6 tutor-mb-32">';
            echo '<label class="tutor-form-label tutor-color-secondary" for="' . esc_attr( $field['meta_key'] ) . '">' . esc_html( $field['label'] ) . '</label>';
    
            if ( $field['type'] === 'textarea' ) {
                echo '<textarea class="tutor-form-control" id="' . esc_attr( $field['meta_key'] ) . '" name="' . esc_attr( $field['meta_key'] ) . '">' . esc_textarea( $value ) . '</textarea>';
            } else {
                echo '<input class="tutor-form-control" type="text" id="' . esc_attr( $field['meta_key'] ) . '" name="' . esc_attr( $field['meta_key'] ) . '" value="' . esc_attr( $value ) . '">';
            }
    
            echo '</div>';
        }
    }
    
    

    /**
     * Save custom profile fields using TutorLMS hook
     *
     * @param int $user_id The ID of the user being updated.
     */
    public function save_custom_profile_fields( $user_id ) {
        $fields = get_option( 'tutor_custom_profile_fields', [] );
        

        if ( empty( $fields ) ) {
            return; // No custom fields to save.
        }

        foreach ( $fields as $field ) {

            // Fetch the field value using TutorLMS utils.
            $value = sanitize_text_field( tutor_utils()->input_old(   $field['meta_key']  ) );
            update_user_meta( $user_id, $field['meta_key'], $value );

        }
    }

    
    
}

new Tutor_Custom_Profile_Fields();
