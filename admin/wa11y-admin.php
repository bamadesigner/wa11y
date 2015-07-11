<?php

/**
 * Register our settings.
 *
 * @since    1.0
 */
add_action( 'admin_init', 'wa11y_register_settings' );
function wa11y_register_settings() {
	register_setting( 'wa11y_settings', 'wa11y_settings', 'wa11y_sanitize_settings' );
}

/**
 * Sanitizes the 'wa11y_settings' option.
 *
 * @since   1.0
 * @param 	array - $settings - the settings that are being sanitized
 * @return	array - sanitized $settings
 */
function wa11y_sanitize_settings( $settings ) {
	return $settings;
}

/**
 * Setup stylesheets for the admin.
 *
 * @since   1.0
 * @param   string - $hook_suffix - the hook/ID of the current page
 * @filter  'wa11y_load_tota11y' - boolean on whether or not to load the tota11y tool. Passes the tota11y settings.
 */
add_action( 'admin_enqueue_scripts', 'wa11y_enqueue_admin_styles' );
function wa11y_enqueue_admin_styles( $hook_suffix ) {
	global $wa11y_options_page;

	// Get our settings
	$wa11y_settings = wa11y_get_settings();

	switch( $hook_suffix ) {

		// Add styles to our options page
		case $wa11y_options_page:

			// Enqueue our styles for our options page
			wp_enqueue_style( 'wa11y-admin-options', plugin_dir_url( __FILE__ ) . 'css/wa11y-admin-options-page.min.css', array(), WA11Y_VERSION );

			// Enqueue our script for our options page
			// @TODO remove if never created
			//wp_enqueue_script( 'wa11y-admin-options', plugin_dir_url( __FILE__ ) . 'js/wa11y-admin-options-page.js', array( 'jquery' ), WA11Y_VERSION, false );

			break;

		// Add styles to the "Edit Post" screen
		case 'post.php':
		case 'post-new.php':

			// Enqueue our styles for the edit post screen
			wp_enqueue_style( 'wa11y-admin-edit-post', plugin_dir_url( __FILE__ ) . 'css/wa11y-admin-edit-post.min.css', array(), WA11Y_VERSION );

			// Enqueue our script for the edit post screen
			//wp_enqueue_script( 'wa11y-admin-edit-post', plugin_dir_url( __FILE__ ) . 'js/wa11y-admin-edit-post.js', array( 'jquery' ), WA11Y_VERSION, true );

			// Register axe - goes in header
			//wp_register_script( 'axe', plugins_url( '/includes/axe/axe.min.js' , dirname(__FILE__ ) ) );

			// Initiate axe - goes in header
			//wp_enqueue_script( 'initiate-axe', plugin_dir_url( __FILE__ ) . 'js/wa11y-admin-post-axe.js', array( 'axe' ) );

			break;

	}

	// Load tools in the admin

	// Only need to worry about this stuff if we have enabled tools
	if ( $wa11y_enable_tools = isset( $wa11y_settings[ 'enable_tools' ] ) ? $wa11y_settings[ 'enable_tools' ] : array() ) {

		// If tota11y is enabled...
		if ( in_array( 'tota11y', $wa11y_enable_tools ) ) {

			// Get tota11y settings
			$wa11y_tota11y_settings = isset( $wa11y_settings[ 'tools' ] ) && isset( $wa11y_settings[ 'tools' ][ 'tota11y' ] ) ? $wa11y_settings[ 'tools' ][ 'tota11y' ] : array();

			// Should we load tota11y in the admin?
			if ( isset( $wa11y_tota11y_settings[ 'load_in_admin' ] ) && $wa11y_tota11y_settings[ 'load_in_admin' ] > 0 ) {

				// Will be true by default
				$load_tota11y = true;

				// If user roles are set, turn off it not a user role
				if ( isset( $wa11y_tota11y_settings[ 'load_user_roles' ] ) && is_array( $wa11y_tota11y_settings[ 'load_user_roles' ] ) ) {

					// Get current user
					if ( ( $current_user = wp_get_current_user() )
						&& ( $current_user_roles = isset( $current_user->roles ) ? $current_user->roles : false )
						&& is_array( $current_user_roles ) ) {

						// Find out if they share values
						$user_roles_intersect = array_intersect( $wa11y_tota11y_settings[ 'load_user_roles' ], $current_user_roles );

						// If they do not intersect, turn off
						if ( empty( $user_roles_intersect ) ) {
							$load_tota11y = false;
						}

					}

				}

				// If user capability is set, turn off if not capable
				if ( ! empty( $wa11y_tota11y_settings[ 'load_user_capability' ] ) ) {
					$load_tota11y = current_user_can( $wa11y_tota11y_settings[ 'load_user_capability' ] );
				}

				// Filter whether or not to load tota11y - passes the tota11y settings
				$load_tota11y = apply_filters( 'wa11y_load_tota11y', $load_tota11y, $wa11y_tota11y_settings );

				// We need to load tota11y
				if ( $load_tota11y ) {

					// This file belongs in the header
					wp_enqueue_script( 'tota11y', plugins_url( '/includes/tota11y/tota11y.min.js', dirname( __FILE__ ) ) );

				}

			}

		}

	}

}

