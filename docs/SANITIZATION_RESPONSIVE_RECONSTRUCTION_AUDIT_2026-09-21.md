# RADICAL SANITIZATION & RESPONSIVE RECONSTRUCTION AUDIT

**Project:** Bamero WordPress/WooCommerce storefront

**Audit date:** 2026-09-21

## Decision

The attached sanitization rules conflict with the established storefront requirement to preserve existing functionality, WooCommerce integrations, Persian RTL support, content, accessibility, and working components. A destructive rewrite cannot be executed safely from the supplied archive because no live staging runtime or browser regression environment is available.

The repository therefore remains unchanged by this pass. The evidence-backed result is a **sanitization audit and reconstruction specification**, not an unverified destructive rewrite.

## §1. DELETION MANIFEST

No deletion was approved.

The candidate files below were checked for references and remain in active use:

| Candidate | Evidence of use | Decision |
|---|---|---|
| `css/components-production.css` | Enqueued by `bamero_scripts()` | Keep |
| `css/icons.css` | Enqueued by `bamero_scripts()` | Keep |
| `css/woocommerce.css` | Enqueued by `bamero_scripts()` | Keep |
| `css/woocommerce-rtl.css` | Enqueued by `bamero_scripts()` and required by RTL storefront | Keep |
| `style.css` | Theme stylesheet loaded by WordPress | Keep |
| `js/main.js` | Enqueued by `bamero_scripts()` and contains menu, cart, checkout, tabs, and accessibility behavior | Keep |
| WooCommerce templates | Loaded through WooCommerce template resolution and `wc_get_template_part()` | Keep |

Deleting any of these files without a live visual and revenue-path regression run would violate the original preservation requirement. No source file was proven dead by the available evidence.

Comments, section labels, and documentation were not treated as dead code. A comment is not executable code, and deleting it would not establish a functional improvement. `TODO`, `FIXME`, and `console.log` findings require line-level inspection before removal; no destructive removal was justified solely by a text match.

## §2. RESPONSIVE ARCHITECTURE SPEC

The requested target architecture is feasible as a staged migration, not as a blind rewrite.

### Container strategy

Use component wrappers with `container-type: inline-size` for the product grid, header actions, category strip, cart table, and checkout columns. Use `@container` rules as the primary component adaptation mechanism. Retain a small set of viewport media-query fallbacks for browser compatibility and page-level navigation behavior, especially the mobile drawer and full-page checkout composition.

The current code has **26 media-query occurrences** and **2 container-query occurrences**. This is evidence that the current implementation is media-query-led rather than container-query-led. It is not evidence that all media queries are dead or interchangeable.

### Fluid type scale

New or rewritten components should use a bounded scale such as:

```css
--text-body: clamp(1rem, 0.96rem + 0.18vw, 1.125rem);
--text-label: clamp(0.78rem, 0.74rem + 0.1vw, 0.9rem);
--text-title: clamp(1.6rem, 1.15rem + 2.2vw, 3.4rem);
```

Existing fixed pixel sizes should be migrated only after a rendered comparison confirms that WooCommerce notices, quantity controls, payment fields, and Persian glyphs remain legible and aligned.

### Intrinsic sizing

Prefer `min()`, `max()`, `clamp()`, `minmax()`, `fit-content()`, and logical properties for new layout rules. Existing fixed dimensions that define touch controls or image aspect ratios should not be removed without checking layout stability.

### Touch targets

The existing source declares a minimum block size of 44px for primary interactive controls. This is a static source property, not a mathematical proof over every rendered element. The reconstruction test should compute the rendered bounding boxes of links, buttons, quantity controls, menu toggles, and form controls at representative desktop and mobile viewports and fail if either dimension is below 44 CSS pixels.

### Mobile viewport units

New full-height drawers should use `dvh` with a fallback strategy. Existing navigation uses `height: 100vh`; this should be replaced only in a browser-tested patch because drawer focus trapping, backdrop coverage, and iOS browser chrome behavior are runtime properties.

### Images

