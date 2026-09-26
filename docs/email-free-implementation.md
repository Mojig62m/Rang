# Email-free implementation policy

All user-facing identity and transaction notifications use normalized mobile numbers and SMS. Platform compatibility identifiers are non-routable and are never used for delivery. SMS is queued through the transactional outbox; checkout and payment callback requests never call a provider directly.
