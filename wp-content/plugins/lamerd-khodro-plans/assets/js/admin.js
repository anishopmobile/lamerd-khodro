/**
 * Plan editor: Jalali date picker and image picker.
 */
( function ( $ ) {
	'use strict';

	// The datepicker bundle references the global `$`, but WordPress ships jQuery in
	// no-conflict mode, so the picker throws before it can wrap the inputs.
	if ( ! window.$ ) {
		window.$ = window.jQuery;
	}

	var data = window.LKP_ADMIN || { strings: {} };
	var strings = data.strings || {};
	var noImage = data.noImage || '';

	/* ---------------------------------------------------------------- Dates */

	function initDates() {
		if ( ! $.fn.pDatepicker || ! $.fn.persianDatepicker ) {
			return;
		}

		$( '.lkp-date' ).each( function () {
			var input = $( this );

			if ( input.data( 'lkp-picker-ready' ) ) {
				return;
			}

			input.data( 'lkp-picker-ready', true ).pDatepicker( {
				format: 'YYYY/MM/DD',
				autoClose: true,
				initialValue: false,
				observer: true,
				responsive: true,
				persianDigit: true,
				calendar: { persian: { locale: 'fa' } },
			} );
		} );
	}

	/* --------------------------------------------------------------- Images */

	var hidden = document.querySelector( '[data-lkp-images]' );
	var grid = document.querySelector( '[data-lkp-media-grid]' );
	var empty = document.querySelector( '[data-lkp-media-empty]' );
	var trigger = document.querySelector( '[data-lkp-media]' );
	var frame = null;
	var sources = {};

	function images() {
		if ( ! hidden || ! hidden.value ) {
			return [];
		}

		return hidden.value
			.split( ',' )
			.map( function ( value ) {
				return value.trim();
			} )
			.filter( Boolean );
	}

	function store( ids ) {
		if ( hidden ) {
			hidden.value = ids.join( ',' );
		}
		if ( empty ) {
			empty.hidden = ids.length > 0;
		}
	}

	function figure( id, index ) {
		var src = sources[ id ] || noImage;
		var item = document.createElement( 'figure' );
		item.className = 'lkp-media-item';
		item.dataset.id = id;
		item.dataset.src = src;

		var img = document.createElement( 'img' );
		img.src = src || noImage;
		img.alt = '';
		item.appendChild( img );

		var remove = document.createElement( 'button' );
		remove.type = 'button';
		remove.className = 'lkp-media-remove';
		remove.setAttribute( 'aria-label', strings.remove || 'حذف تصویر' );
		remove.textContent = '×';
		remove.addEventListener( 'click', function () {
			store(
				images().filter( function ( value ) {
					return value !== id;
				} )
			);
			render();
		} );
		item.appendChild( remove );

		if ( 0 === index ) {
			var badge = document.createElement( 'figcaption' );
			badge.textContent = strings.primary || 'تصویر اصلی';
			item.appendChild( badge );
		} else {
			var makeMain = document.createElement( 'button' );
			makeMain.type = 'button';
			makeMain.className = 'lkp-media-main';
			makeMain.textContent = strings.makePrimary || 'اصلی کن';
			makeMain.addEventListener( 'click', function () {
				var list = images().filter( function ( value ) {
					return value !== id;
				} );
				list.unshift( id );
				store( list );
				render();
			} );
			item.appendChild( makeMain );
		}

		return item;
	}

	function render() {
		if ( ! grid ) {
			return;
		}

		var list = images();
		grid.innerHTML = '';

		list.forEach( function ( id, index ) {
			grid.appendChild( figure( id, index ) );
		} );

		if ( empty ) {
			empty.hidden = list.length > 0;
		}
	}

	if ( trigger ) {
		trigger.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			if ( ! window.wp || ! window.wp.media ) {
				return;
			}

			if ( ! frame ) {
				frame = window.wp.media( {
					title: strings.mediaTitle || 'انتخاب تصاویر طرح',
					button: { text: strings.mediaButton || 'افزودن به طرح' },
					library: { type: 'image' },
					multiple: true,
				} );

				frame.on( 'select', function () {
					var selection = frame.state().get( 'selection' );
					var list = images();

					selection.each( function ( attachment ) {
						var model = attachment.toJSON();
						var thumb =
							model.sizes && model.sizes.thumbnail
								? model.sizes.thumbnail.url
								: model.url;
						var id = String( model.id );

						sources[ id ] = thumb;

						if ( list.indexOf( id ) === -1 ) {
							list.push( id );
						}
					} );

					store( list );
					render();
				} );
			}

			frame.open();
		} );
	}

	$( function () {
		initDates();

		if ( grid ) {
			// Cache server-rendered thumbnails before the first rebuild.
			grid.querySelectorAll( '.lkp-media-item' ).forEach( function ( item ) {
				var img = item.querySelector( 'img' );
				if ( img ) {
					sources[ item.dataset.id ] = img.getAttribute( 'src' ) || noImage;
				}
			} );
		}
	} );
} )( window.jQuery );
