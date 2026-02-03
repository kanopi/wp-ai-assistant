# Load Testing Guide for WP AI Assistant

**Version:** 1.0
**Date:** January 28, 2026
**Purpose:** Guide for conducting load tests before production deployment

---

## Overview

This guide provides comprehensive instructions for load testing the WP AI Assistant plugin to ensure it can handle expected traffic volumes while maintaining acceptable performance.

---

## Test Objectives

1. **Verify performance under normal load** (50 concurrent users)
2. **Validate behavior under peak load** (100 concurrent users)
3. **Assess response to traffic spikes** (0 → 200 → 0 users)
4. **Confirm sustained performance** (30 users for 2 hours)
5. **Identify bottlenecks** and performance limits
6. **Validate auto-scaling** (if configured)
7. **Test graceful degradation** under extreme load

---

## Prerequisites

### Tools Required

1. **k6** (recommended) or **Apache JMeter**
2. **Access to staging environment** (matching production config)
3. **Monitoring tools** (New Relic, Datadog, or similar)
4. **Terminus CLI** for Pantheon interaction
5. **Git** for version control of test scripts

### Environment Preparation

- [ ] Staging environment deployed and stable
- [ ] API keys configured (OpenAI, Pinecone)
- [ ] Content indexed (at least 100 posts/pages)
- [ ] Monitoring enabled and dashboards ready
- [ ] Caching configured (Redis, Varnish)
- [ ] Rate limiting configured appropriately
- [ ] Team notified of load testing schedule

---

## Test Scenarios

### Scenario 1: Normal Load

**Objective:** Verify acceptable performance under typical traffic

**Parameters:**
- **Virtual Users:** 50 concurrent
- **Duration:** 30 minutes
- **Ramp-up:** 5 minutes
- **Steady State:** 20 minutes
- **Ramp-down:** 5 minutes
- **Traffic Mix:**
  - 70% chatbot requests
  - 30% search requests
- **Think Time:** 10-30 seconds between requests

**Success Criteria:**
- Average response time < 5 seconds
- P95 response time < 8 seconds
- P99 response time < 12 seconds
- Error rate < 1%
- No crashes or outages
- CPU usage < 70%
- Memory usage stable

### Scenario 2: Peak Load

**Objective:** Validate performance under peak traffic conditions

**Parameters:**
- **Virtual Users:** 100 concurrent
- **Duration:** 15 minutes
- **Ramp-up:** 3 minutes
- **Steady State:** 10 minutes
- **Ramp-down:** 2 minutes
- **Traffic Mix:**
  - 70% chatbot requests
  - 30% search requests
- **Think Time:** 5-15 seconds between requests

**Success Criteria:**
- Average response time < 8 seconds
- P95 response time < 12 seconds
- P99 response time < 20 seconds
- Error rate < 2%
- Graceful degradation (no crashes)
- CPU usage < 85%
- Memory usage stable

### Scenario 3: Spike Load

**Objective:** Test system behavior during sudden traffic spikes

**Parameters:**
- **Virtual Users:** 0 → 200 → 50 → 0
- **Duration:** 10 minutes total
  - Spike to 200 users in 1 minute
  - Hold for 3 minutes
  - Drop to 50 users
  - Hold for 4 minutes
  - Drop to 0
- **Traffic Mix:**
  - 50% chatbot requests
  - 50% search requests
- **Think Time:** 2-10 seconds

**Success Criteria:**
- System remains responsive during spike
- Auto-recovery after spike
- Error rate < 5% during spike
- No data loss or corruption
- Queuing mechanisms work correctly
- Rate limiting prevents abuse

### Scenario 4: Sustained Load

**Objective:** Verify system stability over extended period

**Parameters:**
- **Virtual Users:** 30 concurrent
- **Duration:** 2 hours
- **Ramp-up:** 5 minutes
- **Steady State:** 110 minutes
- **Ramp-down:** 5 minutes
- **Traffic Mix:**
  - 60% chatbot requests
  - 40% search requests
- **Think Time:** 15-45 seconds

