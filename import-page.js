(function() {
	document.onready = function() {
		var btn = document.querySelector( '.pdi-btn-select .btn' );
		btn.addEventListener( 'click', function( event ) {
			var target = event.target;
			target.parentElement.parentElement.querySelector( '.pdi-file-input' ).
				dispatchEvent( new MouseEvent( 'click' ) );
		});

		var input_field = document.querySelector( '.pdi-file-input' );
		input_field.addEventListener( 'change', function( event ) {
			var files = event.target.files;

			disable_alerts();

			var list_label = document.querySelector( '.pdi-file-list-label' );
			list_label.innerHTML = 'Files to Import';

			var list_tag = document.querySelector( '.pdi-file-list' );
			if ( list_tag ) {
				list_tag.remove();
			}

			var new_list_tag = document.createElement( 'ul' );
			new_list_tag.classList.add( 'pdi-file-list', 'list-group' );
			for ( var i = 0; i < files.length; i++ ) {
				var item = document.createElement( 'li' );
				item.classList.add( 'list-group-item' );
				item.innerHTML = files[ i ].name;
				var typeMatches = files[ i ].name.match( /.*\.(md|markdown|mdown)$/ );

				// attempts to infer the type from the file name, if for some reason the File object
				// didn't receive the file type data (this seems to occur on Windows 10)
				if ( 'text/markdown' !== files[ i ].type && ( typeMatches && 0 === typeMatches.length ) ) {
					item.classList.add( 'list-group-item-danger' );
					item.innerHTML += ' - Invalid file type, won\'t upload';
				}
				new_list_tag.appendChild( item );
			}

			list_label.after( new_list_tag );
			list_label.classList.remove( 'pdi-hidden' );

			var import_btn = document.querySelector( '.pdi-btn-import' );
			import_btn.classList.remove( 'pdi-hidden' );
			import_btn.querySelector( '.btn' ).classList.remove( 'disabled' );
		});

		var import_btn = document.querySelector( '.pdi-btn-import' );
		import_btn.addEventListener( 'click', function( event ) {
			var btn = event.target;
			if ( ! btn.classList.contains( 'btn' ) ) {
				btn = btn.querySelector( '.btn' );
			}

			var form_data = new FormData();
			for ( var i = 0; i < input_field.files.length; i++ ) {
				var file = input_field.files[ i ];
				var typeMatches = file.name.match( /.*\.(md|markdown|mdown)$/ );
				if ( 'text/markdown' === file.type || ( typeMatches && typeMatches.length > 0 ) ) {
					form_data.append( 'files[]', file );
				}
			}
			form_data.append( 'action', 'pdi_import' );
			form_data.append( '_ajax_nonce', PDI.nonce );
			form_data.append( 'post_status', document.querySelector( '.pdi-import-post-status' ).value );
			form_data.append( 'post_type'  , document.querySelector( '.pdi-import-post-type' ).value );
			form_data.append( 'post_author', document.querySelector( '.pdi-import-post-author' ).value );

			// disables the import button
			btn.classList.add( 'disabled' );

			disable_alerts();

			if ( form_data.getAll( 'files[]' ).length > 0 ) {
				jQuery.ajax({
					url: PDI.ajax_url,
					type: 'POST',
					dataType: 'text',
					contentType: false,
					processData: false,
					data: form_data,
					success: function( res ) {
						var json = JSON.parse( res );

						if ( json ) {
							if ( '1' === json.status ) {
								var alert_success = display_success();
								alert_success.innerHTML = 'Import succeeded.';

								var list_label = document.querySelector( '.pdi-file-list-label' );
								list_label.innerHTML = 'Added the following ' +
									'<code>' + json.post_type + '</code>\'s of status, ' +
									'<code>' + json.post_status + '</code>, by ' +
									'<code>' + json.post_author_name + '</code>.';

								var list_tag = document.querySelector( '.pdi-file-list' );
								if ( list_tag ) {
									list_tag.remove();
								}

								var new_list_tag = document.createElement( 'ul' );
								new_list_tag.classList.add( 'pdi-file-list', 'list-group' );
								for ( var i = 0; i < json.new_posts.length; i++ ) {
									var item = document.createElement( 'li' );
									item.classList.add( 'list-group-item',
										'list-group-item-success' );

									var a_perm = document.createElement( 'a' );
									a_perm.classList.add( 'post-view-perma' );
									a_perm.href = json.new_posts[ i ].post_perma;
									a_perm.innerHTML = json.new_posts[ i ].post_title;
									item.appendChild( a_perm );

									var a_edit = document.createElement( 'a' );
									a_edit.classList.add( 'post-edit-perma' );
									a_edit.href = json.new_posts[ i ].edit_perma;
									a_edit.innerHTML = 'Edit';
									item.appendChild( a_edit );

									new_list_tag.appendChild( item );
								}
								list_label.after( new_list_tag );

								// hides the import button
								btn.parentElement.classList.add( 'pdi-hidden' );
								btn.classList.remove( 'disabled' );
							} else {
								ajax_error_callback( res );
							}
						}
					},
					error: ajax_error_callback
				});
			} else {
				var alert_danger = display_error();
				alert_danger.innerHTML = 'No valid files provided; only <code>.md, .markdown, or .mdown</code> files ' +
					'are allowed. Try again.';
			}
		});

		/**
		 * Handles AJAX failure states.
		 *
		 * @param string res The HTTP response.
		 */
		function ajax_error_callback( res ) {
			var json = JSON.parse( res );

			if ( json ) {
				var alert_danger = display_error();
				switch ( json.status ) {
					case '-1':
						alert_danger.innerHTML = 'Import failed. You don\'t seem to have enough ' +
							'permissions to do that. Or, your nonce is invalid. You ' +
							'might try logging out and logging back in.';
						break;
					case '-2':
						alert_danger.innerHTML = 'Import failed. The files weren\'t received. ' +
							'Reload the page and try again.';
						break;
					case '-3':
						alert_danger.innerHTML = 'Import failed. Those files weren\'t readable ' +
							'server side. Reload the page and try again.';
						break;
					case '-4':
						alert_danger.innerHTML = 'Import failed. Post insertion failed. You ' +
							'might not have permission to create the posts.';
						break;
					default:
						alert_danger.innerHTML = 'Import failed. Try reloading the page and ' +
							'reimporting.';
				}
			}
		}

		/**
		 * Toggles the success alert at the top of the page.
		 *
		 * @return HTMLElement The success alert.
		 */
		function display_success() {

			// hides error alert
			var alert_danger = document.querySelector( '.alert-danger' );
			if ( ! alert_danger.classList.contains( 'pdi-hidden' ) ) {
				alert_danger.classList.add( 'pdi-hidden' );
			}

			// shows success alert
			var alert_success = document.querySelector( '.alert-success' );
			if ( alert_success.classList.contains( 'pdi-hidden' ) ) {
				alert_success.classList.remove( 'pdi-hidden' );
			}

			return alert_success;
		}

		/**
		 * Toggles the error ("danger") alert at the top of the page.
		 *
		 * @return HTMLElement The error alert.
		 */
		function display_error() {

			// shows error alert
			var alert_danger = document.querySelector( '.alert-danger' );
			if ( alert_danger.classList.contains( 'pdi-hidden' ) ) {
				alert_danger.classList.remove( 'pdi-hidden' );
			}

			// hides success alert
			var alert_success = document.querySelector( '.alert-success' );
			if ( ! alert_success.classList.contains( 'pdi-hidden' ) ) {
				alert_success.classList.add( 'pdi-hidden' );
			}

			return alert_danger;
		}

		/**
		 * Turns off all alerts.
		 */
		function disable_alerts() {
			var alerts = document.querySelectorAll( '.alert' );
			for ( var i = 0; i < alerts.length; i++ ) {
				if ( ! alerts[ i ].classList.contains( 'pdi-hidden' ) ) {
					alerts[ i ].classList.add( 'pdi-hidden' );
				}
			}
		}
	};
})();
