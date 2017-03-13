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

			var list_tag = document.querySelector( '.pdi-file-list' );
			for ( var i = 0; i < files.length; i++ ) {
				var item = document.createElement( 'li' );
				item.classList.add( 'list-group-item' );
				item.innerHTML = files[ i ].name;
				if ( 'text/markdown' !== files[ i ].type ) {
					item.classList.add( 'list-group-item-danger' );
					item.innerHTML += ' - Invalid file type, won\'t upload';
				}
				list_tag.appendChild( item );
			}

			import_btn.classList.remove('pdi-hidden');
		});

		var import_btn = document.querySelector( '.pdi-btn-import' );
		import_btn.addEventListener( 'click', function( event ) {
			var btn = event.target;
			var form_data = new FormData();
			for ( var i = 0; i < input_field.files.length; i++ ) {
				var file = input_field.files[ i ];
				if ( 'text/markdown' === file.type ) {
					form_data.append( 'files[]', file );
				}
			}
			form_data.append( 'action', 'pdi_import' );
			form_data.append( '_ajax_nonce', PDI.nonce );

			// disables the import button
			btn.classList.add( 'disabled' );

			if ( form_data.getAll( 'files[]' ).length > 0 ) {
				jQuery.ajax({
					url: PDI.ajax_url,
					type: 'POST',
					dataType: 'text',
					contentType: false,
					processData: false,
					data: form_data,
					success: function( res ) {
						event.target.disabled = false;

						var json = JSON.parse( res );
						if ( json ) {
							if ( '1' === json.status ) {
								var alert_success = display_success();
								alert_success.innerHTML = 'Import succeeded.';

								// hides the import button
								import_btn.classList.add( 'pdi-hidden' );
								import_btn.classList.remove( 'disabled' );
							} else {
								ajax_error_callback( res );
							}
						}
					},
					error: ajax_error_callback
				});
			} else {

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
	};
})();