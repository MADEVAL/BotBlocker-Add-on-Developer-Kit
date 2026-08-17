<?php
$base = __DIR__ . '/../dist';
$expect = array(
	'acme-botblocker-sample' => array(
		'acme-botblocker-sample/bbcs-addon.json',
		'acme-botblocker-sample/inc/core.php',
		'acme-botblocker-sample/inc/settings.php',
		'acme-botblocker-sample/inc/class-acme-botblocker-sample-tools-page.php',
		'acme-botblocker-sample/inc/tabpanel-status.php',
		'acme-botblocker-sample/assets/icon.svg',
	),
	'acme-traffic-guard' => array(
		'acme-traffic-guard/bbcs-addon.json',
		'acme-traffic-guard/inc/core.php',
		'acme-traffic-guard/inc/pre-run.php',
		'acme-traffic-guard/inc/shared.php',
		'acme-traffic-guard/inc/class-acme-traffic-guard-tools-page.php',
		'acme-traffic-guard/inc/tabpanel-routes.php',
		'acme-traffic-guard/assets/icon.svg',
	),
);

$fail = 0;
foreach ( $expect as $slug => $must_contain ) {
	$zip = new ZipArchive();
	$path = $base . '/' . $slug . '.zip';
	$ok = $zip->open( $path );
	if ( true !== $ok ) {
		echo "FAIL: cannot open $path (rc=$ok)\n";
		$fail++;
		continue;
	}
	$names = array();
	$bad_slashes = array();
	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		$n = $zip->getNameIndex( $i );
		if ( strpos( $n, '\\' ) !== false ) {
			$bad_slashes[] = $n;
		}
		$names[ $n ] = true;
	}
	if ( ! empty( $bad_slashes ) ) {
		echo "FAIL: $slug has backslash entries: " . implode( ', ', $bad_slashes ) . "\n";
		$fail++;
	}
	foreach ( $must_contain as $file ) {
		if ( ! isset( $names[ $file ] ) ) {
			echo "FAIL: $slug missing entry $file\n";
			$fail++;
		}
	}
	// Extract and verify the file structure really lands as expected (Windows extract).
	$tmp = sys_get_temp_dir() . '/bbcs-verify-' . bin2hex( random_bytes( 4 ) );
	if ( ! mkdir( $tmp, 0700, true ) && ! is_dir( $tmp ) ) {
		echo "FAIL: cannot create tmp\n";
		$fail++;
	}
	if ( $zip->extractTo( $tmp ) ) {
		foreach ( $must_contain as $file ) {
			if ( ! file_exists( $tmp . '/' . $file ) ) {
				echo "FAIL: $slug extraction missing $file\n";
				$fail++;
			}
		}
	} else {
		echo "FAIL: $slug extractTo failed\n";
		$fail++;
	}
	// rrmdir tmp
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $tmp, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $it as $item ) {
		$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}
	rmdir( $tmp );
	$zip->close();
	echo "OK: $slug (" . count( $names ) . " entries, no backslashes, extract OK)\n";
}

echo $fail === 0 ? "VERIFY PASS\n" : "VERIFY FAIL ($fail)\n";
exit( $fail === 0 ? 0 : 1 );
