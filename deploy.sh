#!/usr/bin/env bash
# The deploy script moved to scripts/deploy.sh (PRD 11 E7) and now names the environment. Nothing is done here.
printf 'deploy.sh moved. Run instead:\n\n  bash scripts/deploy.sh production release.tar.gz\n  bash scripts/deploy.sh staging release.tar.gz\n\nSee scripts/README.md.\n' >&2
exit 1
