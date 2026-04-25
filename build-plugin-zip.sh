#!/usr/bin/env bash
set -euo pipefail

PLUGIN_DIR="speedx-site-reset"
OUTPUT_ZIP="SpeedX-Site-Reset.zip"

if [[ ! -f "$PLUGIN_DIR/speedx-site-reset.php" ]]; then
  echo "Error: $PLUGIN_DIR/speedx-site-reset.php not found"
  exit 1
fi

rm -f "$OUTPUT_ZIP"
zip -r "$OUTPUT_ZIP" "$PLUGIN_DIR"

echo "Created $OUTPUT_ZIP"
