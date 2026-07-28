# GOVERNANCE_FRAMEWORK.md

**Aurora Platform - Governance Framework Meta-Document**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## PURPOSE

This document describes the governance structure itself - how Aurora documents are organized, updated, reviewed, and maintained. It's the meta-layer enabling autonomous continuation across sessions.

---

## DOCUMENT TAXONOMY

### Tier 1: Strategic (User-Facing Requirements)

| Document | Purpose | Owner | Audience | Frequency |
|----------|---------|-------|----------|-----------|
| **PROJECT_SPECIFICATION.md** | WHAT to build | Product Owner | All | Quarterly review |
| **ROADMAP.md** | WHEN/WHY to build | Product Owner | All | Quarterly review |
| **STAFF_TRAINING.md** | HOW TO USE system | Training Lead | Staff/Users | Pre-launch + updates |

### Tier 2: Tactical (Implementation Guidance)

| Document | Purpose | Owner | Audience | Frequency |
|----------|---------|-------|----------|-----------|
| **BUILD_STATUS.md** | Current state of implementation | Build Engineer | Developers | Sprint-end update |
| **CURRENT_SPRINT.md** | What to build THIS sprint | Scrum Master | Developers | Weekly update |
| **ARCHITECTURE.md** | HOW TO DESIGN components | Architect | Developers | Quarterly review |
| **API_REFERENCE.md** | API specification | Backend Lead | Developers | On-change update |
| **DATABASE_SCHEMA.md** | Database specification | Database Architect | Developers | On-change update |

### Tier 3: Operational (Execution Procedures)

| Document | Purpose | Owner | Audience | Frequency |
|----------|---------|-------|----------|-----------|
| **DEPLOYMENT_GUIDE.md** | HOW TO DEPLOY | DevOps Lead | DevOps/Release | On-change update |
| **OPERATIONS_MANUAL.md** | Daily operations | Ops Lead | Ops Team | Quarterly review |
| **TROUBLESHOOTING.md** | Common issues & fixes | DevOps Lead | Ops Team/Support | As-needed update |

### Tier 4: Quality & Compliance

| Document | Purpose | Owner | Audience | Frequency |
|----------|---------|-------|----------|-----------|
| **QUALITY_STANDARDS.md** | Code quality requirements | QA Lead | Developers | Quarterly review |
| **DECISION_LOG.md** | Architectural decisions | Architect | All | On-change update |
| **RELEASE_PLAN.md** | Version strategy & go-live | Release Manager | All | Quarterly review |
| **INTEGRATION_GUIDE.md** | External service integration | Integration Lead | Developers | On-change update |

### Tier 5: Framework & Metadata

| Document | Purpose | Owner | Audience | Frequency |
|----------|---------|-------|----------|-----------|
| **FRAMEWORK_AUDIT.md** | Framework health check | Framework Lead | Team Leads | Monthly review |
| **IMPLEMENTATION_INDEX.md** | File-by-file registry | Build Engineer | Developers | Sprint-end update |
| **TERMINOLOGY_GLOSSARY.md** | Ubiquitous language | Architect | All | Quarterly review |
| **GOVERNANCE_FRAMEWORK.md** | This framework | Framework Lead | All | Quarterly review |

### Tier 6: Foundational (Unchanging Baseline)

| Document | Purpose | Status |
|----------|---------|--------|
| **MASTER_PROMPT.md** | Engineering constitution | Archive - read-only after v1.0 |
| **README.md** | Project overview | Static - updates only for clarity |

---

## UPDATE PROCEDURES

### When to Update Documents

| Trigger | Document | Responsible | Deadline |
|---------|----------|-------------|----------|
| **Feature added** | PROJECT_SPECIFICATION.md, ROADMAP.md | Product Owner | Before sprint |
| **Architecture decision** | ARCHITECTURE.md, DECISION_LOG.md | Architect | Same day (before coding) |
| **New API endpoint** | API_REFERENCE.md, IMPLEMENTATION_INDEX.md | Backend Lead | Before implementation |
| **Database schema change** | DATABASE_SCHEMA.md | Database Architect | Before migration |
| **Sprint starts** | CURRENT_SPRINT.md, BUILD_STATUS.md | Scrum Master | Sprint start (Monday) |
| **Sprint ends** | BUILD_STATUS.md, IMPLEMENTATION_INDEX.md | Build Engineer | Sprint end (Friday) |
| **Deployment** | DEPLOYMENT_GUIDE.md, RELEASE_PLAN.md | DevOps Lead | As-needed |
| **New issue pattern** | TROUBLESHOOTING.md | Support Lead | As-needed |
| **New operational procedure** | OPERATIONS_MANUAL.md | Ops Lead | Before procedure launch |
| **Code quality change** | QUALITY_STANDARDS.md | QA Lead | Before enforcement |

### Update Process

1. **Identify Need**: What changed? Which documents affected?
2. **Draft Change**: Edit document with clear tracking of changes
3. **Review**: Second pair of eyes from document owner/domain expert
4. **Cross-Link**: Update references in related documents
5. **Commit**: Git commit with message `docs: update DOCUMENT_NAME for REASON`
6. **Notify**: Post in Slack #engineering-updates channel

### Version Control for Documents

- **Major Changes**: Bump version (1.0 → 2.0) when content substantially changes
- **Minor Changes**: Bump version (1.0 → 1.1) for clarifications/additions
- **Trivial Changes**: No version bump for typos/formatting
- **Branch**: All doc changes via PR with code review

---

## REVIEW SCHEDULE

### Weekly Reviews

