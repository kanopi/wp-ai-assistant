#!/bin/bash
#
# WordPress Plugin Testing Script
#
# Description:
#   Runs PHPUnit tests for WordPress plugins with Composer dependency caching.
#
# Usage:
#   test-wp-plugin.sh [OPTIONS] PLUGIN_PATH
#
# Arguments:
#   PLUGIN_PATH               Path to the plugin (e.g., web/wp-content/plugins/semantic-knowledge)
#
# Options:
#   --cache-key KEY           Custom cache key prefix (default: v1-plugin-composer-deps)
#   --phpunit-options OPTS    Additional PHPUnit options (default: --order-by=defects --stop-on-failure)
#   --skip-cache              Skip cache restoration and saving
#   --store-results           Store test results in CircleCI (default: true in CI)
#   --results-path PATH       Path to store test results (default: {plugin}/coverage)
#   --acf-auth                Enable ACF authentication (requires ACF_USERNAME, ACF_PROD_URL env vars)
#   --yoast-auth              Enable Yoast authentication (requires YOAST_TOKEN env var)
#   --dry-run                 Show what would be done without executing
#   --help                    Show this help message
#
# Environment Variables:
#   ACF_USERNAME              Advanced Custom Fields username (if --acf-auth used)
#   ACF_PROD_URL              Advanced Custom Fields production URL (if --acf-auth used)
#   YOAST_TOKEN               Yoast token (if --yoast-auth used)
#
# Exit Codes:
#   0 - Tests passed
#   1 - Tests failed or execution error
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
PLUGIN_PATH=""
CACHE_KEY="v1-plugin-composer-deps"
PHPUNIT_OPTIONS="--order-by=defects --stop-on-failure"
SKIP_CACHE="false"
STORE_RESULTS="${CIRCLECI:-false}"
RESULTS_PATH=""
ACF_AUTH="false"
YOAST_AUTH="false"
DRY_RUN="false"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --cache-key)
            CACHE_KEY="$2"
            shift 2
            ;;
        --phpunit-options)
            PHPUNIT_OPTIONS="$2"
            shift 2
            ;;
        --skip-cache)
            SKIP_CACHE="true"
            shift
            ;;
        --store-results)
            STORE_RESULTS="true"
            shift
            ;;
        --results-path)
            RESULTS_PATH="$2"
            shift 2
            ;;
        --acf-auth)
            ACF_AUTH="true"
            shift
            ;;
        --yoast-auth)
            YOAST_AUTH="true"
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
        -*)
            echo -e "${RED}Error: Unknown option $1${NC}"
            echo "Use --help for usage information"
            exit 2
            ;;
        *)
            PLUGIN_PATH="$1"
            shift
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

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Validate required parameters
if [[ -z "$PLUGIN_PATH" ]]; then
    log_error "Plugin path is required"
    echo "Usage: $0 [OPTIONS] PLUGIN_PATH"
    exit 2
fi

if [[ ! -d "$PLUGIN_PATH" ]]; then
    log_error "Plugin path does not exist: $PLUGIN_PATH"
    exit 2
fi

if [[ ! -f "$PLUGIN_PATH/composer.json" ]]; then
    log_error "No composer.json found in $PLUGIN_PATH"
    exit 2
fi

# Set default results path if not specified
if [[ -z "$RESULTS_PATH" ]]; then
    RESULTS_PATH="$PLUGIN_PATH/coverage"
fi

log_header "WordPress Plugin Testing"
log_info "Plugin: $PLUGIN_PATH"
log_info "PHPUnit options: $PHPUNIT_OPTIONS"
log_info "Dry run: $DRY_RUN"

# Restore cache (if in CircleCI and cache enabled)
if [[ "$SKIP_CACHE" == "false" ]] && [[ -n "${CIRCLECI:-}" ]]; then
    log_header "Restoring Cache"
    if [[ "$DRY_RUN" == "false" ]]; then
        log_info "Cache restoration handled by CircleCI"
        log_info "Cache keys:"
        log_info "  - ${CACHE_KEY}-{{ checksum \"$PLUGIN_PATH/composer.lock\" }}"
        log_info "  - ${CACHE_KEY}-{{ .Branch }}-"
        log_info "  - ${CACHE_KEY}-main-"
        log_info "  - ${CACHE_KEY}-"
    else
        log_info "[DRY RUN] Would restore cache with key: $CACHE_KEY"
    fi
fi

