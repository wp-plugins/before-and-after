<?php
/* This file includes functions that will be introduced in Wordpress 3.6, 
 * but are not available in the stable version at the time of writing (v3.5.2).
 *
 * These functions are wrapped in function_exists() so that they will not collide
 * when the functions land in Wordpress 3.6
 *
 * Funnctions included:
 *   - has_shortcode
 *	 - shortcode_exists

This plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin .  If not, see <http://www.gnu.org/licenses/>.

*/

if (!function_exists('has_shortcode')){
	/**
	 * Whether the passed content contains the specified shortcode
	 *
	 * @since 3.6.0
	 *
	 * @global array $shortcode_tags
	 * @param string $tag
	 * @return boolean
	 */
	function has_shortcode( $content, $tag ) {
			if ( shortcode_exists( $tag ) ) {
					preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
					if ( empty( $matches ) )
							return false;

					foreach ( $matches as $shortcode ) {
							if ( $tag === $shortcode[2] )
									return true;
					}
			}
			return false;
	}
}

if (!function_exists('shortcode_exists')){
	/**
	 * Whether a registered shortcode exists named $tag
	 *
	 * @since 3.6.0
	 *
	 * @global array $shortcode_tags
	 * @param string $tag
	 * @return boolean
	 */
	function shortcode_exists( $tag ) {
			global $shortcode_tags;
			return array_key_exists( $tag, $shortcode_tags );
	}
}
