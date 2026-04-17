#!/usr/bin/env bash
set -euo pipefail

if ! command -v msgfmt >/dev/null 2>&1; then
	echo "WARN: msgfmt not found; skipping .po -> .mo compilation." >&2
	exit 0
fi

while read -r pofile; do
	# Replace the trailing ".po" with ".mo".
	outfile="${pofile%.po}.mo"
	msgfmt --use-fuzzy -c -o "${outfile}" "${pofile}" || exit $?
done < <(find "$(dirname "$0")" -type f -name '*.po')