**Success Criteria:**
- Consistent response times throughout
- No memory leaks (stable memory usage)
- No performance degradation over time
- Error rate < 0.5%
- No crashes or restarts required
- Log files remain manageable size

---

## k6 Test Scripts

### Installation

```bash
# macOS
brew install k6

# Linux
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6

# Windows
choco install k6
```

### Test Script: Chatbot Load

Create `load-tests/chatbot-load.js`:

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
  stages: [
    { duration: '5m', target: 50 },  // Ramp up to 50 users
    { duration: '20m', target: 50 }, // Stay at 50 users
    { duration: '5m', target: 0 },   // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<8000'], // 95% of requests must complete within 8s
    http_req_failed: ['rate<0.01'],    // Error rate must be less than 1%
    errors: ['rate<0.01'],
  },
};

// Test data: sample questions
const questions = [
  'What services do you offer?',
  'How can I contact support?',
  'What are your office hours?',
  'Do you offer consulting?',
  'What is your pricing?',
  'Can you help with WordPress?',
  'What technologies do you use?',
  'Do you have a blog?',
  'What is your mission?',
  'How long have you been in business?',
];

export default function () {
  // Get random question
  const question = questions[Math.floor(Math.random() * questions.length)];

  // Prepare request
  const url = `${__ENV.BASE_URL}/wp-json/ai-assistant/v1/chat`;
  const payload = JSON.stringify({
    question: question,
    top_k: 5,
  });
  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': __ENV.WP_NONCE,
    },
    timeout: '30s',
  };

  // Send request
  const response = http.post(url, payload, params);

  // Check response
  const checkResult = check(response, {
    'status is 200': (r) => r.status === 200,
    'response has answer': (r) => JSON.parse(r.body).answer !== undefined,
    'response time < 10s': (r) => r.timings.duration < 10000,
  });

  // Record error if check failed
  errorRate.add(!checkResult);

  // Random think time between requests (10-30 seconds)
  sleep(Math.random() * 20 + 10);
}
```

### Test Script: Search Load

Create `load-tests/search-load.js`:

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
  stages: [
    { duration: '5m', target: 50 },
    { duration: '20m', target: 50 },
    { duration: '5m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<5000'],
    http_req_failed: ['rate<0.01'],
    errors: ['rate<0.01'],
  },
};

// Test data: sample search queries
const queries = [
  'web development',
  'WordPress support',
  'consulting services',
  'contact information',
  'pricing plans',
  'case studies',
  'team members',
  'blog posts',
  'portfolio projects',
  'client testimonials',
];

export default function () {
  const query = queries[Math.floor(Math.random() * queries.length)];

  const url = `${__ENV.BASE_URL}/wp-json/ai-assistant/v1/search`;
  const payload = JSON.stringify({
    query: query,
    top_k: 10,
  });
  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': __ENV.WP_NONCE,
    },
    timeout: '15s',
  };

  const response = http.post(url, payload, params);

  const checkResult = check(response, {
    'status is 200': (r) => r.status === 200,
    'response has results': (r) => JSON.parse(r.body).results !== undefined,
    'response time < 8s': (r) => r.timings.duration < 8000,
  });

  errorRate.add(!checkResult);

  sleep(Math.random() * 20 + 10);
}
```

### Test Script: Mixed Traffic

Create `load-tests/mixed-load.js`:

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '5m', target: 50 },
    { duration: '20m', target: 50 },
    { duration: '5m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<8000'],
    http_req_failed: ['rate<0.01'],
    errors: ['rate<0.01'],
  },
};

const chatQuestions = [
  'What services do you offer?',
  'How can I contact support?',
  'What are your office hours?',
];

const searchQueries = [
  'web development',
  'WordPress support',
  'consulting services',
];