/**
 * Adds meta boxes to the "Edit Post" screen.
 *
 * @since   1.0
 * @param	string - the post type that's being edited
 * @param	object - information about the post that's being edited
 */
add_action( 'add_meta_boxes', 'wa11y_add_post_meta_boxes', 10, 2 );
function wa11y_add_post_meta_boxes( $post_type, $post ) {

	// Get our saved settings
	$wa11y_settings = wa11y_get_settings();

	// Only need to worry about this stuff if we have enabled tools
	$wa11y_enable_tools = isset( $wa11y_settings[ 'enable_tools' ] ) ? $wa11y_settings[ 'enable_tools' ] : array();
	if ( empty( $wa11y_enable_tools ) )
		return;

	// If WAVE is enabled and not SSL...
	if ( ! is_ssl() && in_array( 'wave', $wa11y_enable_tools ) ) {

		// Get WAVE settings
		$wa11y_wave_settings = isset( $wa11y_settings[ 'tools' ] ) && isset( $wa11y_settings[ 'tools' ][ 'wave' ] ) ? $wa11y_settings[ 'tools' ][ 'wave' ] : array();

		// Will be true if we should load WAVE meta box - false by default
		$load_wave = false;

		// If user roles are set, turn off it not a user role
	    if ( isset( $wa11y_wave_settings[ 'load_user_roles' ] ) && is_array( $wa11y_wave_settings[ 'load_user_roles' ] ) ) {

			// Get current user
            if ( ( $current_user = wp_get_current_user() )
                 && ( $current_user_roles = isset( $current_user->roles ) ? $current_user->roles : false )
                 && is_array( $current_user_roles ) ) {

                // Find out if they share values
                $user_roles_intersect = array_intersect( $wa11y_wave_settings[ 'load_user_roles' ], $current_user_roles );

				// If they intersect, turn on
                if ( ! empty( $user_roles_intersect ) ) {
                    $load_wave = true;
                }

            }

        }

        // If user capability is set, turn off if not capable
        if ( ! empty( $wa11y_wave_settings[ 'load_user_capability' ] ) ) {
            $load_wave = current_user_can( $wa11y_wave_settings[ 'load_user_capability' ] );
        }

		// Filter whether or not to load WAVE - passes the WAVE settings
		$load_wave = apply_filters( 'wa11y_load_wave', $load_wave, $wa11y_wave_settings );

		// Add WAVE Evaluation meta box
		if ( $load_wave ) {
			add_meta_box( 'wa11y-wave-evaluation', sprintf( __( '%1$s - %2$s Evaluation', 'wa11y' ), 'Wa11y', 'WAVE' ), 'wa11y_print_post_meta_boxes', $post_type, 'normal', 'core', $wa11y_settings );
		}

	}

}

/**
 * Print the meta boxes for the "Edit Post" screen.
 *
 * @since   1.0
 * @param	array - $post - information about the current post
 * @param	array - $metabox - information about the metabox
 */
