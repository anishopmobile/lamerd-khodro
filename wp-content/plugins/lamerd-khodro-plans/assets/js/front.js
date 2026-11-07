/**
 * Plans section: live countdown, gallery and AJAX registration requests.
 */
( function () {
	'use strict';

	var section = document.querySelector( '[data-lkp-section]' );
	if ( ! section || typeof LKP === 'undefined' ) {
		return;
	}

	var config = LKP;
	var faDigits = '۰۱۲۳۴۵۶۷۸۹';
	var selectedFiles = [];
	var lastFocused = null;
	var countdownTimer = null;

	function fa( value ) {
		return String( value ).replace( /\d/g, function ( d ) {
			return faDigits[ Number( d ) ];
		} );
	}

	function pad( number ) {
		return String( number ).padStart( 2, '0' );
	}

	function countdownText( deadline ) {
		var remaining = Math.max( 0, Math.floor( deadline - Date.now() / 1000 ) );
		var days = Math.floor( remaining / 86400 );
		remaining %= 86400;
		var hours = Math.floor( remaining / 3600 );
		remaining %= 3600;
		var minutes = Math.floor( remaining / 60 );
		var seconds = remaining % 60;

		return fa( days ) + ' روز ' + fa( pad( hours ) + ':' + pad( minutes ) + ':' + pad( seconds ) );
	}

	function tick() {
		var now = Math.floor( Date.now() / 1000 );

		section.querySelectorAll( '[data-lkp-countdown]' ).forEach( function ( node ) {
			var deadline = Number( node.dataset.deadline || 0 );
			if ( ! deadline ) {
				return;
			}

			if ( now >= deadline ) {
				var card = node.closest( '[data-lkp-card]' );
				node.textContent = fa( '00:00:00' );
				if ( card && 'future' === card.dataset.state ) {
					// Registration just opened: reload so the request button appears.
					window.location.reload();
				}
				return;
			}

			node.textContent = countdownText( deadline );
		} );
	}

	// Countdown.
	if ( section.querySelector( '[data-lkp-countdown]' ) ) {
		tick();
		countdownTimer = window.setInterval( tick, 1000 );
	}

	// Overlays.
	function openOverlay( overlay ) {
		lastFocused = document.activeElement;
		overlay.hidden = false;
		document.body.classList.add( 'lkp-lock' );
		var dialog = overlay.querySelector( '.lkp-dialog' );
		if ( dialog ) {
			dialog.focus();
		}
	}

	function closeOverlay( overlay ) {
		overlay.hidden = true;
		if ( ! section.querySelector( '.lkp-overlay:not([hidden])' ) ) {
			document.body.classList.remove( 'lkp-lock' );
		}
		if ( lastFocused && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
			lastFocused = null;
		}
	}

	function closeAll() {
		section.querySelectorAll( '.lkp-overlay:not([hidden])' ).forEach( closeOverlay );
	}

	section.addEventListener( 'click', function ( event ) {
		var closer = event.target.closest( '[data-lkp-close]' );
		if ( closer ) {
			var overlay = closer.closest( '.lkp-overlay' );
			if ( overlay ) {
				closeOverlay( overlay );
			}
			return;
		}

		// Click on the backdrop closes the dialog.
		if ( event.target.classList.contains( 'lkp-overlay' ) ) {
			closeOverlay( event.target );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			closeAll();
		}
	} );

	// Keep keyboard focus inside the open dialog.
	section.addEventListener( 'keydown', function ( event ) {
		if ( 'Tab' !== event.key ) {
			return;
		}

		var overlay = section.querySelector( '.lkp-overlay:not([hidden])' );
		if ( ! overlay ) {
			return;
		}

		var focusable = overlay.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea, select, [tabindex]:not([tabindex="-1"])'
		);
		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	// Gallery.
	var galleryModal = section.querySelector( '[data-lkp-gallery-modal]' );
	var galleryImage = galleryModal ? galleryModal.querySelector( '[data-lkp-gallery-image]' ) : null;
	var galleryIndex = galleryModal ? galleryModal.querySelector( '[data-lkp-gallery-index]' ) : null;
	var galleryImages = [];
	var galleryPos = 0;

	function showGallery() {
		if ( ! galleryImage || ! galleryImages.length ) {
			return;
		}
		galleryImage.src = galleryImages[ galleryPos ];
		if ( galleryIndex ) {
			galleryIndex.textContent = fa( galleryPos + 1 ) + ' / ' + fa( galleryImages.length );
		}
	}

	function stepGallery( direction ) {
		if ( ! galleryImages.length ) {
			return;
		}
		galleryPos = ( galleryPos + direction + galleryImages.length ) % galleryImages.length;
		showGallery();
	}

	section.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-lkp-gallery]' );
		if ( button ) {
			try {
				galleryImages = JSON.parse( button.dataset.images || '[]' );
			} catch ( error ) {
				galleryImages = [];
			}
			galleryPos = 0;
			showGallery();
			openOverlay( galleryModal );
			return;
		}

		if ( event.target.closest( '[data-lkp-gallery-prev]' ) ) {
			stepGallery( -1 );
		}
		if ( event.target.closest( '[data-lkp-gallery-next]' ) ) {
			stepGallery( 1 );
		}
	} );

	// Request form.
	var modal = section.querySelector( '[data-lkp-modal]' );
	var successModal = section.querySelector( '[data-lkp-success]' );
	var form = modal ? modal.querySelector( '[data-lkp-form]' ) : null;
	var fileInput = form ? form.querySelector( '[data-lkp-files]' ) : null;
	var preview = form ? form.querySelector( '[data-lkp-preview]' ) : null;
	var errorBox = form ? form.querySelector( '[data-lkp-error]' ) : null;
	var submitButton = form ? form.querySelector( '[data-lkp-submit]' ) : null;
	var planLabel = modal ? modal.querySelector( '[data-lkp-plan-label]' ) : null;

	function resetFiles() {
		selectedFiles = [];
		renderPreview();
		if ( fileInput ) {
			fileInput.value = '';
		}
	}

	function renderPreview() {
		if ( ! preview ) {
			return;
		}

		preview.innerHTML = '';

		if ( ! selectedFiles.length ) {
			var empty = document.createElement( 'p' );
			empty.className = 'lkp-docs-empty';
			empty.textContent = 'تصاویر مدارک را انتخاب کنید';
			preview.appendChild( empty );
			return;
		}

		selectedFiles.forEach( function ( item, index ) {
			var thumb = document.createElement( 'figure' );
			thumb.className = 'lkp-docs-item';

			var img = document.createElement( 'img' );
			img.src = item.url;
			img.alt = 'مدرک ' + fa( index + 1 );

			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.setAttribute( 'aria-label', 'حذف تصویر' );
			remove.textContent = '×';
			remove.addEventListener( 'click', function () {
				URL.revokeObjectURL( item.url );
				selectedFiles.splice( index, 1 );
				renderPreview();
			} );

			var label = document.createElement( 'span' );
			label.textContent = fa( index + 1 );

			thumb.appendChild( img );
			thumb.appendChild( remove );
			thumb.appendChild( label );
			preview.appendChild( thumb );
		} );
	}

	function showError( message ) {
		if ( ! errorBox ) {
			return;
		}
		if ( message ) {
			errorBox.textContent = message;
			errorBox.hidden = false;
		} else {
			errorBox.textContent = '';
			errorBox.hidden = true;
		}
	}

	function openRequest( button ) {
		if ( ! modal || ! form ) {
			return;
		}

		form.reset();
		resetFiles();
		showError( '' );

		form.querySelector( '[name="plan_id"]' ).value = button.dataset.planId || '';
		var opened = form.querySelector( '[data-lkp-opened]' );
		if ( opened ) {
			opened.value = String( Math.floor( Date.now() / 1000 ) );
		}

		if ( planLabel ) {
			planLabel.textContent = [ button.dataset.planCompany, button.dataset.planTitle ].filter( Boolean ).join( ' · ' );
		}

		openOverlay( modal );
		var firstField = form.querySelector( 'input[name="name"]' );
		if ( firstField ) {
			firstField.focus();
		}
	}

	section.addEventListener( 'click', function ( event ) {
		var opener = event.target.closest( '[data-lkp-open]' );
		if ( opener ) {
			event.preventDefault();
			openRequest( opener );
		}
	} );

	if ( fileInput ) {
		fileInput.addEventListener( 'change', function ( event ) {
			var files = Array.prototype.slice.call( event.target.files || [] );
			showError( '' );

			for ( var i = 0; i < files.length; i++ ) {
				var file = files[ i ];

				if ( [ 'image/jpeg', 'image/png' ].indexOf( file.type ) === -1 ) {
					showError( config.strings.fileType );
					continue;
				}
				if ( file.size > config.maxSize ) {
					showError( config.strings.fileSize );
					continue;
				}
				if ( selectedFiles.length >= config.maxFiles ) {
					showError( config.strings.fileCount );
					break;
				}

				selectedFiles.push( { file: file, url: URL.createObjectURL( file ) } );
			}

			renderPreview();
			event.target.value = '';
		} );
	}

	if ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			showError( '' );

			if ( ! selectedFiles.length ) {
				showError( config.strings.needDocs );
				return;
			}

			var data = new FormData( form );
			data.delete( 'documents[]' );
			selectedFiles.forEach( function ( item ) {
				data.append( 'documents[]', item.file );
			} );

			var original = submitButton ? submitButton.textContent : '';
			if ( submitButton ) {
				submitButton.disabled = true;
				submitButton.textContent = config.strings.sending;
			}

			window
				.fetch( config.restUrl, {
					method: 'POST',
					body: data,
					headers: { 'X-LKP-Nonce': config.nonce },
					credentials: 'same-origin',
				} )
				.then( function ( response ) {
					return response.json().then( function ( payload ) {
						return { ok: response.ok, payload: payload };
					} );
				} )
				.then( function ( result ) {
					if ( ! result.ok || ! result.payload || ! result.payload.ok ) {
						var message = result.payload && result.payload.text ? result.payload.text : config.strings.genericError;
						showError( message );
						return;
					}

					closeOverlay( modal );
					selectedFiles = [];
					renderPreview();

					var tracking = successModal ? successModal.querySelector( '[data-lkp-tracking]' ) : null;
					if ( tracking && result.payload.code ) {
						tracking.textContent = result.payload.code;
					}
					if ( successModal ) {
						openOverlay( successModal );
					}
				} )
				.catch( function () {
					showError( config.strings.networkError );
				} )
				.finally( function () {
					if ( submitButton ) {
						submitButton.disabled = false;
						submitButton.textContent = original || config.strings.submit;
					}
				} );
		} );
	}

	// Clean up the countdown when the section leaves the DOM.
	window.addEventListener( 'pagehide', function () {
		if ( countdownTimer ) {
			window.clearInterval( countdownTimer );
		}
	} );
} )();
