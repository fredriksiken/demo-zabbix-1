#!/bin/bash
set -euo pipefail

if ! command -v msgfmt >/dev/null 2>&1; then
        echo "msgfmt is not available; skipping gettext catalog regeneration." >&2
        exit 0
fi

while read pofile; do
        msgfmt --use-fuzzy -c -o ${pofile%po}mo $pofile || exit $?
done < <(find $(dirname $0) -type f -name '*.po')
