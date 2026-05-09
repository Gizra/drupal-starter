#!/bin/bash
set -euo pipefail

SSH_KEY_DIR="/run/ssh-keys"

# Set up SSH directory
mkdir -p /root/.ssh && chmod 700 /root/.ssh

# Copy private key from ddev-ai-ssh mount
if [ -f "$SSH_KEY_DIR/id_ed25519" ]; then
  cp "$SSH_KEY_DIR/id_ed25519" /root/.ssh/ddev_agent_key
  chmod 600 /root/.ssh/ddev_agent_key

  # Wait for web container to write its username (max 15s)
  for i in $(seq 1 15); do
    [ -f "$SSH_KEY_DIR/web-user" ] && break
    sleep 1
  done
  WEB_USER=$(head -n1 "$SSH_KEY_DIR/web-user" 2>/dev/null | tr -d '[:space:]')
  if ! [[ "$WEB_USER" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]]; then
    WEB_USER=ddev
  fi

  # Write SSH client config
  cat > /root/.ssh/config << EOF
Host web
    HostName web
    User $WEB_USER
    IdentityFile /root/.ssh/ddev_agent_key
    StrictHostKeyChecking no
    UserKnownHostsFile /dev/null
    ServerAliveInterval 30
    RequestTTY no
EOF
  chmod 600 /root/.ssh/config
  echo "SSH configured: ssh web <command> connects as '$WEB_USER'"
else
  echo "Warning: SSH keys not found at $SSH_KEY_DIR — web container SSH unavailable"
fi

# Sync API keys from container environment into A0's usr/.env so they
# appear in the UI and survive load_dotenv(override=True) at startup.
A0_ENV="/a0/usr/.env"
sync_key() {
  local key="$1"
  local val="${!key:-}"
  [ -z "$val" ] && return
  if grep -q "^${key}=" "$A0_ENV" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$A0_ENV"
  else
    printf "\n%s=%s\n" "$key" "$val" >> "$A0_ENV"
  fi
}
sync_key GROQ_API_KEY
sync_key GOOGLE_API_KEY

# Write model config for _model_config plugin (v1.13+).
# A0_SET_*_model_* env vars are silently ignored in v1.13; the plugin
# reads usr/plugins/_model_config/config.yaml instead.
MODEL_CONFIG_DIR="/a0/usr/plugins/_model_config"
MODEL_CONFIG_FILE="$MODEL_CONFIG_DIR/config.json"
if [ ! -f "$MODEL_CONFIG_FILE" ]; then
  mkdir -p "$MODEL_CONFIG_DIR"
  cat > "$MODEL_CONFIG_FILE" << 'MODELCFG'
{
  "allow_chat_override": true,
  "chat_model": {
    "provider": "groq",
    "name": "llama-3.3-70b-versatile",
    "api_base": "",
    "ctx_length": 128000,
    "ctx_history": 0.7,
    "vision": false,
    "max_embeds": 10,
    "rl_requests": 0,
    "rl_input": 0,
    "rl_output": 0,
    "kwargs": {}
  },
  "utility_model": {
    "provider": "google",
    "name": "gemini-2.0-flash",
    "api_base": "",
    "ctx_length": 128000,
    "ctx_input": 0.7,
    "rl_requests": 0,
    "rl_input": 0,
    "rl_output": 0,
    "kwargs": {}
  },
  "embedding_model": {
    "provider": "huggingface",
    "name": "sentence-transformers/all-MiniLM-L6-v2",
    "api_base": "",
    "rl_requests": 0,
    "rl_input": 0,
    "kwargs": {}
  }
}
MODELCFG
  echo "Model config written: chat=groq/llama-3.3-70b, utility=google/gemini-2.0-flash"
else
  echo "Model config already exists, skipping write."
fi

# Hand off to the image's normal initialization (runs supervisord which
# manages sshd, cron, searxng, and the Agent Zero Flask UI).
exec /exe/initialize.sh main
