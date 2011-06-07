<?php
/*
Plugin Name: Monitor Pages
Description: Monitors pages for creation or addition and e-mails a notification to a list of people that you provide
Version: 0.4
Author: Mark Jaquith
Author URI: http://coveredwebservices.com/
Text Domain: monitor-pages
*/

// Compat for WordPress versions less than 3.1
if ( !function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		$safe_text = htmlspecialchars( $text, ENT_QUOTES );
		return apply_filters( 'esc_textarea', $safe_text, $text );
	}
}

if ( !function_exists( 'submit_button' ) ) {
	function submit_button( $text = NULL, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = NULL ) {
		echo get_submit_button( $text, $type, $name, $wrap, $other_attributes );
	}
}

if ( !function_exists( 'get_submit_button' ) ) {
	function get_submit_button( $text = NULL, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = NULL ) {
		switch ( $type ) :
			case 'primary' :
			case 'secondary' :
				$class = 'button-' . $type;
				break;
			case 'delete' :
				$class = 'button-secondary delete';
				break;
			default :
				$class = $type; // Custom cases can just pass in the classes they want to be used
		endswitch;
		$text = ( NULL == $text ) ? __( 'Save Changes' ) : $text;

		// Default the id attribute to $name unless an id was specifically provided in $other_attributes
		$id = $name;
		if ( is_array( $other_attributes ) && isset( $other_attributes['id'] ) ) {
			$id = $other_attributes['id'];
			unset( $other_attributes['id'] );
		}

		$attributes = '';
		if ( is_array( $other_attributes ) ) {
			foreach ( $other_attributes as $attribute => $value ) {
				$attributes .= $attribute . '="' . esc_attr( $value ) . '" '; // Trailing space is important
			}
		} else if ( !empty( $other_attributes ) ) { // Attributes provided as a string
			$attributes = $other_attributes;
		}

		$button = '<input type="submit" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class );
		$button	.= '" value="' . esc_attr( $text ) . '" ' . $attributes . ' />';

		if ( $wrap ) {
			$button = '<p class="submit">' . $button . '</p>';
		}

		return $button;
	}
}

class CWS_Monitor_Pages_Plugin {
	static $instance;
	const notify = 'cws_monitor_pages_notify_on';
	const emails = 'cws_monitor_pages_emails';