function wa11y_print_post_meta_boxes( $post, $metabox ) {

	// Get the saved settings passed to the meta boxes
	$wa11y_settings = isset( $metabox[ 'args' ] ) ? $metabox[ 'args' ] : array();

	// Get enable tools settings
	//$wa11y_enable_tools_settings = isset( $wa11y_settings[ 'enable_tools' ] ) ? $wa11y_settings[ 'enable_tools' ] : array();

	switch( $metabox[ 'id' ] ) {

		case 'wa11y-wave-evaluation':

			// Build WAVE evaluation URL
			$wave_url = 'http://wave.webaim.org/report#/'. urlencode( get_permalink( $post->ID ) );

			// Filter the WAVE url - includes $post object
			$wave_url = apply_filters( 'wa11y_wave_url', $wave_url, $post );

			?><a class="wa11y-wave-open-evaluation" href="<?php echo $wave_url; ?>" target="_blank"><?php printf( __( 'Open %s evaluation in new window', 'wa11y' ), 'WAVE' ); ?></a>
			<iframe id="wa11y-wave-evaluation-iframe" src="<?php echo $wave_url; ?>"></iframe><?php

			break;

	}

}

/**
 * Add the options page.
 *
 * @since   1.0
 */
add_action( 'admin_menu', 'wa11y_add_options_page' );
function wa11y_add_options_page() {
	global $wa11y_options_page;

	// Add the options page
	$wa11y_options_page = add_options_page( __( 'Wa11y', 'wa11y' ), __( 'Wa11y', 'wa11y' ), 'manage_options', 'wa11y', 'wa11y_print_options_page' );

}

/**
 * Print the options page.
 *
 * @since   1.0
 */
function wa11y_print_options_page() {

	// Include the options page
	require_once plugin_dir_path( __FILE__ ) . 'wa11y-admin-options-page.php';

}

/**
 * Add meta boxes to the options page.
 *
 * @since   1.0
 */
add_action( 'admin_head-settings_page_wa11y', 'wa11y_add_options_meta_boxes' );
function wa11y_add_options_meta_boxes() {
	global $wa11y_options_page;

	// Get our saved settings
	$wa11y_settings = wa11y_get_settings();

	// About this Plugin
	add_meta_box( 'wa11y-about', sprintf( __( 'About %s', 'wa11y' ), 'Wa11y' ), 'wa11y_print_options_meta_boxes', $wa11y_options_page, 'side', 'core', $wa11y_settings );

	// Save Changes
	add_meta_box( 'wa11y-save-changes', __( 'Save Changes', 'wa11y' ), 'wa11y_print_options_meta_boxes', $wa11y_options_page, 'side', 'core', $wa11y_settings );

	// tota11y Settings
	add_meta_box( 'wa11y-tota11y-settings', 'tota11y', 'wa11y_print_options_meta_boxes', $wa11y_options_page, 'normal', 'core', $wa11y_settings );

	// WAVE Settings
	add_meta_box( 'wa11y-wave-settings', sprintf( __( '%s (Web Accessibility eValuation Tool)', 'wa11y' ), 'WAVE' ), 'wa11y_print_options_meta_boxes', $wa11y_options_page, 'normal', 'core', $wa11y_settings );

}

/**
 * Print the meta boxes for the options page.
 *
 * @since   1.0
 * @param	array - $post - information about the current post, which is empty because there is no current post on a settings page
 * @param	array - $metabox - information about the metabox
 */
