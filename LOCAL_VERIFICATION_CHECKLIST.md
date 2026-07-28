# LOCAL VERIFICATION CHECKLIST

**Sprint 1 - Local Developer Verification Process**

This checklist guides a developer through the complete Sprint 1 verification process on their local machine.

---

## PRE-EXECUTION SETUP

### Environment Verification

- [ ] PHP 8.3+ installed (`php --version`)
- [ ] Composer installed (`composer --version`)
- [ ] Git repository cloned
- [ ] Current branch: main or development
- [ ] Working directory: project root

### Project Setup

- [ ] `composer install` executed successfully
- [ ] `vendor/autoload.php` exists
- [ ] `vendor/bin/phpunit` exists
- [ ] `.env` file configured (copy from `.env.example`)
- [ ] Database credentials configured
- [ ] `config/` directory populated

### Verification

- [ ] `composer.json` exists
- [ ] `phpunit.xml` exists
- [ ] `tests/Unit/` directory exists with 19 test files
- [ ] `tests/Integration/` directory exists
- [ ] `src/` directory exists with source code

---

## TEST EXECUTION

### Unit Test Execution

- [ ] Run: `vendor/bin/phpunit tests/Unit --verbose`
- [ ] All validator tests pass (54 tests)
- [ ] All controller tests pass (64 tests)
- [ ] All service tests pass (26 tests)
- [ ] Total: 144 tests executed
- [ ] Execution time < 60 seconds

### Test Results Verification

- [ ] Tests Passed: 144 (or all tests)
- [ ] Tests Failed: 0 (record in EXECUTION_EVIDENCE.md if >0)
- [ ] Tests Skipped: 0
- [ ] Tests Errored: 0

### Coverage Generation

- [ ] Run: `vendor/bin/phpunit tests/Unit --coverage-html=coverage/ --coverage-text`
- [ ] Coverage directory created: `coverage/html/`
- [ ] Coverage report generated: `coverage/html/index.html`
- [ ] Clover XML generated: `coverage/clover.xml`

### Coverage Verification

- [ ] Overall coverage measured (record in EXECUTION_EVIDENCE.md)
- [ ] Validators coverage ≥90%
- [ ] Controllers coverage ≥60%
- [ ] Services coverage ≥75%
- [ ] Overall coverage ≥60%

---

## QUALITY CHECKS

### Code Style

- [ ] Run: `vendor/bin/phpcs src --standard=PSR12`
- [ ] No style violations found (record any violations)
- [ ] Fix violations: `vendor/bin/phpcbf src --standard=PSR12`

### Static Analysis

- [ ] Run: `vendor/bin/phpstan analyse src --level=9`
- [ ] No critical errors found (record any errors)
- [ ] All type hints correct

### Test Coverage Report Review

- [ ] Open: `coverage/html/index.html` in browser
- [ ] Verify coverage percentages match command output
- [ ] Identify any low-coverage files
- [ ] Document findings in EXECUTION_EVIDENCE.md

---

## INTEGRATION TEST EXECUTION (If Required)

[Only if Unit test coverage <60% - see CI_VERIFICATION_CHECKLIST.md for guidance]

- [ ] Run: `vendor/bin/phpunit tests/Integration --verbose`
- [ ] All integration tests pass
- [ ] Coverage increased to ≥60%

---

## RESULTS DOCUMENTATION

### Record Results

- [ ] Fill out EXECUTION_EVIDENCE.md with actual results
  - Total tests executed
  - Pass/fail counts
  - Coverage percentages
  - Execution time
  - Memory usage
- [ ] No estimated values used
- [ ] All values from actual PHPUnit output

### Results Verification

- [ ] Test execution results match output
- [ ] Coverage results match HTML report
- [ ] All metrics accurately recorded
- [ ] Date and environment documented

---

## FINAL VERIFICATION

### Definition of Done Check

- [ ] All 144 unit tests pass
- [ ] Coverage ≥60% achieved
- [ ] No critical code style violations
- [ ] Static analysis passes
- [ ] All results documented in EXECUTION_EVIDENCE.md

### Decision Point

**If all checks pass:**
- [ ] Sign-off local verification complete
- [ ] Ready for Sprint 1 certification

**If any check fails:**
- [ ] Document failure in EXECUTION_EVIDENCE.md
- [ ] Identify root cause
- [ ] Fix issue
- [ ] Re-run affected tests
- [ ] Update documentation
- [ ] Repeat verification

---

## TROUBLESHOOTING REFERENCE

[See LOCAL_EXECUTION_GUIDE.md for detailed troubleshooting]

| Issue | Solution |
|-------|----------|
| PHP not found | Install PHP 8.3+ and add to PATH |
| Composer not found | Install Composer and add to PATH |
| PHPUnit not found | Run `composer install` |
| Tests fail | Check test file imports and mocks |
| Coverage <60% | Add integration tests or review failures |
| Style violations | Run `vendor/bin/phpcbf` to auto-fix |

---

## SIGN-OFF

**Local Verification Completed:**

```
Date: [TO BE FILLED]
Developer: [TO BE FILLED]
PHP Version: [TO BE FILLED]
All Checks Passed: [YES/NO]

Signature: _________________
```

---

**This checklist is a permanent part of Sprint 1 verification.**
**Use it for every local verification attempt.**
**Keep EXECUTION_EVIDENCE.md up-to-date with results.**

---

END OF LOCAL VERIFICATION CHECKLIST
