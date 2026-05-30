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

## Key decisions (agreed)

| Decision | Choice |
| --- | --- |
| Module layout | **One merged module** `server_ai` (folds in the MCP tools + card builder) |
| Content indexed | **News** (`news`) instead of `resource_item` |
| Tags vocabulary | reuse existing **`tags`** vocabulary + `field_tags` |
| RAG backend | **Port as-is**: `ai_search` + `ai_vdb_provider_pinecone` (Pinecone) |
| Content-model config | lives in the starter's **`config/sync`** (managed via `drush cex/cim`) |
| Collection feature | **Dropped** — no `collection` content type, no `create_*_collection` tool, no collection cards |
| Secrets | **Environment variables** via Key module's *env* provider (set in `.ddev/config.local.yaml`), not committed key files |
| Supporting work | composer deps + ExistingSite/Unit tests + config all included |
| JEP / Judaism references | **Removed**, replaced with neutral, generic copy |

## Architecture

```
Browser (Elm app, served by server_ai)
  │  GET  /ai-content-assistant            → host page + drupalSettings
  │  GET  /ai-content-assistant/config     → JSON: prompt, OpenAI token+model,
  │                                           MCP URL, freshly-minted MCP token
  │  POST /ai-content-assistant/session    → persist one chat turn
  ▼
OpenAI Responses API  ──(remote MCP tool)──►  this site  /_mcp
                                                 │  (mcp_server + simple_oauth)
                                                 ▼
                                      server_ai MCP Tool plugins
                                      (get/list/search/tag News, set skill)
                                                 │
                              search_api index (Pinecone via ai_search)  +  entity storage
```

### Module: `web/modules/custom/server_ai`

Code (ported + genericized from both source modules):

- `server_ai.info.yml` — deps (see Dependencies).
- `server_ai.routing.yml` — 6 routes, paths `/ai-content-assistant*` and
  `/ai-search*` (generic enough to keep).
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
  sensitivity classification (Responses API).
- `src/SessionWriter.php`, `src/SensitivityVerdict.php`.
- `src/CardBuilder.php` (was `ResourceCardBuilder`) — builds News cards.
- `src/Plugin/Tool/*` — MCP tools (see below).
- `elm/` sources + `js/boot.js` + `js/elm-main.js` (compiled) + `css/chat.css`.
- `tests/` — ported ExistingSite + Unit tests.

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

## Secrets via environment variables (changed from source)

The source committed `config/keys/*.key` files. Instead, the port uses the **Key
module's environment-variable provider** so nothing secret is committed and each
environment supplies its own values through `.ddev/config.local.yaml`
(`web_environment`) or the hosting platform's env settings.

- `key.key.server_ai_openai_token` — provider `env`, variable
  `SERVER_AI_OPENAI_TOKEN`.
- MCP consumer credentials (`client_credentials` grant) — provider `env`,
  variables `SERVER_AI_MCP_CLIENT_ID` and `SERVER_AI_MCP_CLIENT_SECRET`
  (the controller reads both; a User/password key over env, or two single-value
  env keys — finalized in the plan).

Example `.ddev/config.local.yaml` (documented in the module README, file itself
git-ignored):

```yaml
web_environment:
  - SERVER_AI_OPENAI_TOKEN=sk-...
  - SERVER_AI_MCP_CLIENT_ID=...
  - SERVER_AI_MCP_CLIENT_SECRET=...
```

The non-secret OpenAI model and MCP server URL stay on the `ai_assistant` config
page; the OAuth consumer entity (hashed secret) is still created once via the UI
(documented), but its credentials are read from env, not a committed file.

## Dependencies

Already in the starter: `ai`, `ai_provider_openai`, `search_api`,
`config_pages`, `paragraphs`, `entity_reference_revisions`, `taxonomy`, `node`.

To add via composer + enable:

- `drupal/key` — secret storage (env provider).
- `drupal/consumers` — OAuth client for MCP.
- `drupal/simple_oauth` — `client_credentials` token minting.
- `drupal/mcp` (provides `mcp_server`) — MCP server + Tool plugin base.
- `drupal/ai_vdb_provider_pinecone` — Pinecone vector DB provider.
- enable `ai_search` (submodule of `ai`).

## Genericization checklist

- `.install` default prompts + sensitivity policy → neutral copy (no "Jewish
  Education Project", "Jewish", "Israel", "Purim", "Passover"; sensitivity
  policy becomes generic hate-speech/harassment/violence wording).
- Controller page chrome strings → generic ("Suggest tags for the next
  untagged News item", "What News do we have on …?").
- `boot.js`: hardcoded `server_label: 'jep-portal'` → `'server-ai'`;
  `data-jep-ai-app` attribute and `drupalSettings.jepAiAssistant` key →
  `data-server-ai-app` / `drupalSettings.serverAi`.
- Namespaces `Drupal\jep_ai_assistant\*` and `Drupal\jep_resource_ai_mcp\*` →
  `Drupal\server_ai\*`; service ids `jep_ai_assistant.*` → `server_ai.*`;
  permission/route/menu ids re-prefixed.
- Tool ids, labels, descriptions → News/tags wording (table above).
- README rewritten generic, documenting env-var secrets + `.ddev` setup.

## Risks / open items

- **Elm recompile.** Renaming the `data-*` attribute, the drupalSettings key,
  and `server_label` is done in `boot.js` (hand-edited) — the compiled
  `elm-main.js` reads those via flags from `boot.js`, so it likely needs **no**
  recompile. User-facing copy comes from server flags, not the Elm blob. If any
  JEP string is found baked into `elm-main.js`, we recompile via `elm/build.sh`
  (needs the Elm toolchain in DDEV; otherwise we patch the blob). Verified
  during implementation.
- **`#theme` host markup.** Source renders a `<div data-…-app>` via `#markup`
  (no theme hook needed) — confirmed; no template required.
- **Pinecone is non-functional until configured** (account + index + API key
  in env). The chat, sessions, sensitivity, and non-search MCP tools work
  without it; `search_news` returns empty until Pinecone is wired. This is the
  accepted "port as-is" tradeoff.
- **`gpt-5.5` default model** in source is a placeholder; the port keeps the
  model configurable on the config page with a sane default constant.
- **Linting/tests must pass**: `ddev phpcs`, `ddev phpstan`, and the ported
  ExistingSite tests, before any commit.

## Out of scope

- Collection creation / UGC.
- Hardening the POC's "ship token to browser" model (documented as POC-only).
- Migrating/seeding demo News content for the index.
