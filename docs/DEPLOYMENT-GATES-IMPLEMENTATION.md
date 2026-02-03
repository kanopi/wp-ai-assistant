# Deployment Gates Implementation Guide

**Date:** January 28, 2026
**Purpose:** Add production deployment gates and automated health checks to CircleCI workflow

---

## Overview

This guide provides the exact CircleCI configuration changes needed to implement:
1. Manual approval gates for production deployments
2. Automated post-deployment health checks
3. Automated rollback on health check failures
4. Deployment notifications with status

## Prerequisites

- CircleCI project configured
- Terminus CLI access configured
- Slack webhook URL (optional, for notifications)
- Health check and rollback scripts already created at:
  - `.circleci/scripts/health-check.sh`
  - `.circleci/scripts/rollback.sh`

---

## Step 1: Add New CircleCI Jobs

Add these three new jobs to the `jobs:` section of your `.circleci/config.yml`:

### Job 1: Manual Approval Gate

```yaml
  # Manual approval gate for production deployments
  request-production-approval:
    docker:
      - image: cimg/base:stable
    steps:
      - run:
          name: Request Production Deployment Approval
          command: |
            echo "This deployment requires manual approval"
            echo "Environment: ${CIRCLE_BRANCH}"
            echo "Commit: ${CIRCLE_SHA1}"
            echo ""
            echo "Please review the following before approving:"
            echo "1. All tests have passed"
            echo "2. Code review is complete"
            echo "3. Security audits show no critical issues"
            echo "4. Stakeholders have signed off"
      - run:
          name: Wait for Approval
          command: |
            echo "Waiting for manual approval in CircleCI UI..."
            echo "Click 'Approve' in the CircleCI workflow to continue"
```

### Job 2: Post-Deployment Health Check

```yaml
  # Post-deployment health checks
  health-check:
    <<: *DEPLOYMENT_DEFAULTS
    steps:
      - checkout
      - ci-tools/copy-ssh-key
      - ci-tools/set-pantheon-url:
          site-id: *TERMINUS_SITE
          store-as: ENV_URL
      - run:
          name: Authenticate Terminus
          command: |
            terminus auth:login --machine-token=${TERMINUS_TOKEN}
      - run:
          name: Run Health Checks
          command: |
            chmod +x .circleci/scripts/health-check.sh
            .circleci/scripts/health-check.sh
          no_output_timeout: 5m
      - store_artifacts:
          path: /tmp/health-check-results.log
          destination: health-check-results
      - slack/notify:
          channel: *SLACK_CHANNEL
          event: fail
          custom: |
            {
              "blocks": [
                {
                  "type": "header",
                  "text": {
                    "type": "plain_text",
                    "text": "Health Check Failed ❌",
                    "emoji": true
                  }
                },
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "Post-deployment health checks failed.\n\n*Environment:* ${ENV_URL}\n*Branch:* ${CIRCLE_BRANCH}\n*Commit:* ${CIRCLE_SHA1:0:7}"
                  }
                },
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "⚠️ *Action Required:* Automated rollback will be initiated"
                  },
                  "accessory": {
                    "type": "button",
                    "text": {
                      "type": "plain_text",
                      "emoji": true,
                      "text": "View Job"
                    },
                    "value": "click_view_job",
                    "url": "${CIRCLE_BUILD_URL}",
                    "action_id": "button-action"
                  }
                }
              ]
            }
      - slack/notify:
          channel: *SLACK_CHANNEL
          event: pass
          custom: |
            {
              "blocks": [
                {
                  "type": "header",
                  "text": {
                    "type": "plain_text",
                    "text": "Health Check Passed ✅",
                    "emoji": true
                  }
                },
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "All post-deployment health checks passed successfully.\n\n*Environment:* ${ENV_URL}\n*Branch:* ${CIRCLE_BRANCH}\n*Commit:* ${CIRCLE_SHA1:0:7}"
                  },
                  "accessory": {
                    "type": "button",
                    "text": {
                      "type": "plain_text",
                      "emoji": true,
                      "text": "View Site"
                    },
                    "value": "click_view_site",
                    "url": "${ENV_URL}",
                    "action_id": "button-action"
                  }
                }
              ]
            }
```

