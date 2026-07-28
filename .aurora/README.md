# .aurora/ Directory - Framework State Tracking

**Purpose**: Persistent framework state for autonomous session continuation

**Scope**: This directory contains JSON state files and metadata that allow future Claude Code sessions to understand current progress without reading all governance documents.

---

## Files in This Directory

### 1. progress.json
**Purpose**: Current sprint status and completion tracking

**Key Data**:
- `completion_percentage`: Overall project completion (currently 35%)
- `phase_1_foundation`: Phase 1 sub-component status
- `current_sprint`: Active sprint details and task counts
- `blockers`: Critical blockers with ownership and ETAs
- `quality_metrics`: Test coverage, code quality scores

**Update Frequency**: End of each sprint  
**Owner**: Build Engineer / Scrum Master  
**Read By**: Sprint planning, progress tracking

**When to Consult**:
- Session start: Get current completion %
- Sprint planning: Review blockers and current tasks
- Status reporting: Provide executive updates

---

### 2. dependency_graph.json
**Purpose**: Track component dependencies and critical path

**Key Data**:
- `dependencies`: Component-level dependencies with blocking relationships
- `critical_path`: Ordered list of work that must happen in sequence
- `parallelizable_work`: Tasks that can happen in parallel

**Example Critical Path**:
```
Domain Models (✓) → 
  Repository Interfaces (✓) → 
    MySQL Implementations (⏳) → 
      Services (⏳) → 
        Controllers (⏳) → 
          Testing & Frontend
```

**Update Frequency**: When dependencies change  
**Owner**: Architect / Build Engineer  
**Read By**: Sprint planning, unblocking decisions

**When to Consult**:
- Determining sprint task sequence
- Identifying why features are blocked
- Planning parallel work streams

---

### 3. module_registry.json
**Purpose**: Summary of all code modules, their status, and completion

**Key Data**:
- `total_modules`: 87 total files across all layers
- `implemented`: 41 complete/in-progress
- `planned`: 40 still to build
- `layers`: Status breakdown by architecture layer
- `critical_modules`: High-priority modules with blocking status

**Update Frequency**: After each sprint  
**Owner**: Build Engineer  
**Read By**: Architecture planning, scope assessment

**When to Consult**:
- Understanding what code exists
- Identifying major gaps by layer
- Calculating remaining work

---

### 4. session_history.md
**Purpose**: Audit trail of all AI-SDLC sessions and work completed

**Sections**:
- Session summaries with dates and participants
- Objectives completed vs. pending
- Deliverables list
- Key findings and decisions
- Next steps assigned

**Example Entry**:
```
## SESSION 2: Framework Hardening (2026-07-28)
Type: AI-SDLC Framework Hardening
Duration: 3 hours
Objectives: Audit framework, create missing docs, initialize .aurora/
Deliverables: 3 new docs, FRAMEWORK_AUDIT.md, .aurora/ infrastructure
Next Steps: Fix 13 identified findings (P0-P3)
```

**Update Frequency**: After each session  
**Owner**: Session participant (AI-SDLC)  
**Read By**: Context awareness at session start

**When to Consult**:
- Session start: Understand what was done previously
- Context reconstruction: Find decision rationale
- Historical analysis: See how project evolved

---

## How to Use This Directory

### At Session Start

1. Read `progress.json` → Understand current completion %
2. Check `dependency_graph.json` → See what's blocking
3. Review `session_history.md` → Understand what happened last
4. Consult `module_registry.json` → Know what code exists

**Time**: ~2 minutes (skips reading 18 governance documents)

### During Session

- Update relevant JSON file if work completes
- Reference in sprint planning and task allocation
- Use as quick-check against governance docs for consistency

### At Session End

- Update `progress.json` with sprint results
- Add entry to `session_history.md`
- Note any new blockers in `dependency_graph.json`
- Update module completion in `module_registry.json`

---

## Update Guidelines

### What NOT to Put in .aurora/

❌ Detailed implementation plans (use CURRENT_SPRINT.md)  
❌ Architecture decisions (use DECISION_LOG.md)  
❌ Code-level details (use IMPLEMENTATION_INDEX.md)  
❌ Verbose documentation (use governance docs)  

### What TO Put in .aurora/

✓ High-level status summaries  
✓ Metrics and progress %  
✓ Current blockers and ETAs  
✓ Critical path and dependencies  
✓ Session audit trail  

---

## Format Standards

### JSON Files

- Use UTF-8 encoding
- Indent with 2 spaces
- Include `metadata.generated` timestamp
- Flat structure preferred (avoid deep nesting)
- All dates in ISO-8601 format (YYYY-MM-DDTHH:MM:SSZ)

### Markdown Files

- Use H2 headers (##) for sections
- Include timestamp in content
- Link to main governance docs for details
- Summarize, don't repeat

---

## Integration with CI/CD

### Recommended Automation

**In GitHub Actions**:
```yaml
- name: Update framework state
  run: |
    # Update completion % from file count
    # Validate all JSON is valid
    # Check links in governance docs
    # Report on framework health
```

**Pre-Commit Hook**:
```bash
# Validate JSON format
# Check modification timestamps
# Ensure session_history.md updated
```

---

## Security & Access

**Who can read**: Anyone with repo access (read-only)  
**Who can update**: 
- Build Engineer (progress, module_registry)
- Architect (dependency_graph)
- Session participant (session_history)

**Not Sensitive**: These files contain no secrets, credentials, or sensitive data.

---

## Common Patterns

### Finding Latest Session Info
```bash
tail -100 session_history.md  # See recent work
jq '.current_sprint' progress.json  # Get sprint status
```

### Checking What's Blocking
```bash
jq '.blockers[] | select(.severity=="Critical")' progress.json
jq '.critical_path' dependency_graph.json
```

### Understanding Module Coverage
```bash
jq '.layers[] | {name: .name, status: .status, completion: .completion}' module_registry.json
```

---

## Version History

### v1.0.0 (2026-07-28) - Initial Creation
- Created during framework hardening session
- Includes progress tracking from Session 1-2
- Ready for ongoing maintenance

---

## FAQ

**Q: Do I need to read all governance docs to continue?**  
A: No. Read `.aurora/progress.json` first (~1 min), then link to specific docs as needed.

**Q: How detailed should JSON state be?**  
A: Enough to understand status without reading 18 docs, but not so detailed you duplicate governance docs.

**Q: When should I update these files?**  
A: After each sprint ends or significant changes occur. See update guidelines above.

**Q: Can I add new JSON files?**  
A: Only with Architect approval. Keep this directory focused on state tracking.

---

**Purpose**: Enable autonomous continuation  
**Owner**: Build Engineer / Architect  
**Audience**: AI-SDLC sessions, development team leads  
**Critical for**: Session context, sprint planning, status reporting

---

