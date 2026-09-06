# webfactory-build (Claude Code plugin marketplace)

Public, self-serve distribution for the `webfactory-build` Claude Code
skill — lets any developer author a [360 Web Factory](https://360.bridging.co.tz/websites)
website as plain HTML/CSS in Claude Code instead of the drag-and-drop
canvas, then package and import it as a real draft site.

This repo is a thin wrapper: a plugin marketplace manifest pointing at
the two files that make up the skill itself, under `skills/webfactory-build/`
(`SKILL.md` is the full authoring reference — markers, forms, sanitizer
rules, verification tips; this README is the "I'm new, walk me through it"
version).

**No SSH, `.env`, or server login to the 360 app server is required for any
of this.** Everything below runs from your own laptop plus a normal browser
login to `https://360.bridging.co.tz`.

## Who this is for

A developer who:
- has (or can get) a 360 account with `website-builder` permission for a
  team, but
- does **not** have shell/SSH access to the app server, and
- wants to design a Web Factory website faster than dragging blocks in the
  GrapesJS canvas.

## Prerequisites

- Claude Code (CLI, desktop, web, or an IDE extension — any surface works).
- A `php` CLI on your own machine (any reasonably recent version). It only
  runs one standalone script with zero dependencies — no Laravel, no
  Composer install, no database.
- A 360 login at `https://360.bridging.co.tz` with `website-builder`
  permission for whichever team you're building the site for.

## Step-by-step

### 1. Get the skill onto your machine

Two ways to do this — pick one:

**A. Claude Code plugin install (recommended, no clone needed):**
```
/plugin marketplace add bridging-technologies/360-webfactory-build-skill
/plugin install webfactory-build@webfactory-build-skill
```
Run both inside any Claude Code session. This pulls the skill straight
from GitHub and makes it available in every session on that machine.

**B. Clone this repo and copy the two files by hand:**
```
git clone git@github.com:bridging-technologies/360-webfactory-build-skill.git
```
Then copy `skills/webfactory-build/SKILL.md` and
`skills/webfactory-build/assemble.php` into either:
- `.claude/skills/webfactory-build/` inside whatever project you're
  working from (scopes the skill to that project), or
- `~/.claude/skills/webfactory-build/` (available in every project on
  your machine).

Either path gets you the same two files — nothing else in this repo is
needed for building a site.

### 2. Start a Claude Code session and describe the site

Open Claude Code anywhere (a scratch folder is fine — it doesn't need to
be inside any particular project) and just ask for what you want, e.g.:

> "Build a Web Factory site for a restaurant called Kukuz — brand color
> `#c0392b`, pages for Home, Menu, and Contact."

Claude will pick up the `webfactory-build` skill automatically and:
- ask follow-up questions about the business and each page's content
  rather than inventing details for a real client, and
- confirm the exact **business type** name against the live dropdown at
  `https://360.bridging.co.tz/websites/create` (this has to match a real
  `website_types` row exactly, e.g. "Restaurant & Food Service", "Retail &
  Shop", "Professional Services" — it'll check the live list rather than
  guess).

### 3. Let it lay out the working directory

Claude authors a small directory like:
```
webfactory-drafts/kukuz/
  site.json       # name, brand_color, website_type, ...
  pages.json      # one entry per page, exactly one is_home: true
  home.html  home.css
  menu.html  menu.css
  contact.html  contact.css
```
Plain HTML/CSS per page. For anything beyond static content — a nav menu
that lists your real pages, a live products/services/blog grid, a contact
form — Claude writes the specific `data-wbx-*` markers `SKILL.md`
documents; you don't need to know these up front, just be aware that
**no inline `<script>` or `on*` handlers survive import** (the sanitizer
strips them silently), so anything interactive has to go through those
markers instead.

### 4. Assemble the export file

```
php assemble.php webfactory-drafts/kukuz webfactory-drafts/kukuz.json
```
(`assemble.php` is wherever you placed it in Step 1.) This validates
`site.json`/`pages.json`, checks every referenced HTML/CSS file exists,
and writes a single `webfactory-export` JSON file — the exact format the
live site's own **Export** button produces and its **Import** form
consumes. It errors out with a specific reason before writing anything if
something's missing or malformed.

### 5. Import it through the browser (your access path — no SSH needed)

1. Log into `https://360.bridging.co.tz`.
2. Go to **Web Factory → Import** (`/websites/import`).
3. Choose the `.json` file from Step 4, submit.
4. On success you land directly on the new draft's editor page.

A failure here shows a specific reason (invalid file, unknown business
type, no template for that type) — the most common one is the business
type string not matching a real row exactly; re-check Step 2 and
re-upload. Nothing in this flow ever touches billing or publishes
anything: every import lands as an **unpublished draft**, under whichever
team's account you imported into.

> There's also a `php artisan website:import` CLI command that does the
> same thing in one line — but it requires shell access to the app
> server, which is exactly what this guide assumes you don't have. If you
> ever do get server access, `SKILL.md` documents it as a faster
> alternative to this same step.

### 6. Review, then publish when ready

Open the new draft's GrapesJS editor, read through each page, tweak
anything by hand (images, spacing, a card you want to reorder — all still
fully editable in the normal canvas), and hit **Publish** yourself when
it's ready. This skill never publishes for you, and a bad import can
always be deleted from the Web Factory list and re-imported from scratch.

## Reference

The full authoring reference — every `data-wbx-*` marker, the exact form
field shapes for contact/newsletter/booking, sanitizer rules, and how to
verify a build before importing — lives in
[`skills/webfactory-build/SKILL.md`](skills/webfactory-build/SKILL.md).
Claude reads this automatically once the skill is installed; it's worth a
skim yourself if you want to hand-edit a page's HTML directly instead of
asking Claude to.

## Publishing an update to this skill (for maintainers)

1. Update the files under `skills/webfactory-build/` (`SKILL.md`,
   `assemble.php`) with the change.
2. Bump `version` in `.claude-plugin/marketplace.json`.
3. Commit and push.

Existing installs don't auto-update — users re-run both commands from
Step 1A above (`marketplace add` refreshes the manifest, `install` pulls
the new content).
