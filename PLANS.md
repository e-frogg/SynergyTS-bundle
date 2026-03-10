# ExecPlans Standard

Create an ExecPlan in `documentation/exec-plans/active/` when any of the following is true:
- change touches more than 10 files,
- implementation is estimated above 2 hours,
- impact spans multiple bounded areas (API + ACL + data + Mercure, etc.).

## File Naming
`documentation/exec-plans/active/YYYY-MM-DD-short-title.md`

## Required Template

```md
# <Title>

## Goal
- ...

## Non-goals
- ...

## Current State (facts only)
- Fact + evidence link
- Unknown + discovery method

## Plan (milestones)
1. Milestone
2. Milestone
3. Milestone

## Risks & Mitigations
- Risk -> mitigation

## Evidence Checklist
- [ ] Commands run:
- [ ] Tests/lints output:
- [ ] Before/after evidence (logs/screenshots/video if UI):
- [ ] CI links:

## Decision Log
- Date - decision - rationale

## Rollback Notes
- Trigger:
- Procedure:
```

## Completion Rule
When finished, move the file to `documentation/exec-plans/completed/` and add a short outcome summary.
