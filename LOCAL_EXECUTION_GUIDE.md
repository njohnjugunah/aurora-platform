# LOCAL EXECUTION GUIDE

**Sprint 1 - Complete Local Verification and Testing**

This guide provides step-by-step instructions for running Sprint 1 verification on your local machine.

---

## REQUIRED SOFTWARE

Before starting, ensure you have:

1. **PHP 8.3 or higher**
   - Required: Yes
   - Check: `php --version`
   - Install: https://www.php.net/downloads.php
   - Or: `choco install php` (Windows), `brew install php@8.3` (macOS)

2. **Composer**
   - Required: Yes
   - Check: `composer --version`
   - Install: https://getcomposer.org/download/
   - Or: `choco install composer` (Windows), `brew install composer` (macOS)

3. **Git**
   - Required: Yes (for cloning)
   - Check: `git --version`

4. **Database (MySQL/MariaDB)** [Optional - for integration tests]
   - Required: No (for unit tests)
   - Required: Yes (for integration tests with real database)
   - Install: https://www.mysql.com/ or `choco install mysql-server`

---

## INSTALLATION STEPS

### Step 1: Clone or Navigate to Repository

```bash
# Clone (first time only)
git clone [repository-url] aurora-platform
cd aurora-platform

# Or navigate to existing clone
cd /path/to/aurora-platform
```

### Step 2: Verify Required Files

```bash
# Verify key configuration files exist
ls composer.json phpunit.xml .env.example
```

**Expected output**: All three files should exist

### Step 3: Copy Environment Configuration

```bash
# Copy example environment file
cp .env.example .env

# Edit .env with your settings
# Key settings needed for tests:
# - APP_ENV=testing
# - APP_DEBUG=true
# - Database credentials (if running integration tests)
```

### Step 4: Install Composer Dependencies

```bash
# Install all dependencies (including dev dependencies)
composer install

# This will:
# - Download PHPUnit 10.0+
# - Download PSR-12 code sniffer
# - Download PHPStan static analyzer
# - Generate autoloader
```

**Expected output**: 
```
Loading composer repositories...
Installing dependencies...
[OK] autoloader regenerated successfully
```

### Step 5: Verify Installation

```bash
# Check PHPUnit installation
vendor/bin/phpunit --version

# Check autoloader
php -r "require 'vendor/autoload.php'; echo 'Autoloader OK\n';"

# Verify test files exist
find tests -name "*Test.php" | wc -l
```

**Expected output**:
```
PHPUnit 10.x.x by Sebastian Bergmann
Autoloader OK
19 test files found
```

---

## EXECUTING TESTS

### Option 1: Run All Unit Tests (RECOMMENDED)

```bash
vendor/bin/phpunit tests/Unit --verbose
```

**Expected:**
- 144 tests executed
- All tests pass
- Execution time: 30-60 seconds
- Memory: 50-100 MB

**Output format:**
```
PHPUnit 10.x.x

Testing tests\Unit\Validators
............................. 50 / 144 (34%)
...
.......................
OK (144 tests, 400+ assertions)
```

### Option 2: Run Tests by Component

```bash
# Validators only (54 tests)
vendor/bin/phpunit tests/Unit/Validators --verbose

# Controllers only (64 tests)
vendor/bin/phpunit tests/Unit/Controllers --verbose

# Services only (26 tests)
vendor/bin/phpunit tests/Unit/Services --verbose
```

### Option 3: Run Specific Test Class

```bash
# Example: AppointmentValidatorTest
vendor/bin/phpunit tests/Unit/Validators/AppointmentValidatorTest.php --verbose
```

### Option 4: Run Tests with Composer Script

```bash
# Using composer script (if available)
composer test

# Or specific suite
composer test:unit
```

---

## GENERATING COVERAGE REPORTS

### Step 1: Generate HTML Coverage Report

```bash
vendor/bin/phpunit tests/Unit \
  --coverage-html=coverage/ \
  --coverage-text \
  --coverage-clover=coverage/clover.xml
```