### Job 3: Automated Rollback

```yaml
  # Automated rollback on health check failure
  automated-rollback:
    <<: *DEPLOYMENT_DEFAULTS
    steps:
      - checkout
      - ci-tools/copy-ssh-key
      - ci-tools/set-pantheon-url:
          site-id: *TERMINUS_SITE
          store-as: ENV_URL
      - run:
          name: Authenticate Terminus
          command: |
            terminus auth:login --machine-token=${TERMINUS_TOKEN}
      - run:
          name: Determine Environment
          command: |
            if [[ "${CIRCLE_BRANCH}" == "main" ]]; then
              echo "export ROLLBACK_ENV=dev" >> $BASH_ENV
            else
              MULTIDEV_ENV=$(echo ${ENV_URL} | sed -n "s|https://\(.*\)-${TERMINUS_SITE}\.pantheonsite\.io.*|\1|p")
              echo "export ROLLBACK_ENV=${MULTIDEV_ENV}" >> $BASH_ENV
            fi
      - run:
          name: Execute Automated Rollback
          command: |
            chmod +x .circleci/scripts/rollback.sh
            .circleci/scripts/rollback.sh --environment ${ROLLBACK_ENV} --force
          no_output_timeout: 10m
      - run:
          name: Verify Rollback
          command: |
            echo "Rollback completed. Running verification health check..."
            chmod +x .circleci/scripts/health-check.sh
            .circleci/scripts/health-check.sh || echo "Rollback verification showed issues - manual intervention required"
      - store_artifacts:
          path: /tmp/rollback-log.txt
          destination: rollback-log
      - slack/notify:
          channel: *SLACK_CHANNEL
          event: always
          custom: |
            {
              "blocks": [
                {
                  "type": "header",
                  "text": {
                    "type": "plain_text",
                    "text": "Automated Rollback Executed 🔄",
                    "emoji": true
                  }
                },
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "Automated rollback was triggered due to health check failure.\n\n*Environment:* ${ROLLBACK_ENV}\n*Branch:* ${CIRCLE_BRANCH}\n*Previous Commit:* ${CIRCLE_SHA1:0:7}"
                  }
                },
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "⚠️ *Action Required:*\n• Investigate the failed deployment\n• Review health check logs\n• Fix issues before redeploying"
                  },
                  "accessory": {
                    "type": "button",
                    "text": {
                      "type": "plain_text",
                      "emoji": true,
                      "text": "View Logs"
                    },
                    "value": "click_view_job",
                    "url": "${CIRCLE_BUILD_URL}",
                    "action_id": "button-action"
                  }
                }
              ]
            }
```

---

## Step 2: Update Existing Workflows

### For Development/Staging Deployments (No Approval Required)

Add health checks after deployment in the `build` workflow. Modify the existing workflow to add the health check job:

```yaml
workflows:
  version: 2
  build:
    when: *IS_BUILD
    jobs:
      # ... existing jobs ...
      - compile-theme:
          context: kanopi-code
          filters:
            tags:
              only: /.*/
      - pantheon-deploy:
          context: kanopi-code
          requires:
            - compile-theme
      # NEW: Add health check after deployment
      - health-check:
          context: kanopi-code
          requires:
            - pantheon-deploy
      # NEW: Add automated rollback if health check fails
      - automated-rollback:
          context: kanopi-code
          requires:
            - health-check
          filters:
            branches:
              only: /^(main|pr-.*)$/
          # Only run if health check failed
          when:
            condition:
              not:
                equal: [ success, << pipeline.status >> ]
```

### For Production Deployments (With Approval Gate)

Create a new workflow for production deployments that includes manual approval:

```yaml
  # NEW WORKFLOW: Production deployment with approval gate
  production-deploy:
    when:
      and:
        - equal: ["deploy-production", << pipeline.parameters.stage >>]
        - equal: ["main", << pipeline.git.branch >>]
    jobs:
      # Step 1: Run all tests and security audits
      - test-indexer:
          context: kanopi-code
      - test-wp-plugin:
          context: kanopi-code
      - security-audit-npm:
          context: kanopi-code
      - security-audit-composer:
          context: kanopi-code

      # Step 2: Compile assets
      - compile-theme:
          context: kanopi-code
          requires:
            - test-indexer
            - test-wp-plugin
            - security-audit-npm
            - security-audit-composer

      # Step 3: Request manual approval
      - request-production-approval:
          type: approval
          requires:
            - compile-theme

      # Step 4: Deploy to production
      - pantheon-deploy:
          context: kanopi-code
          requires:
            - request-production-approval
          filters:
            branches:
              only: main

      # Step 5: Run health checks
      - health-check:
          context: kanopi-code
          requires:
            - pantheon-deploy

      # Step 6: Automated rollback if health checks fail
      - automated-rollback:
          context: kanopi-code
          requires:
            - health-check
          # Only run if health check failed
          when:
            condition:
              not:
                equal: [ success, << pipeline.status >> ]

      # Step 7: Run indexer after successful deployment
      - run-indexer:
          context: kanopi-code
          requires:
            - health-check
```

---

## Step 3: Add Pipeline Parameter

Add a new pipeline parameter to enable production deployment workflow:

```yaml
# At the top of config.yml, update the parameters section:
parameters:
  stage:
    type: string
    default: "build"
  deploy-production:
    type: boolean
    default: false
```

---

## Step 4: Configure Environment Variables

Ensure these environment variables are set in CircleCI project settings:

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `TERMINUS_TOKEN` | Terminus authentication token | `abc123...` |
| `TERMINUS_SITE` | Pantheon site name | `kanopi-2019` |
| `SLACK_WEBHOOK_URL` | Slack webhook for notifications (optional) | `https://hooks.slack.com/...` |

### Variables Set by Workflow

| Variable | Set By | Description |
|----------|--------|-------------|
| `ENV_URL` | `ci-tools/set-pantheon-url` | Deployed environment URL |
| `ROLLBACK_ENV` | Bash script | Environment name for rollback |

---

## Step 5: Test the Implementation

### Test Health Checks

1. **Trigger a deployment to development:**
   ```bash
   git push origin feature/test-deployment-gates
   ```

2. **Monitor CircleCI:**
   - Watch the `pantheon-deploy` job complete
   - Verify `health-check` job runs automatically
   - Check that all health checks pass

3. **Review health check output:**
   - Navigate to the `health-check` job in CircleCI
   - Review each health check result
   - Download health check artifacts

### Test Manual Approval Gate (Production)

1. **Trigger production deployment:**
   ```bash
   # In CircleCI UI, manually trigger the production-deploy workflow
   # OR use CircleCI API
   curl -X POST \
     -H "Circle-Token: ${CIRCLE_TOKEN}" \
     -H "Content-Type: application/json" \
     -d '{
       "parameters": {
         "stage": "deploy-production"
       }
     }' \
     https://circleci.com/api/v2/project/github/YOUR_ORG/YOUR_REPO/pipeline
   ```

2. **Approve deployment:**
   - Navigate to CircleCI workflow
   - Review the `request-production-approval` step
   - Click "Approve" to proceed

3. **Verify deployment gates:**
   - Confirm deployment proceeds only after approval
   - Verify health checks run after deployment
   - Check Slack notifications

### Test Automated Rollback

1. **Simulate health check failure:**
   - Temporarily modify `.circleci/scripts/health-check.sh`
   - Force a health check to fail (e.g., return exit code 1)
   - Push changes and trigger deployment

2. **Verify rollback:**
   - Confirm `automated-rollback` job is triggered
   - Verify rollback script executes
   - Check that previous deployment is restored
   - Verify Slack notification sent

3. **Verify site after rollback:**
   - Check site accessibility
   - Verify plugin is functioning
   - Review rollback logs in CircleCI artifacts

---

## Step 6: Monitoring and Maintenance

### Daily Monitoring

- Review CircleCI workflows daily
- Monitor Slack notifications for deployment status
- Check health check artifacts for warnings

### Weekly Review

- Review deployment gate effectiveness
- Analyze health check pass/fail rates
- Update health check thresholds if needed

### Monthly Audit

- Review all rollback incidents
- Update health check criteria based on learnings
- Refine approval process based on feedback

---

## Rollback Scenarios

### Scenario 1: Health Check Failure