export default function () {
  // 70% chatbot, 30% search
  const isChatbot = Math.random() < 0.7;

  if (isChatbot) {
    // Chatbot request
    const question = chatQuestions[Math.floor(Math.random() * chatQuestions.length)];
    const url = `${__ENV.BASE_URL}/wp-json/ai-assistant/v1/chat`;
    const payload = JSON.stringify({ question, top_k: 5 });
    const params = {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': __ENV.WP_NONCE,
      },
      timeout: '30s',
    };

    const response = http.post(url, payload, params);
    const checkResult = check(response, {
      'chatbot status 200': (r) => r.status === 200,
      'chatbot has answer': (r) => JSON.parse(r.body).answer !== undefined,
    });
    errorRate.add(!checkResult);
  } else {
    // Search request
    const query = searchQueries[Math.floor(Math.random() * searchQueries.length)];
    const url = `${__ENV.BASE_URL}/wp-json/ai-assistant/v1/search`;
    const payload = JSON.stringify({ query, top_k: 10 });
    const params = {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': __ENV.WP_NONCE,
      },
      timeout: '15s',
    };

    const response = http.post(url, payload, params);
    const checkResult = check(response, {
      'search status 200': (r) => r.status === 200,
      'search has results': (r) => JSON.parse(r.body).results !== undefined,
    });
    errorRate.add(!checkResult);
  }

  sleep(Math.random() * 20 + 10);
}
```

### Running Tests

```bash
# Set environment variables
export BASE_URL="https://your-staging-site.pantheonsite.io"
export WP_NONCE="your-nonce-value"

# Run specific test
k6 run load-tests/chatbot-load.js

# Run with custom VUs and duration
k6 run --vus 100 --duration 30m load-tests/mixed-load.js

# Run and output results to JSON
k6 run --out json=results.json load-tests/chatbot-load.js

# Run with cloud results (k6 cloud account required)
k6 run --out cloud load-tests/mixed-load.js
```

---

## Monitoring During Tests

### Key Metrics to Watch

#### Application Metrics
- **Response time** (average, P95, P99)
- **Throughput** (requests per second)
- **Error rate** (percentage)
- **Success rate** (percentage)
- **Concurrent users** (active)

#### System Metrics
- **CPU usage** (percentage)
- **Memory usage** (MB/GB)
- **Network I/O** (MB/s)
- **Disk I/O** (operations/s)
- **Database connections** (count)

#### API Metrics
- **OpenAI API calls** (count, rate)
- **Pinecone API calls** (count, rate)
- **API latency** (ms)
- **API errors** (count)
- **Rate limit hits** (count)

#### Cache Metrics
- **Cache hit rate** (percentage)
- **Cache miss rate** (percentage)
- **Redis memory usage** (MB)
- **Eviction rate** (count)

### Monitoring Tools Commands

#### Terminus (Pantheon)
```bash
# Watch environment metrics
terminus env:metrics kanopi-2019.test --period=hour

# View logs in real-time
terminus logs:tail kanopi-2019.test --type=nginx-access
terminus logs:tail kanopi-2019.test --type=php-error

# Check New Relic (if available)
terminus newrelic:report kanopi-2019.test
```

#### k6 Real-time Dashboard
```bash
# Run with web dashboard
k6 run --out influxdb=http://localhost:8086/k6 load-tests/mixed-load.js

# Then view at: http://localhost:3000
```

---

## Analysis & Reporting

### Test Results Template

```markdown
## Load Test Results

**Date:** YYYY-MM-DD
**Environment:** Staging
**Scenario:** Normal Load (50 concurrent users, 30 minutes)

### Summary
- **Total Requests:** X,XXX
- **Successful Requests:** X,XXX (XX%)
- **Failed Requests:** XXX (X%)
- **Average Response Time:** X.XXs
- **P95 Response Time:** X.XXs
- **P99 Response Time:** XX.XXs
- **Throughput:** XX req/s

### Performance Metrics
| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Avg Response Time | < 5s | X.XXs | ✅ / ❌ |
| P95 Response Time | < 8s | X.XXs | ✅ / ❌ |
| P99 Response Time | < 12s | XX.XXs | ✅ / ❌ |
| Error Rate | < 1% | X.X% | ✅ / ❌ |
| CPU Usage | < 70% | XX% | ✅ / ❌ |
| Memory Usage | Stable | XXX MB | ✅ / ❌ |

### Observations
- List key observations
- Note any bottlenecks
- Describe system behavior

### Issues Found
1. Issue description
2. Issue description

