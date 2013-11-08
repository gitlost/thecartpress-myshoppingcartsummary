<?php
/*
Plugin Name: TheCartPress My Shopping Cart Summary
Plugin URI: 
Description: Adds an alternative Shopping Cart summary widget to TheCartPress eCommerce sites.
Version: 1.0.0
Author: gitlost
Author URI: https://github.com/gitlost
License: GPL2
Parent: thecartpress
*/

/*  Copyright 2013  gitlost

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class TCPMyShoppingCartSummaryWidget extends WP_Widget {
	function __construct() {
		if ( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( 'tcp-mscs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
		$widget = array(
			'classname'		=> 'tcpmyshoppingcartsummarywidget',
			'description'	=> __( 'Adds an alternative Shopping Cart Summary widget to The Cart Press e-commerce system', 'tcp-mscs' ),
		);
		$control = array(
			'width'		=> 300,
			'id_base'	=> 'tcp_myshoppingcartsummary-widget',
		);
		parent::__construct( 'tcp_myshoppingcartsummary-widget', 'My TCP Shopping Cart Summary', $widget, $control );

		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	function activate_plugin() {
		if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'thecartpress/TheCartPress.class.php' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}
	}

	function init() {
		if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'thecartpress/TheCartPress.class.php' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}
		global $thecartpress;
		if ( $thecartpress && $thecartpress->get_setting( 'activate_ajax', true ) ) {
			add_action( 'wp_ajax_tcp_mscs', array( $this, 'tcp_mscs' ) );
			add_action( 'wp_ajax_nopriv_tcp_mscs', array( $this, 'tcp_mscs' ) );
		}
	}

	function admin_init() {
		if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'thecartpress/TheCartPress.class.php' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			return;
		}
	}

	function admin_notices() {
		?><div class="error"><p><?php _e( '<strong>TheCartPress My Shopping Cart Summary</strong> requires TheCartPress plugin to be activated.', 'tcp-mscs' ); ?></p></div><?php
	}

	function widget( $args, $instance ) {
		$shoppingCart = TheCartPress::getShoppingCart();
		$hide_if_empty = isset( $instance['hide_if_empty'] ) ? $instance['hide_if_empty'] : false;
		if ( $hide_if_empty && $shoppingCart->isEmpty() ) return;//TODO
		$load_widget_css = isset( $instance['load_widget_css'] ) ? $instance['load_widget_css'] : false;
		if ( $load_widget_css ) {
			wp_enqueue_style( 'tcp-myshoppingcartsummary', plugins_url( '/myshoppingcartsummary.css', __FILE__ ), array( ), '1.0.0' );
		}
		extract( $args );
		$title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : ' ' );
		echo $before_widget;
		if ( $title ) echo $before_title, $title, $after_title;
		$instance['widget_id'] = $widget_id;
		$this->tcp_mscs( $instance );
		echo $after_widget;
	}

	function tcp_mscs( $args = false ) {
		$is_ajax = ! $args && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'tcp_mscs' && isset( $_REQUEST['args'] );
		if ( $is_ajax ) {
			$args = (array)json_decode( urldecode( $_REQUEST['args'] ) );
		}
		ob_start();
		do_action( 'tcp_mscs_get_shopping_cart_before_summary', $args );
		$args = wp_parse_args( $args, $this->get_defaults() );

		$shoppingCart = TheCartPress::getShoppingCart();
		$is_empty = $shoppingCart->isEmpty();
		$total = $shoppingCart->getTotalToShow( false );
		$count = $shoppingCart->getCount();
		$weight = $shoppingCart->getWeight();
		global $thecartpress;
		$unit_weight = $thecartpress->get_setting( 'unit_weight', 'gr' );
		$discount = $shoppingCart->getAllDiscounts();

		if ( $is_empty ) {
			$format = $args['display_format_empty'];
		} elseif ( $count == 1 ) {
			$format = $args['display_format_single'];
		} else {
			$format = $args['display_format'];
		}

		$shopping_cart_url = esc_attr( tcp_get_the_shopping_cart_url() );
		$checkout_url = esc_attr( tcp_get_the_checkout_url() );

		$link_whole_cart = ( $args['link_whole_cart'] && ( ! $is_empty || ! $args['nolink_if_empty'] ) );

		$widget_id = isset( $args['widget_id'] ) ? str_replace( '-', '_', $args['widget_id'] ) : 'tcp_myshopping_cart_summary'; ?>
	<div id="<?php echo $widget_id; ?>"<?php if ($is_empty) echo ' class="cart-empty"';?>>
		<ul class="tcp_mscs">
			<li class="tcp_mscs_format<?php if ( ! $link_whole_cart ) echo ' tcp_mscs_no_link'; ?>">
			<?php if ( $link_whole_cart ) : ?><a class="tcp_shoppingcartsummary_link" href="<?php echo $shopping_cart_url; ?>"><?php endif; ?>
			<?php echo sprintf( __( $format, 'tcp-mscs' ), tcp_format_the_price( $total ), $count, $weight, $unit_weight, tcp_format_the_price( $discount ) ); ?>
			<?php if ( $link_whole_cart ) : ?></a><?php endif; ?>
			</li>
		<?php if ( ! $is_empty && $args['see_shopping_cart'] ) : ?>
			<li class="tcp_cart_widget_footer_link tcp_shopping_cart_link"><a href="<?php echo $shopping_cart_url; ?>"><?php _e( 'Shopping cart', 'tcp-mscs' ); ?></a></li>
		<?php endif; ?>
		<?php if ( ! $is_empty && $args['see_checkout'] ) : ?>
			<li class="tcp_cart_widget_footer_link tcp_checkout_link"><a href="<?php echo $checkout_url; ?>"><?php _e( 'Checkout', 'tcp-mscs' ); ?></a></li>
		<?php endif; ?>
		<?php if ( ! $is_empty && $args['see_delete_all'] ) : ?>
			<li class="tcp_cart_widget_footer_link tcp_delete_all_link"><form method="post"><input type="submit" name="tcp_delete_shopping_cart" class="tcp_delete_shopping_cart" value="<?php _e( 'Delete', 'tcp-mscs' ); ?>" title="<?php _e( 'Delete', 'tcp-mscs' ); ?>" /></form></li>
		<?php endif; ?>
		<?php if ( ! $is_empty && $args['see_stock_notice'] && ! tcp_is_stock_in_shopping_cart() ) : ?>
			<li class="tcp_not_enough_stock"><?php echo sprintf( __( $args['stock_notice'], 'tcp-mscs' ), $shopping_cart_url ); ?></li>
		<?php endif; ?>
		</ul>
	</div>
		<?php if ( ! $is_ajax ) { ?>
<img src="<?php echo admin_url( 'images/loading.gif' ); ?>" class="tcp_feedback" style="display: none;" />
<script type="text/javascript">
tcpDispatcher.add( 'tcp_listener_<?php echo $widget_id; ?>', 0 );
tcpDispatcher.add( 'tcp_listener_<?php echo $widget_id; ?>', 'tcp_checkout_ok' );

function tcp_listener_<?php echo $widget_id; ?>(){
	var widget = jQuery('#<?php echo $widget_id; ?>');
	widget.find('.tcp_feedback').show();
	jQuery.getJSON(
		'<?php echo admin_url( 'admin-ajax.php' ); ?>',
		{
			action : 'tcp_mscs',
			args : encodeURIComponent('<?php echo json_encode( $args ); ?>')
		}
	).done( function(response) {
		widget.find('.tcp_feedback').hide();
		widget.replaceWith(response);
	} ).fail( function(response) {
		widget.find('.tcp_feedback').hide();
	} );
}
</script>
	<?php
		}
		$out = ob_get_clean();
		$out = apply_filters( 'tcp_mscs_get_shopping_cart_summary', $out, $args );
		if ( $is_ajax ) {
			tcp_return_jsonp( $out );
		} else {
			echo $out;
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['load_widget_css'] = isset( $new_instance['load_widget_css'] );
		$instance['hide_if_empty'] = isset( $new_instance['hide_if_empty'] );
		$instance['link_whole_cart'] = isset( $new_instance['link_whole_cart'] );
		$instance['nolink_if_empty'] = isset( $new_instance['nolink_if_empty'] );
		$instance['see_delete_all'] = isset( $new_instance['see_delete_all'] );
		$instance['see_shopping_cart'] = isset( $new_instance['see_shopping_cart'] );
		$instance['see_checkout'] = isset( $new_instance['see_checkout'] );
		$instance['see_stock_notice'] = isset( $new_instance['see_stock_notice'] );
		$instance['display_format'] = $new_instance['display_format'];
		$instance['display_format_single'] = $new_instance['display_format_single'];
		$instance['display_format_empty'] = $new_instance['display_format_empty'];
		$instance['stock_notice'] = $new_instance['stock_notice'];
		$instance = apply_filters( 'tcp_mscs_shopping_cart_summary_widget_update', $instance, $new_instance );
		return $instance;
	}

	function get_defaults() {
		$defaults = array(
			'title' => '',
			'load_widget_css' => false,
			'hide_if_empty' => false,
			'link_whole_cart' => true,
			'nolink_if_empty' => false,
			'see_delete_all' => false,
			'see_shopping_cart' => false,
			'see_checkout' => false,
			'see_stock_notice' => false,
			'display_format' => __( 'Cart %1$s (%2$s items)' ),
			'display_format_single' => __( 'Cart %1$s (1 item)' ),
			'display_format_empty' => __( 'Cart is empty' ),
			'stock_notice' => __( 'Not enough stock for some products. Visit the <a href="%s">Shopping Cart</a> to see more details.', 'tcp-mscs' ),
		);
		return $defaults;
	}

	function form( $instance ) {
		$instance = wp_parse_args( ( array ) $instance, $this->get_defaults() ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'tcp-mscs' )?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'load_widget_css' ); ?>" name="<?php echo $this->get_field_name( 'load_widget_css' ); ?>"<?php checked( $instance['load_widget_css'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'load_widget_css' ); ?>"><?php _e( 'Load widget css', 'tcp-mscs' ); ?></label>
		<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hide_if_empty' ); ?>" name="<?php echo $this->get_field_name( 'hide_if_empty' ); ?>"<?php checked( $instance['hide_if_empty'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'hide_if_empty' ); ?>"><?php _e( 'Hide if empty', 'tcp-mscs' ); ?></label>
		<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'link_whole_cart' ); ?>" name="<?php echo $this->get_field_name( 'link_whole_cart' ); ?>"<?php checked( $instance['link_whole_cart'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'link_whole_cart' ); ?>"><?php _e( 'Link whole cart', 'tcp-mscs' ); ?></label>
		<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'nolink_if_empty' ); ?>" name="<?php echo $this->get_field_name( 'nolink_if_empty' ); ?>"<?php checked( $instance['nolink_if_empty'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'nolink_if_empty' ); ?>"><?php _e( 'Don\'t link whole cart if empty', 'tcp-mscs' ); ?></label>
		<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'see_delete_all ' ); ?>" name="<?php echo $this->get_field_name( 'see_delete_all' ); ?>"<?php checked( $instance['see_delete_all'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'see_delete_all' ); ?>"><?php _e( 'See delete button', 'tcp-mscs' ); ?></label>
		<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'see_shopping_cart ' ); ?>" name="<?php echo $this->get_field_name( 'see_shopping_cart' ); ?>"<?php checked( $instance['see_shopping_cart'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'see_shopping_cart' ); ?>"><?php _e( 'See shopping cart link', 'tcp-mscs' ); ?></label>
		<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'see_checkout ' ); ?>" name="<?php echo $this->get_field_name( 'see_checkout' ); ?>"<?php checked( $instance['see_checkout'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'see_checkout' ); ?>"><?php _e( 'See checkout link', 'tcp-mscs' ); ?></label>
		<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'see_stock_notice ' ); ?>" name="<?php echo $this->get_field_name( 'see_stock_notice' ); ?>"<?php checked( $instance['see_stock_notice'] ); ?> />
			<label for="<?php echo $this->get_field_id( 'see_stock_notice' ); ?>"><?php _e( 'See stock notice', 'tcp-mscs' ); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'display_format' ); ?>"><?php _e( 'Format string for display:', 'tcp-mscs' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'display_format' ); ?>" name="<?php echo $this->get_field_name( 'display_format' ); ?>" type="text" value="<?php echo esc_attr( __( $instance['display_format'], 'tcp-mscs' ) ); ?>" />
			<span class="description"><?php _e( 'Format string to display cart, eg "Cart %1$s (%2$s items, %3$s %4$s)".<br />%1$s total price, %2$s number of items, %3$s total weight, %4$s weight unit, %5$s total discount.', 'tcp-mscs' ); ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'display_format_single' ); ?>"><?php _e( 'Format string for display when one item in cart:', 'tcp-mscs' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'display_format_single' ); ?>" name="<?php echo $this->get_field_name( 'display_format_single' ); ?>" type="text" value="<?php echo esc_attr( __( $instance['display_format_single'], 'tcp-mscs' ) ); ?>" />
			<span class="description"><?php _e( 'Format string to display cart when there is only one item in the cart, eg "Cart %1$s (1 item, %3$s %4$s)". Arguments same as above.', 'tcp-mscs' ); ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'display_format_empty' ); ?>"><?php _e( 'Format string for display when cart empty:', 'tcp-mscs' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'display_format_empty' ); ?>" name="<?php echo $this->get_field_name( 'display_format_empty' ); ?>" type="text" value="<?php echo esc_attr( __( $instance['display_format_empty'], 'tcp-mscs' ) ); ?>" />
			<span class="description"><?php _e( 'Format string to display cart when there are no items in the cart, eg "Cart is empty".', 'tcp-mscs' ); ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'stock_notice' ); ?>"><?php _e( 'Not enough stock notice:', 'tcp-mscs' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'stock_notice' ); ?>" name="<?php echo $this->get_field_name( 'stock_notice' ); ?>" type="text" value="<?php echo esc_attr( __( $instance['stock_notice'], 'tcp-mscs' ) ); ?>" />
		</p>
		<?php do_action( 'tcp_mscs_shopping_cart_summary_widget_form', $this, $instance ); ?>
		<?php
	}
}

add_action( 'widgets_init', create_function( '', 'register_widget( "tcpmyshoppingcartsummarywidget" );' ), 11 );
