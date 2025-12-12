#!/usr/bin/env bash
set -e

# Simple helper: create venv, install requirements, run prepare_ml_dataset.py
# Usage: bash ml-api/setup_and_run.sh [--venv PATH] [--run]
# Example: bash ml-api/setup_and_run.sh --venv .venv --run

VENV_DIR=".venv"
RUN_SCRIPT="prepare_ml_dataset.py"
REQ_FILE="requirements.txt"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --venv) VENV_DIR="$2"; shift 2;;
    --run) DO_RUN=1; shift;;
    --help) echo "Usage: $0 [--venv PATH] [--run]"; exit 0;;
    *) echo "Unknown arg: $1"; exit 1;;
  esac
done

echo "Using python: $(command -v python3 || command -v python || echo 'python not found')"

# Ensure Python exists
PYTHON="$(command -v python3 || command -v python || true)"
if [ -z "$PYTHON" ]; then
  echo "Python not found on PATH. Install Python 3 and retry." >&2
  exit 1
fi

# Create venv if missing
if [ ! -d "$VENV_DIR" ]; then
  echo "Creating virtualenv at $VENV_DIR ..."
  $PYTHON -m venv "$VENV_DIR"
fi

# Activate venv for this script
# shellcheck disable=SC1090
source "$VENV_DIR/bin/activate"

# Upgrade pip
python -m pip install --upgrade pip

# Install requirements if file exists
if [ -f "$REQ_FILE" ]; then
  echo "Installing requirements from $REQ_FILE ..."
  pip install -r "$REQ_FILE"
else
  echo "No $REQ_FILE found — installing essential packages..."
  pip install sqlalchemy pymysql pandas
fi

echo "Environment ready. Python: $(python -V) Pip: $(pip -V)"

if [ "${DO_RUN:-0}" -eq 1 ]; then
  echo "Running $RUN_SCRIPT ..."
  python "$RUN_SCRIPT"
  echo "Done."
else
  echo "To run the ML preparation script now:"
  echo "  source $VENV_DIR/bin/activate"
  echo "  python $RUN_SCRIPT"
  echo "Or re-run this helper with --run:"
  echo "  bash $0 --venv $VENV_DIR --run"
fi
