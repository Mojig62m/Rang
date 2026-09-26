# EVIDENCE-BASED CODEBASE REHABILITATION REPORT

**Project:** Bamero WordPress/WooCommerce storefront

**Phase:** 1 — Characterization

**Date:** 2026-09-21

## Executive status

Phase 1 is complete. No production file was deleted, rewritten, or behaviorally changed during this pass. The existing production gate, UI checks, JavaScript syntax check, and storefront invariants all pass.

The project contains dynamic WordPress and WooCommerce behavior that cannot be fully characterized from source alone. Those areas are marked **UNTESTABLE LEGACY** until authorized staging is available. Phase 2 property-based behavioral contracts have not been generated, and the workflow correctly stops before any purge or simplification.

## §1. BEHAVIORAL BASELINE

### Repository measurements

The baseline covers the WordPress theme, plugins, and repository checks.

| Measure | Result |
|---|---:|
| PHP files | 21 |
| PHP lines | 3,652 |
| CSS files | 7 |
| CSS lines | 2,568 |
| JavaScript files | 1 |
| JavaScript lines | 487 |
| PHP functions screened | 121 |
| Functions flagged for review by the screening heuristic | 5 |
| Media-query occurrences | 26 |
| Container-query occurrences | 2 |
| Absolute/fixed-position rules | 14 |
| `!important` occurrences | 48 |

The function screen flags a function only when it is longer than 30 lines and has more than eight decision tokens. This is a review heuristic, not a McCabe or SonarQube complexity proof.

Flagged hotspots are:

| File | Function | Lines | Decision-token screen |
|---|---|---:|---:|
| `wp-content/plugins/bamero-mobile-auth/bamero-mobile-auth.php` | `bamero_handle_verify_otp` | 58 | 9 |
| `wp-content/plugins/bamero-mobile-auth/bamero-mobile-auth.php` | `bamero_mobile_auth_shortcode` | 128 | 13 |
| `wp-content/plugins/bamero-woocommerce-setup/bamero-woocommerce-setup.php` | `bamero_create_catalog_products` | 53 | 11 |
| `wp-content/plugins/bamero-zarinpal-gateway/bamero-zarinpal-gateway.php` | `bamero_zarinpal_bootstrap` | 105 | 14 |
| `wp-content/themes/bamero/functions.php` | `bamero_output_entity_graph` | 163 | 9 |

### Executable baseline

The following commands passed in the verification environment:

```text
bash tests/production_gate.sh                         PASS
bash tests/ui_ux_static_checks.sh                     PASS
python3 tests/storefront_modern_invariants.py         PASS
node --check wp-content/themes/bamero/js/main.js      PASS
```

The production gate passed PHP syntax validation for 21 files and its repository-level security, UI, email-boundary, and seed-boundary checks.

The baseline artifacts are reproducible at:

```text
docs/verification-evidence/characterization-baseline-2026-09-21.json
tests/characterization_baseline.py
```

### Behavior classification

The following behavior is characterized at source level:

- Theme and plugin PHP syntax.
- CSS token definitions and RTL logical-direction rules.
- Presence of the modern storefront layer and its enqueue order.
- Header product-search parameters.
- Product availability markup.
- Mobile-menu cleanup path.
- Reduced-motion and local-font declarations.

The following behavior is **UNTESTABLE LEGACY** without runtime characterization:

- WordPress hook ordering with the installed plugin set.
- WooCommerce cart fragments and checkout calculations.
- Database schema, HPOS, transaction isolation, and order persistence.
- Mobile OTP delivery and authentication sessions.
- Zarinpal payment initiation, callback, verification, and replay handling.
- Browser layout, keyboard traversal, accessible names after rendering, and Web Vitals.

## §2. EVIDENCE-BASED DELETION MANIFEST

The deletion manifest is empty.

No file has a proof of non-use. The candidate theme stylesheets and JavaScript are all enqueued by `bamero_scripts()`. WooCommerce templates are loaded by WordPress template resolution or `wc_get_template_part()`. Deleting them would violate the preservation requirement without a behavioral baseline and regression run.

No duplicate-code deletion was performed. A clone detector with the required 85% similarity threshold is not installed in the archive, and textual similarity alone would not prove that two WordPress hooks have equivalent side effects.

No dependency deletion was performed. The repository does not contain a package manager dependency graph for the PHP runtime, and runtime tracing is unavailable without staging.

