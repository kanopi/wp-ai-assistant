# WP AI Assistant - Documentation

Welcome to the WP AI Assistant documentation. This directory contains comprehensive guides and references for customizing and extending the plugin.

## Documentation Index

### Getting Started

- **[Main README](../README.md)** - Plugin overview, installation, and basic usage

### Customization Guides

- **[CUSTOMIZATION.md](CUSTOMIZATION.md)** - Complete guide to customizing content preferences
  - No-code customization via system prompts
  - Industry-specific examples (E-commerce, SaaS, Healthcare, Legal, etc.)
  - Migration guide from hardcoded logic
  - Tips, best practices, and troubleshooting

### Technical Reference

- **[HOOKS.md](HOOKS.md)** - Complete filter and action reference
  - 40+ documented hooks
  - Search module hooks (25+ filters and actions)
  - Chatbot module hooks (12+ filters and actions)
  - Indexer hooks
  - Code examples and best practices

### Code Examples

- **[examples/](../examples/)** - Practical code examples
  - `content-preferences-examples.md` - Ready-to-use system prompt templates
  - `custom-post-type-boost.php` - Advanced relevance scoring
  - `analytics-tracking.php` - Search analytics and monitoring
  - `performance-optimization.php` - Caching and optimization
  - `custom-search-summary.php` - AI summary customization

### Operations Documentation

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Complete deployment procedures
  - Pre-deployment checklist
  - Deployment workflows for dev/staging/production
  - CircleCI automated deployment
  - Post-deployment verification
  - Rollback procedures
  - Zero-downtime deployment strategies

- **[RUNBOOK.md](RUNBOOK.md)** - Operations runbook for routine tasks
  - Daily operations checklist
  - Weekly maintenance procedures
  - Monthly review tasks
  - Monitoring and alerting
  - Log and cache management
  - Database maintenance
  - Performance monitoring
  - API key rotation
  - Backup procedures

- **[INCIDENT-RESPONSE.md](INCIDENT-RESPONSE.md)** - Incident response guide
  - Incident classification (P1-P4)
  - On-call procedures
  - Incident response workflow
  - Common incident scenarios and resolutions
  - Escalation procedures
  - Post-incident review process
  - Communication templates

- **[DISASTER-RECOVERY.md](DISASTER-RECOVERY.md)** - Disaster recovery plan
  - Recovery objectives (RTO/RPO)
  - Backup strategy and verification
  - Recovery procedures for all components
  - Service restoration priorities
  - Disaster scenario playbooks
  - Business continuity planning
  - DR testing procedures

### Other Documentation

- **[THEME-INTEGRATION.md](../THEME-INTEGRATION.md)** - Theme integration guide
- **[CIRCLECI.md](../CIRCLECI.md)** - CI/CD configuration

## Quick Links

### For Non-Developers

**Want to customize content priorities without code?**
→ See [CUSTOMIZATION.md](CUSTOMIZATION.md)

### For Developers

**Need to add custom logic or integrate with other plugins?**
→ See [HOOKS.md](HOOKS.md) and [examples/](../examples/)

**Looking for specific examples?**
- Custom post types: [examples/custom-post-type-boost.php](../examples/custom-post-type-boost.php)
- Analytics: [examples/analytics-tracking.php](../examples/analytics-tracking.php)
- Performance: [examples/performance-optimization.php](../examples/performance-optimization.php)
- AI summaries: [examples/custom-search-summary.php](../examples/custom-search-summary.php)

### For Operations/DevOps

**Deploying to production?**
→ See [DEPLOYMENT.md](DEPLOYMENT.md)

**Need to handle an incident?**
→ See [INCIDENT-RESPONSE.md](INCIDENT-RESPONSE.md)

**Daily maintenance tasks?**
→ See [RUNBOOK.md](RUNBOOK.md)

**Disaster recovery?**
→ See [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md)

## Customization Philosophy

The WP AI Assistant plugin follows a **progressive enhancement** approach to customization:

### Level 1: System Prompt (No Code)
→ Perfect for content prioritization and tone adjustments
→ See [CUSTOMIZATION.md](CUSTOMIZATION.md)

### Level 2: Settings UI (No Code)
→ Fine-tune algorithmic boosts via Settings > AI Assistant
→ See main [README.md](../README.md)

### Level 3: Filters & Actions (PHP Code)
→ Advanced customizations and integrations
→ See [HOOKS.md](HOOKS.md) and [examples/](../examples/)

## Support

- **GitHub Issues**: Report bugs or request features
- **Documentation**: Check this docs folder first
- **Examples**: Review code examples for common patterns

## Contributing

Improvements to documentation are welcome! If you've created a useful customization or found a better way to explain something, please contribute back to the project.
