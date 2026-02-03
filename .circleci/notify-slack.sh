#!/bin/bash
#
# Slack Notification Script
#
# Description:
#   Sends notifications to Slack with dynamic payload generation for various event types.
#
# Usage:
#   notify-slack.sh --type TYPE --channel CHANNEL [OPTIONS]
#
# Options:
#   --type TYPE                Notification type: deployment|indexer-success|indexer-error
#   --channel CHANNEL          Slack channel ID
#   --url URL                  Environment URL (required for all types)
#   --webhook-url URL          Slack webhook URL (default: $SLACK_WEBHOOK)
#   --posts COUNT              Number of posts indexed (indexer-success only)
#   --duration TIME            Indexer duration (indexer-success only)
#   --errors COUNT             Number of errors (indexer-success only)
#   --repo NAME                Repository name (default: $CIRCLE_PROJECT_REPONAME)
#   --branch NAME              Branch name (default: $CIRCLE_BRANCH)
#   --tag NAME                 Tag name (default: $CIRCLE_TAG)
#   --build-url URL            CircleCI build URL (default: $CIRCLE_BUILD_URL)
#   --dry-run                  Show payload without sending
#   --help                     Show this help message
#
# Required Environment Variables:
#   SLACK_WEBHOOK             Slack webhook URL (or use --webhook-url)
#
# Optional Environment Variables (auto-detected in CircleCI):
#   CIRCLE_PROJECT_REPONAME   Repository name
#   CIRCLE_BRANCH             Branch name
#   CIRCLE_TAG                Tag name
#   CIRCLE_BUILD_URL          Build URL
#
# Exit Codes:
#   0 - Notification sent successfully
#   1 - Failed to send notification
#   2 - Invalid arguments or webhook not configured
#

set -eo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
TYPE=""
CHANNEL=""
URL=""
WEBHOOK_URL="${SLACK_WEBHOOK:-}"
POSTS="N/A"
DURATION="N/A"
ERRORS="0"
REPO="${CIRCLE_PROJECT_REPONAME:-unknown}"
BRANCH="${CIRCLE_BRANCH:-unknown}"
TAG="${CIRCLE_TAG:-}"
BUILD_URL="${CIRCLE_BUILD_URL:-#}"
DRY_RUN="false"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --type)
            TYPE="$2"
            shift 2
            ;;
        --channel)
            CHANNEL="$2"
            shift 2
            ;;
        --url)
            URL="$2"
            shift 2
            ;;
        --webhook-url)
            WEBHOOK_URL="$2"
            shift 2
            ;;
        --posts)
            POSTS="$2"
            shift 2
            ;;
        --duration)
            DURATION="$2"
            shift 2
            ;;
        --errors)
            ERRORS="$2"
            shift 2
            ;;
        --repo)
            REPO="$2"
            shift 2
            ;;
        --branch)
            BRANCH="$2"
            shift 2
            ;;
        --tag)
            TAG="$2"
            shift 2
            ;;
        --build-url)
            BUILD_URL="$2"
            shift 2
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
log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Validate required parameters
if [[ -z "$TYPE" ]]; then
    log_error "Notification type is required (--type)"
    exit 2
fi

if [[ -z "$CHANNEL" ]]; then
    log_error "Slack channel is required (--channel)"
    exit 2
fi

if [[ -z "$URL" ]] && [[ "$TYPE" != "deployment" ]]; then
    log_error "Environment URL is required (--url)"
    exit 2
fi

if [[ -z "$WEBHOOK_URL" ]]; then
    log_error "Slack webhook URL is not configured (set SLACK_WEBHOOK or use --webhook-url)"
    exit 2
fi

# Generate Slack payload based on type
generate_deployment_payload() {
    cat <<EOF
{
  "channel": "${CHANNEL}",
  "blocks": [
    {
      "type": "header",
      "text": {
        "type": "plain_text",
        "text": "Deployment Successful 🎉",
        "emoji": true
      }
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "${URL}"
      }
    },
    {
      "type": "divider"
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Repo:* ${REPO}\\n*Branch/Release:* ${BRANCH} ${TAG}"
      },
      "accessory": {
        "type": "button",
        "text": {
          "type": "plain_text",
          "emoji": true,
          "text": "View Job"
        },
        "value": "click_view_job",
        "url": "${BUILD_URL}",
        "action_id": "button-action"
      }
    }
  ]
}
EOF
}

generate_indexer_success_payload() {
    cat <<EOF
{
  "channel": "${CHANNEL}",
  "blocks": [
    {
      "type": "header",
      "text": {
        "type": "plain_text",
        "text": "AI Indexer Completed Successfully ✅",
        "emoji": true
      }
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Environment:* ${URL}\\n*Posts Indexed:* ${POSTS}\\n*Duration:* ${DURATION}\\n*Errors:* ${ERRORS}"
      }
    },
    {
      "type": "divider"
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Repo:* ${REPO}\\n*Branch:* ${BRANCH}"
      },
      "accessory": {
        "type": "button",
        "text": {
          "type": "plain_text",
          "emoji": true,
          "text": "View Job"
        },
        "value": "click_view_job",
        "url": "${BUILD_URL}",
        "action_id": "button-action"
      }
    }
  ]
}
EOF
}

generate_indexer_error_payload() {
    cat <<EOF
{
  "channel": "${CHANNEL}",
  "blocks": [
    {
      "type": "header",
      "text": {
        "type": "plain_text",
        "text": "AI Indexer Failed ❌",
        "emoji": true
      }
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "⚠️ The AI indexer failed to complete successfully.\\n\\n*Environment:* ${URL}\\n*Branch:* ${BRANCH}"
      }
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Common causes:*\\n• Missing Pantheon Secrets (OPENAI_API_KEY, PINECONE_API_KEY)\\n• WordPress plugin not activated\\n• Pinecone index not configured\\n• API authentication issues"
      }
    },
    {
      "type": "divider"
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Repo:* ${REPO}"
      },
      "accessory": {
        "type": "button",
        "text": {
          "type": "plain_text",
          "emoji": true,
          "text": "View Job"
        },
        "value": "click_view_job",
        "url": "${BUILD_URL}",
        "action_id": "button-action"
      }
    }
  ]
}
EOF
}

# Generate appropriate payload
log_info "Generating ${TYPE} notification payload..."

case "$TYPE" in
    deployment)
        PAYLOAD=$(generate_deployment_payload)
        ;;
    indexer-success)
        PAYLOAD=$(generate_indexer_success_payload)
        ;;
    indexer-error)
        PAYLOAD=$(generate_indexer_error_payload)
        ;;
    *)
        log_error "Unknown notification type: $TYPE"
        echo "Valid types: deployment, indexer-success, indexer-error"
        exit 2
        ;;
esac

if [[ "$DRY_RUN" == "true" ]]; then
    log_info "Dry run mode - would send the following payload:"
    echo "$PAYLOAD" | jq .
    exit 0
fi

# Send notification to Slack
log_info "Sending notification to Slack channel: $CHANNEL"

RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
    -H 'Content-Type: application/json' \
    --data "$PAYLOAD" \
    "$WEBHOOK_URL")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [[ "$HTTP_CODE" == "200" ]] && [[ "$BODY" == "ok" ]]; then
    log_success "Notification sent successfully"
    exit 0
else
    log_error "Failed to send notification (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
    exit 1
fi