**Automatic Response:**
1. Health check job fails
2. `automated-rollback` job triggered automatically
3. Previous deployment restored
4. Slack notification sent
5. Manual investigation required

**Manual Steps:**
1. Review health check logs
2. Identify root cause
3. Fix issues in code
4. Redeploy with fixes

### Scenario 2: Manual Rollback Required

**When to Use:**
- Critical bug discovered post-deployment
- Performance degradation detected
- Security vulnerability found

**Steps:**
```bash
# Option 1: Using CircleCI job
# Manually trigger automated-rollback job in CircleCI UI

# Option 2: Using rollback script directly
cd /path/to/project
./.circleci/scripts/rollback.sh \
  --environment dev \
  --force

# Option 3: Using Terminus directly
terminus env:deploy kanopi-2019.dev \
  --sync-content \
  --cc \
  --note="Emergency rollback"
```

### Scenario 3: Rollback to Specific Commit

```bash
./.circleci/scripts/rollback.sh \
  --environment live \
  --commit abc123def \
  --force
```

---

## Troubleshooting

### Health Check Fails Immediately

**Symptoms:**
- `health-check` job fails in less than 30 seconds
- Error: "Site not reachable"

**Solutions:**
1. Verify `ENV_URL` is set correctly
2. Check Pantheon environment is awake
3. Verify DNS and SSL are configured
4. Increase retry count in health check script

### Rollback Script Fails

**Symptoms:**
- `automated-rollback` job fails
- Error: "Failed to get previous commit"

**Solutions:**
1. Verify Terminus authentication
2. Check `TERMINUS_SITE` variable is correct
3. Verify sufficient deployment history exists
4. Review Terminus workflow logs

### Approval Gate Not Showing

**Symptoms:**
- Production workflow doesn't pause for approval
- No approval button in CircleCI UI

**Solutions:**
1. Verify `type: approval` is set on job
2. Check workflow conditionals are correct
3. Verify pipeline parameters are set
4. Review CircleCI project settings

### Notifications Not Sending

**Symptoms:**
- No Slack notifications received
- Health checks pass/fail but no alerts

**Solutions:**
1. Verify `SLACK_WEBHOOK_URL` is set
2. Check Slack webhook is valid
3. Test webhook with curl
4. Review CircleCI Slack orb configuration

---

## Best Practices

### Approval Process

1. **Before Approving:**
   - Review all test results
   - Check security audit reports
   - Verify code review is complete
   - Confirm stakeholder sign-off

2. **Documentation:**
   - Document approval decisions
   - Note any concerns or risks
   - Track who approved and when

3. **Communication:**
   - Notify team of pending production deployment
   - Set expectations for deployment window
   - Have rollback plan ready

### Health Check Criteria

1. **Add Checks Incrementally:**
   - Start with basic checks
   - Add more as system matures
   - Remove redundant checks

2. **Set Appropriate Thresholds:**
   - Balance sensitivity vs noise
   - Account for cold starts
   - Consider time of day variations

3. **Regular Review:**
   - Analyze false positive rate
   - Update checks based on incidents
   - Add new checks for new features

### Rollback Strategy

1. **Automate When Possible:**
   - Use automated rollback for health check failures
   - Keep manual rollback option available
   - Test rollback procedures regularly

2. **Communication:**
   - Always notify team of rollbacks
   - Document reason for rollback
   - Schedule post-mortem for major rollbacks

3. **Prevention:**
   - Learn from each rollback
   - Update deployment checklist
   - Improve testing coverage

---

## Additional Resources

- [CircleCI Approval Jobs Documentation](https://circleci.com/docs/workflows/#holding-a-workflow-for-a-manual-approval)
- [Pantheon Deployment Best Practices](https://pantheon.io/docs/guides/deployment)
- [Terminus CLI Reference](https://pantheon.io/docs/terminus)
- [DEPLOYMENT.md](./DEPLOYMENT.md) - Main deployment guide
- [RUNBOOK.md](./RUNBOOK.md) - Operations runbook
- [INCIDENT-RESPONSE.md](./INCIDENT-RESPONSE.md) - Incident response procedures

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-28 | Initial implementation guide created |

---

**Implementation Status:** ⏳ Pending Implementation
**Next Steps:** Follow Step 1 to add jobs to CircleCI configuration

