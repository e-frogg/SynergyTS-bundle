# AGENTS Guide

Harness-Version: 2  
Harness-Root: documentation  
Last-Harness-Update: 2026-03-10

## Start Here
- Entry point: [documentation/index.md](documentation/index.md)
- High-level map: [ARCHITECTURE.md](ARCHITECTURE.md)
- Plan format: [PLANS.md](PLANS.md)

## How To Run (canonical)
- `composer validate --no-check-publish`
- `php ../../../vendor/bin/php-cs-fixer fix --dry-run --diff`
- `php ../../../vendor/bin/phpstan analyse -c phpstan.neon`
- `bash scripts/check-docs.sh`

## Validate A PR
- Run style + static analysis + `check-docs`.
- If behavior changes, attach evidence (command output, logs, screenshots if UI).
- If a check is flaky, rerun once then open a follow-up PR (see merge doctrine).
- Details: [documentation/run/testing.md](documentation/run/testing.md), [documentation/run/ci.md](documentation/run/ci.md)

## ExecPlan Requirement
Use an ExecPlan in `documentation/exec-plans/active/` when change scope is:
- more than 10 files, or
- estimated above 2 hours, or
- cross-module impact (controller + data + ACL + Mercure, etc.).
Template/rules: [PLANS.md](PLANS.md)

## Where Decisions Live
- Architecture decisions: [documentation/architecture/decisions/](documentation/architecture/decisions/README.md)
- Capability gaps and structural fixes: [documentation/leverage-log.md](documentation/leverage-log.md)

## Escalation Rules (ask a human)
- Product ambiguity (API behavior, data semantics, backward compatibility promises).
- Security/compliance risk (ACL bypass, secret handling, auth boundaries).
- Potential breaking change for consumers.
- Data loss or irrecoverable migration risk.

## Golden Principles
- Validate at boundaries.
- Deny-by-default ACL and explicit grants.
- No bypass of checks/tests in PR validation.
- Keep docs and checks in sync.
- Encode recurring feedback into invariant checks.
Details: [documentation/core-beliefs.md](documentation/core-beliefs.md)

## Merge Doctrine
- Prefer small, short-lived PRs.
- Keep gates minimal but real.
- Fix-forward by default; stop-the-line only for security/data-loss/compliance.
Policy: [documentation/merge-policy.md](documentation/merge-policy.md)
