# webfactory-build (Claude Code plugin marketplace)

Public, self-serve distribution for the `webfactory-build` Claude Code
skill — lets any developer author a [360 Web Factory](https://360.bridging.co.tz/websites)
website as plain HTML/CSS in Claude Code instead of the drag-and-drop
canvas, then package and import it as a real draft site.

This repo is just a thin wrapper: a plugin marketplace manifest pointing
at the two files that make up the actual skill, copied verbatim from the
`360` application repo's `.claude/skills/webfactory-build/`.

## Before you publish this

`.claude-plugin/marketplace.json` has placeholder `owner.name` /
`owner.email` values — fill those in with a real team/contact before
pushing this publicly. Nothing else needs to change.

## Installing (for developers)

```
/plugin marketplace add <your-org>/360-webfactory-build-skill
/plugin install webfactory-build@webfactory-build-skill
```

No access to the 360 application repo, `.env`, or server is required —
same zero-access promise documented in `ai-web-builder.md` in the main
360 repo.

## Publishing an update

1. Copy the updated `SKILL.md` / `assemble.php` from
   `360/.claude/skills/webfactory-build/` into
   `skills/webfactory-build/` here.
2. Bump `version` in `.claude-plugin/marketplace.json`.
3. Commit and push.

Existing installs don't auto-update — users re-run both commands above
(`marketplace add` refreshes the manifest, `install` pulls the new
content).

## Source of truth

The canonical copy of this skill lives in the `360` application repo at
`.claude/skills/webfactory-build/`, alongside the app it targets, so it
stays in sync with the platform as it evolves. This repo should always be
a copy of that, not a fork that drifts.
