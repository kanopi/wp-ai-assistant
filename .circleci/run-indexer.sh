#!/bin/bash
#
# AI Assistant Indexer Execution Script
#
# Description:
#   Executes the Semantic Knowledge indexer with comprehensive environment validation,
#   content change detection, and metrics capture.
#
# Usage:
#   run-indexer.sh [OPTIONS]
#
# Options:
#   --site-id SITE_ID          Terminus site ID (default: $TERMINUS_SITE)
#   --indexer-path PATH        Path to indexer package (default: packages/wp-ai-indexer)
#   --branch BRANCH            Git branch name (default: $CIRCLE_BRANCH or current branch)
#   --env-url URL              Environment URL (optional, will be detected via Terminus)
#   --force                    Force full reindex regardless of changes
#   --skip-change-detection    Skip content change detection and always run
#   --dry-run                  Show what would be done without running indexer
#   --help                     Show this help message
#
# Required Environment Variables:
#   TERMINUS_TOKEN             Pantheon Terminus authentication token
#   TERMINUS_SITE              Pantheon site ID (or use --site-id)
#   OPENAI_API_KEY            OpenAI API key for embeddings
#   PINECONE_API_KEY          Pinecone API key for vector storage
#   WP_API_USERNAME           WordPress API username
#   WP_API_PASSWORD           WordPress Application Password
#
# Optional Environment Variables:
#   FORCE_FULL_REINDEX        Set to 'true' to force full reindex
#   PINECONE_INDEX_HOST       Pinecone index host (if not using default)
#   PINECONE_INDEX_NAME       Pinecone index name (if not using default)
#   CIRCLE_BRANCH             CircleCI branch name (auto-detected)
#   CIRCLE_SHA1               CircleCI commit SHA (auto-detected)
#   CIRCLE_BUILD_URL          CircleCI build URL (auto-detected)
#
# Exit Codes:
#   0 - Success or skipped (no content changes)
#   1 - Failure (indexer error or validation failure)
#   2 - Invalid arguments
#

set -eo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
SITE_ID="${TERMINUS_SITE:-}"
INDEXER_PATH="packages/wp-ai-indexer"
BRANCH="${CIRCLE_BRANCH:-$(git branch --show-current 2>/dev/null || echo 'main')}"
ENV_URL=""
FORCE_REINDEX="${FORCE_FULL_REINDEX:-false}"
SKIP_CHANGE_DETECTION="false"
DRY_RUN="false"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --site-id)
            SITE_ID="$2"
            shift 2
            ;;
        --indexer-path)
            INDEXER_PATH="$2"
            shift 2
            ;;
        --branch)
            BRANCH="$2"
            shift 2
            ;;
        --env-url)
            ENV_URL="$2"
            shift 2
            ;;
        --force)
            FORCE_REINDEX="true"
            shift
            ;;
        --skip-change-detection)
            SKIP_CHANGE_DETECTION="true"
            shift
            ;;
        --dry-run)
            DRY_RUN="true"
            shift
            ;;
        --help)
            grep '^#' "$0" | grep -v '#!/bin/bash' | sed 's/^# //;s/^#//'
            exit 0
            ;;
        *)
            echo -e "${RED}Error: Unknown option $1${NC}"
            echo "Use --help for usage information"
            exit 2
            ;;
    esac
done

# Helper functions
log_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_skip() {
    echo -e "${YELLOW}⊘${NC} $1"
}

# Validate required parameters
if [[ -z "$SITE_ID" ]]; then
    log_error "SITE_ID is required (use --site-id or set TERMINUS_SITE)"
    exit 2
fi

if [[ ! -d "$INDEXER_PATH" ]]; then
    log_error "Indexer path does not exist: $INDEXER_PATH"
    exit 2
fi

log_header "AI Assistant Indexer Execution"
log_info "Site ID: $SITE_ID"
log_info "Indexer Path: $INDEXER_PATH"
log_info "Branch: $BRANCH"
log_info "Dry Run: $DRY_RUN"

# Install Node.js if not present
log_header "Setting Up Node.js Environment"
if ! command -v node &> /dev/null; then
    log_info "Node.js not found, installing..."
    if [[ "$DRY_RUN" == "false" ]]; then
        curl -fsSL https://deb.nodesource.com/setup_22.x | sudo bash -
        sudo apt-get install -y nodejs
    else
        log_info "[DRY RUN] Would install Node.js 22.x"
    fi
fi

if command -v node &> /dev/null; then
    log_success "Node.js version: $(node --version)"
    log_success "npm version: $(npm --version)"
fi

# Install indexer dependencies
log_header "Installing Indexer Dependencies"
if [[ "$DRY_RUN" == "false" ]]; then
    cd "$INDEXER_PATH"
    npm ci
    cd - > /dev/null
    log_success "Dependencies installed"
else
    log_info "[DRY RUN] Would run: cd $INDEXER_PATH && npm ci"
fi