### Recommendations
1. Recommendation
2. Recommendation
```

### Interpreting Results

#### Good Indicators
- Response times consistently under targets
- Error rate < 1%
- CPU usage < 70% under normal load
- Memory usage stable (no leaks)
- Cache hit rate > 70%
- No increase in error rate over time

#### Warning Signs
- Response times approaching thresholds
- Error rate 1-2%
- CPU usage 70-85%
- Memory usage slowly increasing
- Cache hit rate < 60%
- Occasional timeouts

#### Critical Issues
- Response times exceeding thresholds
- Error rate > 2%
- CPU usage > 85%
- Memory leaks evident
- Frequent crashes or restarts
- API rate limits hit regularly

---

## Troubleshooting Common Issues

### High Response Times

**Possible Causes:**
- Database slow queries
- External API latency (OpenAI, Pinecone)
- Insufficient caching
- Unoptimized code
- Resource constraints

**Investigation Steps:**
1. Check APM for slow transactions
2. Review database query logs
3. Check external API latencies
4. Verify cache hit rates
5. Review New Relic traces

**Solutions:**
- Add database indexes
- Implement/improve caching
- Optimize slow queries
- Increase server resources
- Use CDN for static assets

### High Error Rate

**Possible Causes:**
- API rate limits exceeded
- Database connection limits
- Memory exhaustion
- Timeout issues
- Incorrect configuration

**Investigation Steps:**
1. Review error logs
2. Check API usage vs limits
3. Monitor database connections
4. Check memory usage patterns
5. Review timeout settings

**Solutions:**
- Increase rate limits or add throttling
- Increase connection pool size
- Optimize memory usage
- Adjust timeout values
- Fix configuration issues

### Memory Leaks

**Symptoms:**
- Memory usage continuously increasing
- Server eventually crashes
- Performance degradation over time

**Investigation Steps:**
1. Use memory profiling tools
2. Review object caching implementation
3. Check for circular references
4. Monitor transient usage
5. Review plugin interactions

**Solutions:**
- Fix memory leaks in code
- Implement proper object cleanup
- Limit cache sizes
- Clear transients regularly

---

## Post-Test Actions

### Immediate (Within 1 Hour)
- [ ] Stop load tests gracefully
- [ ] Collect and save all test results
- [ ] Review monitoring dashboards
- [ ] Check for any lingering issues
- [ ] Clear test data if necessary

### Short-term (Within 24 Hours)
- [ ] Analyze all collected data
- [ ] Write test results report
- [ ] Document issues found
- [ ] Create tickets for issues
- [ ] Share results with team

### Follow-up (Within 1 Week)
- [ ] Address critical issues
- [ ] Implement optimizations
- [ ] Re-run tests if changes made
- [ ] Update capacity planning
- [ ] Schedule next load test

---

## Load Test Checklist

### Pre-Test
- [ ] Environment prepared and stable
- [ ] Test scripts written and validated
- [ ] Monitoring configured
- [ ] Team notified
- [ ] Backup created
- [ ] Stakeholders informed

### During Test
- [ ] Monitor dashboards continuously
- [ ] Watch for anomalies
- [ ] Document observations
- [ ] Take screenshots of key metrics
- [ ] Be ready to stop test if critical issues arise

### Post-Test
- [ ] Collect all results
- [ ] Generate reports
- [ ] Analyze data
- [ ] Document findings
- [ ] Create action items
- [ ] Share with stakeholders

---

## Resources

### Documentation
- [k6 Documentation](https://k6.io/docs/)
- [Apache JMeter User Manual](https://jmeter.apache.org/usermanual/)
- [New Relic Load Testing Guide](https://docs.newrelic.com/)
- [Pantheon Performance Guide](https://pantheon.io/docs/guides/performance)

### Tools
- [k6 Cloud](https://k6.io/cloud)
- [Grafana](https://grafana.com/) - Visualization
- [InfluxDB](https://www.influxdata.com/) - Metrics storage
- [Locust](https://locust.io/) - Alternative load testing

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-28 | Initial load testing guide created |

---

**Next Steps:** Follow this guide to conduct load tests before production deployment.

