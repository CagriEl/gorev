#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ ! -d .venv ]]; then
  echo "Önce: ./scripts/setup_and_train.sh"
  exit 1
fi
# shellcheck disable=SC1091
source .venv/bin/activate

HOST="${API_HOST:-127.0.0.1}"
PORT="${API_PORT:-8100}"
exec uvicorn main:app --host "$HOST" --port "$PORT"
