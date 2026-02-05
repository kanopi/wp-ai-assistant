#!/bin/bash

###############################################################################
# Automated Rollback Script for Semantic Knowledge Plugin
#
# This script performs an automated rollback to the previous deployment
# when health checks fail or critical issues are detected.
#
# Usage:
#   ./rollback.sh [options]
#
# Options:
#   --environment <env>  Target environment (dev, test, live)
#   --commit <sha>       Specific commit to rollback to (optional)
#   --dry-run           Simulate rollback without making changes
#   --force             Skip confirmation prompts
#
# Exit codes:
#   0 - Rollback completed successfully
#   1 - Rollback failed
#   2 - Invalid arguments or preconditions not met
###############################################################################

set -eo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
DRY_RUN=false
FORCE=false
TARGET_COMMIT=""
ENVIRONMENT=""

###############################################################################
# Helper Functions
###############################################################################

log_info() {
    echo -e "${GREEN}✓${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_step() {
    echo -e "${BLUE}➜${NC} $1"
}

log_section() {
    echo ""
    echo "=================================================="
    echo "$1"
    echo "=================================================="
}

usage() {
    cat << EOF
Usage: $0 [options]

Automated rollback script for Semantic Knowledge Plugin deployment.

Options:
    --environment <env>   Target environment (dev, test, live) [required]
    --commit <sha>        Specific commit SHA to rollback to (optional)
    --dry-run            Simulate rollback without making changes
    --force              Skip confirmation prompts
    -h, --help           Display this help message

Examples:
    # Rollback dev environment to previous deployment
    $0 --environment dev

    # Rollback to specific commit
    $0 --environment dev --commit abc123def

    # Dry run (simulation)
    $0 --environment dev --dry-run

EOF
    exit 0
}

###############################################################################
# Parse Arguments
###############################################################################

while [[ $# -gt 0 ]]; do
    case $1 in
        --environment)
            ENVIRONMENT="$2"
            shift 2
            ;;
        --commit)
            TARGET_COMMIT="$2"
            shift 2
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            echo "Unknown option: $1"
            usage
            ;;
    esac
done

###############################################################################
# Validation
###############################################################################

validate_prerequisites() {
    log_section "Validating Prerequisites"

    # Check required environment variables
    if [[ -z "$TERMINUS_TOKEN" ]]; then
        log_error "TERMINUS_TOKEN is not set"
        exit 2
    fi

    if [[ -z "$TERMINUS_SITE" ]]; then
        log_error "TERMINUS_SITE is not set"
        exit 2
    fi

    # Check required commands
    local required_commands=("terminus" "git")
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            log_error "Required command not found: $cmd"
            exit 2
        fi
    done

    # Validate environment argument
    if [[ -z "$ENVIRONMENT" ]]; then
        log_error "Environment not specified. Use --environment <env>"
        exit 2
    fi

    if [[ ! "$ENVIRONMENT" =~ ^(dev|test|live)$ ]] && [[ ! "$ENVIRONMENT" =~ ^pr-[0-9]+$ ]]; then
        log_error "Invalid environment: $ENVIRONMENT (must be dev, test, live, or pr-XXX)"
        exit 2
    fi

    log_info "All prerequisites validated"
}

###############################################################################
# Rollback Functions
###############################################################################

get_current_commit() {
    log_step "Getting current deployment commit..."

    local current_commit
    current_commit=$(terminus env:code-log ${TERMINUS_SITE}.${ENVIRONMENT} --format=json | jq -r '.[0].hash' 2>/dev/null || echo "")

    if [[ -z "$current_commit" ]]; then
        log_error "Failed to get current commit"
        exit 1
    fi

    echo "$current_commit"
}

get_previous_commit() {
    log_step "Getting previous deployment commit..."

    local previous_commit
    previous_commit=$(terminus env:code-log ${TERMINUS_SITE}.${ENVIRONMENT} --format=json | jq -r '.[1].hash' 2>/dev/null || echo "")

    if [[ -z "$previous_commit" ]]; then
        log_error "Failed to get previous commit"
        exit 1
    fi

    echo "$previous_commit"
}

create_backup() {
    log_section "Creating Backup"

    if [[ "$DRY_RUN" == true ]]; then
        log_info "[DRY RUN] Would create backup of ${ENVIRONMENT} environment"
        return 0
    fi

    log_step "Creating database backup..."
    terminus backup:create ${TERMINUS_SITE}.${ENVIRONMENT} --element=db --keep-for=30

    log_step "Creating files backup..."
    terminus backup:create ${TERMINUS_SITE}.${ENVIRONMENT} --element=files --keep-for=30

    log_info "Backups created successfully"
}

perform_rollback() {
    local target="$1"

    log_section "Performing Rollback"

    if [[ "$DRY_RUN" == true ]]; then
        log_info "[DRY RUN] Would rollback ${ENVIRONMENT} to commit: ${target}"
        log_info "[DRY RUN] Would deploy code from commit: ${target}"
        log_info "[DRY RUN] Would clear caches"
        return 0
    fi

    log_step "Rolling back to commit: ${target}"

    # Deploy the target commit
    if terminus env:deploy ${TERMINUS_SITE}.${ENVIRONMENT} --cc --sync-content --updatedb --note="Automated rollback to ${target}" 2>&1 | grep -q "Deployed"; then
        log_info "Code rolled back successfully"
    else
        log_error "Failed to rollback code"
        exit 1
    fi

    # Clear all caches
    log_step "Clearing caches..."
    terminus env:clear-cache ${TERMINUS_SITE}.${ENVIRONMENT} || log_warning "Cache clear had warnings"

    log_info "Rollback completed"
}

