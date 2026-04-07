#!/usr/bin/env bash
# Ping a 8 hosts: PREFIX.1 … PREFIX.8 (por defecto 192.168.1).
# Uso: ./scripts/ping-lan-hosts-1-a-8.sh
#      PING_PREFIX=10.0.0 ./scripts/ping-lan-hosts-1-a-8.sh
set -u
PREFIX="${PING_PREFIX:-192.168.1}"
for i in $(seq 1 8); do
  host="${PREFIX}.${i}"
  if ping -c 1 -W 2 "$host" &>/dev/null; then
    echo "OK   $host"
  else
    echo "FAIL $host"
  fi
done
