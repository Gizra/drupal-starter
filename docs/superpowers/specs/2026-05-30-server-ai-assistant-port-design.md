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
browser never receives a secret: it streams the chat through a **Drupal SSE
route** that holds the OpenAI token (Key env provider), mints + injects the MCP
token server-side, and proxies the OpenAI Responses stream back to the browser.
No separate service is built this round; the streaming route is designed behind a
clean HTTP contract so it can later be extracted into a standalone generic Node
service (documented upgrade path) if streaming concurrency demands it.

## Key decisions (agreed)

| Decision | Choice |
| --- | --- |
| Module layout | **One merged module** `server_ai` (folds in the MCP tools + card builder) |
| Content indexed | **News** (`news`) instead of `resource_item` |
| Tags vocabulary | reuse existing **`tags`** vocabulary + `field_tags` |
| RAG backend | **Port as-is**: `ai_search` + `ai_vdb_provider_pinecone` (Pinecone) |
| Content-model config | lives in the starter's **`config/sync`** (managed via `drush cex/cim`) |
| Collection feature | **Dropped** — no `collection` content type, no `create_*_collection` tool, no collection cards |
| Secrets to browser | **None.** A **Drupal SSE streaming route** holds the OpenAI token + mints/injects the MCP token server-side |
| Streaming proxy runtime | **Drupal route now** (no separate service built). **Generic Node service is the documented upgrade target** |
| Combined call | **No.** Keep separate calls: streamed chat answer (via the Drupal route) + Drupal-side `OpenAiClient` for title + sensitivity |
| Session persistence | **Unchanged** — Elm POSTs `/session`; attributed to the logged-in user via cookie (precise attribution not required) |
| Secrets storage | **Environment variables** (no committed key files) via Key *env* provider, set in `.ddev/config.local.yaml` |
| Quota management | Out of scope now; the future Node service is the home for per-user/per-month budget enforcement |
| Elm app | **Recompiled** via DDEV (Elm toolchain in the container; not expected on host) |
| Supporting work | composer deps + ExistingSite/Unit tests + config all included |
| JEP / Judaism references | **Removed**, replaced with neutral, generic copy |

## Architecture

```
Browser (Elm app, served by server_ai)  — never holds any secret
  │  GET  /ai-content-assistant            → host page + drupalSettings
  │  GET  /ai-content-assistant/config     → JSON: systemPrompt, model, chrome (NO secrets)
  │  POST /ai-content-assistant/stream     → SSE proxy (this is the new "proxy")
  │  POST /ai-content-assistant/session    → Drupal persists one chat turn (cookie auth)
  ▼
Drupal SSE streaming route (server_ai)  ── reads OpenAI token (Key env) ──┐
  │  mints + injects MCP token (simple_oauth client_credentials)          │
  │  injects Authorization: Bearer <openai>                               │
  │  injects MCP tool { server_url: mcpUrl, authorization: <minted MCP> } │
  ▼                                                                       │
OpenAI Responses API  ──(remote MCP tool)──►  this site  /_mcp  ◄─────────┘
   │  (SSE streamed back through the Drupal route → browser)  (mcp_server + simple_oauth)
   ▼                                                       │
(browser renders deltas/tools)                            ▼
                                      server_ai MCP Tool plugins
                                      (get/list/search/tag News, set skill)
                                                 │
                              search_api index (Pinecone via ai_search) + entity storage

Server-side (PHP, never in browser): OpenAiClient → OpenAI Responses API for
   chat-title generation + question sensitivity classification (separate calls).

Future upgrade (documented, not built): lift the /stream route into a standalone
   generic Node service behind the same request contract; Elm points at its URL.
```

### Module: `web/modules/custom/server_ai`

Code (ported + genericized from both source modules):

- `server_ai.info.yml` — deps (see Dependencies).
- `server_ai.routing.yml` — 8 routes: `/ai-content-assistant`,
  `/ai-content-assistant/config`, `/ai-content-assistant/stream`,
  `/ai-content-assistant/session`, and the four `/ai-search*` equivalents.
- `server_ai.services.yml` — `server_ai.openai_client`,
  `server_ai.session_writer`, `server_ai.card_builder`, plus the streaming
  proxy service.
- `server_ai.libraries.yml` — `js/boot.js`, `js/elm-main.js` (compiled),
  `css/chat.css`; depends on `server_theme/global-styling`.
- `server_ai.links.menu.yml` — admin menu link.
- `server_ai.permissions.yml` — `use ai content assistant`, `use ai search`.
- `server_ai.install` — seeds the `ai_assistant` config page with **generic**
  default prompts + sensitivity policy (idempotent; never overwrites edits).
- `src/Controller/AiChatControllerBase.php`, `AiAssistantController.php`,
  `AiSearchController.php` — adds the `stream()` SSE action.
- `src/StreamingProxy.php` (or a controller method) — server-side OpenAI stream
  proxy: reads token, mints + injects MCP token, relays SSE.
- `src/OpenAiClient.php` + `OpenAiClientInterface.php` — title generation +
  sensitivity classification (Responses API), server-side only.
- `src/SessionWriter.php`, `src/SensitivityVerdict.php`.
- `src/CardBuilder.php` (was `ResourceCardBuilder`) — builds News cards.
- `src/Plugin/Tool/*` — MCP tools (see below).
- `elm/` sources + `js/boot.js` + `js/elm-main.js` (**recompiled** via DDEV) +
  `css/chat.css`.
- `tests/` — ported ExistingSite + Unit tests.

**Controller endpoints:**

