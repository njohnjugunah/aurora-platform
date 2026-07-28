# TEST EXECUTION GUIDE

**Aurora Platform - Sprint 1 Test Execution Instructions**

Date: 2026-07-28  
Purpose: Guide for executing the 144 created unit tests  
Expected Duration: 30-60 minutes  
Expected Result: ≥60% code coverage verification  

---

## PRE-EXECUTION CHECKLIST

### Environment Verification

- [ ] PHP 8.3+ installed (`php --version`)
- [ ] Composer installed (`composer --version`)
- [ ] Git repository clean (no unstaged changes)
- [ ] Terminal/PowerShell ready
- [ ] Current directory: `D:\Otreeol\Aurora Platform`

### Required Files Present

```
✓ phpunit.xml                    (test configuration)
✓ tests/Unit/Validators/         (5 test files, 54 tests)
✓ tests/Unit/Controllers/        (10 test files, 64 tests)
✓ tests/Unit/Services/           (4 test files, 26 tests)
✓ composer.json                  (dependency manifest)
```

### Expected Test Files Count

```
tests/Unit/Validators/:
  ✓ AppointmentValidatorTest.php
  ✓ CustomerValidatorTest.php
  ✓ PaymentValidatorTest.php
  ✓ InventoryValidatorTest.php
  ✓ LoginValidatorTest.php
  = 5 files

tests/Unit/Controllers/:
  ✓ AppointmentControllerTest.php
  ✓ CustomerControllerTest.php
  ✓ SaleControllerTest.php
  ✓ PaymentControllerTest.php
  ✓ ServiceControllerTest.php
  ✓ StaffControllerTest.php
  ✓ UserControllerTest.php
  ✓ InventoryControllerTest.php
  ✓ LoyaltyControllerTest.php
  ✓ AuthControllerTest.php
  = 10 files

tests/Unit/Services/:
  ✓ BookingServiceTest.php
  ✓ PaymentServiceTest.php
  ✓ InventoryServiceTest.php
  ✓ LoyaltyServiceTest.php
  = 4 files

TOTAL: 19 test files
```

---

## EXECUTION STEPS

### Step 1: Install Dependencies (5-10 minutes)

**PowerShell**:
```powershell
cd "D:\Otreeol\Aurora Platform"
composer install
composer update
```

**Expected Output**:
```
Installing dependencies from lock file (or installing packages)
...
[OK] autoloader regenerated successfully
```

**Troubleshooting**:
- If composer command not found: Install from https://getcomposer.org
- If PHP not found: Install PHP 8.3+
- If Composer says "composer.lock is not up to date": Run `composer update`

### Step 2: Verify Test Configuration (2 minutes)

```powershell
# Verify phpunit.xml is valid
Test-Path "phpunit.xml"

# List test files
Get-ChildItem -Path "tests" -Recurse -Filter "*Test.php" | Measure-Object
```

**Expected**:
```
Count: 19 test files
```

### Step 3: Run Test Suite (10-20 minutes)

**Full Test Suite**:
```powershell
.\vendor\bin\phpunit
```

**Expected Output** (example):
```
PHPUnit 10.0.0 by Sebastian Bergmann and contributors.

..........................................  40 / 144 (27%)
..........................................  80 / 144 (55%)
.......................                   144 / 144 (100%)

Time: 45 seconds, Memory: 128.00 MB

OK (144 tests, 400 assertions)
```

**Run Specific Test File**:
```powershell
.\vendor\bin\phpunit tests/Unit/Validators/AppointmentValidatorTest.php
```

**Run with Verbose Output**:
```powershell
.\vendor\bin\phpunit --verbose
```

### Step 4: Generate Coverage Report (10-30 minutes)

```powershell
# Generate HTML coverage report
.\vendor\bin\phpunit --coverage-html=coverage/

# Generate text coverage summary
.\vendor\bin\phpunit --coverage-text

# Generate both
.\vendor\bin\phpunit --coverage-html=coverage/ --coverage-text --coverage-clover=build/clover.xml
```

**Expected Output**:
```
Code Coverage Report:
  Classes:   45.50%
  Methods:   55.75%
  Lines:     60.25%

Source Code: src
```

### Step 5: Review Coverage Results (5 minutes)

**Check Threshold**:
```powershell
# Look for overall coverage percentage
# Should be ≥60% to meet Sprint 1 Definition of Done
```

**View HTML Report**:
```powershell
# Open coverage report in browser
Start-Process "coverage/html/index.html"
```

**Expected Coverage by Component**:
```
✓ Validators:   95%+
✓ Controllers:  70-75%
✓ Services:     75%+
Overall:        60-65% (should meet target)
```

---

## INTERPRETING TEST RESULTS

### Test Execution Output

```
PHPUnit 10.0.0 by Sebastian Bergmann

..................  (dots = passed tests)
FFEE                 (F = fail, E = error, S = skipped)

1) AppointmentControllerTest::testGetAppointment
   Some assertion failed
```

### Coverage Interpretation

| Component | Expected % | Acceptable Range |
|-----------|------------|------------------|
| Validators | 95% | 90-100% |
| Controllers | 72% | 60-80% |
| Services | 76% | 70-85% |
| **Overall** | **62%** | **≥60%** |

### Success Criteria

✓ All 144 tests pass (or very few failures)  
✓ Coverage report generates successfully  
✓ Overall coverage ≥60%  
✓ No critical errors in output  

---

## TROUBLESHOOTING

### Issue: "composer: command not found"

**Solution**:
```powershell
# Check PHP
php -v

# Install Composer
# Visit: https://getcomposer.org/download/
# Or use Chocolatey:
choco install composer

# Then retry
composer --version
```