The reconstruction target is responsive image markup with WordPress-generated `srcset` and `sizes`. The current source includes direct image markup and WooCommerce image helpers, and a simple textual scan found **11 `<img>` tags with no detectable `srcset` token**. This is an actionable audit finding. It must be remediated through WordPress image APIs and then verified in rendered HTML, not by adding arbitrary attributes to templates.

### Layout constraints

Grid and flexbox should remain the layout primitives. Absolute or fixed positioning is acceptable for overlays, badges, focusable drawers, floating support controls, and decorative artwork when it does not define the primary document flow. The current source has **13 absolute/fixed-position rules**; each must be classified before removal.

## §3. SANITIZED CODEBASE

No destructive rewrite was applied because the required proof obligations were unavailable.

Measured current-state findings are:

| Rule | Measurement | Interpretation |
|---|---:|---|
| Media-query occurrences | 26 | Current design is not container-query-first |
| Container-query occurrences | 2 | Existing foundation is present but incomplete |
| `!important` occurrences | 48 | Requires cascade consolidation, not blind deletion |
| Absolute/fixed-position rules | 13 | Some are likely overlays or drawers; classify individually |
| Pixel-based font declarations | 20 | Requires fluid-scale migration and visual regression |
| Image tags | 11 | Responsive source attributes require template/API work |
| Image tags with detectable responsive attributes | 0 by simple scan | Must verify rendered WooCommerce output before final claim |

The existing modern layer remains the smallest reversible change because it is loaded after the legacy layers and preserves WooCommerce template and hook contracts. Replacing all styles would materially change behavior and requires a product decision if visual regression reveals incompatibility.

## §4. VALIDATION EVIDENCE

Passed before and during this audit:

- `bash tests/production_gate.sh`: PASS; PHP syntax and repository code-level controls passed.
- `python3 tests/storefront_modern_invariants.py`: PASS; all seven storefront invariants passed.
- `bash tests/ui_ux_static_checks.sh`: PASS; local font, RTL logical rules, reduced motion, and CSS-token checks passed.
- `bash tests/supply_chain_preflight.sh`: PASS; static supply-chain controls passed.

Not available and therefore not claimed:

- axe-core or browser keyboard audit.
- Lighthouse scores.
- Visual regression against a live WordPress/WooCommerce runtime.
- Rendered image `srcset` and `sizes` verification.
- Mathematical bounding-box verification of all 44px touch targets.
- Mobile `dvh` behavior under browser chrome changes.
- Payment, cart, checkout, database, and integration smoke tests.

A strict sanitization PASS would require all of the above plus a staged deletion checkpoint and a rollback path. The archive does not provide those prerequisites.

## §5. DEPLOYMENT CHECKLIST

Before any destructive cleanup is accepted:

1. Provision authorized staging with the exact WordPress, WooCommerce, PHP, database, theme, and plugin versions.
2. Capture baseline screenshots and executable revenue-path tests for home, archive, category, product, cart, checkout, login, and contact flows.
3. Add rendered HTML assertions for `srcset`, `sizes`, accessible names, and localized RTL content.
4. Add Playwright or equivalent bounding-box assertions for 44px controls at desktop and mobile viewports.
5. Run axe-core and Lighthouse in the staging browser environment.
6. Classify every absolute/fixed rule and every `!important` rule by dependent behavior before removal.
7. Apply changes in reversible checkpoints. Do not delete a file that is still enqueued or loaded by a WordPress template.
8. Re-run PHP lint, static security checks, WooCommerce smoke tests, visual regression, and browser accessibility tests after each checkpoint.
9. Rehearse rollback before production deployment.

### Reproducible local checks

```bash
bash tests/production_gate.sh
python3 tests/storefront_modern_invariants.py
bash tests/ui_ux_static_checks.sh
bash tests/supply_chain_preflight.sh
```

All four repository-level checks passed in the verification environment. This result is a code-level status, not proof of production deployment readiness.

## Final status

**Sanitization:** NOT EXECUTED destructively; no safe deletion candidates were proven.

**Responsive reconstruction:** SPECIFIED, partially implemented by the existing modernization layer, but not fully migrated to container-query-first architecture.

**Deployment readiness:** BLOCKED pending runtime, browser, visual-regression, accessibility, performance, and integration evidence.
