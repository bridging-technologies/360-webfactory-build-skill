---
name: webfactory-build
description: Author a team's Web Factory website (the /websites GrapesJS builder) by hand-writing HTML/CSS pages in Claude Code instead of dragging blocks in the canvas, then package and import them as a real draft website. Use when asked to build, design, or scaffold a WebFactory site, or to speed up website authoring for a team.
---

# Building a Web Factory site in Claude Code

Web Factory (`/websites`, publicly `https://360.bridging.co.tz`) is this
app's GrapesJS-based site builder. Driving its block canvas by hand is the
normal path for a non-technical team member, but it's slow going through
Claude Code — hand-writing the HTML/CSS directly gets the same result
faster. This skill authors a site as plain files, packages them into the
same JSON format the builder's own Export button produces, and gets that
file imported through the exact code path a browser upload would use — so
nothing about the result is second-class: it lands in `website_pages`/
`website_page_versions` like any other site, opens fine in the GrapesJS
editor afterward, and goes through the same hosting-fee gate before it can
publish.

**No server access required.** `assemble.php` is a standalone PHP script —
no Laravel, no `.env`, no database, no `vendor/` install. Everything up to
"you have a JSON file" runs on any machine with a plain `php` CLI. Getting
that file *into* 360 then just needs a browser and a 360 login — see
"Importing the file" below. A separate, faster path exists for anyone who
does have shell access to the app server, but it's optional.

## The pieces

- `SKILL.md` (this file) + `assemble.php` (next to it) — the entire skill.
  Two files, no other dependency. Safe to copy anywhere.
- `assemble.php` turns a directory of hand-written pages into a
  `webfactory-export` JSON envelope (the same format the live site's
  **Export** button on an existing website produces, and its **Import**
  form at `/websites/import` consumes).

## Using this on a machine that isn't this server

If you're a developer working from your own laptop against
`https://360.bridging.co.tz` rather than this repo/server directly, you
still get the full skill — just copy these two files:

- Into a project's `.claude/skills/webfactory-build/` to scope it to that
  project, **or**
- Into your user-level `~/.claude/skills/webfactory-build/` to have it in
  every Claude Code session on that machine, regardless of which project
  you're in.

Either way, you need nothing else from this repo — no clone, no server
login, no `.env`. Just a local `php` CLI (any reasonably recent PHP works;
`assemble.php` has zero external dependencies) and a browser session
logged into `https://360.bridging.co.tz` for the final import step.

If you *do* have this repo cloned, the skill is already there — nothing to
copy, it's committed at `.claude/skills/webfactory-build/`.

## Workflow

1. **Gather requirements** conversationally: business name, brand color
   (hex), which pages, and what goes on each. Ask the user rather than
   inventing content for a real client site.

2. **Confirm the business type name.** `website_type` in `site.json` must
   match one of 360's business-type names *exactly*, or the import fails
   with "Unknown website type" (in the browser, or on the CLI). Two ways
   to check, no server access needed for either:
   - Log into `https://360.bridging.co.tz`, open `/websites/create`, and
     read the options in the "Business Type" dropdown — this is always
     current, since it's the live list.
   - As of 2026-09-04 the list was: **Restaurant & Food Service, Retail &
     Shop, Professional Services, Medical & Health, Schools & Training,
     NGOs & CBOs, Media & Journalism**. Treat this as a fallback, not a
     source of truth — it can change; the dropdown always wins if the two
     disagree.

   (Anyone with server shell access can instead run
   `php artisan tinker --execute="echo App\Models\Website\WebsiteType::pluck('name')->implode(', ');"`
   for the same list.)

   `website_template` in `site.json` is optional — leave it out and the
   import falls back to that type's first active template. This only
   satisfies a required FK on `Website`; it has no effect on the content
   you're authoring, since every page's content comes from your files, not
   the template.