This phase therefore records **zero approved deletions**.

## §3. COMPLEXITY REDUCTION MAP

No complexity reduction was applied before Phase 2 contracts and user approval.

The five flagged functions are review candidates, not defects. Their behavior crosses authentication, catalog provisioning, payment, or structured-data boundaries. Splitting any of them can change hook timing, global state, response shape, database writes, or provider calls. Each requires a characterization test before refactoring.

The next safe map is:

| Hotspot | Required Phase 2 contract | Refactor gate |
|---|---|---|
| `bamero_handle_verify_otp` | Valid, expired, reused, malformed, and rate-limited OTP properties | No change until authentication tests pass |
| `bamero_mobile_auth_shortcode` | Rendered form fields, nonce, error state, and successful transition properties | Browser/component characterization required |
| `bamero_create_catalog_products` | Product count, names, metadata, idempotency, and no-duplicate-write properties | Database-backed test required |
| `bamero_zarinpal_bootstrap` | Hook registration, configuration failure mode, and callback routing properties | Merchant sandbox required |
| `bamero_output_entity_graph` | JSON-LD shape, escaping, schema type, and no-sensitive-data properties | Rendered HTML test required |

## §4. RESPONSIVE COMPLIANCE REPORT

The current responsive strategy is media-query-led with a limited container-query foundation. It is not rejected merely for using media queries because the attached standard requires auditing and improvement rather than a technology-prescriptive migration.

The current source already provides logical RTL properties, reduced-motion rules, responsive product grids, focus-visible styling, local font loading, and 44px minimum block sizes for key controls. These are source-level findings; they do not prove rendered WCAG conformance.

Actionable evidence-backed improvements for a later approved phase are:

1. Add rendered browser assertions for interactive bounding boxes and verify the 44×44 CSS-pixel target at representative viewports.
2. Verify rendered WooCommerce image markup and add `srcset`/`sizes` through WordPress image APIs where absent.
3. Replace selected full-height drawer usage with `dvh`/`svh` only after browser testing confirms focus and backdrop behavior.
4. Consolidate cascade conflicts before removing `!important`; the current count is 48 occurrences.
5. Use container queries for reusable components where component width, rather than viewport width, determines layout.
6. Run axe-core, keyboard traversal, and Lighthouse on authorized staging.

No WCAG 2.2 AA PASS is claimed because no rendered browser evidence exists.

## §5. REHABILITATED CODE

Phase 1 intentionally produced characterization tooling and evidence only:

- `tests/characterization_baseline.py` generates the source metrics and executes the baseline gates.
- `docs/verification-evidence/characterization-baseline-2026-09-21.json` records the machine-readable baseline.
- No production behavior was changed during this phase.

The previously implemented storefront modernization remains in place and continues to pass the existing production and invariant checks.

Phase 2 is not started. It requires property-based contracts for critical behavior and explicit user approval before any purge, simplification, or deletion.

## §6. RESIDUAL RISKS & TRADE-OFFS

The largest residual risk is the gap between source-level checks and runtime WordPress behavior. Static success does not prove that active plugins, database versions, browser behavior, payment providers, and deployed headers interact correctly.

The five complexity hotspots may contain maintainability debt, but refactoring them before characterization could introduce regressions in authentication, catalog provisioning, payment, or structured data. Preserving behavior is the safer trade-off until the contracts exist.

The responsive layer can be improved incrementally. A mandatory container-query-only rewrite would increase compatibility and regression risk without evidence that the current approach is unmaintainable. The current audit therefore prefers intrinsic sizing and targeted component queries while retaining compatible viewport fallbacks.

## Approval gate

Per the attached rehabilitation protocol, the process stops here. Before Phase 2 or any deletion/refactor, the project owner must approve the behavioral-contract plan for the five hotspots and provide or authorize a staging runtime for database, browser, payment, and authentication characterization.

## References

[1]: https://www.w3.org/TR/WCAG22/ "Web Content Accessibility Guidelines 2.2"

[2]: https://www.iso.org/standard/78176.html "ISO/IEC 25010:2023 Systems and software engineering"

[3]: https://developer.wordpress.org/themes/advanced-topics/child-themes/ "WordPress Developer Resources: Child Themes and Theme Customization"

[4]: https://developer.woocommerce.com/docs/ "WooCommerce Developer Documentation"