**What this does:**
- Generates HTML report in `coverage/html/` directory
- Prints text summary to console
- Creates XML report for CI integration

**Expected output:**
```
Code Coverage Report:
  Classes:   XX.XX%
  Methods:   XX.XX%
  Lines:     XX.XX%

...lines coverage details...
```

### Step 2: View HTML Report in Browser

```bash
# Open coverage report in your default browser
open coverage/html/index.html              # macOS
explorer coverage/html/index.html          # Windows PowerShell
xdg-open coverage/html/index.html         # Linux
```

**What to look for:**
- Overall coverage % at top
- Per-file breakdown
- Line numbers showing covered vs uncovered code
- Low-coverage areas

### Step 3: Check Coverage Threshold

```bash
# Extract overall coverage percentage
vendor/bin/phpunit tests/Unit --coverage-text | grep -E "^[ ]*[0-9]+\.[0-9]+%"
```

**Expected:** Coverage ≥60%

---

## QUALITY CHECKS (OPTIONAL)

### PHP Code Style

```bash
# Check PSR-12 compliance
vendor/bin/phpcs src --standard=PSR12

# Auto-fix style violations (if any)
vendor/bin/phpcbf src --standard=PSR12
```

**Expected:** No errors

### Static Analysis

```bash
# Run PHPStan at level 9 (strict)
vendor/bin/phpstan analyse src --level=9
```

**Expected:** No errors

### All Quality Checks

```bash
# Run all checks at once
vendor/bin/phpunit tests/Unit && \
vendor/bin/phpcs src --standard=PSR12 && \
vendor/bin/phpstan analyse src --level=9
```

---

## RECORDING RESULTS

### Create Execution Evidence

Open `EXECUTION_EVIDENCE.md` and fill in:

