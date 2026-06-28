<?php
/**
 * BotBlocker add-on package validator.
 *
 * Usage:
 *   php tools/validate-addon.php path/to/addon-folder
 *   php tools/validate-addon.php path/to/addon.zip
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This validator must be run from the command line.\n");
    exit(1);
}

$target = $argv[1] ?? '';
if ($target === '') {
    fwrite(STDERR, "Usage: php tools/validate-addon.php <addon-folder-or-zip>\n");
    exit(1);
}

$target = str_replace('\\', DIRECTORY_SEPARATOR, $target);
$target = realpath($target) ?: $target;

$errors = array();
$warnings = array();
$tmp_dir = '';

function bbcs_validator_error(array &$errors, string $message): void {
    $errors[] = $message;
}

function bbcs_validator_warning(array &$warnings, string $message): void {
    $warnings[] = $message;
}

function bbcs_validator_slug(string $value): string {
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9_\-]/', '', $value);
    return is_string($value) ? $value : '';
}

function bbcs_validator_safe_relative_path($path): string {
    if (!is_string($path) || trim($path) === '') {
        return '';
    }

    $path = str_replace('\\', '/', trim($path));
    $path = ltrim($path, '/');

    if (
        $path === ''
        || $path === '.'
        || strpos($path, "\0") !== false
        || strpos($path, ':') !== false
        || preg_match('#(^|/)\.\.(/|$)#', $path)
    ) {
        return '';
    }

    return $path;
}

function bbcs_validator_safe_symbol_name($value): string {
    if (!is_string($value)) {
        return '';
    }

    $value = trim($value);
    if ($value === '') {
        return '';
    }

    return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) ? $value : '';
}

function bbcs_validator_safe_callable_name($value): string {
    if (!is_string($value)) {
        return '';
    }

    $value = trim($value);
    if ($value === '') {
        return '';
    }

    return preg_match('/^[A-Za-z_\\\\][A-Za-z0-9_\\\\]*(::[A-Za-z_][A-Za-z0-9_]*)?$/', $value) ? $value : '';
}

function bbcs_validator_rrmdir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            bbcs_validator_rrmdir($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
}

function bbcs_validator_collect_files(string $dir, string $suffix = ''): array {
    $files = array();
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if ($suffix === '' || substr($path, -strlen($suffix)) === $suffix) {
            $files[] = $path;
        }
    }

    sort($files);
    return $files;
}

function bbcs_validator_find_functions(array $php_files): array {
    $functions = array();
    foreach ($php_files as $file) {
        $contents = file_get_contents($file);
        if (!is_string($contents)) {
            continue;
        }
        if (preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $contents, $matches)) {
            foreach ($matches[1] as $function) {
                $functions[$function] = $file;
            }
        }
    }
    return $functions;
}

function bbcs_validator_lint_php(string $file): array {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    $output = array();
    $code = 0;
    exec($cmd, $output, $code);
    return array($code, implode("\n", $output));
}

function bbcs_validator_extract_zip(string $zip_file, array &$errors): string {
    if (!class_exists('ZipArchive')) {
        bbcs_validator_error($errors, 'ZipArchive is unavailable; ZIP validation cannot continue.');
        return '';
    }

    $zip = new ZipArchive();
    if ($zip->open($zip_file) !== true) {
        bbcs_validator_error($errors, 'ZIP cannot be opened.');
        return '';
    }

    if ($zip->numFiles < 1 || $zip->numFiles > 500) {
        bbcs_validator_error($errors, 'ZIP must contain between 1 and 500 entries.');
    }

    $roots = array();
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $name = isset($stat['name']) ? str_replace('\\', '/', (string) $stat['name']) : '';

        if (
            $name === ''
            || strpos($name, "\0") !== false
            || strpos($name, ':') !== false
            || $name[0] === '/'
            || preg_match('#(^|/)\.\.(/|$)#', $name)
        ) {
            bbcs_validator_error($errors, 'ZIP contains an unsafe path: ' . $name);
            continue;
        }

        if (isset($stat['size']) && (int) $stat['size'] > 5 * 1024 * 1024) {
            bbcs_validator_error($errors, 'ZIP entry is larger than 5 MB: ' . $name);
        }

        $root = explode('/', trim($name, '/'))[0] ?? '';
        if ($root !== '' && $root !== '__MACOSX') {
            $roots[$root] = true;
        }
    }

    if (count($roots) !== 1) {
        bbcs_validator_error($errors, 'ZIP must contain exactly one root folder.');
    }

    if (!empty($errors)) {
        $zip->close();
        return '';
    }

    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bbcs-addon-validator-' . bin2hex(random_bytes(8));
    if (!mkdir($tmp, 0700, true) && !is_dir($tmp)) {
        $zip->close();
        bbcs_validator_error($errors, 'Failed to create temporary extraction directory.');
        return '';
    }

    if (!$zip->extractTo($tmp)) {
        $zip->close();
        bbcs_validator_rrmdir($tmp);
        bbcs_validator_error($errors, 'Failed to extract ZIP.');
        return '';
    }

    $zip->close();
    return $tmp;
}

function bbcs_validator_get_root_from_extracted(string $dir, array &$errors): string {
    $entries = array_values(array_filter(scandir($dir) ?: array(), static function ($entry): bool {
        return $entry !== '.' && $entry !== '..' && $entry !== '__MACOSX';
    }));

    $dirs = array();
    foreach ($entries as $entry) {
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($path)) {
            bbcs_validator_error($errors, 'Package root must contain exactly one folder; found file at top level: ' . $entry);
            continue;
        }
        $dirs[] = $path;
    }

    if (count($dirs) !== 1) {
        bbcs_validator_error($errors, 'Package must contain exactly one root folder.');
        return '';
    }

    return $dirs[0];
}

function bbcs_validator_validate_folder(string $root, array &$errors, array &$warnings): void {
    if (!is_dir($root)) {
        bbcs_validator_error($errors, 'Add-on root folder does not exist: ' . $root);
        return;
    }

    $root_name = basename($root);
    if ($root_name !== bbcs_validator_slug($root_name)) {
        bbcs_validator_error($errors, 'Root folder must be a sanitized lowercase slug: ' . $root_name);
    }

    $manifest_path = $root . DIRECTORY_SEPARATOR . 'bbcs-addon.json';
    if (!is_file($manifest_path)) {
        bbcs_validator_error($errors, 'Missing bbcs-addon.json in root folder.');
        return;
    }

    $raw_manifest = file_get_contents($manifest_path);
    $manifest = is_string($raw_manifest) ? json_decode($raw_manifest, true) : null;
    if (!is_array($manifest)) {
        bbcs_validator_error($errors, 'bbcs-addon.json is not valid JSON.');
        return;
    }

    foreach (array('slug', 'name', 'version', 'requires_core', 'core') as $field) {
        if (!isset($manifest[$field]) || trim((string) $manifest[$field]) === '') {
            bbcs_validator_error($errors, 'Missing required manifest field: ' . $field);
        }
    }

    $slug = isset($manifest['slug']) ? bbcs_validator_slug((string) $manifest['slug']) : '';
    if ($slug === '' || $slug !== (string) ($manifest['slug'] ?? '')) {
        bbcs_validator_error($errors, 'Manifest slug must be sanitized lowercase slug.');
    }
    if ($slug !== '' && $slug !== $root_name) {
        bbcs_validator_error($errors, 'Manifest slug must match root folder name.');
    }

    $schema_value = isset($manifest['schema']) ? trim((string) $manifest['schema']) : '';
    if ($schema_value === '') {
        bbcs_validator_warning($warnings, 'Manifest does not declare "schema". BotBlocker defaults it to "2.0", but a well-formed v2 manifest should declare "schema": "2.0".');
    } elseif ($schema_value !== '2.0') {
        bbcs_validator_warning($warnings, 'Manifest schema is not "2.0".');
    }

    if (empty($manifest['requires_php'])) {
        bbcs_validator_warning($warnings, 'Manifest should declare requires_php.');
    }

    if (empty($manifest['description'])) {
        bbcs_validator_warning($warnings, 'Manifest should include a useful Add-ons card description.');
    }

    $path_fields = array(
        'main' => $manifest['main'] ?? '',
        'core' => $manifest['core'] ?? '',
        'settings.view' => is_array($manifest['settings'] ?? null) ? ($manifest['settings']['view'] ?? '') : '',
        'assets.icon' => is_array($manifest['assets'] ?? null) ? ($manifest['assets']['icon'] ?? '') : ($manifest['icon'] ?? ''),
        'assets.readme' => is_array($manifest['assets'] ?? null) ? ($manifest['assets']['readme'] ?? 'readme.txt') : 'readme.txt',
    );

    foreach ($path_fields as $field => $relative) {
        if ($relative === '' || $relative === null) {
            continue;
        }
        $safe = bbcs_validator_safe_relative_path($relative);
        if ($safe === '') {
            bbcs_validator_error($errors, 'Unsafe relative path in manifest field ' . $field);
            continue;
        }
        $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safe);
        if (!file_exists($absolute)) {
            // Core marks a package invalid only when the `core` file is missing.
            // A declared-but-missing settings.view, main, or asset leaves the package
            // valid and loadable (no settings tab / no icon), so those are warnings.
            if ($field === 'core') {
                bbcs_validator_error($errors, 'Manifest path does not exist for ' . $field . ': ' . $safe);
            } else {
                bbcs_validator_warning($warnings, 'Manifest path does not exist for ' . $field . ': ' . $safe);
            }
        }
    }

    $php_files = bbcs_validator_collect_files($root, '.php');
    foreach ($php_files as $php_file) {
        list($lint_code, $lint_output) = bbcs_validator_lint_php($php_file);
        if ($lint_code !== 0) {
            bbcs_validator_error($errors, 'PHP lint failed for ' . $php_file . ': ' . $lint_output);
        }

        $contents = file_get_contents($php_file);
        if (is_string($contents) && strpos($contents, "defined( 'ABSPATH' )") === false && strpos($contents, "defined('ABSPATH')") === false) {
            bbcs_validator_warning($warnings, 'PHP file should guard direct access with ABSPATH: ' . $php_file);
        }
    }

    $functions = bbcs_validator_find_functions($php_files);
    $settings = is_array($manifest['settings'] ?? null) ? $manifest['settings'] : array();
    $settings_option = isset($settings['option']) ? bbcs_validator_slug((string) $settings['option']) : '';
    $settings_sanitize = isset($settings['sanitize']) ? trim((string) $settings['sanitize']) : '';

    if (!empty($settings['view']) && $settings_option === '') {
        bbcs_validator_warning($warnings, 'settings.view is declared without settings.option. The tab can render, but generic settings save will not run.');
    }

    if ($settings_option !== '' && $settings_option !== (string) ($settings['option'] ?? '')) {
        bbcs_validator_error($errors, 'settings.option must use lowercase letters, numbers, hyphens, or underscores.');
    }

    if ($settings_option !== '' && $settings_sanitize === '') {
        bbcs_validator_warning($warnings, 'settings.option is declared without settings.sanitize.');
    }

    if ($settings_sanitize !== '' && strpos($settings_sanitize, '::') === false && !isset($functions[$settings_sanitize])) {
        // Core falls back to BotBlockerAddons::sanitizeSettingsValue() when the declared
        // sanitizer is not callable, so a missing function-style sanitizer degrades but does
        // not invalidate the package. Class::method callbacks cannot be verified by static
        // scanning and are accepted as long as the files parse.
        bbcs_validator_warning($warnings, 'settings.sanitize callback not found in PHP files (BotBlocker will fall back to its built-in sanitizer): ' . $settings_sanitize);
    }

    $lifecycle = is_array($manifest['lifecycle'] ?? null) ? $manifest['lifecycle'] : array();
    foreach ($lifecycle as $event => $callback) {
        if ($event === 'file') {
            continue;
        }
        if (!is_string($callback) || trim($callback) === '') {
            continue;
        }
        // Class::method callbacks are accepted by core via is_callable() and cannot be
        // verified by static scanning, so only function-style callbacks are checked here.
        if (strpos($callback, '::') === false && !isset($functions[$callback])) {
            bbcs_validator_error($errors, 'Lifecycle callback not found in PHP files: ' . $callback);
        }
    }

    $features = array();
    if (is_array($manifest['features'] ?? null)) {
        foreach ($manifest['features'] as $feature) {
            $feature = bbcs_validator_slug((string) $feature);
            if ($feature !== '') {
                $features[] = $feature;
            }
        }
    }

    $runtime = is_array($manifest['runtime'] ?? null) ? $manifest['runtime'] : array();
    $pre_run = is_array($runtime['pre_run'] ?? null) ? $runtime['pre_run'] : array();
    $has_traffic_provider = in_array('traffic_decision_provider', $features, true);
    $has_pre_run = !empty($pre_run);

    if ($has_traffic_provider) {
        bbcs_validator_warning($warnings, 'This package declares traffic_decision_provider. Treat it as critical-risk traffic-control code: disabled by default, dry-run first, staging test, and rollback required.');

        if (!$has_pre_run) {
            bbcs_validator_error($errors, 'traffic_decision_provider requires runtime.pre_run manifest contract.');
        }
    }

    if ($has_pre_run && !$has_traffic_provider) {
        bbcs_validator_error($errors, 'runtime.pre_run is declared but features does not include traffic_decision_provider.');
    }

    if ($has_pre_run) {
        if (empty($pre_run['enabled'])) {
            bbcs_validator_error($errors, 'runtime.pre_run.enabled must be true for a pre-run traffic provider.');
        }

        if (($pre_run['contract'] ?? '') !== 'traffic_decision_provider') {
            bbcs_validator_error($errors, 'runtime.pre_run.contract must be traffic_decision_provider.');
        }

        $pre_run_file_rel = bbcs_validator_safe_relative_path($pre_run['file'] ?? '');
        if ($pre_run_file_rel === '') {
            bbcs_validator_error($errors, 'runtime.pre_run.file must be a safe package-relative path.');
        } else {
            $pre_run_file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pre_run_file_rel);
            if (!is_file($pre_run_file)) {
                bbcs_validator_error($errors, 'runtime.pre_run.file does not exist: ' . $pre_run_file_rel);
            }
        }

        $register = bbcs_validator_safe_callable_name($pre_run['register'] ?? '');
        if ($register === '') {
            bbcs_validator_error($errors, 'runtime.pre_run.register must be a safe callable name.');
        } elseif (strpos($register, '::') === false && !isset($functions[$register])) {
            bbcs_validator_error($errors, 'runtime.pre_run.register callback not found in PHP files: ' . $register);
        }

        $ready_constant = bbcs_validator_safe_symbol_name($pre_run['ready_constant'] ?? '');
        $ready_callback = bbcs_validator_safe_callable_name($pre_run['ready_callback'] ?? '');
        if ($ready_constant === '' && $ready_callback === '') {
            bbcs_validator_error($errors, 'runtime.pre_run must declare ready_constant or ready_callback.');
        }
        if (!empty($pre_run['ready_constant']) && $ready_constant === '') {
            bbcs_validator_error($errors, 'runtime.pre_run.ready_constant must be a safe symbol name.');
        }
        if (!empty($pre_run['ready_callback']) && $ready_callback === '') {
            bbcs_validator_error($errors, 'runtime.pre_run.ready_callback must be a safe callable name.');
        }
        if ($ready_callback !== '' && strpos($ready_callback, '::') === false && !isset($functions[$ready_callback])) {
            bbcs_validator_error($errors, 'runtime.pre_run.ready_callback not found in PHP files: ' . $ready_callback);
        }
    }

    $all_files = bbcs_validator_collect_files($root);
    foreach ($all_files as $file) {
        $relative = substr($file, strlen($root) + 1);
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

        if (preg_match('/\.(php|js|css|txt|md|json)$/', $file)) {
            $contents = file_get_contents($file);
            if (!is_string($contents)) {
                continue;
            }

            if (strpos($contents, 'plugin_dir_url(') !== false) {
                bbcs_validator_error($errors, 'Do not use plugin_dir_url() for uploaded add-on assets: ' . $relative);
            }

            if (preg_match('/\bbotblocker_tools_(core|login|headers|malware|https_protocol)_settings\b/', $contents) && strpos($slug, 'bbcs-') !== 0) {
                bbcs_validator_warning($warnings, 'Third-party add-ons should not use BotBlocker built-in shared settings options: ' . $relative);
            }
        }
    }

    if (!empty($settings['view']) && $settings_option !== '') {
        $settings_view = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, bbcs_validator_safe_relative_path($settings['view']));
        if (is_file($settings_view)) {
            $contents = file_get_contents($settings_view);
            if (is_string($contents)) {
                $has_literal_option_fields = preg_match('/name\s*=\s*["\']' . preg_quote($settings_option, '/') . '\[[^"\']+\]["\']/', $contents);
                $has_dynamic_option_fields = preg_match('/name\s*=\s*["\'][^"\']*<\?php\s+echo\s+esc_attr\([^"\']*option[^"\']*\[[^"\']+\]["\']/i', $contents);

                if (!$has_literal_option_fields && !$has_dynamic_option_fields) {
                    bbcs_validator_warning($warnings, 'Settings view should render fields named like ' . $settings_option . '[field].');
                }

                if (preg_match_all('/<(input|select|textarea)\b[^>]*\bname\s*=\s*["\']([^"\']+)["\']/i', $contents, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $name = $match[2];
                        if ($name === '' || strpos($name, $settings_option . '[') === 0) {
                            continue;
                        }
                        if (strpos($name, '<?php') !== false && stripos($name, 'option' ) !== false) {
                            continue;
                        }
                        bbcs_validator_warning($warnings, 'Settings field is not under settings.option: ' . $name);
                    }
                }
            }
        }
    }

    if (!empty($path_fields['assets.icon'])) {
        $icon = strtolower((string) $path_fields['assets.icon']);
        if (preg_match('/\.(php|html?|phtml)$/', $icon)) {
            bbcs_validator_error($errors, 'assets.icon must be a static browser image, not PHP/HTML.');
        }
    }

    if (!empty($manifest['assets']) && is_array($manifest['assets'])) {
        $core_rel = bbcs_validator_safe_relative_path((string) ($manifest['core'] ?? ''));
        $core_path = $core_rel !== '' ? $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $core_rel) : '';
        $core_contents = is_file($core_path) ? file_get_contents($core_path) : '';
        if (is_string($core_contents) && $core_contents !== '' && strpos($core_contents, 'BotBlockerAddons::fileUrl') === false) {
            bbcs_validator_warning($warnings, 'Package declares assets; core should use BotBlockerAddons::fileUrl() for runtime URLs when assets are enqueued or rendered.');
        }
    }
}

if (is_file($target)) {
    if (strtolower(pathinfo($target, PATHINFO_EXTENSION)) !== 'zip') {
        bbcs_validator_error($errors, 'File target must be a .zip package.');
    } elseif (filesize($target) > 20 * 1024 * 1024) {
        bbcs_validator_error($errors, 'ZIP is larger than 20 MB.');
    } else {
        $tmp_dir = bbcs_validator_extract_zip($target, $errors);
        if ($tmp_dir !== '') {
            $root = bbcs_validator_get_root_from_extracted($tmp_dir, $errors);
            if ($root !== '') {
                bbcs_validator_validate_folder($root, $errors, $warnings);
            }
        }
    }
} elseif (is_dir($target)) {
    bbcs_validator_validate_folder($target, $errors, $warnings);
} else {
    bbcs_validator_error($errors, 'Target does not exist: ' . $target);
}

if ($tmp_dir !== '') {
    bbcs_validator_rrmdir($tmp_dir);
}

foreach ($warnings as $warning) {
    fwrite(STDOUT, "[WARN] " . $warning . "\n");
}

foreach ($errors as $error) {
    fwrite(STDOUT, "[ERROR] " . $error . "\n");
}

if (!empty($errors)) {
    fwrite(STDOUT, "Validation failed with " . count($errors) . " error(s) and " . count($warnings) . " warning(s).\n");
    exit(1);
}

fwrite(STDOUT, "Validation passed with " . count($warnings) . " warning(s).\n");
exit(0);
