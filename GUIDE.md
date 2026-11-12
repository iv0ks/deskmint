# Deskmint — Learning Guide

> A guided, **non-spoiler** path to solving this lab. It teaches the *method*,
> not the answer — the exact winning payload and the flag stay in
> [SOLUTION.md](SOLUTION.md).

## Before you start

Read the shared **[Path Traversal field guide](https://github.com/iv0ks/appsec-labs/blob/main/docs/path-traversal.md)**
first. It covers what path traversal (CWE-22) is, why it appears, and the manual
testing workflow with **Burp Suite** and **OWASP ZAP**. This guide assumes it.

## What this level teaches

**Level 3 — a blocklist filter that strips `../`.** Deskmint's attachment
endpoint "sanitises" the filename by removing `../` sequences before using it.
It *feels* safe, but it is a blocklist, and this one runs **only once** (a
single, non-recursive pass). The lesson: blocklists for traversal are brittle,
and a filter that does not re-scan its own output can be defeated by a payload
that *reconstructs* the forbidden sequence after the strip.

- **Entry point:** `GET /attachment.php?file=<name>`
- **The broken defence:** a single-pass `../` strip.
- **Served directory:** `attachments/` · **target:** a file one level above it.

## How to approach it

1. Open a ticket, download an attachment through your proxy, and capture the
   `file` parameter.
2. Baseline the legitimate value, then try a plain `../`. Notice it is
   neutralised — the filter *is* doing something.
3. Now think like the filter: it removes `../` **once**. Can you craft an input
   where removing one `../` *leaves another one behind*? Overlapping / nested
   sequences are the idea.
4. Keep the path as-is and confirm the escape to the target above
   `attachments/`.

## Going further

Blocklists fail in many directions at once — once you have the non-recursive
bypass, consider how encodings or backslashes might defeat *other* naive
filters (later levels explore exactly that).

## Why it matters

"We strip the dangerous characters" is an extremely common (and extremely
bypassable) real-world defence. Seeing a single-pass strip fall to a nested
payload builds the instinct to distrust blocklists and reach for
canonicalisation instead.

When you have captured the flag (or want to check the fix), read
[SOLUTION.md](SOLUTION.md).