# Configure Composer authentication
if [[ "$ACF_AUTH" == "true" ]] || [[ "$YOAST_AUTH" == "true" ]]; then
    log_header "Configuring Composer Authentication"

    if [[ "$ACF_AUTH" == "true" ]]; then
        if [[ -z "${ACF_USERNAME:-}" ]] || [[ -z "${ACF_PROD_URL:-}" ]]; then
            log_error "ACF authentication requested but ACF_USERNAME or ACF_PROD_URL not set"
            exit 1
        fi

        if [[ "$DRY_RUN" == "false" ]]; then
            log_info "Configuring ACF authentication..."
            composer config -g http-basic.connect.advancedcustomfields.com "$ACF_USERNAME" "$ACF_PROD_URL"
            log_success "ACF authentication configured"
        else
            log_info "[DRY RUN] Would configure ACF authentication"
        fi
    fi

    if [[ "$YOAST_AUTH" == "true" ]]; then
        if [[ -z "${YOAST_TOKEN:-}" ]]; then
            log_error "Yoast authentication requested but YOAST_TOKEN not set"
            exit 1
        fi

        if [[ "$DRY_RUN" == "false" ]]; then
            log_info "Configuring Yoast authentication..."
            composer config -g http-basic.my.yoast.com token "$YOAST_TOKEN"
            log_success "Yoast authentication configured"
        else
            log_info "[DRY RUN] Would configure Yoast authentication"
        fi
    fi
fi

# Install Composer dependencies
log_header "Installing Composer Dependencies"
if [[ "$DRY_RUN" == "false" ]]; then
    cd "$PLUGIN_PATH"

    log_info "Running composer install..."
    if composer install --prefer-dist --no-interaction; then
        log_success "Dependencies installed"
    else
        log_error "Composer install failed"
        exit 1
    fi

    cd - > /dev/null
else
    log_info "[DRY RUN] Would run: cd $PLUGIN_PATH && composer install --prefer-dist --no-interaction"
fi

# Save cache (if in CircleCI and cache enabled)
if [[ "$SKIP_CACHE" == "false" ]] && [[ -n "${CIRCLECI:-}" ]]; then
    log_header "Saving Cache"
    if [[ "$DRY_RUN" == "false" ]]; then
        log_info "Cache saving handled by CircleCI"
        log_info "Cache paths:"
        log_info "  - $PLUGIN_PATH/vendor"
        log_info "  - ~/.composer/cache"
        log_info "  - ~/.cache/composer"
    else
        log_info "[DRY RUN] Would save cache to: $CACHE_KEY"
    fi
fi

# Run PHPUnit tests
log_header "Running PHPUnit Tests"
if [[ "$DRY_RUN" == "false" ]]; then
    cd "$PLUGIN_PATH"

    # Check if PHPUnit is available
    if [[ ! -f "vendor/bin/phpunit" ]]; then
        log_error "PHPUnit not found in vendor/bin/phpunit"
        log_error "Make sure phpunit/phpunit is in composer.json require-dev"
        exit 1
    fi

    # Run PHPUnit with specified options
    log_info "Executing: vendor/bin/phpunit $PHPUNIT_OPTIONS"
    if vendor/bin/phpunit $PHPUNIT_OPTIONS; then
        log_success "Tests passed"
        TEST_RESULT=0
    else
        log_error "Tests failed"
        TEST_RESULT=1
    fi

    cd - > /dev/null
else
    log_info "[DRY RUN] Would run: vendor/bin/phpunit $PHPUNIT_OPTIONS"
    TEST_RESULT=0
fi

# Store test results and artifacts (if in CircleCI)
if [[ "$STORE_RESULTS" == "true" ]] && [[ -n "${CIRCLECI:-}" ]]; then
    log_header "Storing Test Results"
    if [[ "$DRY_RUN" == "false" ]]; then
        if [[ -d "$RESULTS_PATH" ]]; then
            log_success "Test results available at: $RESULTS_PATH"
            log_info "Store test results handled by CircleCI"
            log_info "  - store_test_results: $RESULTS_PATH"
            log_info "  - store_artifacts: $RESULTS_PATH"
        else
            log_info "No test results found at $RESULTS_PATH"
        fi
    else
        log_info "[DRY RUN] Would store test results from: $RESULTS_PATH"
    fi
fi

log_header "Testing Complete"
if [[ $TEST_RESULT -eq 0 ]]; then
    log_success "All tests passed"
else
    log_error "Tests failed"
fi

exit $TEST_RESULT
