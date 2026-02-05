# Plugin Rename: wp-ai-assistant → semantic-knowledge

## Overview
Comprehensive rename of the plugin from "wp-ai-assistant" to "semantic-knowledge" across all files, classes, database elements, and documentation.

**Scope:** 200+ occurrences across 53+ files
**Impact Level:** Medium - Clean rename with no backward compatibility needed
**Note:** Plugin has never been installed, so no migration or backward compatibility required

---

## Naming Strategy

### Pattern Mapping
| Current Pattern | New Pattern | Example |
|----------------|-------------|---------|
| `wp-ai-assistant` | `semantic-knowledge` | Directory, plugin slug |
| `WP_AI_Assistant` | `Semantic_Knowledge` | Main plugin class |
| `WP_AI_*` | `Semantic_Knowledge_*` | All other classes |
| `wp_ai_assistant` | `semantic_knowledge` | Options, functions |
| `wp-ai-` | `sk-` | CSS classes, IDs |
| `wpAiAssistant*` | `semanticKnowledge*` | JavaScript variables |
| `ai-assistant/v1` | `semantic-knowledge/v1` | REST API namespace |
| `ai-indexer` | `sk-indexer` | WP-CLI commands |

---

## Implementation Phases

### Phase 1: Core Infrastructure ⚙️
**Goal:** Update core plugin files, constants, and class structure

**Tasks:**
1. Rename main plugin file: `wp-ai-assistant.php` → `semantic-knowledge.php`
2. Update plugin header metadata in `semantic-knowledge.php`:
   - Plugin Name: "Semantic Knowledge"
   - Description: Update to emphasize semantic search and knowledge management
   - Text Domain: `semantic-knowledge`
3. Rename all class files:
   - Pattern: `class-wp-ai-*.php` → `class-semantic-knowledge-*.php`
   - 16 files total in `/includes/`, `/includes/migrations/`, `/includes/modules/`
4. Update all class names:
   - Main class: `WP_AI_Assistant` → `Semantic_Knowledge`
   - All others: `WP_AI_*` → `Semantic_Knowledge_*`
5. Update constants in main plugin file:
   - `WP_AI_ASSISTANT_VERSION` → `SEMANTIC_KNOWLEDGE_VERSION`
   - `WP_AI_ASSISTANT_SCHEMA_VERSION` → `SEMANTIC_KNOWLEDGE_SCHEMA_VERSION`
   - `WP_AI_ASSISTANT_DIR` → `SEMANTIC_KNOWLEDGE_DIR`
   - `WP_AI_ASSISTANT_URL` → `SEMANTIC_KNOWLEDGE_URL`
6. Update option key constant:
   - `'wp_ai_assistant_settings'` → `'semantic_knowledge_settings'`

**Critical Files:**
- `/semantic-knowledge.php` (renamed from wp-ai-assistant.php)
- All 16 class files in `/includes/`

---

### Phase 2: Database & Options 🗄️
**Goal:** Update database table names and WordPress option keys

**Tasks:**
1. Update database table names in code:
   - `wp_ai_chat_logs` → `wp_sk_chat_logs`
   - `wp_ai_search_logs` → `wp_sk_search_logs`
2. Update WordPress option keys in code:
   - `wp_ai_assistant_settings` → `semantic_knowledge_settings`
   - `wp_ai_assistant_db_version` → `semantic_knowledge_db_version`
   - `wp_ai_assistant_migrated` → `semantic_knowledge_migrated`
   - `wp_ai_chatbot_settings` → `semantic_knowledge_chatbot_settings`
   - `wp_ai_search_settings` → `semantic_knowledge_search_settings`
   - All backup option keys
3. Update post type registrations:
   - `ai_chat_log` → `sk_chat_log`
   - `ai_search_log` → `sk_search_log`

**Critical Files:**
- `/includes/class-semantic-knowledge-database.php`
- `/includes/class-semantic-knowledge-logger.php`
- `/semantic-knowledge.php`

---

### Phase 3: REST API & Hooks 🔌
**Goal:** Update REST API endpoints and WordPress hook names

**Tasks:**
1. Update REST API namespace:
   - `ai-assistant/v1` → `semantic-knowledge/v1`
   - Routes become: `/wp-json/semantic-knowledge/v1/chat`, etc.
2. Update route namespace constants in 3 files:
   - `/includes/class-semantic-knowledge-indexer-controller.php`
   - `/includes/modules/class-semantic-knowledge-chatbot-module.php`
   - `/includes/modules/class-semantic-knowledge-search-module.php`
