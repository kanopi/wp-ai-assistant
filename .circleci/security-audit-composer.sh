#!/bin/bash
#
# Composer Security Audit Script
#
# Description:
#   Runs composer audit on PHP packages to detect security vulnerabilities.
#
# Usage:
#   security-audit-composer.sh [OPTIONS] PLUGIN_PATH
#
# Arguments:
#   PLUGIN_PATH               Path to the plugin (e.g., web/wp-content/plugins/semantic-knowledge)
#
# Options:
#   --cache-key KEY           Custom cache key prefix (default: v1-plugin-composer-deps)
#   --skip-cache              Skip cache restoration
#   --fail-on-vulnerabilities Fail (exit 1) if vulnerabilities are found (default: false)
#   --output-file FILE        Custom output file path (default: {plugin}/composer-audit-report.json)
#   --store-artifacts         Store audit report as artifact (default: true in CI)
#   --acf-auth                Enable ACF authentication (requires ACF_USERNAME, ACF_PROD_URL env vars)
#   --yoast-auth              Enable Yoast authentication (requires YOAST_TOKEN env var)
#   --install-dev             Install dev dependencies (default: false)
#   --dry-run                 Show what would be done without executing
#   --help                    Show this help message
#
# Environment Variables:
#   ACF_USERNAME              Advanced Custom Fields username (if --acf-auth used)
#   ACF_PROD_URL              Advanced Custom Fields production URL (if --acf-auth used)
#   YOAST_TOKEN               Yoast token (if --yoast-auth used)
#
# Exit Codes:
#   0 - No vulnerabilities found or vulnerabilities ignored
#   1 - Vulnerabilities found (when --fail-on-vulnerabilities is set)
#   2 - Invalid arguments or execution error
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
SKIP_CACHE="false"
FAIL_ON_VULNERABILITIES="false"
OUTPUT_FILE=""
STORE_ARTIFACTS="${CIRCLECI:-false}"
ACF_AUTH="false"
YOAST_AUTH="false"
INSTALL_DEV="false"
DRY_RUN="false"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --cache-key)
            CACHE_KEY="$2"
            shift 2
            ;;
        --skip-cache)
            SKIP_CACHE="true"
            shift
            ;;
        --fail-on-vulnerabilities)
            FAIL_ON_VULNERABILITIES="true"
            shift
            ;;
        --output-file)
            OUTPUT_FILE="$2"
            shift 2
            ;;
        --store-artifacts)
            STORE_ARTIFACTS="true"
            shift
            ;;
        --acf-auth)
            ACF_AUTH="true"
            shift
            ;;
        --yoast-auth)
            YOAST_AUTH="true"
            shift
            ;;
        --install-dev)
            INSTALL_DEV="true"
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

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
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

# Set default output file if not specified
if [[ -z "$OUTPUT_FILE" ]]; then
    OUTPUT_FILE="$PLUGIN_PATH/composer-audit-report.json"
fi

log_header "Composer Security Audit"
log_info "Plugin: $PLUGIN_PATH"
log_info "Output file: $OUTPUT_FILE"
log_info "Install dev dependencies: $INSTALL_DEV"
log_info "Dry run: $DRY_RUN"

# Restore cache (if in CircleCI and cache enabled)
if [[ "$SKIP_CACHE" == "false" ]] && [[ -n "${CIRCLECI:-}" ]]; then
    log_header "Restoring Cache"
    if [[ "$DRY_RUN" == "false" ]]; then
        log_info "Cache restoration handled by CircleCI"
        log_info "Cache key: ${CACHE_KEY}-{{ checksum \"$PLUGIN_PATH/composer.lock\" }}"
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
            exit 2
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
            exit 2
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

    INSTALL_OPTIONS="--no-scripts"
    if [[ "$INSTALL_DEV" == "false" ]]; then
        INSTALL_OPTIONS="$INSTALL_OPTIONS --no-dev"
    fi

    log_info "Running composer install $INSTALL_OPTIONS..."
    if composer install $INSTALL_OPTIONS; then
        log_success "Dependencies installed"
    else
        log_error "Composer install failed"
        exit 2
    fi

    cd - > /dev/null
else
    log_info "[DRY RUN] Would run: cd $PLUGIN_PATH && composer install --no-dev --no-scripts"
fi

# Run composer audit
log_header "Running composer audit"
AUDIT_RESULT=0

if [[ "$DRY_RUN" == "false" ]]; then
    cd "$PLUGIN_PATH"

    # Run audit with JSON output (continue on failure)
    log_info "Generating JSON audit report..."
    if composer audit --format=json > "$(basename "$OUTPUT_FILE")" 2>&1; then
        log_success "No vulnerabilities found"
        AUDIT_RESULT=0
    else
        VULNERABILITIES_EXIT_CODE=$?
        log_warning "Vulnerabilities detected"
        AUDIT_RESULT=1
    fi

    # Run audit again for human-readable output
    echo ""
    log_info "Running audit for display output..."
    if composer audit; then
        log_success "Audit passed"
    else
        # Parse JSON report to show summary
        if [[ -f "$(basename "$OUTPUT_FILE")" ]]; then
            if command -v jq &> /dev/null; then
                echo ""
                log_info "Vulnerability summary:"
                # Try to extract count from JSON report
                VULN_COUNT=$(jq -r '.advisories | length // 0' "$(basename "$OUTPUT_FILE")" 2>/dev/null || echo "unknown")
                echo "Total advisories: $VULN_COUNT"
            fi
        fi
    fi

    # Move output file to final location if different
    if [[ "$(basename "$OUTPUT_FILE")" != "$OUTPUT_FILE" ]]; then
        mkdir -p "$(dirname "$OUTPUT_FILE")"
        mv "$(basename "$OUTPUT_FILE")" "$OUTPUT_FILE"
    fi

    cd - > /dev/null

    if [[ -f "$OUTPUT_FILE" ]]; then
        log_success "Audit report saved to: $OUTPUT_FILE"
    fi
else
    log_info "[DRY RUN] Would run: composer audit --format=json"
    AUDIT_RESULT=0
fi

# Store artifacts (if in CircleCI)
if [[ "$STORE_ARTIFACTS" == "true" ]] && [[ -n "${CIRCLECI:-}" ]]; then
    log_header "Storing Artifacts"
    if [[ "$DRY_RUN" == "false" ]]; then
        if [[ -f "$OUTPUT_FILE" ]]; then
            log_info "Artifact storage handled by CircleCI"
            log_info "  - store_artifacts: $OUTPUT_FILE"
        else
            log_warning "Output file not found: $OUTPUT_FILE"
        fi
    else
        log_info "[DRY RUN] Would store artifact: $OUTPUT_FILE"
    fi
fi

log_header "Security Audit Complete"

if [[ $AUDIT_RESULT -eq 0 ]]; then
    log_success "No vulnerabilities found"
    exit 0
else
    if [[ "$FAIL_ON_VULNERABILITIES" == "true" ]]; then
        log_error "Vulnerabilities found - failing build"
        exit 1
    else
        log_warning "Vulnerabilities found but not failing build"
        log_info "Use --fail-on-vulnerabilities to fail on vulnerabilities"
        exit 0
    fi
fi