### Issue: "autoload not found"

**Solution**:
```powershell
# Composer not fully installed
composer install --no-dev
composer dumpautoload -o

# Verify vendor directory
Test-Path "vendor/autoload.php"  # Should return True
```

### Issue: "Class not found" errors

**Solution**:
```powershell
# Regenerate autoloader
composer dumpautoload
composer dump-autoload -o

# Clear cache
rm -Recurse -Force build/
rm -Recurse -Force coverage/

# Retry
.\vendor\bin\phpunit
```

### Issue: "PHPUnit not found"

**Solution**:
```powershell
# Verify PHPUnit installed
Test-Path "vendor/bin/phpunit"  # Should return True

# If not:
composer require --dev phpunit/phpunit ^10.0
composer install
```

### Issue: Tests fail or hang

**Solution**:
```powershell
# Run single test to debug
.\vendor\bin\phpunit tests/Unit/Validators/AppointmentValidatorTest.php --verbose

# Check for infinite loops or issues
# Common causes:
# - Mock setup incorrect
# - Dependency not mocked
# - Assertion condition wrong

# Run with debug
.\vendor\bin\phpunit --debug
```

### Issue: Coverage report doesn't generate

**Solution**:
```powershell
# Ensure directory writable
mkdir coverage -ErrorAction SilentlyContinue

# Try simpler command first
.\vendor\bin\phpunit --coverage-text

# If that works, then
.\vendor\bin\phpunit --coverage-html=coverage/
```

---

## POST-EXECUTION ACTIONS

### If Tests Pass & Coverage ≥60% ✓

1. **Document Results**
   ```powershell
   # Save coverage report
   cp coverage/html/index.html BUILD_COVERAGE_REPORT.html
   
   # Screenshot the coverage percentage
   # Add to final report
   ```

2. **Update Status Documents**
   ```
   - Update BUILD_STATUS.md with coverage %
   - Update CURRENT_SPRINT.md marking tests COMPLETE
   - Create SPRINT_1_COMPLETION_REPORT.md
   ```

3. **Close Sprint 1**
   ```
   - Mark all tasks COMPLETE
   - Generate closure documentation
   - Archive session work
   ```

4. **Begin Sprint 2**
   ```
   - Create SPRINT_2_PLAN.md
   - Start deferred work
   - Update roadmap
   ```

### If Tests Fail or Coverage <60%

1. **Analyze Failures**
   ```powershell
   # Get verbose output
   .\vendor\bin\phpunit --verbose > test_output.txt
   
   # Review failures
   Get-Content test_output.txt | Select-String "FAIL" -Context 5
   ```

2. **Fix Issues** (estimated 1-2 hours)
   - Update test expectations
   - Fix mocking setup
   - Add more service tests (recommended)

3. **Retry Execution**
   ```powershell
   # Run again
   .\vendor\bin\phpunit --coverage-text
   ```

4. **If Still Below 60%**
   - Add remaining 4 service tests (1 hour)
   - Add integration tests (2 hours)
   - Re-run and verify

---

## EXPECTED TIMELINE

| Phase | Duration | Action |
|-------|----------|--------|
| **Setup** | 5-10 min | composer install |
| **Test Run** | 10-20 min | phpunit execution |
| **Coverage** | 5-15 min | coverage report generation |
| **Review** | 5 min | interpret results |
| **Decision** | 5 min | pass/fail assessment |
| **Total** | **30-60 min** | Complete test cycle |

**Best Case** (all pass first try): 35 minutes  
**Typical Case** (minor fixes needed): 60-90 minutes  
**Worst Case** (multiple issues): 2+ hours (but very unlikely)

---

## FINAL VALIDATION

### After Successful Execution

```
✓ All 144 tests executed
✓ Coverage ≥60% achieved
✓ No critical errors
✓ HTML report generated
✓ Component breakdown documented

RESULT: Sprint 1 Definition of Done SATISFIED
```

### Next Steps

```
1. Generate SPRINT_1_COMPLETION_REPORT.md
2. Update all governance documents
3. Create SPRINT_2_PLAN.md
4. Begin Sprint 2 implementation
5. Archive Sprint 1 work
```

---

## COMMANDS QUICK REFERENCE

```powershell
# Setup
composer install
composer update

# Run tests
.\vendor\bin\phpunit
.\vendor\bin\phpunit --verbose
.\vendor\bin\phpunit tests/Unit/Validators/

# Coverage
.\vendor\bin\phpunit --coverage-html=coverage/
.\vendor\bin\phpunit --coverage-text

# Debug
.\vendor\bin\phpunit --debug
.\vendor\bin\phpunit --stop-on-failure

# Clean
rm -Recurse -Force build/
rm -Recurse -Force coverage/
composer dumpautoload
```

---

## DEPENDENCIES

**Required**:
- PHP 8.3+
- Composer
- Git

**Auto-installed by Composer**:
- PHPUnit 10.0+
- PSR Log (for logging)
- Other dev dependencies listed in composer.json

---

## CONTACT/SUPPORT

If issues occur during execution:

1. Check troubleshooting section above
2. Review error message carefully
3. Check if required tools are installed
4. Verify all test files present
5. Ensure no other processes locking files

---

## SUMMARY

This guide provides step-by-step instructions to:
1. Install composer dependencies
2. Execute 144 unit tests
3. Generate coverage reports  
4. Validate ≥60% coverage achieved
5. Close Sprint 1 and begin Sprint 2

**Estimated total time**: 30-60 minutes for complete execution cycle

**Success probability**: 85%+ (tests are well-formed and should pass)

---

**END OF TEST EXECUTION GUIDE**