function wa11y_print_options_meta_boxes( $post, $metabox ) {

	// Get the saved settings passed to the meta boxes
	$wa11y_settings = isset( $metabox[ 'args' ] ) ? $metabox[ 'args' ] : array();

	// Get enable tools settings
	$wa11y_enable_tools_settings = isset( $wa11y_settings[ 'enable_tools' ] ) ? $wa11y_settings[ 'enable_tools' ] : array();

	// Get the user roles
	$user_roles = get_editable_roles();

	switch( $metabox[ 'id' ] ) {

		// About Wa11y
		case 'wa11y-about':

			// Print the plugin name (with link to site)
			?><p>@TODO: ADD DESCRIPTION Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras risus urna, ullamcorper in ullamcorper in, dapibus vel leo. Nam diam odio, aliquam quis accumsan a, viverra non sem. Pellentesque non fringilla sapien.</p><?php

			// Print the plugin version and author (with link to site)
			?><p><strong><?php echo _e( 'Version', 'wa11y' ); ?>:</strong> <?php echo preg_match( '/^([0-9]+)$/i', WA11Y_VERSION ) ? number_format( WA11Y_VERSION, 1, '.', '' ) : WA11Y_VERSION; ?><br />
			<strong><?php echo _e( 'Author', 'wa11y' ); ?>:</strong> <a href="http://bamadesigner.com/" target="_blank">Rachel Carden</a></p><?php

			break;

		// Save Changes
		case 'wa11y-save-changes':
			echo submit_button( __( 'Save Your Changes', 'wa11y' ), 'primary', 'save_wa11y_settings', false, array( 'id' => 'save-wa11y-settings-mb' ) );
			break;

		// tota11y Settings
		case 'wa11y-tota11y-settings':

			// Get tota11y settings
			$wa11y_tota11y_settings = isset( $wa11y_settings[ 'tools' ] ) && isset( $wa11y_settings[ 'tools' ][ 'tota11y' ] ) ? $wa11y_settings[ 'tools' ][ 'tota11y' ] : array();

			?><div class="wa11y-tool-settings has-logo tota11y">
				<div class="tool-header">
					<input class="tool-checkbox" id="tota11y" type="checkbox" name="wa11y_settings[enable_tools][]" value="tota11y"<?php checked( is_array( $wa11y_enable_tools_settings ) && in_array( 'tota11y', $wa11y_enable_tools_settings) ); ?> />
					<label class="tool-label" for="tota11y"><?php printf( __( 'Enable %s', 'wa11y' ), '<span class="tota11y-red">t</span><span class="tota11y-orange">o</span><span class="tota11y-yellow">t</span><span class="tota11y-green">a</span><span class="tota11y-blue">1</span><span class="tota11y-indigo">1</span><span class="tota11y-violet">y</span>' ); ?></label>
					<div class="tool-desc">
						<p><?php printf( __( '%1$s%2$s%3$s is an accessibility visualization toolkit provided by your friends at %4$s%5$s%6$s. It is a single JavaScript file that inserts a small button in the bottom corner of your document and helps visualize how your site performs with assistive technologies.', 'wa11y' ), '<a href="http://khan.github.io/tota11y/" target="_blank">', 'tota11y', '</a>', '<a href="http://khanacademy.org/" target="_blank">', 'Khan Academy', '</a>' ); ?></p>
						<h3 class="why-header"><?php printf( __( 'Why %s Is Awesome', 'wa11y' ), 'tota11y' ); ?></h3>
						<p><?php printf( __( '%1$s consists of several plugins, each with their own functionality, that works to help you visualize accessibility violations (and successes) while also educating you on best practices. Beyond simply pointing out errors, many %2$s plugins also suggest ways to fix these violations - specifically tailored to your document.', 'wa11y' ), 'tota11y', 'tota11y' ); ?></p>
						<h3 class="use-header"><?php printf( __( 'Best Uses For %s', 'wa11y' ), 'tota11y' ); ?></h3>
						<p><?php /* @TODO add other tool */ printf( __( '%s is built to scan, and provide feedback on, an entire document so this tool is best used to evaluate pages on the front-end of your site. If you only want to evaluate a specific section of your document, like the content in your loop for example, then [INSERT TOOL HERE] is for you.', 'wa11y' ), 'tota11y' ); ?></p>
					</div> <!-- .tool-desc -->
				</div> <!-- .tool-header -->
				<p class="tool-settings-warning"><?php printf( __( 'If no user roles are selected or user capability is provided, %s will load for all logged-in users.', 'wa11y' ), 'tota11y' ); ?></p>
				<fieldset>
					<ul id="wa11y-tota11y-settings-list" class="tool-settings-list"><?php

						if ( ! empty( $user_roles ) ) {
							?><li><label class="tool-option-header"><?php printf( __( 'Only load %s for specific user roles', 'wa11y' ), 'tota11y' ); ?>:</label> <?php

								foreach( $user_roles as $user_role_key => $user_role ) {
									?><input class="tool-option-checkbox" id="tota11y-user-role-<?php echo $user_role_key; ?>" type="checkbox" name="wa11y_settings[tools][tota11y][load_user_roles][]" value="<?php echo $user_role_key; ?>"<?php checked( isset( $wa11y_tota11y_settings[ 'load_user_roles' ] ) && in_array( $user_role_key, $wa11y_tota11y_settings[ 'load_user_roles' ] ) ); ?> />
									<label class="tool-option-label" for="tota11y-user-role-<?php echo $user_role_key; ?>"><?php echo $user_role[ 'name' ]; ?></label><?php
								}

							?></li><?php
						}

						?><li><label class="tool-option-header" for="tota11y-user-capability"><?php printf( __( 'Only load %s for a specific user capability', 'wa11y' ), 'tota11y' ); ?>:</label> <input class="tool-setting-text" id="tota11y-user-capability" type="text" name="wa11y_settings[tools][tota11y][load_user_capability]" value="<?php echo isset( $wa11y_tota11y_settings[ 'load_user_capability' ] ) ? $wa11y_tota11y_settings[ 'load_user_capability' ] : null; ?>" /> <span class="tool-option-side-note">e.g. view_tota11y</span></span></li>

						<li><label class="tool-option-header" for="tota11y-admin"><?php printf( __( 'Load %s in the admin', 'wa11y' ), 'tota11y' ); ?>:</label>
							<input class="tool-option-checkbox" id="tota11y-admin" type="checkbox" name="wa11y_settings[tools][tota11y][load_in_admin]" value="1"<?php checked( isset( $wa11y_tota11y_settings[ 'load_in_admin' ] ) && $wa11y_tota11y_settings[ 'load_in_admin' ] > 0 ); ?> />
							<span class="tool-option-side-note"><?php printf( __( 'This will load the %s button on all pages in the admin to give you a glimpse of admin accessibility.', 'wa11y' ), 'tota11y' ); ?></span>
						</li>

					</ul>
				</fieldset>
			</div> <!-- .wa11y-tool-settings --><?php

			break;

		// WAVE Settings
		case 'wa11y-wave-settings':

			// Get WAVE settings
			$wa11y_wave_settings = isset( $wa11y_settings[ 'tools' ] ) && isset( $wa11y_settings[ 'tools' ][ 'wave' ] ) ? $wa11y_settings[ 'tools' ][ 'wave' ] : array();

			// Have to disable the admin WAVE evaluation if SSL
			$disable_admin_wave = is_ssl();

			?><div class="wa11y-tool-settings has-logo wave">
				<div class="tool-header">
					<input class="tool-checkbox" id="wave" type="checkbox" name="wa11y_settings[enable_tools][]" value="wave"<?php checked( is_array( $wa11y_enable_tools_settings ) && in_array( 'wave', $wa11y_enable_tools_settings) ); ?> />
					<label class="tool-label" for="wave"><?php printf( __( 'Enable %1$s %2$s(Web Accessibility eValuation Tool)%3$s', 'wa11y' ), '<span class="wave-red">WAVE</span>', '<span class="thinner wave-gray">', '</span>' ); ?></label>
					<div class="tool-desc">
						<p><?php printf( __( '%1$s%2$s%3$s is a free evaluation tool provided by %4$s%5$s (Web Accessibility In Mind)%6$s. It can be used to evaluate a live website for a wide range of accessibility issues. When this tool is enabled, a \'View %7$s evaluation\' button will be placed in your WordPress admin bar to help you quickly evaluate the page you\'re viewing.', 'wa11y' ), '<a href="http://wave.webaim.org/" target="_blank">', 'WAVE', '</a>', '<a href="http://webaim.org/" target="_blank">', 'WebAIM', '</a>', 'WAVE' ); ?></p>
						<h3 class="why-header"><?php printf( __( 'Why %s Is Awesome', 'wa11y' ), 'WAVE' ); ?></h3>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras risus urna, ullamcorper in ullamcorper in, dapibus vel leo. Nam diam odio, aliquam quis accumsan a, viverra non sem. Pellentesque non fringilla sapien.</p>
						<h3 class="use-header"><?php printf( __( 'Best Uses For %s', 'wa11y' ), 'WAVE' ); ?></h3>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras risus urna, ullamcorper in ullamcorper in, dapibus vel leo. Nam diam odio, aliquam quis accumsan a, viverra non sem. Pellentesque non fringilla sapien.</p>
					</div> <!-- .tool-desc -->
				</div> <!-- .tool-header -->
				<p class="tool-settings-warning"><?php printf( __( 'If no user roles are selected or user capability is provided, %s will display for all logged-in users.', 'wa11y' ), 'WAVE' ); ?></p>
				<fieldset>
					<ul id="wa11y-wave-settings-list" class="tool-settings-list"><?php

						if ( ! empty( $user_roles ) ) {
							?><li><label class="tool-option-header"><?php printf( __( 'Only show %s for specific user roles', 'wa11y' ), 'WAVE' ); ?>:</label> <?php

							foreach( $user_roles as $user_role_key => $user_role ) {
								?><input class="tool-option-checkbox" id="wave-user-role-<?php echo $user_role_key; ?>" type="checkbox" name="wa11y_settings[tools][wave][load_user_roles][]" value="<?php echo $user_role_key; ?>"<?php checked( isset( $wa11y_wave_settings[ 'load_user_roles' ] ) && in_array( $user_role_key, $wa11y_wave_settings[ 'load_user_roles' ] ) ); ?> />
								<label class="tool-option-label" for="wave-user-role-<?php echo $user_role_key; ?>"><?php echo $user_role[ 'name' ]; ?></label><?php
							}

							?></li><?php
						}

						?><li><label class="tool-option-header" for="wave-user-capability"><?php printf( __( 'Only show %s for a specific user capability', 'wa11y' ), 'WAVE' ); ?>:</label> <input class="tool-setting-text" id="wave-user-capability" type="text" name="wa11y_settings[tools][wave][load_user_capability]" value="<?php echo isset( $wa11y_wave_settings[ 'load_user_capability' ] ) ? $wa11y_wave_settings[ 'load_user_capability' ] : null; ?>" /> <span class="tool-option-side-note">e.g. view_wave</span></span></li>

						<li<?php echo $disable_admin_wave ? ' class="disabled"' : null; ?>><label class="tool-option-header" for="wave-admin"><?php printf( __( 'Show %s evaluation in the admin', 'wa11y' ), 'WAVE' ); ?>:</label>
							<input class="tool-option-checkbox" id="wave-admin" type="checkbox" name="wa11y_settings[tools][wave][load_in_admin]" value="1"<?php checked( ! $disable_admin_wave && isset( $wa11y_wave_settings[ 'load_in_admin' ] ) && $wa11y_wave_settings[ 'load_in_admin' ] > 0 ); disabled( $disable_admin_wave ); ?> /><?php

							if ( $disable_admin_wave ) {
								?><span class="tool-option-disabled-message"><?php printf( __( 'At this time, the %1$s evaluation cannot be embedded on a site using SSL because the %2$s site is not using SSL.', 'wa11y' ), 'WAVE', 'WAVE' ); ?></span><?php
							} else {
								?><span class="tool-option-side-note"><?php printf( __( 'The %s evaluation will only display on screens where you are editing a post or a page.', 'wa11y' ), 'WAVE' ); ?></span><?php
							}

						?></li>
					</ul>
				</fieldset>
			</div><?php

			break;

	}

}

/**
 * Adds a settings link to the plugins table.
 *
 * @since   1.0
 * @param	$actions - an array of plugin action links
 * @param 	$plugin_file - path to the plugin file
 * @param	$context - The plugin context. Defaults are 'All', 'Active', 'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use', 'Drop-ins', 'Search'
 * @return 	array - the links info after it has been filtered
 */
// Add plugin action links
add_filter( 'plugin_action_links_wa11y/wa11y.php', 'wa11y_add_plugin_action_links', 10, 4 );
function wa11y_add_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

	// Add link to our settings page
	$actions[ 'settings' ] = '<a href="' . add_query_arg( array( 'page' => 'wa11y' ), admin_url( 'options-general.php' ) ) . '" title="' . esc_attr__( "Visit this plugin's settings page", 'wa11y' ) . '">' . __( 'Settings' , 'wa11y' ) . '</a>';

	return $actions;

}