3. Update WordPress hooks (18+ occurrences):
   - `wp_ai_assistant_*` → `semantic_knowledge_*`
   - `wp_ai_*` → `semantic_knowledge_*`
   - Examples:
     - `wp_ai_assistant_cleanup_logs` → `semantic_knowledge_cleanup_logs`
     - `wp_ai_assistant_enable_csp` → `semantic_knowledge_enable_csp`
     - `wp_ajax_wp_ai_assistant_*` → `wp_ajax_semantic_knowledge_*`
4. Update WP-CLI command names:
   - `wp ai-indexer` → `wp sk-indexer`
   - `wp ai-assistant install-indexer` → `wp semantic-knowledge install-indexer`
   - `wp ai-assistant check-indexer` → `wp semantic-knowledge check-indexer`
5. Update template function names:
   - `wp_ai_get_search_summary()` → `semantic_knowledge_get_search_summary()`
   - `wp_ai_is_search()` → `semantic_knowledge_is_search()`
   - `wp_ai_the_search_summary()` → `semantic_knowledge_the_search_summary()`

**Critical Files:**
- `/includes/class-semantic-knowledge-indexer-controller.php`
- `/includes/modules/class-semantic-knowledge-chatbot-module.php`
- `/includes/modules/class-semantic-knowledge-search-module.php`
- `/includes/class-semantic-knowledge-cli.php`
- All files with `add_action()`, `add_filter()`, `do_action()`, `apply_filters()`

---

### Phase 4: Frontend Assets 🎨
**Goal:** Update CSS classes, JavaScript variables, and DOM selectors

**Tasks:**
1. Update CSS classes (15+ patterns):
   - `.wp-ai-chatbot-*` → `.sk-chatbot-*`
   - `.wp-ai-search-*` → `.sk-search-*`
   - `.ai-search-summary` → `.sk-search-summary`
   - Update both definitions and references
2. Update CSS IDs:
   - `#wp-ai-chatbot-title` → `#sk-chatbot-title`
   - `#wp-ai-chat-announcements` → `#sk-chat-announcements`
3. Update JavaScript global objects:
   - `window.wpAiAssistantChatbot` → `window.semanticKnowledgeChatbot`
4. Update JavaScript localized script handles:
   - `wp_localize_script()` calls with new object names
5. Update enqueue handles:
   - `wp-ai-chatbot` → `semantic-knowledge-chatbot`
   - `wp-ai-search` → `semantic-knowledge-search`

**Critical Files:**
- `/assets/css/chatbot.css`
- `/assets/css/search.css`
- `/assets/js/chatbot.js`
- `/assets/js/search.js`
- `/includes/class-semantic-knowledge-assets.php`

---

### Phase 5: Configuration & Packages 📦
**Goal:** Update package names and build configuration

**Tasks:**
1. Update `composer.json`:
   - Package name: `"kanopi/wp-ai-assistant"` → `"kanopi/semantic-knowledge"`
   - Installer name: `"wp-ai-assistant"` → `"semantic-knowledge"`
   - Autoload namespace: Keep or update test namespace
   - Composer scripts messages
2. Update root `package.json`:
   - Package name: `"wp-ai-assistant"` → `"semantic-knowledge"`
3. Update `indexer/package.json`:
   - Package name: `"wp-ai-assistant-indexer"` → `"semantic-knowledge-indexer"`
4. Update `phpcs.xml.dist`:
   - Element value: `"wp-ai-assistant"` → `"semantic-knowledge"`
5. Update CircleCI scripts (4 files):
   - `/circleci/test-plugin.sh`
   - `/circleci/security-audit-composer.sh`
   - `/circleci/run-indexer.sh`
   - `/circleci/health-check.sh`
6. Update admin settings page:
   - Menu slug: `wp-ai-assistant` → `semantic-knowledge`
   - Tab URLs and settings fields

**Critical Files:**
- `/composer.json`
- `/package.json`
- `/indexer/package.json`
- `/phpcs.xml.dist`
- `.circleci/*.sh`
- `/includes/class-semantic-knowledge-settings.php`

**Package Management:**
- Composer package rename requires new packagist registration
- NPM package rename affects indexer dependency chain

---

### Phase 6: Documentation 📚
**Goal:** Update all documentation references

