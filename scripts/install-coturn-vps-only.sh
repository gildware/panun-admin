#!/usr/bin/env bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq coturn openssl
TURN_USER="panun_turn"
TURN_SECRET="$(openssl rand -hex 24)"
PUBLIC_IP="${1:-187.127.135.66}"
cat >/etc/turnserver.conf <<EOF
listening-port=3478
fingerprint
lt-cred-mech
user=${TURN_USER}:${TURN_SECRET}
no-multicast-peers
no-cli
min-port=49152
max-port=49252
log-file=/var/log/turnserver.log
external-ip=${PUBLIC_IP}
realm=dev.panunkaergar.com
EOF
if [[ -f /etc/default/coturn ]]; then
  sed -i 's/^#*TURNSERVER_ENABLED=.*/TURNSERVER_ENABLED=1/' /etc/default/coturn
  grep -q '^TURNSERVER_ENABLED=1' /etc/default/coturn || echo 'TURNSERVER_ENABLED=1' >>/etc/default/coturn
fi
systemctl enable coturn
systemctl restart coturn
command -v ufw >/dev/null && ufw allow 3478/tcp && ufw allow 3478/udp && ufw allow 49152:49252/udp || true
printf '%s' "$TURN_SECRET" >/root/panun-turn-credential.txt
chmod 600 /root/panun-turn-credential.txt
echo "COTURN_STATUS=$(systemctl is-active coturn)"
echo "TURN_URL=turn:${PUBLIC_IP}:3478"
echo "TURN_USERNAME=${TURN_USER}"
echo "TURN_CREDENTIAL=${TURN_SECRET}"
