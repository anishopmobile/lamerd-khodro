<?php
/**
 * Jalali (Persian) date conversion and formatting.
 *
 * The admin form sends Jalali date/time strings; timestamps are always rebuilt
 * on the server so a tampered request cannot change a plan's schedule.
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

/**
 * Jalali date helper.
 */
class LKP_Jalali {

	/**
	 * Normalise Persian/Arabic digits to Latin digits.
	 *
	 * @param string $value Input.
	 * @return string
	 */
	public static function latin_digits( $value ) {
		$value = (string) $value;
		$map   = array(
			'۰' => '0',
			'۱' => '1',
			'۲' => '2',
			'۳' => '3',
			'۴' => '4',
			'۵' => '5',
			'۶' => '6',
			'۷' => '7',
			'۸' => '8',
			'۹' => '9',
			'٠' => '0',
			'١' => '1',
			'٢' => '2',
			'٣' => '3',
			'٤' => '4',
			'٥' => '5',
			'٦' => '6',
			'٧' => '7',
			'٨' => '8',
			'٩' => '9',
		);

		return strtr( $value, $map );
	}

	/**
	 * Persian digits for display.
	 *
	 * @param string|int $value Value.
	 * @return string
	 */
	public static function persian_digits( $value ) {
		return strtr( (string) $value, self::digit_map() );
	}

	/**
	 * Latin digit map.
	 *
	 * @return array<string,string>
	 */
	private static function digit_map() {
		return array(
			'0' => '۰',
			'1' => '۱',
			'2' => '۲',
			'3' => '۳',
			'4' => '۴',
			'5' => '۵',
			'6' => '۶',
			'7' => '۷',
			'8' => '۸',
			'9' => '۹',
		);
	}

	/**
	 * Jalali month names.
	 *
	 * @return string[]
	 */
	public static function months() {
		return array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	}

	/**
	 * Convert a Jalali date to Gregorian.
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month.
	 * @param int $jd Jalali day.
	 * @return array{0:int,1:int,2:int}
	 */
	public static function to_gregorian( $jy, $jm, $jd ) {
		$jy += 1595;
		$days = -355668 + ( 365 * $jy ) + ( intdiv( $jy, 33 ) * 8 ) + intdiv( ( $jy % 33 ) + 3, 4 ) + $jd
			+ ( ( $jm < 7 ) ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 ) + 186 );

		$gy    = 400 * intdiv( $days, 146097 );
		$days %= 146097;

		if ( $days > 36524 ) {
			$days--;
			$gy   += 100 * intdiv( $days, 36524 );
			$days %= 36524;
			if ( $days >= 365 ) {
				$days++;
			}
		}