	public function __construct() {
		self::$instance =& $this;
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		// Translations
		load_plugin_textdomain( 'monitor-pages', false, basename( dirname( __FILE__ ) ) . '/i18n' );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'transition_post_status', array( $this, 'transition' ), 10, 3 );
	}

	public function admin_menu() {
		$callback = add_submenu_page( 'edit.php?post_type=page', __( 'Manage Page Notifications', 'monitor-pages' ), __( 'Manage Notifications', 'monitor-pages' ), 'manage_options', 'monitor-pages', array( $this, 'admin_page' ) );
		add_action( "load-{$callback}", array( &$this, 'load_admin' ) );
	}

	public function load_admin() {
		add_option( self::notify, 'both' );
		if ( $_POST ) {
			check_admin_referer( 'cws-mpp-update-options' );
			if ( !in_array( $_POST[self::notify], array( 'both', 'created' ) ) )
				$_POST[self::notify] = 'both';
			update_option( self::notify, $_POST[self::notify] );
			update_option( self::emails, array_map( 'trim', explode( "\n", stripslashes( $_POST[self::emails] ) ) ) );
			set_transient( 'cws-monitor-pages-updated', true, 120 );
			wp_redirect( admin_url( 'edit.php?post_type=page&page=monitor-pages' ) );
			exit();
		} elseif ( get_transient( 'cws-monitor-pages-updated' ) ) {
			delete_transient( 'cws-monitor-pages-updated' );
			add_action( 'cws-monitor-pages-notices', array( $this, 'updated' ) );
		}
	}

	public function updated() {
		echo '<div class="updated"><p><strong>' . __( 'Settings saved.', 'monitor-pages' ) . '</strong></p></div>';
	}

	public function transition( $new_status, $old_status, $post ) {
		if ( $post->post_type != 'page' )
			return;
		switch( get_option( self::notify ) ) {
			case 'created' :
				if ( !in_array( $new_status, array( 'publish', 'future' ) ) || $old_status == $new_status )
					return;
				break;
			case 'both' :
			default :
				if ( !in_array( $new_status, array( 'publish', 'future' ) ) && !in_array( $old_status, array( 'publish', 'future' ) ) )
					return;
				break;
		}
		// Still here? Let's craft the notification e-mail
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		// Determine the type of event
		if ( $new_status == $old_status ) {
			// Easy, it was just updated
			$type = 'updated';
		} elseif ( 'draft' == $new_status || 'pending' == $new_status ) {
			if ( 'future' == $old_status )
				$type = 'unscheduled';
			else
				$type = 'unpublished';
		} elseif( in_array( $new_status, array( 'future', 'publish', 'trash', 'private' ) ) ) {
			$type = $new_status;
		} else {
			$type = 'unknown';
		}
		$events = array(
			'updated'     => __( 'Page Updated', 'monitor-pages' ),
			'unscheduled' => __( 'Page Unscheduled', 'monitor-pages' ),
			'unpublished' => __( 'Page Unpublished', 'monitor-pages' ),
			'future'      => __( 'Page Scheduled', 'monitor-pages' ),
			'publish'     => __( 'Page Published', 'monitor-pages' ),
			'trash'       => __( 'Page Trashed', 'monitor-pages' ),
			'pending'     => __( 'Page Made Private', 'monitor-pages' )
		);
		$subject = sprintf( __('[%1$s] %2$s: "%3$s"', 'monitor-pages'), $blogname, $events[$type], $post->post_title );
		$message = sprintf( __( '%1$s: "%2$s"' . "\r\n\r\n" . '%3$s', 'monitor-pages' ), $events[$type], $post->post_title, get_permalink( $post->ID ) );
		foreach ( $this->get_emails() as $email ) {
			wp_mail( $email, $subject, $message );
		}
	}

	private function get_emails() {
		return (array) get_option( self::emails );
	}

	public function admin_page() {
?>
	<div class="wrap">
	<?php screen_icon( 'edit-pages' ); ?>
	<h2><?php echo esc_html( $GLOBALS['title'] ); ?></h2>
	<?php do_action( 'cws-monitor-pages-notices' ); ?>
	<form method="post" action="">
		<?php wp_nonce_field( 'cws-mpp-update-options' ); ?>
		<table class="form-table"> 
		<tr valign="top"> 
		<th scope="row"><?php _e( 'Send notification e-mails when', 'monitor-pages' ); ?></th>
		<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Send notification e-mails when', 'monitor-pages' ); ?></span></legend>
		<label for="cws-mpp-created"><input type="radio" name="<?php echo self::notify; ?>" id="cws-mpp-created" <?php checked( get_option( self::notify ), 'created' ); ?> value="created" /> <?php _e( 'Pages are published or scheduled', 'monitor-pages' ); ?></label><br />
		<label for="cws-mpp-both"><input type="radio" name="<?php echo self::notify; ?>" id="cws-mpp-both" <?php checked( get_option( self::notify ), 'both' ); ?> value="both" /> <?php _e( 'Pages are published, scheduled, or modified', 'monitor-pages' ); ?></label><br />
		</fieldset></td></tr>

		<tr valign="top"> 
		<th scope="row"><?php _e( 'Notification addresses', 'monitor-pages' ); ?></th>
		<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Notification addresses', 'monitor-pages' ); ?></span></legend>
		<p><label for="cws-mpp-emails"><?php _e( 'Send notifications to the following e-mail addresses (one per line)', 'monitor-pages' ); ?></label></p> 
		<p> 
		<textarea name="<?php echo self::emails; ?>" rows="10" cols="50" id="cws-mpp-emails" class="large-text code"><?php echo esc_textarea( implode( "\n", $this->get_emails() ) ); ?></textarea>
		</p>
		</fieldset></td></tr>
		</table>
	<?php submit_button(); ?>
	</form>
	</div>

<?php
	}

}

new CWS_Monitor_Pages_Plugin;
