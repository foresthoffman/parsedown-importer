<?php
/**
 * Plugin Name:     Parsedown Importer
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     pdi
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Pdi
 */

require_once 'inc/Parsedown.php';

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
			<p>Import Markdown files (ending with <code>.md</code>) and convert them directly into WordPress posts/pages.</p>
			<div class='jumbotron pdi-jumbo'>
				<p>
					Drag and drop<br/>or
				</p>
				<div class='divider'></div>
				<p class='pdi-btn-wrap pdi-btn-select'><a href='#' class='btn btn-primary' role='button'>Select files</a></p>
				<input class='pdi-file-input pdi-hidden' name='pdi-file-input' type='file' value='' multiple />
			</div>
			<ul class='pdi-file-list list-group'></ul>
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
			$Parsedown = new Parsedown();

			if ( isset( $_FILES ) && isset( $_FILES['files'] ) && count( $_FILES['files'] ) > 0 ) {
				$files = $_FILES['files'];
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
							'post_status'  => 'private',               // will be customizable
							'post_type'    => 'post',                  // will be customizable
							'post_author'  => get_current_user_id(),   // will be customizable
							'post_title'   => esc_html( $post_title ),
							'post_content' => $pd_text
						);
						if ( $post_id = wp_insert_post( $postarr ) ) {
							$new_posts[] = array(
								'post_title' => $post_title,
								'post_perma' => get_post_permalink( $post_id )
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
			$response['status'] = '1';
			$response['new_posts'] = $new_posts;
			wp_die( json_encode( $response ) );
		}
	}
	$ParsedownImporterPlugin = new ParsedownImporter();
}