		$gy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$gy  += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}

		$gd    = $days + 1;
		$leap  = ( 0 === $gy % 4 && 0 !== $gy % 100 ) || 0 === $gy % 400;
		$month = array( 0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

		for ( $gm = 1; $gm <= 12 && $gd > $month[ $gm ]; $gm++ ) {
			$gd -= $month[ $gm ];
		}

		return array( $gy, $gm, $gd );
	}

	/**
	 * Convert a Gregorian date to Jalali.
	 *
	 * @param int $gy Gregorian year.
	 * @param int $gm Gregorian month.
	 * @param int $gd Gregorian day.
	 * @return array{0:int,1:int,2:int}
	 */
	public static function to_jalali( $gy, $gm, $gd ) {
		$g_days = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2    = ( $gm > 2 ) ? $gy + 1 : $gy;
		$days   = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 )
			+ intdiv( $gy2 + 399, 400 ) + $gd + $g_days[ $gm - 1 ];

		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$jy  += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return array( $jy, $jm, $jd );
	}

	/**
	 * Parse a "YYYY/MM/DD" Jalali string.
	 *
	 * @param string $value Date string.
	 * @return array{0:int,1:int,2:int}|null
	 */
	public static function parse( $value ) {
		$value = trim( self::latin_digits( (string) $value ) );
		$value = str_replace( array( '-', '.', ' ' ), '/', $value );

		if ( ! preg_match( '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $value, $m ) ) {
			return null;
		}

		$jy = (int) $m[1];
		$jm = (int) $m[2];
		$jd = (int) $m[3];

		if ( $jy < 1300 || $jy > 1500 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31 ) {
			return null;
		}

		list( $gy, $gm, $gd ) = self::to_gregorian( $jy, $jm, $jd );
		list( $ry, $rm, $rd ) = self::to_jalali( $gy, $gm, $gd );

		if ( $ry !== $jy || $rm !== $jm || $rd !== $jd ) {
			return null;
		}

		return array( $jy, $jm, $jd );
	}

	/**
	 * Build a Unix timestamp from a Jalali date and a HH:MM time.
	 *
	 * @param string $date Jalali date (YYYY/MM/DD).
	 * @param string $time Time (HH:MM), defaults to midnight.
	 * @return int Timestamp, or 0 when invalid.
	 */
	public static function to_timestamp( $date, $time = '00:00' ) {
		$parts = self::parse( $date );
		if ( ! $parts ) {
			return 0;
		}

		$time = trim( self::latin_digits( (string) $time ) );
		if ( ! preg_match( '/^(\d{1,2}):(\d{1,2})$/', $time, $tm ) ) {
			$tm = array( 0, '0', '0' );
		}

		$hour   = min( 23, max( 0, (int) $tm[1] ) );
		$minute = min( 59, max( 0, (int) $tm[2] ) );

		list( $gy, $gm, $gd ) = self::to_gregorian( $parts[0], $parts[1], $parts[2] );

		$local = sprintf( '%04d-%02d-%02d %02d:%02d:00', $gy, $gm, $gd, $hour, $minute );

		try {
			$date_obj = new DateTimeImmutable( $local, wp_timezone() );
			return (int) $date_obj->getTimestamp();
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * Timestamp parts in the site timezone.
	 *
	 * @param int $timestamp Timestamp.
	 * @return array{jy:int,jm:int,jd:int,hour:int,minute:int}
	 */
	public static function parts( $timestamp ) {
		$local = getdate( (int) $timestamp + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		list( $jy, $jm, $jd ) = self::to_jalali( (int) $local['year'], (int) $local['mon'], (int) $local['mday'] );

		return array(
			'jy'     => $jy,
			'jm'     => $jm,
			'jd'     => $jd,
			'hour'   => (int) $local['hours'],
			'minute' => (int) $local['minutes'],
		);
	}

	/**
	 * Gregorian formatted date in the site timezone (for <input type="time"> / admin JS).
	 *
	 * @param int $timestamp Timestamp.
	 * @return array{date:string,time:string}
	 */
	public static function gregorian_input( $timestamp ) {
		$local = getdate( (int) $timestamp + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );

		return array(
			'date' => sprintf( '%04d-%02d-%02d', $local['year'], $local['mon'], $local['mday'] ),
			'time' => sprintf( '%02d:%02d', $local['hours'], $local['minutes'] ),
		);
	}

	/**
	 * Jalali string for a timestamp: "1405/06/05".
	 *
	 * @param int $timestamp Timestamp.
	 * @return string
	 */
	public static function date_string( $timestamp ) {
		$p = self::parts( $timestamp );
		return sprintf( '%04d/%02d/%02d', $p['jy'], $p['jm'], $p['jd'] );
	}

	/**
	 * Time string for a timestamp: "23:59".
	 *
	 * @param int $timestamp Timestamp.
	 * @return string
	 */
	public static function time_string( $timestamp ) {
		$p = self::parts( $timestamp );
		return sprintf( '%02d:%02d', $p['hour'], $p['minute'] );
	}

	/**
	 * Human Jalali date ("۵ شهریور ۱۴۰۵").
	 *
	 * @param int  $timestamp Timestamp.
	 * @param bool $with_time Include the time.
	 * @return string
	 */
	public static function format( $timestamp, $with_time = false ) {
		$timestamp = (int) $timestamp;
		if ( $timestamp <= 0 ) {
			return '—';
		}

		$p      = self::parts( $timestamp );
		$months = self::months();
		$month  = $months[ $p['jm'] - 1 ] ?? '';
		$text   = self::persian_digits( $p['jd'] ) . ' ' . $month . ' ' . self::persian_digits( $p['jy'] );

		if ( $with_time ) {
			$text .= ' — ' . self::persian_digits( sprintf( '%02d:%02d', $p['hour'], $p['minute'] ) );
		}

		return $text;
	}
}
