#!/usr/bin/env php
<?php
/**
 * reku.php — command line control of the Zehnder ComfoAir Q through the ESP32.
 * Usage: php reku.php [status|low|medium|high|auto|away|away-off|boost|bypass-on|bypass-off|bypass-auto]
 */

require __DIR__ . '/reku.inc.php';

$cmd = $argv[1] ?? 'status';

if ($cmd === 'status') {
    $s = reku_status();
    if ($s['mode'] === null) { fwrite(STDERR, "No answer from the ESP at " . REKU_IP . "\n"); exit(1); }
    printf("Mode:       %s, fan %s%s\n", $s['mode'], $s['fan_level'], $s['away'] ? ' (away)' : '');
    printf("Flow:       supply %d m3/h, exhaust %d m3/h, power %d W\n", $s['supply_flow'], $s['exhaust_flow'], $s['power']);
    printf("Outdoor:    %5.1f C  %3d %%\n", $s['outdoor'], $s['outdoor_hum']);
    printf("Supply:     %5.1f C  %3d %%   (into the rooms)\n", $s['supply'], $s['supply_hum']);
    printf("Extract:    %5.1f C  %3d %%   (from the rooms)\n", $s['extract'], $s['extract_hum']);
    printf("Exhaust:    %5.1f C  %3d %%   (out of the house)\n", $s['exhaust'], $s['exhaust_hum']);
    printf("Bypass:     %s %%, %s\n", $s['bypass'], $s['bypass_mode']);
    printf("Filter:     replace in %s days\n", $s['filter_days']);
    exit(0);
}

if (!reku_command($cmd)) {
    fwrite(STDERR, "Unknown command or no answer from the ESP: $cmd\n");
    fwrite(STDERR, "Commands: status low medium high auto away away-off boost bypass-on bypass-off bypass-auto\n");
    exit(1);
}
echo "OK: $cmd\n";
