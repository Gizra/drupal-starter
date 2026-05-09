#!/bin/bash
set -euo pipefail

SSH_KEY_DIR="/run/ssh-keys"

# Set up SSH directory for the node user
mkdir -p /home/node/.ssh && chmod 700 /home/node/.ssh

# Copy private key from ddev-ai-ssh mount
if [ -f "$SSH_KEY_DIR/id_ed25519" ]; then
  cp "$SSH_KEY_DIR/id_ed25519" /home/node/.ssh/ddev_agent_key
  chmod 600 /home/node/.ssh/ddev_agent_key

  # Wait for web container to write its username (max 15s)
  for i in $(seq 1 15); do
    [ -f "$SSH_KEY_DIR/web-user" ] && break
    sleep 1
  done
  WEB_USER=$(head -n1 "$SSH_KEY_DIR/web-user" 2>/dev/null | tr -d '[:space:]')
  if ! [[ "$WEB_USER" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]]; then
    WEB_USER=ddev
  fi

  cat > /home/node/.ssh/config << EOF
Host web
    HostName web
    User $WEB_USER
    IdentityFile /home/node/.ssh/ddev_agent_key
    StrictHostKeyChecking no
    UserKnownHostsFile /dev/null
    ServerAliveInterval 30
    RequestTTY no
EOF
  chmod 600 /home/node/.ssh/config
  echo "SSH configured: ssh web <command> connects as '$WEB_USER'"
else
  echo "Warning: SSH keys not found at $SSH_KEY_DIR — web container SSH unavailable"
fi

# Install Claude CLI into the persistent data dir on first run
CLAUDE_PREFIX="/home/node/.openclaw/claude-cli"
CLAUDE_BIN="$CLAUDE_PREFIX/bin/claude"
if [ ! -x "$CLAUDE_BIN" ]; then
  echo "Installing Claude Code CLI (first run — cached in openclaw data dir)..."
  npm install -g @anthropic-ai/claude-code --prefix "$CLAUDE_PREFIX" --quiet 2>&1 \
    && echo "Claude CLI installed." \
    || echo "Warning: Claude CLI install failed — claude-cli/* models unavailable"
fi
if [ -x "$CLAUDE_BIN" ]; then
  export PATH="$CLAUDE_PREFIX/bin:$PATH"
fi

# Generate gateway token on first run and print it for the user
OPENCLAW_DIR="/home/node/.openclaw"
OPENCLAW_ENV="$OPENCLAW_DIR/.env"
mkdir -p "$OPENCLAW_DIR"
if ! grep -q "^OPENCLAW_GATEWAY_TOKEN=" "$OPENCLAW_ENV" 2>/dev/null; then
  # head exits after 32 bytes, causing SIGPIPE to tr; || true suppresses pipefail.
  TOKEN=$(tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32) || true
  printf "OPENCLAW_GATEWAY_TOKEN=%s\n" "$TOKEN" >> "$OPENCLAW_ENV"
  echo "============================================================"
  echo "Generated gateway token: $TOKEN"
  echo "Run: ddev openclaw health --token $TOKEN"
  echo "============================================================"
fi

# If arguments were passed (docker compose run <cmd>), run them directly.
# Otherwise start the gateway (docker compose up).
if [ $# -gt 0 ]; then
  exec "$@"
fi

# --bind lan required for Docker bridge networking (loopback-only won't reach CLI containers).
exec node openclaw.mjs gateway --allow-unconfigured --bind lan
