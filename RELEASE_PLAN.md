# RELEASE_PLAN.md

**Aurora Platform - Release & Versioning Strategy**

Version: 1.0.0  
Status: Active  
Last Updated: 2026-07-28

---

## VERSIONING SCHEME

**Semantic Versioning: MAJOR.MINOR.PATCH**

- **MAJOR**: Breaking changes (new database schema, API breaking)
- **MINOR**: New features (backwards compatible)
- **PATCH**: Bug fixes (backwards compatible)

Example: v1.2.3
- 1 = Major version (Phase 1 release)
- 2 = Minor features added
- 3 = Bug fixes applied

---

## RELEASE PHASES

### Phase 1: Foundation (Current)
- **Version**: v1.0.0
- **Target**: 2026-08-07
- **Focus**: Core operations (appointments, POS, inventory, customers)
- **Go-Live**: Single location, limited staff

### Phase 2: Digital Channels
- **Version**: v2.0.0
- **Target**: 2026-09-30
- **Features**: Customer portal, mobile app, advanced reporting

### Phase 3: Intelligence
- **Version**: v3.0.0
- **Target**: 2026-11-30
- **Features**: AI recommendations, accounting integration

### Phase 4: Scale
- **Version**: v4.0.0
- **Target**: 2027-03-31
- **Features**: Multi-location, API marketplace

---

## RELEASE SCHEDULE

### Major Releases

| Version | Target | Preparation |
|---------|--------|-------------|
| **v1.0.0** | 2026-08-07 | Sprint S1-S4 |
| **v2.0.0** | 2026-09-30 | Sprint S5-S8 |
| **v3.0.0** | 2026-11-30 | Sprint S9-S12 |
| **v4.0.0** | 2027-03-31 | Sprint S13-S20 |

### Patch Releases (As Needed)

- v1.0.1, v1.0.2, etc. for bug fixes
- No features in patch releases
- Released without sprint planning

### Release Branches

```
main (production)
  ↑
develop (staging)
  ↑
feature/* (feature branches)
hotfix/* (critical fixes)
release/* (release preparation)
```

---

## GO-LIVE CHECKLIST (v1.0.0)

### Pre-Launch (Week of 8/5)

**Code**:
- [ ] All features implemented
- [ ] Unit tests pass (80%+ coverage)
- [ ] Integration tests pass
- [ ] Security audit passed
- [ ] Performance baseline met

**Infrastructure**:
- [ ] Production environment deployed
- [ ] Database backups automated
- [ ] Monitoring configured
- [ ] Log aggregation working
- [ ] SSL/TLS configured
- [ ] CDN configured (if applicable)

**Documentation**:
- [ ] API documentation complete
- [ ] Staff training materials ready
- [ ] Deployment runbooks written
- [ ] Emergency procedures documented
- [ ] Support procedures established

**Staff**:
- [ ] All staff trained on system
- [ ] Super-users identified
- [ ] Support team briefed
- [ ] On-call rotation established

**Business**:
- [ ] Customer communication ready
- [ ] Data migration plan finalized
- [ ] Legacy system backup created
- [ ] Launch day schedule confirmed
- [ ] Budget approved

### Launch Day (Tuesday 2026-08-07)

**6:00 AM**: Deployment verification
- [ ] Production servers up
- [ ] Database online
- [ ] Backups recent
- [ ] Monitoring active

**7:00 AM**: Final tests
- [ ] Login works
- [ ] Book appointment workflow works
- [ ] Payment processing works
- [ ] Receipts print

**8:00 AM**: Soft launch
- [ ] Limited staff on system
- [ ] Core operations only
- [ ] Support team on standby

**12:00 PM**: Full launch
- [ ] All staff on system
- [ ] Full operations enabled
- [ ] Customer communications sent

**6:00 PM**: Stability check
- [ ] No critical errors
- [ ] Response times good
- [ ] Data integrity verified
- [ ] Backups working

### Post-Launch (Week of 8/7)

- [ ] Daily health checks
- [ ] Weekly performance review
- [ ] Feedback collection
- [ ] Issue triage and fixes
- [ ] Staff support as needed

---

## BACKPORT & SUPPORT POLICY

### Support Windows

| Version | Release | EOL | Support |
|---------|---------|-----|---------|
| **v1.x** | 2026-08-07 | 2027-02-07 | 6 months |
| **v2.x** | 2026-09-30 | 2027-03-30 | 6 months |
| **v3.x** | 2026-11-30 | 2027-05-30 | 6 months |

### Patch Release Criteria

Patches released for:
- **Critical bugs**: System-wide failures
- **Security issues**: Data breach risk
- **Data corruption**: Database issues
- **Payment issues**: Revenue impact

Not patched:
- Feature requests
- Minor bugs
- UI/UX improvements

---

## VERSIONING RULES

### Breaking Changes (Major)

Examples requiring v2.0.0+:
- Changing API endpoint URLs
- Changing database schema incompatibly
- Removing endpoints
- Changing authentication method

### Features (Minor)

Examples for v1.1.0:
- New API endpoints
- New database tables
- UI enhancements
- Third-party integrations

### Fixes (Patch)

Examples for v1.0.1:
- Bug fixes
- Performance improvements
- Documentation corrections
- Dependency updates

---

## HOTFIX PROCESS

**When**: Critical production issue requires immediate fix

**Process**:
1. Create hotfix branch from main
2. Fix issue and test thoroughly
3. Merge to main and develop
4. Tag as patch release (v1.0.1)
5. Deploy immediately
6. Update CHANGELOG

**Example**:
```bash
git checkout -b hotfix/payment-processing-fix main
# Fix code
git commit -m "Fix: M-Pesa callback verification"
git tag v1.0.1
git push origin main develop --tags
```

---

## CHANGELOG FORMAT

**Version 1.0.0 - 2026-08-07**

**Added**
- Initial release with core salon operations
- Appointment booking and management
- POS and payment processing with M-Pesa
- Inventory tracking
- Customer profiles and loyalty program
- Staff performance tracking
- Admin user management
- Comprehensive reporting

**Fixed**
- [List any fixes from development]

**Security**
- [Any security-related fixes]

**Breaking Changes**
- [Any breaking changes from Phase 0]

---

**END OF RELEASE_PLAN.md**

**Related Documents**: ROADMAP.md, BUILD_STATUS.md, CURRENT_SPRINT.md
