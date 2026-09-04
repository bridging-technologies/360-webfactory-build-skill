# webfactory-build (Claude Code plugin marketplace)

Public, self-serve distribution for the `webfactory-build` Claude Code
skill — lets any developer author a [360 Web Factory](https://360.bridging.co.tz/websites)
website as plain HTML/CSS in Claude Code instead of the drag-and-drop
canvas, then package and import it as a real draft site.

This repo is a thin wrapper: a plugin marketplace manifest pointing at
the two files that make up the skill itself, under `skills/webfactory-build/`.

## Installing (for developers)

```
/plugin marketplace add bridging-technologies/360-webfactory-build-skill
/plugin install webfactory-build@webfactory-build-skill
```

No repo access, `.env`, or server login is required to use this — just a
360 account with `website-builder` permission for the team you're
building a site for.

## Publishing an update

1. Update the files under `skills/webfactory-build/` (`SKILL.md`,
   `assemble.php`) with the change.
2. Bump `version` in `.claude-plugin/marketplace.json`.
3. Commit and push.

Existing installs don't auto-update — users re-run both commands above
(`marketplace add` refreshes the manifest, `install` pulls the new
content).