# Authenticate with Terminus and detect environment URL
log_header "Authenticating with Terminus"
if [[ -z "$TERMINUS_TOKEN" ]]; then
    log_error "TERMINUS_TOKEN is not set"
    exit 1
fi

if [[ "$DRY_RUN" == "false" ]]; then
    terminus auth:login --machine-token="${TERMINUS_TOKEN}" > /dev/null 2>&1
    log_success "Terminus authenticated"
else
    log_info "[DRY RUN] Would authenticate with Terminus"
fi

# Determine Pantheon environment
if [[ "$BRANCH" == "main" ]]; then
    PANTHEON_ENV="dev"
else
    # For non-main branches, try to detect multidev name
    if [[ -n "$ENV_URL" ]]; then
        PANTHEON_ENV=$(echo "$ENV_URL" | sed -n "s|https://\(.*\)-${SITE_ID}\.pantheonsite\.io.*|\1|p")
    else
        # Fallback: use branch name sanitized for multidev
        PANTHEON_ENV=$(echo "$BRANCH" | sed 's/[^a-z0-9-]/-/g' | cut -c1-11)
    fi
fi

log_info "Pantheon environment: $PANTHEON_ENV"

# Get environment URL if not provided
if [[ -z "$ENV_URL" ]]; then
    log_info "Detecting environment URL..."
    if [[ "$DRY_RUN" == "false" ]]; then
        ENV_URL=$(terminus env:view "${SITE_ID}.${PANTHEON_ENV}" --print 2>/dev/null || echo "")
        if [[ -z "$ENV_URL" ]]; then
            log_error "Could not detect environment URL"
            exit 1
        fi
        log_success "Environment URL: $ENV_URL"
    else
        ENV_URL="https://${PANTHEON_ENV}-${SITE_ID}.pantheonsite.io"
        log_info "[DRY RUN] Would detect URL, using: $ENV_URL"
    fi
else
    log_success "Using provided URL: $ENV_URL"
fi

# Activate WordPress plugin
log_header "Activating WordPress Plugin"
if [[ "$DRY_RUN" == "false" ]]; then
    log_info "Ensuring plugin is activated on ${SITE_ID}.${PANTHEON_ENV}..."
    terminus wp "${SITE_ID}.${PANTHEON_ENV}" -- plugin activate semantic-knowledge 2>&1 | grep -v "^Warning:" || true

    # Verify plugin is active
    if terminus wp "${SITE_ID}.${PANTHEON_ENV}" -- plugin list --status=active --name=semantic-knowledge --format=count 2>&1 | grep -q "1"; then
        log_success "Plugin is active"
    else
        log_warning "Plugin activation verification failed, but continuing..."
    fi
else
    log_info "[DRY RUN] Would activate semantic-knowledge plugin"
fi

# Set WordPress API Base URL
export WP_API_BASE="${ENV_URL}/wp-json/wp/v2"
log_info "WordPress API Base: $WP_API_BASE"

# Verify required environment variables
log_header "Verifying Environment Variables"
VALIDATION_FAILED=false

check_var() {
    local var_name=$1
    local display_value=${2:-false}

    if [[ -z "${!var_name}" ]]; then
        log_error "$var_name is not set"
        VALIDATION_FAILED=true
    else
        if [[ "$display_value" == "true" ]]; then
            log_success "$var_name is set (value: ${!var_name})"
        else
            log_success "$var_name is set"
        fi
    fi
}

check_var "OPENAI_API_KEY"
check_var "PINECONE_API_KEY"
check_var "WP_API_USERNAME" "true"
check_var "WP_API_PASSWORD"

if [[ "$VALIDATION_FAILED" == "true" ]]; then
    log_error "Required environment variables are missing"
    exit 1
fi

# Test WordPress API authentication
log_header "Testing WordPress API Authentication"
if [[ "$DRY_RUN" == "false" ]]; then
    AUTH_HEADER="Authorization: Basic $(echo -n "${WP_API_USERNAME}:${WP_API_PASSWORD}" | base64)"
    RESPONSE=$(curl -s -w "\n%{http_code}" -H "$AUTH_HEADER" "${WP_API_BASE}/types")
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

    if [[ "$HTTP_CODE" == "401" ]]; then
        log_error "Authentication failed (401 Unauthorized)"
        echo ""
        echo "This usually means:"
        echo "  1. The credentials are incorrect"
        echo "  2. You need to use an Application Password instead of a regular password"
        echo "  3. The user doesn't have sufficient permissions"
        echo ""
        echo "To create an Application Password in WordPress:"
        echo "  1. Go to: ${ENV_URL}/wp-admin/profile.php"
        echo "  2. Scroll to 'Application Passwords'"
        echo "  3. Create a new application password"
        echo "  4. Update WP_API_PASSWORD with the generated password"
        exit 1
    elif [[ "$HTTP_CODE" == "200" ]]; then
        log_success "Authentication successful"
    else
        log_warning "Unexpected response code: $HTTP_CODE (continuing anyway)"
    fi
