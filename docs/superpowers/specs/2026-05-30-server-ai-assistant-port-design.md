# Design: Port the JEP AI Assistant to a generic `server_ai` module

- **Date:** 2026-05-30
- **Branch:** `feature/server-ai-assistant`
- **Source:** `jep-onedb/jep-portal` branch `343-poc`, modules `jep_ai_assistant` + `jep_resource_ai_mcp`
- **Target:** `drupal-starter`, new merged module `web/modules/custom/server_ai`

## Goal

Port the JEP AI content-assistant proof-of-concept into the Drupal starter as a
single, generic, reusable module named **`server_ai`**. Strip every JEP- and
Judaism-specific reference. Where the source tags/searches `resource_item`
content, the port operates on the starter's **News** (`news`) content type and
the existing **Tags** (`tags`) vocabulary.

## What the feature does (recap)

Two Elm-based chat pages backed by the **OpenAI Responses API**:

1. **`/ai-content-assistant`** (staff) — drives MCP tools to review and tag News
   content. Tool-call JSON is shown for debugging.
2. **`/ai-search`** (visitor) — RAG over News via a semantic search MCP tool;
   results render as cards.

OpenAI runs the tool-calling loop **server-side**, calling this site's `/_mcp`
endpoint (Drupal's MCP server) with a short-lived OAuth Bearer token minted via
the `client_credentials` grant. Conversations are persisted as
`ai_chat_session` nodes (ChatGPT-style sidebar + resume) and each user question
is screened against a configurable sensitivity policy; violations are flagged
for admin review in a View.

**Security change vs. the source POC.** The source shipped the OpenAI token *and*
the MCP Bearer token to the browser. That is unacceptable. In this port the
browser never receives a secret: it streams the chat **directly to a small
standalone proxy service** (generic Node) that holds the OpenAI token, mints +
injects the MCP token, and relays the OpenAI Responses SSE stream back.

**Why a separate service, not a Drupal route.** The production target is
**Pantheon**, whose Global CDN (Fastly) enforces a hard ~59-second request
timeout and can buffer responses — both of which break SSE streaming for a
multi-step tool-calling turn routed through Drupal. The browser connecting
directly to the proxy bypasses Pantheon entirely, so streaming is reliable. The
proxy needs **no cross-service auth**: session-writing stays in Drupal (Elm
POSTs `/session` with its own cookie), so the proxy only streams — it never
writes data as a user. The proxy is also the natural future home for per-user /
per-month token-budget quota enforcement.

## Key decisions (agreed)

| Decision | Choice |
| --- | --- |
| Module layout | **One merged module** `server_ai` (folds in the MCP tools + card builder) |
| Content indexed | **News** (`news`) instead of `resource_item` |
| Tags vocabulary | reuse existing **`tags`** vocabulary + `field_tags` |
| RAG backend | **Port as-is**: `ai_search` + `ai_vdb_provider_pinecone` (Pinecone) |
| Content-model config | lives in the starter's **`config/sync`** (managed via `drush cex/cim`) |
| Collection feature | **Dropped** — no `collection` content type, no `create_*_collection` tool, no collection cards |
| Secrets to browser | **None.** A standalone **generic Node proxy** holds the OpenAI token + mints/injects the MCP token; browser streams to it directly |
| Streaming proxy runtime | **Separate generic Node service** (browser → proxy direct, bypasses Pantheon's 59s timeout + CDN buffering). Local: DDEV add-on container; deploy: Cloud Run / Lambda / Fly / etc. |
| Combined call | **No.** Keep separate calls: streamed chat answer (via the proxy) + Drupal-side `OpenAiClient` for title + sensitivity |
| Session persistence | **Stays in Drupal** — Elm POSTs `/session` with its cookie; proxy needs no cross-service auth |
| Secrets storage | **Environment variables** (no committed key files): proxy secrets in its own env; Drupal's server-side OpenAI token via Key *env* provider, set in `.ddev/config.local.yaml` |
| Quota management | Out of scope now; the proxy is the designed home for per-user/per-month budget enforcement |
| Elm app | **Recompiled** via DDEV (Elm toolchain in the container; not expected on host) |
| Supporting work | composer deps + ExistingSite/Unit tests + config all included |
| JEP / Judaism references | **Removed**, replaced with neutral, generic copy |

## Architecture

```
Browser (Elm app, served by server_ai)  — never holds any secret
  │  GET  /ai-content-assistant            → host page + drupalSettings
  │  GET  /ai-content-assistant/config     → JSON: systemPrompt, model, mcpUrl,
  │                                           proxyUrl, chrome   (NO secrets)
  │  POST /ai-content-assistant/session    → Drupal persists one chat turn (cookie auth)
  │
  │  POST <proxyUrl>  (model, instructions, input, previous_response_id?, mcpUrl)
  ▼          — direct from browser, bypasses Pantheon CDN; no secrets in body
Generic Node proxy service  ── holds OPENAI_API_KEY, mints + caches MCP token ──┐
  │  injects Authorization: Bearer <openai>                                     │
  │  injects MCP tool { server_url: mcpUrl, authorization: <minted MCP> }       │
  │  (future: per-user/month quota enforcement)                                │
  ▼                                                                            │
OpenAI Responses API  ──(remote MCP tool)──►  this site  /_mcp  ◄──────────────┘
   │  (SSE streamed back through proxy → browser)   (mcp_server + simple_oauth)
   ▼                                                       │
(browser renders deltas/tools)                            ▼
                                      server_ai MCP Tool plugins
                                      (get/list/search/tag News, set skill)
                                                 │
                              search_api index (Pinecone via ai_search) + entity storage

Server-side (PHP, never in browser): OpenAiClient → OpenAI Responses API for
   chat-title generation + question sensitivity classification (separate calls).
```

### Module: `web/modules/custom/server_ai`

Code (ported + genericized from both source modules):

- `server_ai.info.yml` — deps (see Dependencies).
- `server_ai.routing.yml` — 6 routes: `/ai-content-assistant`,
  `/ai-content-assistant/config`, `/ai-content-assistant/session`, and the three
  `/ai-search*` equivalents. (No streaming route — streaming lives in the proxy.)
- `server_ai.services.yml` — `server_ai.openai_client`,
  `server_ai.session_writer`, `server_ai.card_builder`.
- `server_ai.libraries.yml` — `js/boot.js`, `js/elm-main.js` (compiled),
  `css/chat.css`; depends on `server_theme/global-styling`.
- `server_ai.links.menu.yml` — admin menu link.
- `server_ai.permissions.yml` — `use ai content assistant`, `use ai search`.
- `server_ai.install` — seeds the `ai_assistant` config page with **generic**
  default prompts + sensitivity policy (idempotent; never overwrites edits).
- `src/Controller/AiChatControllerBase.php`, `AiAssistantController.php`,
  `AiSearchController.php`.
- `src/OpenAiClient.php` + `OpenAiClientInterface.php` — title generation +
  sensitivity classification (Responses API), server-side only.
- `src/SessionWriter.php`, `src/SensitivityVerdict.php`.
- `src/CardBuilder.php` (was `ResourceCardBuilder`) — builds News cards.
- `src/Plugin/Tool/*` — MCP tools (see below).
- `elm/` sources + `js/boot.js` + `js/elm-main.js` (**recompiled** via DDEV) +
  `css/chat.css`.
- `proxy/` — the standalone generic Node proxy service (server.js, package.json,
  `.ddev`/run docs, README). Ships in the module so the feature is self-contained.
- `tests/` — ported ExistingSite + Unit tests.

**Controller endpoints:**

- `appConfig()` returns **only non-secret** values: `systemPrompt`,
  `openaiModel`, `mcpUrl`, `proxyUrl`, page chrome, and the `/session` URL. It
  does **not** return the OpenAI token or an MCP token (both are injected by the
  proxy). `mintMcpToken()` / `readUserPassword()` are **removed from Drupal** —
  minting moves into the proxy.
- `session()` is **unchanged** from source: Elm POSTs each completed turn; it is
  persisted as the current (cookie-authenticated) user via `SessionWriter`, which
  also runs `OpenAiClient` title + sensitivity. The proxy is not involved.

### MCP tools (genericized, `collection` tool dropped)

| Source tool | Ported tool id | Operates on |
| --- | --- | --- |
| `get_resource_item` | `get_news_item` | `news` node: title, body, current + suggested tags |
| `list_resource_items` | `list_news_items` | `news` nodes, paged, filter by has-suggestion |
| `search_resources` | `search_news` | `news` via search_api/Pinecone index |
| `list_topic_terms` | `list_tags` | `tags` vocabulary terms |
| `set_resource_topic_ai_suggestion` | `set_news_tags_suggestion` | writes `field_tags_ai_suggestion` on `news` |
| `set_assistant_skill` | `set_assistant_skill` (kept) | overwrites admin system prompt |
| `create_ugc_collection` | **dropped** | — |

### Field / bundle mapping

| Source (JEP) | Target (starter) | Notes |
| --- | --- | --- |
| `resource_item` node | `news` node | |
| `body` / `field_res_item_description` | `field_body` | News body (text_long) + node title |
| `field_res_item_preview_image` / `_header_image` | `field_featured_image` | media:image |
| `resource_topic` vocabulary | `tags` vocabulary | existing |
| `field_resource_topic` | `field_tags` | existing on News (curated tags) |
| `field_res_topic_ai_suggestion` | `field_tags_ai_suggestion` | **NEW** field on News (entity_ref → tags) |
| `user_generated_collection` + `field_collection_*` | — | dropped |
| search_api index `rag_resource_items` | `rag_news` | new index over News |

### Chat-session content model (unchanged shape, generic)

- Node type `ai_chat_session` + fields `field_session_rows`,
  `field_session_response_id`, `field_session_flagged`,
  `field_session_flag_reason`, `field_session_flagged_row`,
  `field_session_policy_snapshot`, `field_session_is_admin`.
- Paragraph types `ai_user_question` (`field_chat_question`,
  `field_question_flagged`) and `ai_assistant_response` (`field_chat_answer`,
  `field_chat_resources` → references **News**). `field_chat_collection`
  dropped.
- `config_pages` type `ai_assistant` with `field_ai_prompt_admin`,
  `field_ai_prompt_search`, `field_ai_sensitivity_policy`,
  `field_ai_openai_model`, `field_ai_mcp_url`.
- View `flagged_ai_sessions` at `/admin/content/flagged-ai-sessions`.

## The proxy service (generic Node)

A small standalone HTTP service shipped in `web/modules/custom/server_ai/proxy/`.
It is the trust boundary: it holds every secret the browser must not see and
relays the OpenAI Responses SSE stream. The browser connects to it **directly**
(not through Pantheon), so streaming is immune to the CDN's 59s timeout +
buffering. It writes no data as a user, so it needs no Drupal session/identity.

**Request contract (browser → proxy).** `POST <proxyUrl>` with the JSON body the
Elm app already assembles for OpenAI, minus auth: `{ model, instructions, input,
previous_response_id?, mcpUrl, stream: true }`. The proxy:

1. Injects `Authorization: Bearer <OPENAI_API_KEY>`.
2. Mints (and caches until expiry) an MCP Bearer token from Drupal's
   `/oauth/token` (`client_credentials`), then injects the MCP tool block
   `{ type: 'mcp', server_label: 'server-ai', server_url: mcpUrl,
   authorization: <minted token>, require_approval: 'never' }`.
3. Forwards to the OpenAI Responses API and **streams the SSE response back**
   to the browser unchanged.
4. Returns scoped CORS headers so the browser can stream cross-origin.

**Proxy env (secrets):** `OPENAI_API_KEY`, `MCP_CLIENT_ID`, `MCP_CLIENT_SECRET`,
`MCP_TOKEN_URL` (the site's `/oauth/token`), optional `MCP_SERVER_URL` to pin the
MCP URL instead of trusting the request body, and `ALLOWED_ORIGIN` for CORS.

**Local dev:** run the proxy as a DDEV add-on/extra container (or `node proxy/`
in a side terminal); `proxyUrl` points at it. **Deploy:** any Node host —
Cloud Run, Lambda (Function URL), Fly, Render. README documents both.

**Future quota management (designed-for, not built now).** The browser will send
a short-lived Drupal-signed identity token; the proxy tracks per-user/month spend
and refuses once the budget is exceeded. The contract above leaves room for this
with no further browser changes.

**`boot.js` change.** Instead of `fetch('https://api.openai.com/v1/responses',
{Authorization: token})`, it `fetch(proxyUrl, …)` with no auth header. SSE
parsing is unchanged. The hardcoded `server_label: 'jep-portal'` and the MCP
authorization injection move out of `boot.js` into the proxy.

## Secrets via environment variables (no committed key files)

The source committed `config/keys/*.key` files. This port commits **no
secrets**. Two independent secret homes, both env-var based:

**1. The proxy** — `OPENAI_API_KEY`, `MCP_CLIENT_ID`, `MCP_CLIENT_SECRET`,
`MCP_TOKEN_URL` in its own environment. These are the secrets that used to reach
the browser.

**2. Drupal server-side** — only for `OpenAiClient` (title + sensitivity, which
run in PHP, never in the browser), via the **Key module's env provider**:

- `key.key.server_ai_openai_token` — provider `env`, variable
  `SERVER_AI_OPENAI_TOKEN`.

Set in `.ddev/config.local.yaml` (git-ignored; documented in the README):

```yaml
web_environment:
  - SERVER_AI_OPENAI_TOKEN=sk-...
```

The non-secret OpenAI model, MCP server URL, and **proxy URL** live on the
`ai_assistant` config page (`field_ai_openai_model`, `field_ai_mcp_url`,
`field_ai_proxy_url`). The OAuth consumer entity (hashed secret) is created once
via the UI (documented); its client id/secret are supplied to the proxy via env,
not committed.

## Dependencies

Already in the starter: `ai`, `ai_provider_openai`, `search_api`,
`config_pages`, `paragraphs`, `entity_reference_revisions`, `taxonomy`, `node`.

To add via composer + enable:

- `drupal/key` — secret storage (env provider) for Drupal's server-side token.
- `drupal/consumers` — OAuth consumer entity for MCP (the proxy mints against it).
- `drupal/simple_oauth` — exposes `/oauth/token` (`client_credentials` grant)
  that the **proxy** calls to mint the MCP token.
- `drupal/mcp` (provides `mcp_server`) — MCP server + Tool plugin base.
- `drupal/ai_vdb_provider_pinecone` — Pinecone vector DB provider.
- enable `ai_search` (submodule of `ai`).

Note: `simple_oauth` + `consumers` stay required even though Drupal no longer
mints the token in PHP — the proxy mints against Drupal's token endpoint and
consumer entity.

## Genericization checklist

- `.install` default prompts + sensitivity policy → neutral copy (no "Jewish
  Education Project", "Jewish", "Israel", "Purim", "Passover"; sensitivity
  policy becomes generic hate-speech/harassment/violence wording).
- Controller page chrome strings → generic ("Suggest tags for the next
  untagged News item", "What News do we have on …?").
- `boot.js`: streams to the configurable `proxyUrl` with no auth header (was a
  direct `api.openai.com` call with the token); hardcoded
  `server_label: 'jep-portal'` and MCP-token injection move into the proxy;
  `data-jep-ai-app` attribute and `drupalSettings.jepAiAssistant` key →
  `data-server-ai-app` / `drupalSettings.serverAi`.
- Tool ids, labels, descriptions → News/tags wording (table above).
- Namespaces `Drupal\jep_ai_assistant\*` and `Drupal\jep_resource_ai_mcp\*` →
  `Drupal\server_ai\*`; service ids `jep_ai_assistant.*` → `server_ai.*`;
  permission/route/menu ids re-prefixed.
- README rewritten generic, documenting env-var secrets + `.ddev` setup + how
  to run/deploy the proxy service.

## Risks / open items

- **Elm recompile (required).** We recompile `elm-main.js` from the `elm/`
  sources via DDEV — the Elm toolchain runs in the container (a `ddev` custom
  command or `ddev exec`), not on the host. The plan adds a build step + DDEV
  command for it. Any JEP strings baked into Elm source are removed and the blob
  regenerated; we never hand-patch the compiled output. Also: `boot.js` now
  streams to `proxyUrl` — verify the compiled Elm only depends on `boot.js`
  flags (no baked endpoint).
- **Proxy reachability + CORS.** The browser must reach the proxy directly
  (separate origin), the proxy must reach OpenAI + Drupal's `/oauth/token`, and
  OpenAI must reach `/_mcp` (public URL / `ddev share` during dev). The proxy
  returns scoped CORS headers (`ALLOWED_ORIGIN`). All documented in the README.
- **Two OpenAI token copies.** The proxy's `OPENAI_API_KEY` and Drupal's
  `SERVER_AI_OPENAI_TOKEN` are the same value in two env homes (browser traffic
  via the proxy; server-side title/sensitivity via Drupal). Intentional; neither
  is committed.
- **Pinecone is non-functional until configured** (account + index + API key in
  env). Chat, sessions, sensitivity, and non-search MCP tools work without it;
  `search_news` returns empty until Pinecone is wired.
- **`gpt-5.5` default model** in source is a placeholder; the port keeps the
  model configurable on the config page with a sane default constant.
- **Linting/tests must pass**: `ddev phpcs`, `ddev phpstan`, and the ported
  ExistingSite tests, before any commit.

## Out of scope

- Collection creation / UGC.
- Quota / token-budget enforcement in the proxy (designed-for; not built now).
- The single combined OpenAI call (summary + sensitivity + title in one) — kept
  as separate calls for now.
- Production hardening of the proxy beyond holding secrets + streaming + scoped
  CORS (rate limiting, WAF).
- Migrating/seeding demo News content for the index.
