<?php
/**
 * Plugin Name:     Easy Digital Downloads - Purchase Limit
 * Plugin URI:      https://easydigitaldownloads.com/extension/purchase-limit/
 * Description:     Allows site owners to specify max purchase limits on individual products
 * Version:         1.2.17
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-purchase-limit
 *
 * @package         EDD\PurchaseLimit
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2013-2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_Purchase_Limit' ) ) {

    /**
     * Main EDD_Purchase_Limit class
     *
     * @since       1.0.0
     */
    class EDD_Purchase_Limit {

        /**
         * @var         EDD_Purchase_Limit $instance The one true EDD_Purchase_Limit
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.1
         * @return      object self::$instance The one true EDD_Purchase_Limit
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Purchase_Limit();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.9
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_PURCHASE_LIMIT_VERSION', '1.2.17' );

            // Plugin path
            define( 'EDD_PURCHASE_LIMIT_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_PURCHASE_LIMIT_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.9
         * @return      void
         */
        private function includes() {
            // Include scripts
            require_once EDD_PURCHASE_LIMIT_DIR . 'includes/scripts.php';
            require_once EDD_PURCHASE_LIMIT_DIR . 'includes/functions.php';
            require_once EDD_PURCHASE_LIMIT_DIR . 'includes/shortcodes.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.1
         * @return      void
         */
        private function hooks() {
            // Register settings
            add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

            // Handle licensing
            if( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, 'Purchase Limit', EDD_PURCHASE_LIMIT_VERSION, 'Daniel J Griffiths' );
            }

            // Add default purchase limit field to downloads config metabox
            add_action( 'edd_meta_box_fields', array( $this, 'pl_metabox_row' ), 20 );
            add_action( 'edd_meta_box_fields', array( $this, 'date_metabox_row' ), 20 );

            // Add header to variable pricing table
            add_action( 'edd_download_price_table_head', array( $this, 'price_header' ), 10 );

            // Add options to variable pricing table
            add_action( 'edd_download_price_table_row', array( $this, 'price_row' ), 20, 3 );

            // Add variable pricing global disable field
            add_action( 'edd_after_price_field', array( $this, 'variable_disable' ), 20, 1 );

            // Add purchase limit to saved fields
            add_filter( 'edd_metabox_fields_save', array( $this, 'save_fields' ) );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_PURCHASE_LIMIT_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_purchase_limit_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-purchase-limit' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-purchase-limit', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-purchase-limit/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-purchase-limit/ folder
                load_textdomain( 'edd-purchase-limit', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-purchase-limit/languages/ folder
                load_textdomain( 'edd-purchase-limit', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-purchase-limit', false, $lang_dir );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'edd_purchase_limit_settings',
                    'name'  => '<strong>' . __( 'Purchase Limit Settings', 'edd-purchase-limit' ) . '</strong>',
                    'desc'  => __( 'Configure Purchase Limit Settings', 'edd-purchase-limit' ),
                    'type'  => 'header',
                ),
                array(
                    'id'    => 'edd_purchase_limit_sold_out_label',
                    'name'  => __( 'Sold Out Button Label', 'edd-purchase-limit' ),
                    'desc'  => __( 'Enter the text you want to use for the button on sold out items', 'edd-purchase-limit' ),
                    'type'  => 'text',
                    'size'  => 'regular',
                    'std'   => __( 'Sold Out', 'edd-purchase-limit' )
                ),
                array(
                    'id'    => 'edd_purchase_limit_scope',
                    'name'  => __( 'Scope', 'edd-purchase-limit' ),
                    'desc'  => __( 'Choose whether you want purchase limits to apply site-wide or per-user', 'edd-purchase-limit' ),
                    'type'  => 'select',
                    'options'   => array(
                        'site-wide' => __( 'Site Wide', 'edd-purchase-limit' ),
                        'per-user'  => __( 'Per User', 'edd-purchase-limit' )
                    ),
                    'std'   => 'site-wide'
                ),
                array(
                    'id'    => 'edd_purchase_limit_show_counts',
                    'name'  => __( 'Show Remaining Purchases', 'edd-purchase-limit' ),
                    'desc'  => __( 'Specify whether or not you want to display remaining purchase counts on downloads', 'edd-purchase-limit' ),
                    'type'  => 'checkbox'
                ),
                array(
                    'id'    => 'edd_purchase_limit_remaining_label',
                    'name'  => __( 'Remaining Purchases Label', 'edd-purchase-limit' ),
                    'desc'  => __( 'Enter the text you want to use for the remaining purchases label', 'edd-purchase-limit' ),
                    'type'  => 'text',
                    'size'  => 'regular',
                    'std'   => __( 'Remaining', 'edd-purchase-limit' )
                ),
                array(
                    'id'    => 'edd_purchase_limit_restrict_date',
                    'name'  => __( 'Enable Date Restriction', 'edd-purchase-limit' ),
                    'desc'  => __( 'Specify whether or not to enable restriction by date range', 'edd-purchase-limit' ),
                    'type'  => 'checkbox'
                ),
                array(
                    'id'    => 'edd_purchase_limit_g_start_date',
                    'name'  => __( 'Global Start Date', 'edd-purchase-limit' ),
                    'desc'  => __( 'Define a global start date', 'edd-purchase-limit' ),
                    'type'  => 'text',
                    'size'  => 'regular'
                ),
                array(
                    'id'    => 'edd_purchase_limit_g_end_date',
                    'name'  => __( 'Global End Date', 'edd-purchase-limit' ),
                    'desc'  => __( 'Define a global end date', 'edd-purchase-limit' ),
                    'type'  => 'text',
                    'size'  => 'regular'
                ),
                array(
                    'id'    => 'edd_purchase_limit_pre_date_label',
                    'name'  => __( 'Pre-Date Label', 'edd-purchase-limit' ),
                    'desc'  => __( 'Enter the text you want to use for items which are not yet available', 'edd-purchase-limit' ),
                    'type'  => 'text',
                    'size'  => 'regular',
                    'std'   => __( 'This product is not yet available!', 'edd-purchase-limit' )
                ),
                array(
                    'id'    => 'edd_purchase_limit_post_date_label',
                    'name'  => __( 'Post-Date Label', 'edd-purchase-limit' ),
                    'desc'  => __( 'Enter the text you want to use for items which are no longer available', 'edd-purchase-limit' ),
                    'type'  => 'text',
                    'size'  => 'regular',
                    'std'   => __( 'This product is no longer available!', 'edd-purchase-limit' )
                ),
                array(
                    'id'    => 'edd_purchase_limit_error_handler',
                    'name'  => __( 'Error Handler', 'edd-purchase-limit' ),
                    'desc'  => __( 'How should we handle non-inline errors?', 'edd-purchase-limit' ),
                    'type'  => 'select',
                    'options'   => array(
                        'std'       => __( 'Standard', 'edd-purchase-limit' ),
                        'redirect'  => __( 'Redirect', 'edd-purchase-limit' )
                    ),
                    'std'   => 'std'
                ),
                array(
                    'id'    => 'edd_purchase_limit_error_message',
                    'name'  => __( 'Error Message', 'edd-purchase-limit' ),
                    'desc'  => __( 'Enter the text you want to use for the error message', 'edd-purchase-limit' ),
                    'type'  => 'text',
                    'std'   => sprintf( __( 'This %s is sold out!', 'edd-purchase-limit' ), edd_get_label_singular( true ) )
                ),
                array(
                    'id'    => 'edd_purchase_limit_redirect_url',
                    'name'  => __( 'Error Redirect', 'edd-purchase-limit' ),
                    'desc'  => __( 'Where should we redirect on error?', 'edd-purchase-limit' ),
                    'type'  => 'select',
                    'options'   => edd_get_pages()
                )
            );

            $settings = array_merge( $settings, $new_settings );

            return $settings;
        }


        /**
         * Render the purchase limit row in the download configuration metabox
         *
         * @access      public
         * @since       1.0.0
         * @param       int $post_id The ID of this download
         * @return      void
         */
        public function pl_metabox_row( $post_id = 0 ) {
            $enabled        = edd_has_variable_prices( $post_id );
            $display        = $enabled ? ' style="display: none;"' : '';
            $purchase_limit = edd_pl_get_file_purchase_limit( $post_id, 'standard' );

            echo '<div id="edd_purchase_limit"' . $display . '>';
            echo '<p><strong>' . __( 'Purchase Limit:', 'edd-purchase-limit' ) . '</strong></p>';
            echo '<label for="edd_purchase_limit_field">';
            echo '<input type="text" name="_edd_purchase_limit" id="edd_purchase_limit_field" value="' . esc_attr( $purchase_limit ) . '" size="30" style="width: 100px;" placeholder="0" /> ';
            echo __( 'Leave blank or set to 0 for unlimited, set to -1 to mark a product as sold out.', 'edd-purchase-limit' );
            echo '</label>';
            echo '</div>';
        }


        /**
         * Render the date restriction row in the download configuration metabox
         *
         * @access      public
         * @since       1.0.6
         * @param       int $post_id The ID of this download
         * @return      void
         */
        public function date_metabox_row( $post_id = 0 ) {
            if( !edd_get_option( 'edd_purchase_limit_restrict_date' ) ) return;

            $start_date = get_post_meta( $post_id, '_edd_purchase_limit_start_date', true );
            $end_date = get_post_meta( $post_id, '_edd_purchase_limit_end_date', true );

            echo '<div id="edd_purchase_limit_date_range">';
            echo '<p><strong>' . __( 'Restrict Purchases to Date Range:', 'edd-purchase-limit' ) . '</strong></p>';
            echo '<label for="edd_purchase_limit_start_date">' . __( 'Start Date', 'edd-purchase-limit' ) . ' ';
            echo '<input type="text" name="_edd_purchase_limit_start_date" id="edd_purchase_limit_start_date" class="edd_pl_datepicker" value="' . esc_attr( $start_date ) . '" placeholder="mm/dd/yyyy" />';
            echo '</label>';
            echo '<label for="edd_purchase_limit_end_date" style="margin-left: 15px;">' . __( 'End Date', 'edd-purchase-limit' ) . ' ';
            echo '<input type="text" name="_edd_purchase_limit_end_date" id="edd_purchase_limit_end_date" class="edd_pl_datepicker" value="' . esc_attr( $end_date ) . '" placeholder="mm/dd/yyyy" />';
            echo '</label>';
            echo '</div>';
        }


        /**
         * Add the header cell to the variable pricing table
         *
         * @access      public
         * @since       1.0.4
         * @param       int $post_id The ID of this download
         * @return      void
         */
        public function price_header( $post_id = 0 ) {
            echo '<th class="edd_purchase_limit_var_title">' . __( 'Purchase Limit', 'edd-purchase-limit' ) . '</th>';
        }


        /**
         * Add the table cell to the variable pricing table
         *
         * @access      public
         * @since       1.0.4
         * @param       int $post_id The ID of this download
         * @param       int $price_key The key of this download item
         * @param       array $args Args to pass for this row
         * @return      void
         */
        public function price_row( $post_id = 0, $price_key = 0, $args = array() ) {
            $prices         = edd_get_variable_prices( $post_id );
            $purchase_limit = edd_pl_get_file_purchase_limit( $post_id, 'variable', $price_key );

            echo '<td class="edd_purchase_limit_var_field">';
            echo '<label for="edd_variable_prices[' . $price_key . '][purchase_limit]">';
            echo '<input type="text" value="' . $purchase_limit . '" id="edd_variable_prices[' . $price_key . '][purchase_limit]" name="edd_variable_prices[' . $price_key . '][purchase_limit]" style="float:left;width:100px;" placeholder="0" />';
            echo '</label>';
            echo '</td>';
        }


        /**
         * Add field to allow globally disabling product on sold out variable
         *
         * @access      public
         * @since       1.2.7
         * @param       int $post_id The ID of this post
         * @return      void
         */
        public function variable_disable( $post_id = 0 ) {
            $disabled = get_post_meta( $post_id, '_edd_purchase_limit_variable_disable', true );

            echo '<p>';
            echo '<input type="checkbox" name="_edd_purchase_limit_variable_disable" id="_edd_purchase_limit_variable_disable" value="1" ' . checked( true, $disabled, false ) . ' />&nbsp;';
            echo '<label for="_edd_purchase_limit_variable_disable">' . __( 'Disable product when any item sells out', 'edd-purchase-limit' ) . '</label>';
            echo '</p>';
        }


        /**
         * Add purchase limit to saved fields
         *
         * @access      public
         * @since       1.0.0
         * @param       array $fields The current fields EDD is saving
         * @return      array The updated fields to save
         */
        public function save_fields( $fields ) {
            $extra_fields = array(
                '_edd_purchase_limit',
                '_edd_purchase_limit_start_date',
                '_edd_purchase_limit_end_date',
                '_edd_purchase_limit_variable_disable'
            );

            return array_merge( $fields, $extra_fields );
        }
    }
}


/**
 * The main function responsible for returning the one true EDD_Purchase_Limit
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Purchase_Limit The one true EDD_Purchase_Limit
 */
function EDD_Purchase_Limit() {
    if( !class_exists( 'Easy_Digital_Downloads' ) ) {
        if( !class_exists( 'S214_EDD_Activation' ) ) {
            require_once( 'includes/class.s214-edd-activation.php' );
        }

        $activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();

        return EDD_Purchase_Limit::instance();
    } else {
        return EDD_Purchase_Limit::instance();
    }
}
add_action( 'plugins_loaded', 'EDD_Purchase_Limit' );
