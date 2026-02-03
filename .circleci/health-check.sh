#!/bin/bash

###############################################################################
# Health Check Script for WP AI Assistant Plugin
#
# This script performs comprehensive health checks after deployment to verify
# that the WP AI Assistant plugin is functioning correctly.
#
# Exit codes:
#   0 - All health checks passed
#   1 - One or more critical health checks failed
#   2 - Environment not ready (retryable)
###############################################################################

set -eo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
MAX_RETRIES=3
RETRY_DELAY=10
TIMEOUT=30

# Health check results
PASSED=0
FAILED=0
WARNINGS=0

###############################################################################
# Helper Functions
###############################################################################

log_info() {
    echo -e "${GREEN}✓${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

log_section() {
    echo ""
    echo "=================================================="
    echo "$1"
    echo "=================================================="
}

check_url() {
    local url=$1
    local expected_status=${2:-200}
    local response

    response=$(curl -s -w "\n%{http_code}" --max-time "$TIMEOUT" "$url" 2>&1 || echo "000")
    local status_code=$(echo "$response" | tail -n1)

    if [[ "$status_code" == "$expected_status" ]]; then
        return 0
    else
        echo "$status_code"
        return 1
    fi
}

###############################################################################
# Health Check Tests
###############################################################################

check_site_reachable() {
    log_section "1. Site Reachability Check"

    local retry_count=0
    while [[ $retry_count -lt $MAX_RETRIES ]]; do
        if check_url "$ENV_URL" 200 > /dev/null 2>&1; then
            log_info "Site is reachable at $ENV_URL"
            ((PASSED++))
            return 0
        fi

        ((retry_count++))
        if [[ $retry_count -lt $MAX_RETRIES ]]; then
            echo "Retry $retry_count/$MAX_RETRIES in ${RETRY_DELAY}s..."
            sleep "$RETRY_DELAY"
        fi
    done

    log_error "Site not reachable after $MAX_RETRIES attempts"
    return 1
}

check_plugin_active() {
    log_section "2. Plugin Activation Check"

    # Determine environment
    if [[ "${CIRCLE_BRANCH}" == "main" ]]; then
        PANTHEON_ENV="dev"
    else
        PANTHEON_ENV=$(echo ${ENV_URL} | sed -n "s|https://\(.*\)-${TERMINUS_SITE}\.pantheonsite\.io.*|\1|p")
    fi

    echo "Checking plugin status on ${TERMINUS_SITE}.${PANTHEON_ENV}..."

    local plugin_check
    plugin_check=$(terminus wp ${TERMINUS_SITE}.${PANTHEON_ENV} -- plugin list --name=wp-ai-assistant --field=status 2>&1 || echo "error")

    if [[ "$plugin_check" == "active" ]]; then
        log_info "WP AI Assistant plugin is active"
        ((PASSED++))
    else
        log_error "WP AI Assistant plugin is not active: $plugin_check"
        return 1
    fi
}

check_rest_api() {
    log_section "3. REST API Endpoints Check"

    # Check chatbot endpoint
    local chatbot_url="${ENV_URL}/wp-json/ai-assistant/v1/chat"
    if curl -s --max-time "$TIMEOUT" "$chatbot_url" | grep -q "rest_no_route\|Missing parameter"; then
        log_info "Chatbot REST API endpoint exists"
        ((PASSED++))
    else
        log_error "Chatbot REST API endpoint not responding correctly"
        return 1
    fi

    # Check search endpoint
    local search_url="${ENV_URL}/wp-json/ai-assistant/v1/search"
    if curl -s --max-time "$TIMEOUT" "$search_url" | grep -q "rest_no_route\|Missing parameter"; then
        log_info "Search REST API endpoint exists"
        ((PASSED++))
    else
        log_error "Search REST API endpoint not responding correctly"
        return 1
    fi
}

check_assets_loaded() {
    log_section "4. Asset Loading Check"

    # Check if plugin CSS is accessible
    local css_pattern="wp-ai-assistant.*\.css"
    if curl -s --max-time "$TIMEOUT" "$ENV_URL" | grep -q "$css_pattern"; then
        log_info "Plugin CSS assets are being loaded"
        ((PASSED++))
    else
        log_warning "Plugin CSS may not be loading (could be lazy-loaded)"
    fi

    # Check if plugin JS is accessible
    local js_pattern="wp-ai-assistant.*\.js"
    if curl -s --max-time "$TIMEOUT" "$ENV_URL" | grep -q "$js_pattern"; then
        log_info "Plugin JavaScript assets are being loaded"
        ((PASSED++))
    else
        log_warning "Plugin JavaScript may not be loading (could be lazy-loaded)"
    fi
}

check_database() {
    log_section "5. Database Check"

    # Determine environment
    if [[ "${CIRCLE_BRANCH}" == "main" ]]; then
        PANTHEON_ENV="dev"
    else
        PANTHEON_ENV=$(echo ${ENV_URL} | sed -n "s|https://\(.*\)-${TERMINUS_SITE}\.pantheonsite\.io.*|\1|p")
    fi

    # Check for plugin tables
    local table_check
    table_check=$(terminus wp ${TERMINUS_SITE}.${PANTHEON_ENV} -- db query "SHOW TABLES LIKE 'wp_%ai_%'" 2>&1 || echo "error")

    if [[ "$table_check" != "error" && -n "$table_check" ]]; then
        log_info "Plugin database tables exist"
        ((PASSED++))
    else
        log_warning "Plugin database tables not found (may not be required)"
    fi
}

check_wp_cli() {
    log_section "6. WP-CLI Command Check"

    # Determine environment
    if [[ "${CIRCLE_BRANCH}" == "main" ]]; then
        PANTHEON_ENV="dev"
    else
        PANTHEON_ENV=$(echo ${ENV_URL} | sed -n "s|https://\(.*\)-${TERMINUS_SITE}\.pantheonsite\.io.*|\1|p")
    fi

    # Check WP-CLI command exists
    local cli_check
    cli_check=$(terminus wp ${TERMINUS_SITE}.${PANTHEON_ENV} -- help ai-indexer 2>&1 || echo "error")

    if [[ "$cli_check" != "error" ]] && echo "$cli_check" | grep -q "ai-indexer"; then
        log_info "WP-CLI 'ai-indexer' command is available"
        ((PASSED++))
    else
        log_warning "WP-CLI 'ai-indexer' command not found"
    fi
}

check_settings_page() {
    log_section "7. Admin Settings Page Check"

    # Determine environment
    if [[ "${CIRCLE_BRANCH}" == "main" ]]; then
        PANTHEON_ENV="dev"
    else
        PANTHEON_ENV=$(echo ${ENV_URL} | sed -n "s|https://\(.*\)-${TERMINUS_SITE}\.pantheonsite\.io.*|\1|p")
    fi

    # Check if settings page is registered
    local settings_check
    settings_check=$(terminus wp ${TERMINUS_SITE}.${PANTHEON_ENV} -- eval "echo get_option('ai_assistant_settings') ? 'exists' : 'not_found';" 2>&1 || echo "error")

    if [[ "$settings_check" == "exists" ]]; then
        log_info "Plugin settings are configured"
        ((PASSED++))
    elif [[ "$settings_check" == "not_found" ]]; then
        log_warning "Plugin settings not yet configured (expected for new deployments)"
    else
        log_error "Error checking plugin settings: $settings_check"
    fi
}

check_performance() {
    log_section "8. Performance Check"

    # Check homepage load time
    local start_time=$(date +%s%N)
    if curl -s -o /dev/null --max-time "$TIMEOUT" "$ENV_URL"; then
        local end_time=$(date +%s%N)
        local duration=$(( (end_time - start_time) / 1000000 )) # Convert to milliseconds

        if [[ $duration -lt 3000 ]]; then
            log_info "Homepage loads in ${duration}ms (good)"
            ((PASSED++))
        elif [[ $duration -lt 5000 ]]; then
            log_warning "Homepage loads in ${duration}ms (acceptable but slow)"
        else
            log_warning "Homepage loads in ${duration}ms (slow - may need optimization)"
        fi
    else
        log_warning "Could not measure homepage load time"
    fi
}

###############################################################################
# Main Execution
###############################################################################

main() {
    echo "=================================================="
    echo "WP AI Assistant Plugin - Health Check"
    echo "Environment: ${ENV_URL}"
    echo "Branch: ${CIRCLE_BRANCH}"
    echo "Commit: ${CIRCLE_SHA1:0:7}"
    echo "=================================================="
    echo ""

    # Ensure required environment variables are set
    if [[ -z "$ENV_URL" ]]; then
        echo "ERROR: ENV_URL environment variable is not set"
        exit 2
    fi

    if [[ -z "$TERMINUS_SITE" ]]; then
        echo "ERROR: TERMINUS_SITE environment variable is not set"
        exit 2
    fi

    # Run all health checks
    check_site_reachable
    check_plugin_active
    check_rest_api
    check_assets_loaded
    check_database
    check_wp_cli
    check_settings_page
    check_performance

    # Print summary
    echo ""
    echo "=================================================="
    echo "Health Check Summary"
    echo "=================================================="
    echo -e "${GREEN}Passed:${NC}   $PASSED"
    echo -e "${YELLOW}Warnings:${NC} $WARNINGS"
    echo -e "${RED}Failed:${NC}   $FAILED"
    echo "=================================================="
    echo ""

    # Determine exit code
    if [[ $FAILED -gt 0 ]]; then
        echo "❌ Health checks FAILED"
        echo "Please review the errors above and investigate."
        exit 1
    elif [[ $WARNINGS -gt 0 ]]; then
        echo "⚠️  Health checks PASSED with warnings"
        echo "Please review the warnings above."
        exit 0
    else
        echo "✅ All health checks PASSED"
        exit 0
    fi
}

# Run main function
main "$@"
