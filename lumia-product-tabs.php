<?php
/**
 * Plugin Name: Lumia Product Tabs
 * Plugin URI: http://weblumia.com/lumia-product-tabs/
 * Description: Adding multiple custom tabs in woocommerce product details page.
 * Author: Jinesh.P.V
 * Author URI: http://weblumia.com
 * Version: 2.1
 * Tested up to: 4.2.2
 * Text Domain: lumia-product-tabs
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2016 Jinesh.P.V (email: jinuvijay5@gmail.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package     Lumia Product Tabs
 * @author      Jinesh.P.V
 * @category    Plugin
 * @copyright   Copyright (c) 2015-2016 Jinesh.P.V (email: jinuvijay5@gmail.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Check if WooCommerce is active and bail if it's not
if ( ! LumiaProductTabs::is_woocommerce_core_active() ) {
	return;
}

/**
 * The LumiaProductTabs global object
 * @name $lumia_product_tabs
 * @global LumiaProductTabs $GLOBALS['lumia_product_tabs']
 */
 
$GLOBALS['lumia_product_tabs'] = new LumiaProductTabs();

class LumiaProductTabs {

	private $tab_data = false;

	/**
	 * Gets things started by adding an action to initialize this plugin once
	 * WooCommerce is known to be active and initialized
	 */
	public function __construct() {
		
		/** plugin version number */
		define( 'VERSION', '.2.5' );

		/** plugin text domain */
		define( 'TEXT_DOMAIN', 'lumia_product_tabs' );

		/** plugin version name */
		define( 'VERSION_OPTION_NAME', 'lumia_product_tabs_db_version' );
	
		// Installation
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) $this->install_plugin();
		
