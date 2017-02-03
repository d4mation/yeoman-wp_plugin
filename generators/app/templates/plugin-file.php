<?php
/*
Plugin Name: <%= pluginName %>
<% if (pluginURL !== '') { %>Plugin URL: <%= pluginURL _%><%= "\n" %><% } -%>
Description: <%= pluginDescription %>
Version: 0.1.0
Text Domain: <%= textDomain %>
<% if (author !== '') { %>Author: <%= author _%><%= "\n" %><% } -%>
<% if (authorURI !== '') { %>Author URL: <%= authorURI _%><%= "\n" %><% } -%>
<% if (contributors !== '') { %>Contributors: <%= contributors _%><%= "\n" %><% } -%>
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( '<%= pkgName %>' ) ) {

	/**
	 * Main <%= pkgName %> class
	 *
	 * @since	  1.0.0
	 */
	class <%= pkgName %> {
		
		/**
		 * @var			<%= pkgName %> $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			<%= pkgName %> $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true <%= pkgName %>
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );
			
			if ( ! defined( '<%= pkgName %>_ID' ) ) {
				// Plugin Text Domain
				define( '<%= pkgName %>_ID', $this->plugin_data['TextDomain'] );
			}

			if ( ! defined( '<%= pkgName %>_VER' ) ) {
				// Plugin version
				define( '<%= pkgName %>_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( '<%= pkgName %>_DIR' ) ) {
				// Plugin path
				define( '<%= pkgName %>_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( '<%= pkgName %>_URL' ) ) {
				// Plugin URL
				define( '<%= pkgName %>_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( '<%= pkgName %>_FILE' ) ) {
				// Plugin File
				define( '<%= pkgName %>_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = <%= pkgName %>_DIR . '/languages/';
			$lang_dir = apply_filters( '<%= pkgNameLowerCase %>_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), <%= pkgName %>_ID );
			$mofile = sprintf( '%1$s-%2$s.mo', <%= pkgName %>_ID, $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/' . <%= pkgName %>_ID . '/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-slack/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( <%= pkgName %>_ID, $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-slack/languages/ folder
				load_textdomain( <%= pkgName %>_ID, $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( <%= pkgName %>_ID, false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				<%= pkgName %>_ID . '-admin',
				<%= pkgName %>_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : <%= pkgName %>_VER
			);
			
			wp_register_script(
				<%= pkgName %>_ID . '-admin',
				<%= pkgName %>_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : <%= pkgName %>_VER,
				true
			);
			
			wp_localize_script( 
				<%= pkgName %>_ID . '-admin',
				'<%= javaScriptObject %>',
				apply_filters( '<%= pkgNameLowerCase -%>_localize_admin_script', array() )
			);
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true <%= pkgName %>
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \<%= pkgName %> The one true <%= pkgName %>
 */
add_action( 'plugins_loaded', '<%= pkgNameLowerCase %>_load' );
function <%= pkgNameLowerCase %>_load() {

	require_once __DIR__ . '/core/<%= textDomain %>-functions.php';
	<%= instanceName %>();

}