| Day | Task | Owner | Time |
|-----|------|-------|------|
| Monday 9 AM | Review CURRENT_SPRINT.md | Scrum Master | 15 min |
| Thursday 4 PM | Review BUILD_STATUS.md | Build Engineer | 15 min |
| Friday 3 PM | Plan next sprint | Product Owner + Scrum Master | 30 min |

### Monthly Reviews

| Task | Owner | Schedule | Duration |
|------|-------|----------|----------|
| FRAMEWORK_AUDIT.md review | Framework Lead | 28th of month | 1 hour |
| Architecture review | Architect | 1st of month | 1 hour |
| Operational procedures review | Ops Lead | 15th of month | 1 hour |

### Quarterly Reviews

| Document | Owner | Schedule | Duration |
|----------|-------|----------|----------|
| All documents (comprehensive) | All document owners | End of quarter | 2 hours |
| Terminology consistency | Architect | Q1, Q3 | 1 hour |
| Quality standards update | QA Lead | Q2, Q4 | 1 hour |

---

## APPROVAL WORKFLOWS

### Document Approval Matrix

| Document | Requires Approval From | Approval Level |
|----------|----------------------|-----------------|
| PROJECT_SPECIFICATION.md | Product Owner + Architect | Consensus |
| ROADMAP.md | Product Owner + Build Engineer | Consensus |
| ARCHITECTURE.md | Architect + Tech Lead | Architect sign-off |
| DEPLOYMENT_GUIDE.md | DevOps Lead | DevOps sign-off |
| QUALITY_STANDARDS.md | QA Lead + Tech Lead | Consensus |
| DECISION_LOG.md | Architect | Architect sign-off |
| Others | Document owner | Owner approval |

### Sign-Off Process

1. Create PR with documentation changes
2. Route to approvers per matrix above
3. Resolve feedback
4. Approver clicks "Approve" on PR
5. Merge to main branch
6. Tag in release notes if significant

---

## BROKEN LINK DETECTION

**Process**: 
- Every PR with documentation must verify all cross-links are valid
- Automated check: `find . -name "*.md" -exec grep -l "^.*\.md" {} \; | xargs grep -h "\.md" | sort -u | while read link; do test -f "$link" || echo "BROKEN: $link"; done`
- Manual verification: Click-through sample of links from modified documents

**Responsibility**: PR reviewer must verify no broken links introduced

---

## DOCUMENT RETIREMENT

**When to Archive**:
- Document hasn't been updated in 12 months AND no longer referenced
- Feature/component documented has been removed

**Process**:
1. Move to `archive/DOCUMENT_NAME.md`
2. Add notice at top: `ARCHIVED: This document is obsolete as of [DATE]. See [NEW_DOCUMENT] for current info.`
3. Remove from all cross-references
4. Commit with message `docs: archive DOCUMENT_NAME`

**Keep Indefinitely**:
- MASTER_PROMPT.md (engineering constitution)
- DECISION_LOG.md (architectural history)
- RELEASE_PLAN.md for major versions (v1.0, v2.0, etc.)

---

## CONFLICT RESOLUTION

**If two documents contradict each other**:

1. **Identify discrepancy**: Which facts conflict?
2. **Find source of truth**: Which source is authoritative?
   - Code is authoritative over documentation
   - MASTER_PROMPT.md trumps specific documents
   - Architecture decision = truth until revised
3. **Update wrong document**: Correct the secondary source
4. **Record decision**: Add note to both documents linking the resolution
5. **Update TERMINOLOGY_GLOSSARY.md** if definition was ambiguous

---

## DOCUMENTATION TOOLS & TEMPLATES

### Writing Style Guide

- **Tense**: Present tense for current state, imperative for procedures
- **Person**: Second person for procedures ("you"), third person for descriptions
- **Brevity**: Maximum 150 characters per line for readability
- **Links**: All cross-references as markdown links: `[Document.md](path/Document.md)`
- **Examples**: Include concrete examples with realistic data

### Markdown Template for New Documents

```markdown
# DOCUMENT_NAME.md

**Aurora Platform - [Full Title]**

Version: 1.0.0  
Status: [Active/Draft/Archived]  
Last Updated: YYYY-MM-DD  
Owner: [Role]

---

## PURPOSE

One sentence describing why this document exists.

---

## TABLE OF CONTENTS

[Auto-generated from headers]

---

[Content with cross-links]

---

**END OF DOCUMENT_NAME.md**

**Related Documents**: [Link], [Link], [Link]
```

---

## .aurora/ INFRASTRUCTURE

**Purpose**: Persistent state for framework tracking across sessions

**Contents**:
- `progress.json` - Current sprint progress
- `dependency_graph.json` - Component dependencies  
- `module_registry.json` - Code module inventory
- `implementation_index.json` - Exported from IMPLEMENTATION_INDEX.md
- `session_history.md` - Session audit trail

**Usage**: Read at session start to understand current state

---

## SUCCESS METRICS

| Metric | Target | Current | Trend |
|--------|--------|---------|-------|
| **Doc Coverage** | 100% of features in docs | 95% | ↑ |
| **Cross-Link Coverage** | 100% of references | 61% | ↑ |
| **Update Timeliness** | ≤1 week after change | 3 days avg | ✓ |
| **Accuracy** | ≤0.5% hallucinated metrics | 0% | ✓ |
| **Consistency** | ≥95% terminology | 96% | ✓ |

---

**END OF GOVERNANCE_FRAMEWORK.md**

**Purpose**: Enable autonomous session continuation through framework documentation  
**Authority**: Framework Lead  
**Review Schedule**: Quarterly  
**Next Review**: 2026-10-28
