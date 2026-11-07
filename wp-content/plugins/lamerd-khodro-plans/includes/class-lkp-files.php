<?php
/**
 * Private file storage for applicant documents and plan circulars.
 *
 * Nothing stored here is web readable: files live outside the public uploads
 * structure and are streamed only to users with the management capability.
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

/**
 * Private upload handling.
 */
class LKP_Files {

	/**
	 * Maximum size for an applicant document (bytes).
	 */
	const DOCUMENT_MAX = 5242880; // 5 MB.

	/**
	 * Maximum size for a plan circular (bytes).
	 */
	const CIRCULAR_MAX = 10485760; // 10 MB.

	/**
	 * Allowed MIME types per upload kind.
	 *
	 * @param string $type Upload kind: document|circular|catalog.
	 * @return array<string,string> MIME => extension.
	 */
	public static function allowed( $type ) {
		if ( 'document' !== $type ) {
			return array(
				'application/pdf' => 'pdf',
				'image/jpeg'      => 'jpg',
				'image/png'       => 'png',
			);
		}

		return array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
		);
	}

	/**
	 * Maximum accepted size for an upload kind.
	 *
	 * @param string $type Upload kind.
	 * @return int
	 */
	public static function max_size( $type ) {
		return 'document' === $type ? self::DOCUMENT_MAX : self::CIRCULAR_MAX;
	}

	/**
	 * Ensure a directory exists and cannot be listed or executed.
	 *
	 * @param string $dir Absolute path.
	 * @return bool
	 */
	public static function protect_dir( $dir ) {
		$dir = (string) $dir;

		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$rules = "# Deny all direct access to private plan files.\n"
				. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n";
			@file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$index = trailingslashit( $dir ) . 'index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return is_dir( $dir ) && is_writable( $dir );
	}

	/**
	 * Absolute path to the private directory, prepared on demand.
	 *
	 * @return string|WP_Error
	 */
	public static function dir() {
		$dir = LKP_PRIVATE_DIR;

		if ( ! self::protect_dir( $dir ) ) {
			return new WP_Error( 'lkp_storage', 'پوشهٔ ذخیره‌سازی فایل‌های خصوصی قابل نوشتن نیست.' );
		}

		return $dir;
	}

	/**
	 * Validate and store one uploaded file.
	 *
	 * @param array  $file Entry from $_FILES.
	 * @param string $type document|circular|catalog.
	 * @return array{stored:string,name:string,mime:string,size:int}|WP_Error
	 */
	public static function store( $file, $type = 'document' ) {
		$type = in_array( $type, array( 'circular', 'catalog' ), true ) ? $type : 'document';

		if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['error'], $file['size'], $file['name'] ) ) {
			return new WP_Error( 'lkp_no_file', 'فایلی ارسال نشده است.' );
		}

		if ( UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
			return new WP_Error( 'lkp_no_file', 'فایلی ارسال نشده است.' );
		}

		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'lkp_upload_error', 'بارگذاری فایل با خطا روبه‌رو شد. دوباره تلاش کنید.' );
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'lkp_upload_error', 'فایل ارسالی معتبر نیست.' );
		}

		$size = (int) $file['size'];
		if ( $size <= 0 || $size > self::max_size( $type ) ) {
			return new WP_Error( 'lkp_file_size', 'حجم فایل بیش از حد مجاز است.' );
		}

		$mime  = '';
		$finfo = null;
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = $finfo ? (string) finfo_file( $finfo, $file['tmp_name'] ) : '';
		}
		if ( '' === $mime ) {
			$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
			$mime    = (string) ( $checked['type'] ?? '' );
		}

		$allowed = self::allowed( $type );
		if ( ! isset( $allowed[ $mime ] ) ) {
			return new WP_Error( 'lkp_file_type', 'نوع فایل مجاز نیست. فقط JPG یا PNG (و برای بخشنامه PDF) پذیرفته می‌شود.' );
		}

		if ( 0 === strpos( $mime, 'image/' ) ) {
			$image = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! $image || empty( $image[0] ) || empty( $image[1] ) ) {
				return new WP_Error( 'lkp_file_type', 'تصویر ارسالی معتبر نیست.' );
			}
		}

		$dir = self::dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		$stored = bin2hex( random_bytes( 24 ) ) . '.' . $allowed[ $mime ];
		$path   = trailingslashit( $dir ) . $stored;

		if ( ! move_uploaded_file( $file['tmp_name'], $path ) ) {
			return new WP_Error( 'lkp_upload_error', 'ذخیرهٔ فایل انجام نشد. دوباره تلاش کنید.' );
		}

		@chmod( $path, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		$name = sanitize_file_name( (string) $file['name'] );
		if ( '' === $name ) {
			$name = 'file.' . $allowed[ $mime ];
		}

		return array(
			'stored' => $stored,
			'name'   => mb_substr( $name, 0, 190 ),
			'mime'   => $mime,
			'size'   => $size,
		);
	}

	/**
	 * Absolute path for a stored file name.
	 *
	 * @param string $stored Stored basename.
	 * @return string
	 */
	public static function path( $stored ) {
		return trailingslashit( LKP_PRIVATE_DIR ) . basename( (string) $stored );
	}

	/**
	 * Delete a stored file.
	 *
	 * @param string $stored Stored basename.
	 * @return void
	 */
	public static function delete( $stored ) {
		$stored = basename( (string) $stored );
		if ( '' === $stored ) {
			return;
		}

		$path = self::path( $stored );
		if ( is_file( $path ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Stream a private file to the current user.
	 *
	 * @param string $stored        Stored basename.
	 * @param string $original_name Download name.
	 * @param string $disposition   inline|attachment.
	 * @return void
	 */
	public static function stream( $stored, $original_name = '', $disposition = 'inline' ) {
		$path = self::path( $stored );

		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			wp_die( 'فایل یافت نشد.', 'فایل یافت نشد', array( 'response' => 404 ) );
		}

		$mime = '';
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = $finfo ? (string) finfo_file( $finfo, $path ) : '';
		}
		if ( '' === $mime ) {
			$mime = 'application/octet-stream';
		}

		$name = $original_name ? $original_name : basename( $path );

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Disposition: ' . ( 'attachment' === $disposition ? 'attachment' : 'inline' ) . '; filename="' . rawurlencode( $name ) . '"' );
		header( 'X-Robots-Tag: noindex, nofollow' );

		readfile( $path );
		exit;
	}
}
