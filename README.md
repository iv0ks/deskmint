# Deskmint

[![GHCR](https://img.shields.io/badge/ghcr.io-iv0ks%2Fdeskmint-blue?logo=docker)](https://github.com/iv0ks/deskmint/pkgs/container/deskmint)


**Deskmint is an intentionally vulnerable application built for security education.** It is a small helpdesk / ticketing app with a deliberate [path traversal](https://owasp.org/www-community/attacks/Path_Traversal)
(CWE-22) flaw — hidden behind a naive, non-recursive `../` filter — so you can practice finding and exploiting it in a safe and controlled environment.

<br>

## 🗂️ Project layout

The repository is organised so the vulnerable code is easy to find and reason
about. Three areas, clearly separated: the **application** (PHP) with its public
front controllers in `public/` and its logic in `src/`, the **served data** in
`attachments/` (the directory the app is *meant* to expose), and the **tooling**
around it (Docker image with Apache + mod_php, CI/CD, linters). The challenge
flag is deliberately kept *out* of both the repo and the served directory — see
the note under the tree.

```
deskmint/
├── public/                 # web root (DocumentRoot, served by Apache)
│   ├── index.php           # ticket list (home)
│   ├── ticket.php          # single ticket view + attachments
│   ├── attachment.php      # the VULNERABLE download endpoint
│   ├── style.css           # helpdesk UI styles (with dark mode)
│   └── favicon.svg
├── src/                    # application logic (PSR-4 Deskmint\)
│   ├── Attachment.php      # the broken "../" filter (the bug lives here)
│   ├── Config.php          # paths resolved from DATA_ROOT
│   ├── Tickets.php         # demo ticket data
│   └── View.php            # layout / rendering helpers
├── attachments/            # served ticket files (the "allowed" directory)
├── tests/                  # PHPUnit tests (assert the traversal)
├── tools/traefik/          # reverse-proxy config (static + dynamic routing)
├── SOLUTION.md             # exploit writeup + the fix (spoilers)
├── SECURITY.md             # security policy (it's a lab — read before reporting)
├── .github/workflows/      # integration · delivery · security · release
├── Dockerfile              # single image: php:8.3-apache (mod_php)
├── docker-compose.yml      # deskmint + traefik stack
├── composer.json           # autoload + dev tools (phpunit, php-cs-fixer, phpstan)
├── phpstan.neon · .php-cs-fixer.php · phpunit.xml
└── .env.example            # Traefik ACME credentials template (copy to .env)
```

The PHP code lives in `public/` + `src/` and reads its files from
`attachments/`, just like a real helpdesk would. The target `flag.txt` is
**not** in the repo: the Docker image writes it to `/app/flag.txt` at build
time — *outside* the served `attachments/` dir — so traversal can reach it while
the flag stays out of version control.

<br>

## 🚀 The Lab

Deskmint runs as a Docker Compose stack:

- **`deskmint`** — the helpdesk app: a single `php:8.3-apache` image where
  Apache serves static files and executes the PHP directly. Published on `8099`.
- **`traefik`** — a reverse proxy that serves the app over HTTPS on a real
  hostname. Optional: only needed for the domain-based access.

Because the stack attaches to an **external** Docker network (`iv0ks-dev`),
create it once before the first run:

```bash
docker network create iv0ks-dev
```

#### Option A — just the app

Start only the `deskmint` service (Compose skips `traefik` when you name a
service explicitly):

```bash
docker compose up -d --build deskmint
```

The helpdesk is now reachable directly at:

- **<http://localhost:8099>**

That is all you need to start playing with Burp Suite.

#### Option B — full stack with Traefik (HTTPS + hostname)

Bring up everything to also expose the app through Traefik with TLS:

```bash
docker compose up -d --build
```

Now the *same* app is reachable two ways at once:

- **<http://localhost:8099>** — direct, no TLS (handy for quick testing).
- **<https://deskmint.iv0ks.dev>** — proxied by Traefik with a real certificate.

Traefik routing is defined in `tools/traefik/`. TLS uses the ACME DNS-01
challenge via Namecheap, so Traefik needs those API credentials — copy
`.env.example` to `.env` and fill them in (the `.env` file is gitignored).

#### Developing without Docker

For quick frontend iteration you can use PHP's built-in server (the flag is
seeded locally and gitignored):

```bash
printf 'FLAG{DESKMINT_ROOT_TICKET}\n' > flag.txt
DATA_ROOT="$PWD" php -S 127.0.0.1:8099 -t public
```

<br>

## 🎯 The Challenge

Deskmint lets support agents download the files attached to a ticket. The
download endpoint tries to keep you inside the attachments folder by stripping
`../` from the filename — but it only does so **once**. Your goal:

> **Read a file that lives _outside_ the attachments directory — specifically,
> capture the flag stored in `flag.txt`.**

The flag has the format `FLAG{...}`.

| | |
|---|---|
| **Vulnerability** | Path Traversal (CWE-22) |
| **Difficulty** | ⭐⭐⭐ Level 3 — naive non-recursive `../` filter |
| **Stack** | PHP · Apache (mod_php) |
| **Entry point** | `GET /attachment.php?file=<name>` |

<details>
<summary>Hint 1</summary>
Open a ticket and watch the <code>Download</code> request in Burp. The filename
goes straight into a <code>?file=</code> parameter.
</details>

<details>
<summary>Hint 2</summary>
A plain <code>../</code> gets blocked — there is a filter. But does it run more
than once? What happens to <code>....//</code> after a single strip of
<code>../</code>?
</details>

A full walkthrough lives in [SOLUTION.md](SOLUTION.md) (spoilers!).

New to path traversal, or want a guided, non-spoiler approach? Start with the
**[Learning Guide](GUIDE.md)**.