1. **Test Execution Summary**
   - Date: [today's date]
   - Environment: [your PHP version]
   - Total tests: [from output]
   - Passed: [count]
   - Failed: [count]
   - Execution time: [in seconds]

2. **Coverage Results**
   - Overall: [percentage from report]
   - Validators: [%]
   - Controllers: [%]
   - Services: [%]

3. **Example entry:**
   ```
   Total Tests: 144
   Passed: 144
   Failed: 0
   Overall Coverage: 63.25%
   Validators: 95%
   Controllers: 72%
   Services: 76%
   ```

### Use LOCAL_VERIFICATION_CHECKLIST.md

Follow the checklist to track completion:
- [ ] PHP verified
- [ ] Composer installed
- [ ] Dependencies installed
- [ ] Tests executed
- [ ] Coverage generated
- [ ] Results documented

---

## TROUBLESHOOTING

### PHP Not Found

**Error**: `php: command not found` or `The term 'php' is not recognized`

**Solution**:
```bash
# Find PHP installation
which php                    # macOS/Linux
where php                    # Windows PowerShell

# Add to PATH if needed
# Windows: System Properties > Environment Variables > Add PHP directory
# macOS: export PATH="/usr/local/php/bin:$PATH" in ~/.zshrc
# Linux: Add PHP bin directory to ~/.bashrc
```

### Composer Not Found

**Error**: `composer: command not found`

**Solution**:
```bash
# Download Composer
curl -sS https://getcomposer.org/installer | php

# Move to PATH
mv composer.phar /usr/local/bin/composer

# Make executable
chmod +x /usr/local/bin/composer
```

### Dependencies Not Installed

**Error**: `Class not found` in tests

**Solution**:
```bash
# Full reset
rm -rf vendor/
rm composer.lock
composer install
composer dump-autoload -o
```

### Tests Fail - Import/Namespace Errors

**Error**: `Class 'App\...' not found`

**Solution**:
```bash
# Verify PSR-4 mapping in composer.json
cat composer.json | grep -A 3 "psr-4"

# Regenerate autoloader
composer dump-autoload -o

# Check source files exist
ls src/Application/Controllers/AppointmentController.php
```

### Tests Fail - Mock Issues

**Error**: `Call to undefined method` on mocks

**Solution**:
1. Check the mock is properly instantiated in setUp()
2. Verify the method exists in the interface/class
3. Check the method signature matches exactly
4. Example fix in test file:
   ```php
   // Correct
   $this->repository->method('find')->willReturn([...]);
   
   // Wrong - typo
   $this->repository->method('findById')->willReturn([...]);
   ```

### Coverage Report Doesn't Generate

**Error**: `coverage/html/ directory empty`

**Solution**:
```bash
# Ensure coverage directory is writable
mkdir -p coverage
chmod -R 777 coverage

# Try simplified command first
vendor/bin/phpunit tests/Unit --coverage-text

# Then add HTML output
vendor/bin/phpunit tests/Unit --coverage-html=coverage/
```

### Memory Errors

**Error**: `Memory limit exceeded`

**Solution**:
```bash
# Increase PHP memory limit
php -d memory_limit=-1 vendor/bin/phpunit tests/Unit

# Or set in php.ini
memory_limit = 1024M
```

### Tests Timeout

**Error**: `Test timed out`

**Solution**:
```bash
# Increase timeout in phpunit.xml or run with flag
vendor/bin/phpunit tests/Unit --verbose --process-timeout=300
```

---

## COMPLETE WORKFLOW

Here's the complete command sequence to verify Sprint 1:

```bash
# 1. Navigate to project
cd /path/to/aurora-platform

# 2. Install dependencies
composer install

# 3. Run all unit tests
vendor/bin/phpunit tests/Unit --verbose

# 4. Generate coverage report
vendor/bin/phpunit tests/Unit \
  --coverage-html=coverage/ \
  --coverage-text \
  --coverage-clover=coverage/clover.xml

# 5. View report
open coverage/html/index.html

# 6. Optional: Run quality checks
vendor/bin/phpcs src --standard=PSR12
vendor/bin/phpstan analyse src --level=9

# 7. Record results in EXECUTION_EVIDENCE.md
# (Edit the file with your actual results)
```

**Expected total time**: 5-10 minutes

---

## INTERPRETING RESULTS

### Success Indicators

✓ All 144 tests pass  
✓ No errors or failures  
✓ Coverage ≥60%  
✓ Execution time <60 seconds  
✓ No memory warnings  

### Failure Indicators

✗ Any test failed  
✗ Coverage <60%  
✗ Memory exhausted  
✗ Timeout errors  
✗ Autoloader errors  

### Next Steps

**If all checks pass:**
- Record results in EXECUTION_EVIDENCE.md
- Mark LOCAL_VERIFICATION_CHECKLIST.md complete
- Proceed to Sprint 1 certification

**If any check fails:**
- Review TROUBLESHOOTING section above
- Fix the issue
- Re-run tests
- Document in EXECUTION_EVIDENCE.md what was fixed

---

## QUICK REFERENCE

| Task | Command |
|------|---------|
| Install deps | `composer install` |
| Run tests | `vendor/bin/phpunit tests/Unit --verbose` |
| Check coverage | `vendor/bin/phpunit tests/Unit --coverage-text` |
| HTML report | `vendor/bin/phpunit tests/Unit --coverage-html=coverage/` |
| Style check | `vendor/bin/phpcs src --standard=PSR12` |
| Static analysis | `vendor/bin/phpstan analyse src --level=9` |
| Run specific test | `vendor/bin/phpunit tests/Unit/Validators/AppointmentValidatorTest.php` |
| Reset vendor | `rm -rf vendor/ composer.lock && composer install` |
| Reset autoloader | `composer dump-autoload -o` |

---

## SUPPORT

For issues:
1. Check TROUBLESHOOTING section above
2. Review test file imports and structure
3. Verify PHP version: `php --version` (must be 8.3+)
4. Verify Composer: `composer --version`
5. Check phpunit.xml configuration
6. Run: `composer diagnose` for Composer issues

---

**This guide is permanent. Use it for every local verification.**

---

END OF LOCAL EXECUTION GUIDE
