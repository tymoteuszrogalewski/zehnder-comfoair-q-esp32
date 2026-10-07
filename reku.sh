#!/bin/bash
# Control the ventilation unit:  ./reku.sh status | low | medium | high | auto | away | away-off | boost | bypass-on | bypass-off | bypass-auto
php "$(dirname "$0")/php/reku.php" "$@"
