#!/usr/bin/env bash
# Symlink both example plugins into the local WordPress site.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SITE="${IWORKS_WP_ROOT:-/opt/homebrew/var/www/www.iworks.new}"
PLUGINS="${SITE}/wp-content/plugins"

if [[ ! -d "${PLUGINS}" ]]; then
	echo "Plugins directory not found: ${PLUGINS}" >&2
	exit 1
fi

for name in iworksie-example-one iworksie-example-two; do
	src="${ROOT}/examples/${name}"
	dst="${PLUGINS}/${name}"
	if [[ -L "${dst}" || -e "${dst}" ]]; then
		rm -rf "${dst}"
	fi
	ln -s "${src}" "${dst}"
	echo "Linked ${dst} -> ${src}"
done
