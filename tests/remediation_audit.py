#!/usr/bin/env python3
import json, pathlib, re, subprocess
ROOT=pathlib.Path(__file__).resolve().parents[1]
def run(cmd): return subprocess.run(cmd,cwd=ROOT,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode==0
def exists(p): return (ROOT/p).is_file() and (ROOT/p).stat().st_size>0
def has(p, pat): return re.search(pat,(ROOT/p).read_text(errors='ignore'),re.I|re.M) is not None
checks=[]
def add(i, area, title, status, evidence, action): checks.append({'id':i,'area':area,'title':title,'status':status,'evidence':evidence,'required_action':action})
php=list(ROOT.rglob('*.php'))
add('S01','code','PHP lint','PASS' if all(run(['php','-l',str(p)]) for p in php) else 'FAIL',f'{len(php)} PHP files','Keep in CI')
add('S02','tests','Production gate','PASS' if run(['bash','tests/production_gate.sh']) else 'FAIL','tests/production_gate.sh','Keep required')
add('S03','security','Schema-before-write','PASS' if run(['bash','tests/no_write_before_schema.sh']) else 'FAIL','11 source-order assertions','Expand with new write paths')
add('S04','security','Monitoring checks','PASS' if run(['bash','tests/monitoring_checks.sh']) else 'FAIL','health route static checks','Run HTTP probe on staging')
add('S05','supply-chain','Remediation preflight','PASS' if run(['bash','tests/supply_chain_preflight.sh']) else 'FAIL','supply_chain_preflight.sh','Run image scanner at deployment')
add('S06','cicd','CI workflow','PASS' if exists('.github/workflows/production-gate.yml') else 'FAIL','.github/workflows/production-gate.yml','Protect branch and require successful run')
add('S07','infra','Production overlay','PASS' if exists('docker-compose.production.yml') and has('docker-compose.production.yml',r'immutable') else 'FAIL','docker-compose.production.yml','Inject approved image digests and secrets')
add('S08','config','Debug defaults','PASS' if has('.env.example',r'WP_DEBUG_LOG=0') and has('setup-env.sh',r'WP_DEBUG_LOG=0') else 'FAIL','.env.example; setup-env.sh','Keep production defaults')
add('S09','security','CSP script policy','PASS' if has('wp-content/plugins/bamero-production-core/bamero-production-core.php',r"script-src.*nonce-") and not has('wp-content/plugins/bamero-production-core/bamero-production-core.php',r'unsafe-eval') else 'FAIL','nonce CSP; no unsafe-eval','Verify deployed headers')
add('B01','runtime','WordPress/WooCommerce boot','BLOCKED','No staging runtime evidence','Run staging smoke')
add('B02','runtime','Database/schema/HPOS','BLOCKED','No runtime DB evidence','Run migration and HPOS integration')
add('B03','external','SMS.ir OTP and delivery','BLOCKED','No credentials/provider logs','Run real provider contract tests')
add('B04','external','Zarinpal payment/callback','BLOCKED','No merchant sandbox evidence','Run success/cancel/replay/verify')
add('B05','e2e','Browser revenue path','BLOCKED','No Playwright staging evidence','Run E2E matrix')
add('B06','performance','Web Vitals p75','BLOCKED','No field/RUM evidence','Collect p75')
add('B07','resilience','Load/race/idempotency','BLOCKED','No load report','Run concurrent staging test')
add('B08','operations','Backup/restore','BLOCKED','No restore evidence','Run checksum restore drill')
add('B09','operations','Rollback/cache rehearsal','BLOCKED','No rollback evidence','Run canary rollback')
add('B10','security','WPScan/dependency scan','BLOCKED','No target/report/token','Run scans on final artifact')
add('B11','observability','Metrics/alerts/SLO','BLOCKED','No backend/alert test','Configure metrics and failure drill')
add('B12','operations','Cron/worker health','BLOCKED','No worker runtime log','Run stale-job/retry drill')
add('B13','deployment','DNS/TLS/permissions','BLOCKED','No deployed host evidence','Run TLS/header/permission scans')
add('B14','supply-chain','Immutable image digests','BLOCKED','Overlay requires owner-supplied digests','Commit approved digests and scan')
add('B15','supply-chain','Exact platform dependency inventory','BLOCKED','No WordPress/WooCommerce lock or SBOM','Commit final inventory/SBOM')
add('B16','frontend','Accessibility deployed pages','BLOCKED','No axe/browser evidence','Run axe and keyboard matrix')
result={'audit':'remediation-reaudit-2026-09-21','controls':len(checks),'pass':sum(x['status']=='PASS' for x in checks),'fail':sum(x['status']=='FAIL' for x in checks),'blocked':sum(x['status']=='BLOCKED' for x in checks),'decision':'GO' if all(x['status']=='PASS' for x in checks) else 'NO-GO','checks':checks}
out=ROOT/'docs/verification-evidence/remediation-reaudit-2026-09-21.json'; out.parent.mkdir(exist_ok=True); out.write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n'); print(json.dumps(result,ensure_ascii=False,indent=2)); raise SystemExit(0 if result['decision']=='GO' else 1)
