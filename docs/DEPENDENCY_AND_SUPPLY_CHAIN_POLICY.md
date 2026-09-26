# Dependency and supply-chain policy

WordPress and WooCommerce are runtime platform dependencies and must be provisioned at exact, approved versions in the deployment manifest. Production container images must be referenced by immutable digest through `docker-compose.production.yml`; mutable development tags in `docker-compose.yml` are local-only.

Every release must produce an SBOM, scan the source and container images for known vulnerabilities, store the report with the artifact checksum, and block unresolved high or critical findings. The CI workflow enforces repository lint and static gates; the deployment operator must additionally run the image and dependency scanner because the sandbox does not contain the production image registry or runtime.

No package manager lockfile is fabricated for WordPress core or WooCommerce. The absence of a lockfile is intentional for the current source-only bundle and remains a deployment prerequisite until the exact platform inventory is committed by the deployment owner.
