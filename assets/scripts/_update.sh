#!/bin/bash
set -euo pipefail

CTI_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$CTI_ROOT"

echo "CTI git update — $CTI_ROOT"
echo

echo "==> Main repo: fetch"
git fetch --all --prune

echo "==> Main repo: pull"
git pull --ff-only

echo "==> Submodules: sync URLs"
git submodule sync --recursive

echo "==> Submodules: init + fetch + update (remote branch from .gitmodules)"
git submodule update --init --recursive --remote

echo
echo "Done."
