#!/usr/bin/env bash
set -euo pipefail
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
if [[ $# -eq 1 && "$1" == *::* ]]; then
  CLASS="${1%%::*}"
  METHOD="${1##*::}"
  exec "${REPO_ROOT}/tests/run-filtered.sh" "${CLASS}" "${METHOD}"
fi
if [[ $# -lt 2 ]]; then
  echo "Usage: $0 <TestClass> <testMethod> | <TestClass::testMethod>" >&2
  exit 2
fi
exec "${REPO_ROOT}/tests/run-filtered.sh" "$1" "$2"