verify_rollback() {
    log_section "Verifying Rollback"

    if [[ "$DRY_RUN" == true ]]; then
        log_info "[DRY RUN] Would verify rollback success"
        return 0
    fi

    # Get environment URL
    local env_url
    env_url=$(terminus env:info ${TERMINUS_SITE}.${ENVIRONMENT} --field=domain 2>/dev/null || echo "")

    if [[ -z "$env_url" ]]; then
        log_warning "Could not determine environment URL for verification"
        return 1
    fi

    env_url="https://${env_url}"

    # Basic health check
    log_step "Checking site accessibility..."
    if curl -s -o /dev/null -w "%{http_code}" --max-time 30 "$env_url" | grep -q "200"; then
        log_info "Site is accessible at $env_url"
    else
        log_error "Site is not accessible after rollback"
        return 1
    fi

    # Check plugin status
    log_step "Checking plugin status..."
    local plugin_status
    plugin_status=$(terminus wp ${TERMINUS_SITE}.${ENVIRONMENT} -- plugin list --name=semantic-knowledge --field=status 2>&1 || echo "error")

    if [[ "$plugin_status" == "active" ]]; then
        log_info "Plugin is active after rollback"
    else
        log_warning "Plugin status: $plugin_status"
    fi

    log_info "Rollback verification completed"
}

send_notification() {
    local status="$1"
    local target_commit="$2"

    log_section "Sending Notifications"

    if [[ "$DRY_RUN" == true ]]; then
        log_info "[DRY RUN] Would send notifications about rollback"
        return 0
    fi

    # Prepare notification message
    local message
    if [[ "$status" == "success" ]]; then
        message="✅ Rollback completed successfully\n\nEnvironment: ${ENVIRONMENT}\nRolled back to: ${target_commit}\nCircleCI Build: ${CIRCLE_BUILD_URL:-N/A}"
    else
        message="❌ Rollback failed\n\nEnvironment: ${ENVIRONMENT}\nTarget commit: ${target_commit}\nCircleCI Build: ${CIRCLE_BUILD_URL:-N/A}\n\nPlease investigate immediately."
    fi

    # Send to Slack if configured
    if [[ -n "$SLACK_WEBHOOK_URL" ]]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"${message}\"}" \
            "$SLACK_WEBHOOK_URL" > /dev/null 2>&1 || log_warning "Failed to send Slack notification"
        log_info "Slack notification sent"
    fi

    # Log to Pantheon workflow
    terminus workflow:list ${TERMINUS_SITE}.${ENVIRONMENT} --format=table 2>&1 || true

    log_info "Notifications sent"
}

###############################################################################
# Main Execution
###############################################################################

main() {
    echo "=================================================="
    echo "Automated Rollback Script"
    echo "Semantic Knowledge Plugin"
    echo "=================================================="
    echo "Environment: ${ENVIRONMENT}"
    echo "Dry Run: ${DRY_RUN}"
    echo "Force: ${FORCE}"
    echo "=================================================="
    echo ""

    # Validate prerequisites
    validate_prerequisites

    # Authenticate with Terminus
    log_step "Authenticating with Terminus..."
    terminus auth:login --machine-token=${TERMINUS_TOKEN} > /dev/null 2>&1

    # Get current and previous commits
    CURRENT_COMMIT=$(get_current_commit)
    log_info "Current commit: ${CURRENT_COMMIT}"

    # Determine target commit
    if [[ -n "$TARGET_COMMIT" ]]; then
        ROLLBACK_TARGET="$TARGET_COMMIT"
        log_info "Target commit (specified): ${ROLLBACK_TARGET}"
    else
        ROLLBACK_TARGET=$(get_previous_commit)
        log_info "Target commit (previous): ${ROLLBACK_TARGET}"
    fi

    # Confirmation
    if [[ "$FORCE" != true && "$DRY_RUN" != true ]]; then
        echo ""
        log_warning "You are about to rollback ${ENVIRONMENT} environment"
        log_warning "Current: ${CURRENT_COMMIT}"
        log_warning "Target:  ${ROLLBACK_TARGET}"
        echo ""
        read -p "Are you sure you want to proceed? (yes/no): " -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
            echo "Rollback cancelled by user"
            exit 0
        fi
    fi

    # Execute rollback steps
    create_backup
    perform_rollback "$ROLLBACK_TARGET"
    verify_rollback

    # Determine success
    if [[ $? -eq 0 ]]; then
        send_notification "success" "$ROLLBACK_TARGET"
        echo ""
        log_section "Rollback Completed Successfully"
        echo "Environment ${ENVIRONMENT} has been rolled back to commit: ${ROLLBACK_TARGET}"
        echo "Previous commit: ${CURRENT_COMMIT}"
        echo ""
        log_info "Please verify the site is functioning correctly"
        exit 0
    else
        send_notification "failure" "$ROLLBACK_TARGET"
        echo ""
        log_section "Rollback Failed"
        log_error "Please investigate and perform manual rollback if necessary"
        exit 1
    fi
}

# Run main function
main "$@"