else
    log_info "[DRY RUN] Would test API authentication"
fi

# Detect content changes
log_header "Detecting Content Changes"
SHOULD_RUN_INDEXER="false"
REINDEX_REASON=""

if [[ "$FORCE_REINDEX" == "true" ]]; then
    log_success "Forced full reindex requested"
    SHOULD_RUN_INDEXER="true"
    REINDEX_REASON="Forced full reindex"
elif [[ "$SKIP_CHANGE_DETECTION" == "true" ]]; then
    log_success "Change detection skipped (--skip-change-detection)"
    SHOULD_RUN_INDEXER="true"
    REINDEX_REASON="Change detection skipped"
else
    # Check for content-related file changes
    CHANGED_FILES=$(git diff --name-only HEAD~1 HEAD 2>/dev/null || echo "")
    CONTENT_CHANGED=false

    # Check for WordPress content changes
    if echo "$CHANGED_FILES" | grep -qE "web/wp-content/(themes|plugins)/.*\.php$"; then
        log_success "Content files changed (themes/plugins)"
        CONTENT_CHANGED=true
        REINDEX_REASON="Theme/plugin content changed"
    fi

    # Check for changes to the AI plugin itself
    if echo "$CHANGED_FILES" | grep -q "web/wp-content/plugins/semantic-knowledge/"; then
        log_success "AI Assistant plugin changed"
        CONTENT_CHANGED=true
        REINDEX_REASON="AI Assistant plugin changed"
    fi

    # Run weekly full reindex (every Monday)
    if [[ "$(date +%u)" == "1" ]] && [[ "$BRANCH" == "main" ]]; then
        log_success "Weekly full reindex (Monday)"
        CONTENT_CHANGED=true
        REINDEX_REASON="Weekly full reindex"
    fi

    # Always run on main branch deployments
    if [[ "$BRANCH" == "main" ]]; then
        log_success "Main branch deployment"
        CONTENT_CHANGED=true
        REINDEX_REASON="Main branch deployment"
    fi

    if [[ "$CONTENT_CHANGED" == "true" ]]; then
        log_success "Content changes detected - will run indexer"
        log_info "Reason: $REINDEX_REASON"
        SHOULD_RUN_INDEXER="true"
    else
        log_skip "No content changes detected - skipping indexer"
        SHOULD_RUN_INDEXER="false"
    fi
fi

# Export metrics for use by notification scripts
export INDEXER_SUCCESS="skipped"
export POSTS_INDEXED="N/A"
export INDEXER_DURATION="N/A"
export INDEXER_ERRORS="0"
export ENV_URL

# Skip indexing if no changes detected
if [[ "$SHOULD_RUN_INDEXER" == "false" ]]; then
    log_header "Indexer Skipped"
    log_skip "No indexing needed"
    exit 0
fi

if [[ "$DRY_RUN" == "true" ]]; then
    log_header "Dry Run Complete"
    log_info "Would run indexer with reason: $REINDEX_REASON"
    exit 0
fi

# Run AI Assistant Indexer with Metrics
log_header "Running AI Assistant Indexer"
log_info "WordPress API: $WP_API_BASE"
log_info "Reason: $REINDEX_REASON"

cd "$INDEXER_PATH"

# Capture start time
START_TIME=$(date +%s)
log_info "Started at: $(date)"

# Create temporary output file
OUTPUT_FILE=$(mktemp)
trap "rm -f $OUTPUT_FILE" EXIT

# Run indexer and capture output
if npx wp-ai-indexer index 2>&1 | tee "$OUTPUT_FILE"; then
    # Success - parse metrics from output
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    MINUTES=$((DURATION / 60))
    SECONDS=$((DURATION % 60))

    # Try to extract posts indexed from output
    POSTS_INDEXED=$(grep -oP 'Successfully indexed \K\d+' "$OUTPUT_FILE" | tail -1 || echo "N/A")
    INDEXER_ERRORS=$(grep -ic "error" "$OUTPUT_FILE" || echo "0")

    log_header "Indexer Completed Successfully"
    log_success "Posts indexed: $POSTS_INDEXED"
    log_success "Duration: ${MINUTES}m ${SECONDS}s"
    log_success "Errors: $INDEXER_ERRORS"

    # Export for external use (Slack notifications, etc.)
    export POSTS_INDEXED
    export INDEXER_DURATION="${MINUTES}m ${SECONDS}s"
    export INDEXER_ERRORS
    export INDEXER_SUCCESS="true"

    exit 0
else
    # Failure
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    MINUTES=$((DURATION / 60))
    SECONDS=$((DURATION % 60))

    log_header "Indexer Failed"
    log_error "Failed after ${MINUTES}m ${SECONDS}s"

    echo ""
    echo "Last 50 lines of output:"
    tail -50 "$OUTPUT_FILE"

    export INDEXER_SUCCESS="false"
    exit 1
fi
