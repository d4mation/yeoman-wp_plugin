<?php
/**
 * Plugin Name: <%- pluginName %>
<% if (pluginURI !== '') { %> * Plugin URI: <%- pluginURI _%><%= "\n" %><% } -%>
 * Description: <%- pluginDescription %>
 * Version: 0.1.0
 * Text Domain: <%- textDomain %>
<% if (author !== '') { %> * Author: <%- author _%><%= "\n" %><% } -%>
<% if (authorURI !== '') { %> * Author URI: <%- authorURI _%><%= "\n" %><% } -%>
<% if (contributors !== '') { %> * Contributors: <%- contributors _%><%= "\n" %><% } -%>
<% if (gitHubURI !== '') { %> * GitHub Plugin URI: <%- gitHubURI _%><%= "\n" %><% } -%>
<% if (gitHubURI !== '') { %> * GitHub Branch: master<%= "\n" %><% } -%>
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( '<%- pkgName -%>' ) ) {

    /**
     * Main <%- pkgName -%> class
     *
     * @since      {{VERSION}}
     */
    final class <%- pkgName -%> {
        
        /**
         * @var          array $plugin_data Holds Plugin Header Info
         * @since        {{VERSION}}
         */
        public $plugin_data;
        
        /**
         * @var          array $admin_errors Stores all our Admin Errors to fire at once
         * @since        {{VERSION}}
         */
        private $admin_errors = array();

        /**
         * Get active instance
         *
         * @access     public
         * @since      {{VERSION}}
         * @return     object self::$instance The one true <%- pkgName %>
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
            
            if ( version_compare( get_bloginfo( 'version' ), '<%- minimumWP -%>' ) < 0 ) {
                
                $this->admin_errors[] = sprintf( _x( '%s requires v%s of %sWordPress%s or higher to be installed!', 'First string is the plugin name, followed by the required WordPress version and then the anchor tag for a link to the Update screen.', '<%- textDomain -%>' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<%- minimumWP -%>', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>', '</strong></a>' );
                
                if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
                    add_action( 'admin_notices', array( $this, 'admin_errors' ) );
                }
                
                return false;
                
            }
            
            $this->require_necessities();
            
            // Register our CSS/JS for the whole plugin
            add_action( 'init', array( $this, 'register_scripts' ) );
            
        }

        /**
         * Setup plugin constants
         *
         * @access     private
         * @since      {{VERSION}}
         * @return     void
         */
        private function setup_constants() {
            
            // WP Loads things so weird. I really want this function.
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            
            // Only call this once, accessible always
            $this->plugin_data = get_plugin_data( __FILE__ );

            if ( ! defined( '<%- pkgName -%>_VER' ) ) {
                // Plugin version
                define( '<%- pkgName -%>_VER', $this->plugin_data['Version'] );
            }

            if ( ! defined( '<%- pkgName -%>_DIR' ) ) {
                // Plugin path
                define( '<%- pkgName -%>_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
            }

            if ( ! defined( '<%- pkgName -%>_URL' ) ) {
                // Plugin URL
                define( '<%- pkgName -%>_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
            }
            
            if ( ! defined( '<%- pkgName -%>_FILE' ) ) {
                // Plugin File
                define( '<%- pkgName -%>_FILE', __FILE__ );
            }

        }

        /**
         * Internationalization
         *
         * @access     private 
         * @since      {{VERSION}}
         * @return     void
         */
        private function load_textdomain() {

            // Set filter for language directory
            $lang_dir = trailingslashit( <%- pkgName -%>_DIR ) . 'languages/';
            $lang_dir = apply_filters( '<%- pkgNameLowerCase -%>_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), '<%- textDomain -%>' );
            $mofile = sprintf( '%1$s-%2$s.mo', '<%- textDomain -%>', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = trailingslashit( WP_LANG_DIR ) . '<%- textDomain -%>/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/<%- textDomain -%>/ folder
                // This way translations can be overridden via the Theme/Child Theme
                load_textdomain( '<%- textDomain -%>', $mofile_global );
            }
            else if ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/<%- textDomain -%>/languages/ folder
                load_textdomain( '<%- textDomain -%>', $mofile_local );
            }
            else {
                // Load the default language files
                load_plugin_textdomain( '<%- textDomain -%>', false, $lang_dir );
            }

        }
        
        /**
         * Include different aspects of the Plugin
         * 
         * @access     private
         * @since      {{VERSION}}
         * @return     void
         */
        private function require_necessities() {
            
        }
        
        /**
         * Show admin errors.
         * 
         * @access     public
         * @since      {{VERSION}}
         * @return     HTML
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
         * @access     public
         * @since      {{VERSION}}
         * @return     void
         */
        public function register_scripts() {
            
            wp_register_style(
                '<%- textDomain -%>',
                <%- pkgName -%>_URL . 'dist/assets/css/app.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : <%- pkgName -%>_VER
            );
            
            wp_register_script(
                '<%- textDomain -%>',
                <%- pkgName -%>_URL . 'dist/assets/js/app.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : <%- pkgName -%>_VER,
                true
            );
            
            wp_localize_script( 
                '<%- textDomain -%>',
                '<%- javaScriptObject -%>',
                apply_filters( '<%- pkgNameLowerCase -%>_localize_script', array() )
            );
            
            wp_register_style(
                '<%- textDomain -%>-admin',
                <%- pkgName -%>_URL . 'dist/assets/css/admin.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : <%- pkgName -%>_VER
            );
            
            wp_register_script(
                '<%- textDomain -%>-admin',
                <%- pkgName -%>_URL . 'dist/assets/js/admin.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : <%- pkgName -%>_VER,
                true
            );
            
            wp_localize_script( 
                '<%- textDomain -%>-admin',
                '<%- javaScriptObject -%>',
                apply_filters( '<%- pkgNameLowerCase -%>_localize_admin_script', array() )
            );
            
        }
        
    }
    
} // End Class Exists Check

/**
 * The main function responsible for returning the one true <%- pkgName %>
 * instance to functions everywhere
 *
 * @since      {{VERSION}}
 * @return     \<%- pkgName -%> The one true <%- pkgName %>
 */
add_action( 'plugins_loaded', '<%- pkgNameLowerCase -%>_load' );
function <%- pkgNameLowerCase -%>_load() {

    require_once trailingslashit( __DIR__ ) . 'core/<%- textDomain -%>-functions.php';
    <%- instanceName -%>();

}