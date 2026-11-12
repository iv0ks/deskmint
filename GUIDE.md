# Deskmint: Learning Guide

![CWE-22](https://img.shields.io/badge/CWE--22-Path%20Traversal-red?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-Apache-777BB4?style=flat-square&logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Lab-Docker-2496ED?style=flat-square&logo=docker&logoColor=white)

**Deskmint is an intentionally vulnerable application built for security education.** It is a small helpdesk / ticketing app with a deliberate [path traversal](https://owasp.org/www-community/attacks/Path_Traversal)
(CWE-22) flaw — hidden behind a naive, non-recursive `../` filter — so you can practice finding and exploiting it in a safe and controlled environment.

<br>

> [!TIP]
> A guided, **non-spoiler** path to solving this lab. It teaches the *method*, not the answer — the exact winning payload and the flag stay in [SOLUTION.md](SOLUTION.md).

<br>

## 🧬 What path traversal is

Here is the whole idea in one sentence:

> **Path traversal happens when an app builds a file path out of user input and
> then trusts that path to stay where it's supposed to.**

A web app usually wants to serve files from *one* folder — here, `attachments/`.
The intended deal is: "give me a filename, I'll give you that attachment *from
this folder*." The catch is that the folder boundary is a **convention**, not a
**rule** the code enforces. Filesystems understand `..` as "go up one level", so
a filename like `../something.txt` is a perfectly valid instruction to leave the
folder. If the check for it is weak, the app obeys.

Think of `attachments/` as a room and the filename as directions.
"`vpn-client.log`" means "the log file in this room." "`../something`" means
"step out the door, then grab a file in the hallway." Deskmint *does* put a lock
on the door this time — it just picked a lock that's trivially jiggled open.

That "filename → file read" shape is the **sink** you're training your eye to
spot. Once you can recognise it on sight, you can find this bug in seconds.

> Want the deeper background — history, variants, encoding tricks, and the full
> manual-testing workflow with **Burp Suite** and **OWASP ZAP**? Read the shared
> **[Path Traversal field guide](https://github.com/iv0ks/appsec-labs/blob/main/docs/path-traversal.md)**.
> This lab is a hands-on application of it.

<br>

## 🎓 What this level teaches

**Level 3 — a blocklist filter that strips `../`.** Deskmint "sanitises" the
filename by removing `../` sequences before using it. It *feels* safe, but it is
a blocklist, and this one runs **only once** (a single, non-recursive pass). The
lesson: blocklists for traversal are brittle, and a filter that does not
re-scan its own output can be defeated by a payload that *reconstructs* the
forbidden sequence after the strip.

- **Entry point:** `GET /attachment.php?file=<name>`
- **The broken defence:** a single-pass `../` strip.
- **Served directory:** `attachments/` · **target:** a file one level above it.

## 🪧 How to approach it

1. Open a ticket, download an attachment through your proxy, and capture the
   `file` parameter.
2. Send it to Repeater / Request Editor and re-send the known-good value for
   your **baseline**.
3. Now probe: a plain `../` is neutralised — the filter *is* doing something.
   Think like the filter: it removes `../` **once**. Can you craft an input
   where removing one `../` *leaves another one behind*? Overlapping / nested
   sequences are the idea.
4. Confirm the escape to the target above `attachments/`. Keep the path
   **as-is** so it isn't normalised before it is sent (`curl --path-as-is`, or
   edit the raw request in your proxy).

## 🕵️‍♂️ Going further

Once you have the non-recursive bypass, chain it to climb several levels and
read files elsewhere on the container filesystem. Blocklists fail in many
directions at once — later levels explore how *encodings* defeat other naive
filters. The labs run as a non-root user inside Docker, so the bug is real but
the blast radius is contained on purpose.

## 🤔 Why it matters

"We strip the dangerous characters" is an extremely common — and extremely
bypassable — real-world defence. Seeing a single-pass strip fall to a nested
payload builds the instinct to distrust blocklists and reach for
canonicalisation instead.

---

When you have captured the flag (or want to check the fix and the *why* behind
it), continue to **[SOLVE → SOLUTION.md](SOLUTION.md)**.
