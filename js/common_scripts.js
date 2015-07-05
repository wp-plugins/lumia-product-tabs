/**
 * Plugin Name: Lumia Product Tabs
 * Plugin URI: http://weblumia.com/lumia-product-tabs/
 * Description: Adding multiple custom tabs in woocommerce product details page.
 * Author: Weblumia
 * Author URI: http://weblumia.com
 * Version: 2.0.0
 * Tested up to: 4.1
 * Text Domain: lumia-product-tabs
 * License: AGPL2
 */
 
jQuery( document ).ready(function() {
	
	/*
	*
	* jquery method for add new rows
	*
	*/
	
	jQuery( document ).on( "click", ".addtab", function( e ) {	
		var count = jQuery( '.lumia_product_tab_inner' ).length;			
		var html = '<div class="lumia_product_tab_inner"><p class="form-field _wc_lumia_product_tabs_title_' + count + '_field"><label for="_wc_lumia_product_tabs_title_' + count + '">Tab Name</label><input type="text" id="_wc_lumia_product_tabs_title_' + count + '" name="_wc_lumia_product_tabs_title[]" class="wc_lumia_product_tabs_title"></p><p class="form-field _wc_lumia_product_tabs_content_' + count + '_field"><label for="_wc_lumia_product_tabs_content_' + count + '" style="display:block;">Description</label><textarea style="width:100%;" id="_wc_lumia_product_tabs_content_' + count + '" name="_wc_lumia_product_tabs_content[]" class=""></textarea></p></div>';
		newRow = jQuery( html ).insertBefore( ".action_block" ); 
		jQuery( ".action_block" ).html( '<a href="javascript:;" class="button addtab button-small" style="margin-right:5px;">Add</a><a href="javascript:;" class="button deletetab button-small">Delete</a>' );
	});
	
	/*
	*
	* jquery method for delete rows
	*
	*/
	
	jQuery( document ).on( "click", ".deletetab", function( e ) {	
		var count = jQuery( '.lumia_product_tab_inner' ).length;
		jQuery( this ).closest( 'p.action_block' ).prev().remove(); 
		if ( count == 2 ) {
			jQuery( ".deletetab" ).remove();
		}
	});
});
