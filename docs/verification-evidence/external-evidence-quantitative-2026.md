# External evidence for quantitative readiness

- Google SRE Workbook: SLI is good events divided by total events. Error budget is 100% minus the SLO. Source: https://sre.google/workbook/implementing-slos/
- web.dev field measurement: Core Web Vitals should be reported at the 75th percentile; averages can be misleading. Source: https://web.dev/articles/vitals-field-measurement-best-practices
- web.dev Web Vitals: recommended good thresholds are LCP <= 2.5 s, INP <= 200 ms, CLS <= 0.1. Source: https://web.dev/articles/vitals
- NIST IR 6129: black-box conformance testing can use binomial inference, confidence bounds and sequential tests; assumptions include test independence and coverage of functionality. Source: https://nvlpubs.nist.gov/nistpubs/Legacy/IR/nistir6129.pdf
- NIST discussion: exact confidence intervals can be obtained with Clopper-Pearson; stopping criteria should reflect risk/cost and testing should stop on critical failure. Source: same NIST source above.