- `appConfig()` returns **only non-secret** values: `systemPrompt`,
  `openaiModel`, page chrome, and the `/stream` + `/session` URLs. It does **not**
  return the OpenAI token or an MCP token (both are injected server-side by the
  stream route).
- `stream()` is the new SSE proxy. It reads the conversation from the request,
  reads the OpenAI token (Key env), mints the MCP token (simple_oauth
  `client_credentials`), calls the OpenAI Responses API with `stream: true`, and
  relays the SSE events straight back to the browser. The MCP-minting logic
  (`mintMcpToken` from the source) lives here, server-side.
- `session()` is **unchanged** from source: Elm POSTs each completed turn; it is
  persisted as the current (cookie-authenticated) user via `SessionWriter`, which
  also runs `OpenAiClient` title + sensitivity.

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

## Secrets via environment variables (no committed key files)

The source committed `config/keys/*.key` files. This port commits **no
secrets**. Everything is server-side in Drupal, read from environment variables
via the **Key module's env provider**:

- `key.key.server_ai_openai_token` — provider `env`, variable
  `SERVER_AI_OPENAI_TOKEN`. Used by both the stream route (browser traffic) and
  `OpenAiClient` (title + sensitivity).
- MCP consumer credentials for `client_credentials` minting — provider `env`,
  variables `SERVER_AI_MCP_CLIENT_ID` / `SERVER_AI_MCP_CLIENT_SECRET` (read by
  the stream route; exact key shape finalized in the plan).

Set in `.ddev/config.local.yaml` (git-ignored; documented in the README):

```yaml
web_environment:
  - SERVER_AI_OPENAI_TOKEN=sk-...
  - SERVER_AI_MCP_CLIENT_ID=...
  - SERVER_AI_MCP_CLIENT_SECRET=...
```

The non-secret OpenAI model and MCP server URL live on the `ai_assistant` config
page (`field_ai_openai_model`, `field_ai_mcp_url`). The OAuth consumer entity
(hashed secret) is still created once via the UI (documented); its client
id/secret are supplied through env, not committed.

## Dependencies

Already in the starter: `ai`, `ai_provider_openai`, `search_api`,
`config_pages`, `paragraphs`, `entity_reference_revisions`, `taxonomy`, `node`.

To add via composer + enable:

- `drupal/key` — secret storage (env provider).
- `drupal/consumers` — OAuth consumer entity for MCP.
- `drupal/simple_oauth` — `/oauth/token`, `client_credentials` grant (the stream
  route mints the MCP token against it, server-side).
- `drupal/mcp` (provides `mcp_server`) — MCP server + Tool plugin base.
- `drupal/ai_vdb_provider_pinecone` — Pinecone vector DB provider.
- enable `ai_search` (submodule of `ai`).

## Genericization checklist

- `.install` default prompts + sensitivity policy → neutral copy (no "Jewish
  Education Project", "Jewish", "Israel", "Purim", "Passover"; sensitivity
  policy becomes generic hate-speech/harassment/violence wording).
- Controller page chrome strings → generic ("Suggest tags for the next
  untagged News item", "What News do we have on …?").
- `boot.js`: streams to the Drupal `/stream` URL with no auth header (was a
  direct `api.openai.com` call with the token); hardcoded
  `server_label: 'jep-portal'` and MCP-token injection move server-side;
  `data-jep-ai-app` attribute and `drupalSettings.jepAiAssistant` key →
  `data-server-ai-app` / `drupalSettings.serverAi`.
- Tool ids, labels, descriptions → News/tags wording (table above).
- Namespaces `Drupal\jep_ai_assistant\*` and `Drupal\jep_resource_ai_mcp\*` →
  `Drupal\server_ai\*`; service ids `jep_ai_assistant.*` → `server_ai.*`;
  permission/route/menu ids re-prefixed.
- README rewritten generic, documenting env-var secrets + `.ddev` setup + the
  Node-service upgrade path.

## Risks / open items

- **Elm recompile (required).** We recompile `elm-main.js` from the `elm/`
  sources via DDEV — the Elm toolchain runs in the container (a `ddev` custom
  command or `ddev exec`), not on the host. The plan adds a build step + DDEV
  command for it. Any JEP strings baked into Elm source are removed and the blob
  regenerated; we never hand-patch the compiled output. Also: `boot.js` now
  points OpenAI streaming at the Drupal `/stream` URL — verify the compiled Elm
  only depends on `boot.js` flags (no baked endpoint).
- **PHP worker per stream.** The Drupal SSE route holds one PHP-FPM worker for
  the duration of each active chat stream. Fine for a staff tagging tool + modest
  search traffic. The documented upgrade path (extract to a generic Node service
  behind the same contract) is the answer if concurrency grows.
- **MCP reachability.** OpenAI must reach `/_mcp` (public URL / `ddev share`
  during dev); the MCP URL stays configurable. Unchanged requirement from source.
- **Pinecone is non-functional until configured** (account + index + API key in
  env). Chat, sessions, sensitivity, and non-search MCP tools work without it;
  `search_news` returns empty until Pinecone is wired.
- **`gpt-5.5` default model** in source is a placeholder; the port keeps the
  model configurable on the config page with a sane default constant.
- **Linting/tests must pass**: `ddev phpcs`, `ddev phpstan`, and the ported
  ExistingSite tests, before any commit.

## Out of scope

- Collection creation / UGC.
- The standalone generic Node streaming service (documented upgrade path only;
  not built this round).
- Quota / token-budget enforcement (lands in the future Node service).
- The single combined OpenAI call (summary + sensitivity + title in one) — kept
  as separate calls for now.
- Migrating/seeding demo News content for the index.
