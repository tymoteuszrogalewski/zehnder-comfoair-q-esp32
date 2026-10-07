<?php
/**
 * reku.inc.php — tiny client for the ESP32 (ESPHome web server REST API).
 * Read sensors and send commands to the Zehnder ComfoAir Q. No libraries, only PHP.
 *
 * ESPHome 2026.3+ addresses entities by their NAME in the URL, e.g.
 *   GET  http://ESP/sensor/Outdoor%20Air%20Temperature
 *   POST http://ESP/button/Manual%20Permanent%20Low/press
 */

$cfg = __DIR__ . '/config.php';
if (!file_exists($cfg)) {
    fwrite(STDERR, "Missing config.php — copy config.example.php to config.php and set REKU_IP.\n");
    exit(1);
}
require $cfg;

// One HTTP call to the ESP. Returns decoded JSON (GET) or true (POST), false on error.
function reku_http($path, $post = false) {
    $ctx = stream_context_create(['http' => [
        'method'  => $post ? 'POST' : 'GET',
        'header'  => $post ? "Content-Length: 0\r\n" : '',
        'content' => '',
        'timeout' => 5,
    ]]);
    $body = @file_get_contents('http://' . REKU_IP . '/' . $path, false, $ctx);
    if ($body === false) return false;
    return $post ? true : json_decode($body, true);
}

// Read one entity, e.g. reku_get('sensor', 'Outdoor Air Temperature'). Returns the value or null.
function reku_get($domain, $name) {
    $r = reku_http($domain . '/' . rawurlencode($name));
    return $r === false ? null : ($r['value'] ?? $r['state'] ?? null);
}

// The most useful readings in one array.
function reku_status() {
    $s = [];
    foreach ([
        'outdoor'       => ['sensor', 'Outdoor Air Temperature'],
        'supply'        => ['sensor', 'Supply Air Temperature'],
        'extract'       => ['sensor', 'Extract Air Temperature'],
        'exhaust'       => ['sensor', 'Exhaust Air Temperature'],
        'outdoor_hum'   => ['sensor', 'Outdoor Air Humidity'],
        'supply_hum'    => ['sensor', 'Supply Air Humidity'],
        'extract_hum'   => ['sensor', 'Extract Air Humidity'],
        'exhaust_hum'   => ['sensor', 'Exhaust Air Humidity'],
        'supply_flow'   => ['sensor', 'Supply Fan Flow'],
        'exhaust_flow'  => ['sensor', 'Exhaust Fan Flow'],
        'power'         => ['sensor', 'Power'],
        'bypass'        => ['sensor', 'Bypass State'],
        'filter_days'   => ['sensor', 'Filter Replacement Remaining Days'],
        'fan_level'     => ['text_sensor', 'Fan Level'],
        'mode'          => ['text_sensor', 'Operating Mode'],
        'bypass_mode'   => ['text_sensor', 'Bypass Activation Mode'],
        'away'          => ['switch', 'Away Mode'],
    ] as $key => [$domain, $name]) {
        $s[$key] = reku_get($domain, $name);
    }
    return $s;
}

// Commands. Each returns true when the ESP accepted it.
// Fan speeds use the "Manual Permanent" buttons from comfoair-q.yaml (they stay until changed).
function reku_command($cmd) {
    $map = [
        'low'       => 'button/Manual Permanent Low/press',
        'medium'    => 'button/Manual Permanent Medium/press',
        'high'      => 'button/Manual Permanent High/press',
        'auto'      => 'switch/Auto Ventilation/turn_on',
        'away'      => 'switch/Away Mode/turn_on',
        'away-off'  => 'switch/Away Mode/turn_off',
        'boost'     => 'button/Boost (15 min)/press',
        'bypass-on' => 'button/Bypass On (1h)/press',
        'bypass-off'=> 'button/Bypass Off (1h)/press',
        'bypass-auto' => 'button/Bypass Auto/press',
    ];
    if (!isset($map[$cmd])) return false;
    [$domain, $name, $action] = explode('/', $map[$cmd]);
    return reku_http($domain . '/' . rawurlencode($name) . '/' . $action, true) === true;
}
