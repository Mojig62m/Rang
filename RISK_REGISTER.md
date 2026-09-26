# Risk register

| ID | Risk | Severity | Current control/status | Required next evidence |
| --- | --- | --- | --- | --- |
| R-01 | Live payment/email delivery cannot be proven without configured providers. | High | No credentials were added; consultation mail failure is surfaced. | Sandbox mail and gateway tests with authorized credentials. |
| R-02 | WordPress/WooCommerce runtime is absent, so hooks, templates, checkout and product provisioning cannot be integration-tested. | High | PHP syntax and static checks only. | Disposable WordPress + WooCommerce test environment. |
| R-03 | Required third-party plugins and Zarinpal gateway have supply-chain/compatibility risk. | Medium | Auto-install removed; manual documented install required. | Version-pinned, reviewed plugin selection and vulnerability scan. |
| R-04 | Contact/newsletter collection requirements (consent, retention, delivery provider) are unspecified. | Medium | Inert forms are documented; no personal-data storage was introduced. | Product/privacy requirements and integration tests. |
| R-05 | Missing images and third-party font CDN can degrade layout/privacy/availability. | Medium | Not altered without an approved asset source. | Local licensed assets or approved CDN and browser checks. |
| R-06 | RTL, responsive behavior, keyboard flow, contrast, and font rendering are not browser-verified. | Medium | Static review only. | Playwright/axe or manual viewport and assistive-technology testing. |

| R-07 | Staging cannot execute in this runner because Docker/database daemon are unavailable. | High | Reproducible Compose stack added; no fake runtime claims. | Run stack on Docker-capable host and capture browser evidence. |
