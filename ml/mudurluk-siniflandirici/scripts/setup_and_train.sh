#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ ! -d .venv ]]; then
  python3 -m venv .venv
fi
# shellcheck disable=SC1091
source .venv/bin/activate

pip install -q --upgrade pip
pip install -q -r requirements.txt

if [[ ! -f .env ]] && [[ -f .env.example ]]; then
  cp .env.example .env
fi

python data_loader.py
python train.py
echo "Tamam. API: ./scripts/start_api.sh"