		add_action( 'admin_head',       array( $this, 'load_woocommerce_admin_scripts' ) );
		add_action( 'init',             array( $this, 'load_text_domain' ) );
		add_action( 'woocommerce_init', array( $this, 'woocommerce_admin_init' ) );
	}
	
	/**
	 * Install woocommerce custom product tabs
	 * 
	 * @since 2.1
	 */
	 
	private function install_plugin() {

		global $wpdb;

		$installed_version = get_option( VERSION_OPTION_NAME );

		// installed version lower than plugin version?
		if ( -1 === version_compare( $installed_version, VERSION ) ) {
			// new version number
			update_option( VERSION_OPTION_NAME, VERSION );
		}
	}

	/**
	 * Load text domain for lumia-product-tabs
	 *
	 * @since 2.1
	 */
	 
	public function load_text_domain() {

		// localization
		load_plugin_textdomain( 'lumia-product-tabs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}


	/**
	 * admin init woocommerce lumia product tabs
	 * 
	 * @since 2.1
	 */
	 
	public function woocommerce_admin_init() {
		
		// backend stuff
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'lumia_product_admin_tab_html' ) );
		add_action( 'woocommerce_product_write_panels',     array( $this, 'lumia_product_admin_tab_content_html' ) );
		add_action( 'woocommerce_process_product_meta',     array( $this, 'lumia_product_tab_save_data' ), 10, 2 );

		// frontend stuff
		add_filter( 'woocommerce_product_tabs', array( $this, 'add_lumia_product_custom_tabs' ) );

		// allow the use of shortcodes within the tab content
		add_filter( 'woocommerce_custom_product_tabs_lite_content', 'do_shortcode' );
	}

	/**
	 * Add the custom product tab
	 *
	 * @since 2.1
	 */
	 
	public function add_lumia_product_custom_tabs( $tabs ) {
		
		global $product;

		if ( self::product_has_lumia_product_tabs( $product ) ) {
			foreach ( $this->tab_data as $tab ) {
				$tabs[ $tab['id'] ] = array(
					'title'    => __( $tab['tab_name'], TEXT_DOMAIN ),
					'priority' => 25,
					'callback' => array( $this, 'lumia_product_tabs_panel_content' ),
					'content'  => $tab['tab_content'], 
				);
			}
		}
		
		return $tabs;
	}


	/**
	 * lumia product tab panel content for the given tab
	 *
	 * @since 2.1
	 */
	 
	public function lumia_product_tabs_panel_content( $key, $tab ) {

		// allow shortcodes to function
		$content = apply_filters( 'the_content', $tab['content'] );
		$content = str_replace( ']]>', ']]&gt;', $content );

		echo apply_filters( 'woocommerce_custom_product_tabs_lite_heading', '<h2>' . $tab['title'] . '</h2>', $tab );
		echo apply_filters( 'woocommerce_custom_product_tabs_lite_content', $content, $tab );
	}

	/**
	 * load woocommerce admin scripts
	 * 
	 * @since 2.1
	 */
	 
	public function load_woocommerce_admin_scripts() {
		
		wp_enqueue_script( 'common-js', plugins_url( '/js/common_scripts.js', __FILE__ ), array(), '1.2.0', true );
	}

	/**
	 * Adding a new tab to the product admin interface
	 * 
	 * @since 2.1
	 */
	 
	public function lumia_product_admin_tab_html() {
		
		echo "<li class=\"product_tabs\"><a href=\"#lumia_product_tabs\">" . __( 'Product Tabs', TEXT_DOMAIN ) . "</a></li>";
	}
	
	/**
	 * Adding a new tab content to the product admin interface
	 * 
	 * @since 2.1
	 */
	 
	public function lumia_product_admin_tab_content_html() {
		
		global $post;

		// pull the custom tab data out of the database
		$lumia_product_tab = maybe_unserialize( get_post_meta( $post->ID, 'lumia_product_custom_tabs', true ) );

		if ( empty( $lumia_product_tab ) ) {
			$lumia_product_tab[] = array( 'tab_name' => '', 'tab_content' => '' );
		}
		
		$i = 0;
		echo '<div id="lumia_product_tabs" class="panel wc-metaboxes-wrapper woocommerce_options_panel">';
		
		foreach ( $lumia_product_tab as $tabObj ) {
			
			$i++;
			// display the custom tab panel
			
			echo '<div class="lumia_product_tab_inner">';
			woocommerce_wp_text_input( array( 'id' => '_wc_lumia_product_tabs_title_' . $i, 'name' => '_wc_lumia_product_tabs_title[]', 'label' => __( 'Tab Name', TEXT_DOMAIN ), 'description' => '', 'value' => $tabObj['tab_name'], 'class' => 'wc_lumia_product_tabs_title' ) );
			self::lumia_tab_content_textarea_html( array( 'id' => '_wc_lumia_product_tabs_content_' . $i, 'name' => '_wc_lumia_product_tabs_content[]', 'label' => __( 'Description', TEXT_DOMAIN ), 'value' => $tabObj['tab_content'] ) );
			echo 	'</div>';			
		}
		
		echo '<p class="action_block"><a href="javascript:;" class="button addtab button-small">Add</a>';
		
		if( count( $lumia_product_tab ) > 1 )
			echo '<a style="margin-left:10px;" href="javascript:;" class="button deletetab button-small">Delete</a>';
			
		echo '</p>
				</div>
				<style type="text/css">
				.woocommerce_options_panel .wc_lumia_product_tabs_title {
					width:100% !important;
				}
				.lumia_product_tab_inner {
					border-bottom: 1px solid #dfdfdf;
					margin-bottom:5px;
					padding-bottom:5px;
				}
			  </style>';		
	}


	/**
	 * Saves the data inputed into the product boxes, as post meta data
	 * 
	 * @since 2.1
	 */
	 
	public function lumia_product_tab_save_data( $post_id, $post ) {

		if ( empty( $_POST['_wc_lumia_product_tabs_title'] ) && empty( $_POST['_wc_lumia_product_tabs_content'] ) && get_post_meta( $post_id, 'lumia_product_custom_tabs', true ) ) {
			// clean up if the custom tabs are removed
			delete_post_meta( $post_id, 'lumia_product_custom_tabs' );
		} elseif ( ! empty( $_POST['_wc_lumia_product_tabs_title'] ) || ! empty( $_POST['_wc_lumia_product_tabs_content'] ) ) {
			$tab_data = array();

			$tab_id = '';
			if ( $_POST['_wc_lumia_product_tabs_title'] ) { 
				// save the data to the database
				for( $i = 0; $i < count( $_POST['_wc_lumia_product_tabs_title'] ) ; $i++ ) {
					
					if ( strlen( $_POST['_wc_lumia_product_tabs_title'][$i] ) != strlen( utf8_encode( $_POST['_wc_lumia_product_tabs_title'][$i] ) ) ) {
						// can't have titles with utf8 characters as it breaks the tab-switching javascript
						$tab_id = "lumia-tab-custom";
					} else {
						// convert the tab title into an id string
						echo $tab_id = strtolower( $_POST['_wc_lumia_product_tabs_title'][$i] );
						$tab_id = preg_replace( "/[^\w\s]/", '', $tab_id );
						// remove non-alphas, numbers, underscores or whitespace
						$tab_id = preg_replace( "/_+/", ' ', $tab_id );
						// replace all underscores with single spaces
						$tab_id = preg_replace( "/\s+/", '-', $tab_id );
						// replace all multiple spaces with single dashes
						$tab_id = 'lumia-tab-' . $tab_id;
						// prepend with 'lumia-tab-' string
					}
					
					$tab_data[] = array( 
										'tab_name' => $_POST['_wc_lumia_product_tabs_title'][$i], 
										'id' => $tab_id, 
										'tab_content' => $_POST['_wc_lumia_product_tabs_content'][$i]
									);
				}
				
				update_post_meta( $post_id, 'lumia_product_custom_tabs', $tab_data );
			}	
		}
	}


	private function lumia_tab_content_textarea_html( $field ) {
		
		global $post;

		$post_id = $post->ID;
		if ( ! isset( $field['value'] ) ) $field['value'] = get_post_meta( $post_id, $field['id'], true );

		echo '<p class="form-field ' . $field['id'] . '_field">
				<label style="display:block;" for="' . $field['id'] . '">' . $field['label'] . '</label>
				<textarea class="' . $field['class'] . '" name="' . $field['name'] . '" id="' . $field['id'] . '" style="width:100%;">' . esc_textarea( $field['value'] ) . '</textarea> ';
		echo '</p>';
	}


	/** Helper methods ******************************************************/


	/**
	 * Lazy-load the product_tabs meta data, and return true if it exists,
	 * false otherwise
	 *
	 * @return true if there is custom tab data, false otherwise
	 */
	 
	private function product_has_lumia_product_tabs( $product ) {
		if ( false === $this->tab_data ) {
			$this->tab_data = maybe_unserialize( get_post_meta( $product->id, 'lumia_product_custom_tabs', true ) );
		}
		// tab must at least have a title to exist
		return ! empty( $this->tab_data ) && ! empty( $this->tab_data[0] ) && ! empty( $this->tab_data[0]['tab_name'] );
	}


	/**
	 * Checks if WooCommerce is active
	 *
	 * @since  2.1
	 * @return bool true if WooCommerce is active, false otherwise
	 */
	 
	public static function is_woocommerce_core_active() {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}
