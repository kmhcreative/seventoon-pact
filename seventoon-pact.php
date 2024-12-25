<?php
/*
Plugin Name: Seventoon PACT
Plugin URI: http://www.github.com/kmhcreative/seventoon-pact
Description: Posts As Comic Taxonomy
Version: 0.1
Author: K.M. Hansen (kmhcreative)
Author URI: http://www.kmhcreative.com
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/*  Copyright 2024  K.M. Hansen  (email : software@kmhcreative.com)

    Seventoon PACT is intended to give you shortcodes of the chapter
    list and drop-down as well as order comic/chapter categories in
    reading order in archives. It is designed to work with the Seventoon
    theme, but should work with other themes, if you want to use regular
    posts as comics and comic/chapter categories instead of a dedicated
    comic management plugin.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/* Minimum Version Checks */
	function seventoon_pact_wp_version_check(){
		// if not using minimum WP and PHP versions, bail!
		$wp_version = get_bloginfo('version');
		global $pagenow;
		if ( is_admin() && $pagenow=="plugins.php" && ($wp_version < 3.5 || PHP_VERSION < 5.6 ) ) {
		echo "<div class='notice notice-error is-dismissible'><p><b>ERROR:</b> ComicPost is <em>activated</em> but requires <b>WordPress 3.5</b> and <b>PHP 5.6</b> or greater to work.  You are currently running <b>Wordpress <span style='color:red;'>".$wp_version."</span></b> and <b>PHP <span style='color:red;'>".PHP_VERSION."</span></b>. Please upgrade.</p></div>";
			return;
		}
	};
	add_action('admin_notices', 'seventoon_pact_wp_version_check');


// ComicEaselLite Plugin Info Function
function seventoon_pact_pluginfo($whichinfo = null) {
	global $seventoon_pact_pluginfo;
	if (empty($seventoon_pact_pluginfo) || $whichinfo == 'reset') {
		// Important to assign pluginfo as an array to begin with.
		$seventoon_pact_pluginfo = array();
		$seventoon_pact_coreinfo = wp_upload_dir();
		$seventoon_pact_addinfo = array(
				// if wp_upload_dir reports an error, capture it
				'error' => $seventoon_pact_coreinfo['error'],
				// upload_path-url
				'base_url' => trailingslashit($seventoon_pact_coreinfo['baseurl']),
				'base_path' => trailingslashit($seventoon_pact_coreinfo['basedir']),
				// plugin directory/url
				'plugin_file' => __FILE__,
				'plugin_url' => plugin_dir_url(__FILE__),
				'plugin_path' => plugin_dir_path(__FILE__),
				'plugin_basename' => plugin_basename(__FILE__),
				'version' => '0.1'
		);
		// Combine em.
		$seventoon_pact_pluginfo = array_merge($seventoon_pact_pluginfo, $seventoon_pact_addinfo);
	}
	if ($whichinfo) {
		if (isset($seventoon_pact_pluginfo[$whichinfo])) {
			return $seventoon_pact_pluginfo[$whichinfo];
		} else return false;
	}
	return $seventoon_pact_pluginfo;
}

function seventoon_pact_activation() {
	// FLUSH PERMALINKS //
    // ATTENTION: This is *only* done during plugin activation hook in this example!
    // You should *NEVER EVER* do this on every page load!!
    flush_rewrite_rules();
}
// Add Custom Post Type and Flush Rewrite Rules
register_activation_hook( __FILE__, 'seventoon_pact_activation' );
// check if SevenToon theme is active, if it is we don't do any of this stuff
$theme = wp_get_theme(); // gets the current theme
if ( 'SevenToon' == $theme->name || 'Seventoon' == $theme->parent_theme ) {
	// widgets already registered
} else {
	@require('functions/widgets.php');
}
	@require('functions/seventoon-pact_frontend_functions.php');
	@require('functions/seventoon-pact_shortcodes.php');

	function seventoon_pact_promo_slider_enqueue_script() {   //Enqueue script on widget page
		// Do not do this if SevenToon Theme is active
		$theme = wp_get_theme(); // gets current theme
		if ( 'SevenToon' == $theme->name || 'Seventoon' == $theme->parent_theme) {
			return;
		}
		global $pagenow;
		if($pagenow=='widgets.php'||$pagenow=='customize.php')
		{
			wp_enqueue_style( 'seventoon-pact-promos-admin-style', seventoon_pact_pluginfo('plugin_url') .'css/admin-style.css');
			wp_enqueue_media();
			
			wp_enqueue_script('jquery-ui-core');

			$seventoon_translation_array = array(
			'newtab_string' => __( 'Open link in a new tab', 'seventoon-pact' ),
			'newtab_value' => __( 'New tab', 'seventoon-pact' ),
			'sametab_value' => __( 'Same tab', 'seventoon-pact' ),
			'confirm_message' => __( 'This is the last image of this Widget. Are you sure want to proceed.', 'seventoon-pact' )
			);
	        wp_register_script( 'seventoon-pact-promos-admin-script', seventoon_pact_pluginfo('plugin_url') .'js/admin.js',array("jquery"));
			wp_enqueue_script( 'seventoon-pact-promos-admin-script');
		}
	}
	add_action('admin_enqueue_scripts', 'seventoon_pact_promo_slider_enqueue_script');

// Plugin Update Check can no longer be inside if-else
@require('plugin-update-checker/plugin-update-checker.php');
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$SevenToonPactUpdateChecker = PucFactory::buildUpdateChecker(
'https://github.com/kmhcreative/seventoon-pact',
	__FILE__,'seventoon-pact'
);
$SevenToonPactUpdateChecker->getVcsApi()->enableReleaseAssets();


?>