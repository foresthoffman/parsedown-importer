<?php
/**
 * Plugin Name:     Parsedown Importer
 * Version:         1.0.8
 * Description:     An unofficial Parsedown importer for translating Markdown files into WordPress posts/pages.
 * Author:          Forest Hoffman
 * Author URI:      http://foresthoffman.com/
 * Plugin URI:      https://plugins.svn.wordpress.org/parsedown-importer/
 * Text Domain:     pdi
 * Domain Path:     /languages
 * License: 		GPL2
 *
 * Parsedown Importer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Parsedown Importer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Parsedown Importer. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.
 *
 * @package Pdi
 */

require_once dirname( __FILE__ ) . '/ParsedownExtended.php';

if ( ! class_exists( 'ParsedownImporter' ) ) {
	class ParsedownImporter {
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
		}

		public function init() {

			// registering bootstrap
			wp_register_style(
				'bootstrap',
				plugin_dir_url( __FILE__ ) . 'node_modules/bootstrap/dist/css/bootstrap.min.css',
				null, 1.0
			);

			// registering custom stylesheet
			wp_register_style(
				'pdi-import-page',
				plugin_dir_url( __FILE__ ) . 'import-page.css',
				array( 'bootstrap' ), 1.0
			);

			// registering custom script for import page
			wp_register_script(
				'pdi-import-page-js',
				plugin_dir_url( __FILE__ ) . 'import-page.js',
				null, 1.0, true
			);

			add_action( 'admin_menu', array( $this, 'add_import_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_page_scripts' ) );
			add_action( 'wp_ajax_pdi_import', array( $this, 'pdi_import' ) );
		}

		public function add_import_page() {
			$page_title = 'Parsedown Import Page';
			$menu_title = 'Parsedown Import';
			$capability = 'import';
			$menu_slug  = 'pdi-page';
			$function   = array( $this, 'render_import_page' );
			$page = add_management_page( $page_title, $menu_title, $capability, $menu_slug,
				$function );
		}

		public function enqueue_import_page_scripts( $hook ) {
			if ( 'tools_page_pdi-page' === $hook ) {
				wp_enqueue_style( 'pdi-import-page' );
				wp_enqueue_script( 'pdi-import-page-js' );

				$localized_array = array(
					'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
					'nonce'    => wp_create_nonce( plugin_basename( __FILE__ ), 'pdi_import_nonce' )
				);
				wp_localize_script( 'pdi-import-page-js', 'PDI', $localized_array );
			}
		}

		public function render_import_page() {
			?>
			<div class='alert alert-danger pdi-hidden' role='alert'></div>
			<div class='alert alert-success pdi-hidden' role='alert'></div>
			<h1>Parsedown Import</h1>
			<p>Import Markdown files (ending with <code>.md, .markdown, or .mdown</code>) and convert them directly into WordPress posts/pages.</p>
			<div class='pdi-import-options'>
				<h3>Import Settings</h3>
				<!-- post status option -->

				<div class='pdi-import-option-wrap'>
					<label class='pdi-import-option-label' for='pdi-import-post-status'>
						Post status:
					</label>
					<select class='pdi-import-post-status'>
						<option value='draft' selected>Draft</option>
						<option value='publish'>Publish (not recommended)</option>
						<option value='private'>Private</option>
					</select>
				</div>
				<!-- #post status option -->

				<!-- post type option -->
				<div class='pdi-import-option-wrap'>
					<label class='pdi-import-option-label' for='pdi-import-post-status'>
						Post Type:
					</label>
					<select class='pdi-import-post-type'>
						<option value='post' selected>Post</option>
						<option value='page'>Page</option>
					</select>
				</div>
				<!-- #post type option -->

				<!-- post author option -->
				<div class='pdi-import-option-wrap'>
					<label class='pdi-import-option-label' for='pdi-import-post-status'>
						Post author:
					</label>
					<select class='pdi-import-post-author'>
						<?php
						$user_array = get_users( array(
							'role__in' => array( 'administrator', 'editor', 'author', 'contributor' ),
							'orderby'  => 'Display Name',
							'fields'   => array(
								'ID',
								'display_name'
							)
						));
						foreach ( $user_array as $user ) {
							printf(
								'<option value="%d"%s>%s</option>',
								$user->ID,
								( (int) $user->ID === get_current_user_id() ? ' selected' : '' ),
								$user->display_name
							);
						}
						?>
					</select>
				</div>
				<!-- #post author option -->

				<div class='pdi-file-input-wrap'>
					<p class='pdi-btn-wrap pdi-btn-select'><a href='#' class='btn btn-primary' role='button'>Select files</a></p>
					<input class='pdi-file-input pdi-hidden' name='pdi-file-input' type='file' value='' multiple />
				</div>
			</div>
			<label class='pdi-file-list-label pdi-hidden' for='pdi-file-list'></label>
			<!-- <ul class='pdi-file-list list-group'></ul> -->
			<p class='pdi-btn-wrap pdi-btn-import pdi-hidden'><a href='#' class='btn btn-primary' role='button'>Import</a></p>
			<?php
		}

		public function pdi_import() {
			$response = array( 'status' => 0, 'new_posts' => array() );

			if ( ! check_ajax_referer( plugin_basename( __FILE__ ), 'pdi_import_nonce', false ) ||
					! current_user_can( 'import' ) ) {

				// invalid nonce, or not enough permissions
				$response['status'] = '-1';
				wp_die( json_encode( $response ) );
			}

			$new_posts = array();
			$Parsedown = new ParsedownExtended();

			if ( isset( $_FILES ) && isset( $_FILES['files'] ) && count( $_FILES['files'] ) > 0 ) {
				$files = $_FILES['files'];
				$post_status = '';
				$post_type = '';
				$post_author = 0;

				if ( ! empty( $_POST['post_status'] ) ) {
					$temp_status = strtolower( $_POST['post_status'] );
					if ( 'draft' === $temp_status ||
							'publish' === $temp_status ||
							'private' === $temp_status ) {
						$post_status = $temp_status;
					} else {
						$post_status = 'draft';
					}
				}

				if ( ! empty( $_POST['post_type'] ) ) {
					$temp_type = strtolower( $_POST['post_type'] );
					if ( 'post' === $temp_type || 'page' === $temp_type ) {
						$post_type = $temp_type;
					} else {
						$post_type = 'post';
					}
				}

				if ( ! empty( $_POST['post_author'] ) ) {
					$temp_author = (int) $_POST['post_author'];
					if ( $temp_author > 0 && user_can( $temp_author, 'edit_posts' ) ) {
						$post_author = $temp_author;
					} else {
						$post_author = get_current_user_id();
					}
				}

				for ( $i = 0; $i < count( $files['error'] ); $i++ ) {
					$text = $buffer = '';
					$post_title = substr( $files['name'][ $i ], 0, -3 );

					if ( $reader = fopen( $files['tmp_name'][ $i ], 'r' ) ) {
						while ( $buffer = fgets( $reader ) ) {
							$text .= $buffer;
						}
						fclose( $reader );

						$pd_text = $Parsedown->text( $text );

						$postarr = array(
							'post_status'  => $post_status,
							'post_type'    => $post_type,
							'post_author'  => $post_author,
							'post_title'   => esc_html( $post_title ),
							'post_content' => $pd_text
						);
						if ( $post_id = wp_insert_post( $postarr ) ) {
							$new_posts[] = array(
								'post_title' => $post_title,
								'post_perma' => get_post_permalink( $post_id ),
								'edit_perma' => admin_url() . "post.php?post={$post_id}&action=edit",
							);
						} else {

							// post insertion failure
							$reponse['status'] = '-4';
							wp_die( json_encode( $response ) );
						}
					} else {

						// file reader error
						$reponse['status'] = '-3';
						wp_die( json_encode( $response ) );
					}
				}
			} else {

				// no files to import
				$reponse['status'] = '-2';
				wp_die( json_encode( $response ) );
			}

			// success
			$response['status']           = '1';

			// used for outputting the added posts
			$response['new_posts']        = $new_posts;
			$response['post_status']      = $post_status;
			$response['post_type']        = $post_type;
			$response['post_author_name'] = get_userdata( $post_author )->display_name;
			wp_die( json_encode( $response ) );
		}
	}
	$ParsedownImporterPlugin = new ParsedownImporter();
}