**Tasks:**
1. Update comprehensive docs (14 files in `/docs/`):
   - `/docs/ARCHITECTURE.md` - Class references and diagrams
   - `/docs/API.md` - REST endpoint documentation
   - `/docs/CONFIGURATION.md` - Settings and constants
   - `/docs/DEPLOYMENT.md` - Installation commands
   - `/docs/HOOKS.md` - Hook reference guide
   - `/docs/SHORTCODES.md` - Shortcode reference
   - `/docs/USER-GUIDE.md` - User-facing documentation
   - All other docs files
2. Update root documentation:
   - `/README.md` - Installation, usage, examples
   - `/CONTRIBUTING.md` - Developer guidelines
   - `/CIRCLECI.md` - CI/CD documentation
   - `/ACCESSIBILITY.md` - Accessibility guide
3. Update example files (4 files):
   - `/examples/analytics-tracking.php`
   - `/examples/performance-optimization.php`
   - `/examples/custom-post-type-boost.php`
   - `/examples/custom-search-summary.php`

**Documentation Quality:**
- Search and replace is not sufficient
- Review each document for context-specific updates
- Update code examples and commands
- Verify all links and references

---

### Phase 7: Testing & Updates 🧪
**Goal:** Update test files and verify all changes

**Tasks:**
1. Update test files (11+ files in `/tests/`):
   - `/tests/bootstrap.php`
   - All unit test files referencing old class names
   - All integration test files
2. Update test class names and namespaces:
   - Test classes may reference `WP_AI_*` classes
3. Run full test suite:
   ```bash
   composer test
   npm test
   ```
4. Manual verification checklist:
   - [ ] Plugin activates without errors
   - [ ] Database migration completes successfully
   - [ ] Settings page loads with new slug
   - [ ] REST API endpoints respond at new namespace
   - [ ] WP-CLI commands work with new names
   - [ ] Chatbot displays with new CSS classes
   - [ ] Search functionality works
   - [ ] Logs are written to new tables
   - [ ] Old settings data is preserved
5. Code quality checks:
   ```bash
   composer phpcs
   npm run lint
   ```

**Critical Files:**
- All files in `/tests/` directory
- `/tests/bootstrap.php`

---

## Beads Issues to Create

After plan approval, create these beads issues in priority order:

### High Priority (Core Functionality)
1. **Phase 1: Rename core infrastructure** - Main plugin file, classes, constants
2. **Phase 2: Create database migration** - Tables, options, post types
3. **Phase 3: Update REST API and hooks** - Namespace, filters, actions

### Medium Priority (User-Facing)
4. **Phase 4: Update frontend assets** - CSS, JavaScript, DOM selectors
5. **Phase 5: Update configuration** - Composer, NPM, build files

### Low Priority (Documentation)
6. **Phase 6: Update documentation** - All docs, README, examples
7. **Phase 7: Update tests and verify** - Test files, manual verification

---

## Post-Rename Tasks

After implementation:
1. Update GitHub repository name (if applicable)
2. Register new Packagist package: `kanopi/semantic-knowledge`
3. Create WordPress.org plugin submission
4. Update any external documentation or marketing materials
5. Notify users of rename via plugin admin notice
6. Consider redirect/deprecation of old package name

---

## Risk Assessment

**Low Risk:**
- Plugin has never been installed, so no existing users affected
- No data migration needed
- No backward compatibility concerns

**Testing Priority:**
- Plugin activation and initialization
- REST API endpoints respond correctly
- Frontend functionality works
- WP-CLI commands execute properly
- All tests pass

---

## Timeline Estimate

- Phase 1: Core Infrastructure - ~1.5 hours
- Phase 2: Database & Options - ~1 hour
- Phase 3: REST API & Hooks - ~1.5 hours
- Phase 4: Frontend Assets - ~1 hour
- Phase 5: Configuration - ~1 hour
- Phase 6: Documentation - ~1.5 hours
- Phase 7: Testing - ~1.5 hours

**Total:** ~9 hours of focused work (simplified with no migration needed)

---

## Success Criteria

✅ All 200+ occurrences renamed consistently
✅ Plugin activates and works without errors
✅ All tests pass (unit, integration, code quality)
✅ REST API endpoints respond correctly at new namespace
✅ Frontend displays properly with new CSS classes
✅ WP-CLI commands work with new names
✅ Documentation is comprehensive and accurate
✅ No PHP errors, warnings, or notices
✅ Package names updated on Packagist/NPM (if applicable)