3. **Lay out a working directory**, e.g. `webfactory-drafts/<slug>/`
   (anywhere on your machine — it doesn't need to be inside this repo):
   ```
   site.json
   pages.json
   home.html
   home.css
   about.html
   about.css
   ```
   `site.json`:
   ```json
   {
     "name": "Acme Traders",
     "brand_color": "#0d6efd",
     "website_type": "Professional Services",
     "sitemap_enabled": true,
     "robots_directive": "index,follow"
   }
   ```
   (`website_type` must be one of this install's actual `website_types.name`
   rows from step 2 above — e.g. "Restaurant & Food Service", "Retail &
   Shop", "Professional Services", "Medical & Health", "Schools & Training",
   "NGOs & CBOs", "Media & Journalism" — not a business-type CodeValue from
   anywhere else in the app; check with the command in step 2, don't assume
   this list is current.)
   `pages.json` — exactly one entry needs `"is_home": true`:
   ```json
   [
     {"slug": "", "title": "Home", "is_home": true, "sort_order": 0,
      "meta_title": "Acme Traders", "html_file": "home.html", "css_file": "home.css"},
     {"slug": "about", "title": "About", "is_home": false, "sort_order": 1,
      "html_file": "about.html", "css_file": "about.css"}
   ]
   ```

4. **Write each page's HTML/CSS** — see "Authoring rules" below before
   writing anything.

5. **Assemble and sanity-check the JSON**:
   ```
   php assemble.php webfactory-drafts/<slug> webfactory-drafts/<slug>.json
   ```
   (path to `assemble.php` depends on where you placed the skill — see
   "Using this on a machine that isn't this server" above)

6. **Import it — pick whichever path matches your access:**

   **A. Browser import (works for anyone with a 360 login, no server access):**
   1. Log into `https://360.bridging.co.tz`.
   2. Go to `/websites/import` (or Web Factory → Import).
   3. Choose the `.json` file `assemble.php` just wrote, submit.
   4. On success you land straight on the new draft's editor page — done.
      A failure here (invalid file / unknown type / no template) shows the
      same flash message either import path would give; fix per step 2
      and re-upload.

   **B. CLI import (only if you have shell access to the app server):**
   ```
   php artisan website:import webfactory-drafts/<slug>.json --team=<team-slug>
   ```
   `--team` takes the team's **slug** (the same one in its 360 URLs), not a
   numeric id. Prints the new website's id and edit URL. Faster for anyone
   already on the server (e.g. scripting several imports at once), but not
   required — path A does exactly the same thing through the browser.

   Either way: this creates real rows for whichever team owns the import —
   confirm which team/account before importing under someone else's login,
   don't guess. It always lands as a **draft**; nothing here ever publishes
   or touches billing.

   **Updating an already-imported site** (CLI only): after editing your
   local `.html`/`.css`/`pages.json` and re-running `assemble.php`, re-run
   the import with `--update=<website id or slug>` instead of creating a
   duplicate site:
   ```
   php artisan website:import webfactory-drafts/<slug>.json --team=<team-slug> --update=<website-slug>
   ```
   This upserts pages by slug — existing pages get their draft content
   refreshed, pages not seen before are added, and nothing not in the file
   is removed unless you also pass `--prune-pages`. It only ever touches
   the **draft** version of each page, so a live published page keeps
   showing its old content until you re-publish from the editor. There is
   no browser-based equivalent yet — updating an existing site currently
   requires CLI/server access; without it, re-edit by hand in the GrapesJS
   canvas instead of re-importing.

7. **Hand it back**: open the new draft's edit page to eyeball it, tweak
   anything by hand in the GrapesJS canvas, then publish through the normal
   Publish button when ready. This skill never publishes for you.

## Authoring rules (read before writing HTML)

Saved HTML goes through `App\Services\Website\HtmlSanitizer` on import,
which **silently strips**, not errors on:

- `<script>`, `<object>`, `<embed>` tags entirely
- every `on*` attribute (`onclick`, `onload`, …)
- `javascript:`/`data:` URLs in any `href`/`src`
- an `<iframe src>` that isn't `https://`

So: **no inline JavaScript, ever** — it will vanish without warning. All
interactivity comes from the markers below, which the public renderer
(`PublicWebsiteController::renderPageHtml()`) expands server-side at
request time, and from `public_html/js/website-runtime.js`
(mobile nav, sliders, reveal animations, form submission), which is
already wired into every page — you don't add a `<script>` tag for it,
you just use the markup it looks for.

**Live, self-updating blocks** (write these markers, don't hardcode data):

| Marker | On | Behavior |
|---|---|---|
| `data-wbx-auto-nav="1"` | a `<nav>`/container | Filled with real `<a>` links to this site's actual pages, current page marked `class="active"` |
| `data-wbx-source="products"` + `data-wbx-limit="6"` | a container | Filled with the team's real for-sale, active products (image, name, price), each linking to its live product detail page |
| `data-wbx-source="services"` | a container | Filled with the team's real published services |
| `data-wbx-source="blog"` + `data-wbx-limit="3"` | a container | Filled with the team's real published blog posts (image, title, summary, date), each linking to its live post page |
| `data-wbx-lang-switch="1"` | a container | Links between this page's locale siblings (removed entirely if there's only one locale) |
| `data-hide-on="mobile"` / `data-hide-on="desktop"` | any element | CSS-only responsive visibility, already in `_base_styles.blade.php` |
| `data-show-after="Y-m-d\TH:i"` / `data-show-until="…"` | any element | Element is dropped server-side outside that window |

**Photo gallery with lightbox** — copy this shape exactly (the `wbx-gallery-trigger`
button classes are what `website-runtime.js` hooks to open the popup viewer;
plain `<img>` tags with no wrapper button will just render as static images):
```html
<section class="wbx-gallery">
  <div class="wbx-grid">
    <div class="wbx-item"><button type="button" class="wbx-gallery-trigger" aria-label="View photo 1"><img src="https://..." alt="..."></button></div>
    <div class="wbx-item"><button type="button" class="wbx-gallery-trigger" aria-label="View photo 2"><img src="https://..." alt="..."></button></div>
  </div>
</section>
```
Any number of `.wbx-item` entries works. Clicking a photo opens a full-screen
popup with prev/next arrows that cycle through the other photos in that same
`.wbx-grid` (Escape closes, click-outside closes); multiple galleries on one
page each get their own independent prev/next cycle. All styling
(`.wbx-gallery`, `.wbx-lightbox-*`) already lives in `_base_styles.blade.php`
and the GrapesJS "Image Gallery" block — no CSS of your own needed.

**Contact/newsletter/booking forms** — copy this shape exactly (honeypot
included, off-screen via the `.wbx-hp` class already defined site-wide):
```html
<form data-wbx-form="contact" method="post">
  <input type="text" name="website" class="wbx-hp" tabindex="-1" autocomplete="off">
  <input type="text" name="name" required>
  <input type="email" name="email" required>
  <input type="tel" name="phone">
  <textarea name="message" required></textarea>
  <button type="submit">Send</button>
</form>
```
`data-wbx-form` must be `contact` (name, email, phone?, message required),
`newsletter` (email only), or `booking` (name, email, phone required;
preferred_date/message optional) — these exact field names, nothing else.
The form's `action`/hidden fields are injected at render time; don't set
`action` yourself.

**Everything else is plain HTML/CSS** — headings, sections, cards, a real
`<img src="https://...">` (only `https://` image URLs; there's no CLI path
to upload binary assets — either host images externally or leave the media
picker to whoever opens the GrapesJS editor afterward), normal CSS in the
page's own `.css` file. Reuse the visual language already in
`resources/views/websites/public/_base_styles.blade.php`
(`.wbx-dyn-card`, `.wbx-product*`, etc.) so an imported page doesn't look
like a foreign object next to canvas-built ones.

## Verifying before you import

No server access, so no dry-run transaction available to you — but the
risk is already low without one: an import always lands as an unpublished
**draft**, under whichever team's account did the import, and a bad draft
can just be deleted from the Web Factory list and re-imported. Before
uploading, it's still worth:
- Re-reading `assemble.php`'s own output — it reports the page count and
  errors out (with a specific reason) on a malformed `site.json`/`pages.json`
  or a missing referenced file, before it ever writes a JSON file.
- Spot-checking the written JSON has `"format": "webfactory-export"` and a
  non-empty `"pages"` array with the content you expect.
- After import, opening the draft's editor page and reading through each
  page before touching Publish.

If you *do* have shell access to the app server, you can additionally
dry-run the CLI import inside a rolled-back transaction to check a file is
well-formed with zero risk at all:
```
php artisan tinker --execute="
DB::beginTransaction();
try {
    \$code = Artisan::call('website:import', ['file' => 'webfactory-drafts/<slug>.json', '--team' => '<team-slug>']);
    echo Artisan::output();
} finally {
    DB::rollBack();
}
"
```

## Making this available to other developers

- **If they have this repo cloned**: nothing to do — it's committed at
  `.claude/skills/webfactory-build/`, so opening the project in any Claude
  Code surface picks it up automatically and stays in sync with the app's
  schema as both evolve together.
- **If they only work against `https://360.bridging.co.tz`** (most
  developers, per the team's own setup — no server/artisan access): send
  them just the two files in this folder (`SKILL.md` + `assemble.php`) to
  drop into their own `.claude/skills/webfactory-build/` or
  `~/.claude/skills/webfactory-build/` — see "Using this on a machine
  that isn't this server" above. Nothing else from the repo is needed, and
  the browser-import path (step 6A) is the one they'll use.
- `php artisan website:import` is a normal Artisan command
  (`app/Console/Commands/Website/ImportWebsiteCommand.php`) for the subset
  of developers who *do* have server shell access — self-documenting via
  `php artisan list website` / `--help`, with or without this skill.
- If this ever needs to be shared *outside* this repo (a different app, a
  different team's tooling), that would be the point to package it as a
  Claude Code **plugin** instead — this skill deliberately isn't one,
  since a plugin implies a stable, versioned contract for other projects,
  and this is tightly coupled to `webfactory-export`'s exact shape, which
  belongs to this app and can change with it.
