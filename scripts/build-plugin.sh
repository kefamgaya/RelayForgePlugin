#!/usr/bin/env bash
# Package the plugin into your Documents folder for upload or manual copy to wp-content/plugins.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${OUT:-${HOME}/Documents}"
NAME="relayforge-wordpress"
DEST="$OUT/${NAME}"

rsync -a --delete \
  --exclude '.build' \
  --exclude '.git' \
  --exclude 'relayforge-wordpress.zip' \
  --exclude '*.zip' \
  --exclude 'scripts' \
  "${ROOT}/" "${DEST}/"

( cd "$OUT" && rm -f "${NAME}.zip" && zip -rq "${NAME}.zip" "${NAME}" )

echo "Plugin folder: ${DEST}"
echo "Plugin zip:      ${OUT}/${NAME}.zip"
