<?php
/**
 * Assembles a webfactory-export JSON file (the format
 * WebsiteController::export()/import() and `php artisan website:import`
 * all speak) from a plain directory of hand-authored HTML/CSS pages.
 *
 * Standalone on purpose — no Laravel bootstrap, so it runs the same inside
 * or outside this repo, and a developer can read it top to bottom in under
 * a minute without knowing the app's internals.
 *
 * Usage:
 *   php assemble.php <site-dir> <output.json>
 *
 * <site-dir> must contain:
 *   site.json   — {name, brand_color, website_type, website_template?,
 *                  sitemap_enabled?, robots_directive?}
 *   pages.json  — [{slug, title, is_home, sort_order, meta_title?,
 *                   meta_description?, canonical_url?, html_file, css_file?}, ...]
 *   the html_file/css_file paths referenced above, relative to <site-dir>
 *
 * See SKILL.md in this directory for the full authoring guide.
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php assemble.php <site-dir> <output.json>\n");
    exit(1);
}

[, $siteDir, $outputPath] = $argv;
$siteDir = rtrim($siteDir, '/');

$sitePath = $siteDir . '/site.json';
$pagesPath = $siteDir . '/pages.json';

foreach ([$sitePath, $pagesPath] as $required) {
    if (!is_file($required)) {
        fwrite(STDERR, "Missing required file: {$required}\n");
        exit(1);
    }
}

$site = json_decode(file_get_contents($sitePath), true);
$pages = json_decode(file_get_contents($pagesPath), true);

if (!is_array($site) || empty($site['name']) || empty($site['website_type'])) {
    fwrite(STDERR, "site.json must at least set \"name\" and \"website_type\".\n");
    exit(1);
}

if (!is_array($pages) || count($pages) === 0) {
    fwrite(STDERR, "pages.json must be a non-empty array of page descriptors.\n");
    exit(1);
}

$homeCount = 0;
$builtPages = [];

foreach ($pages as $i => $page) {
    if (empty($page['title']) || empty($page['html_file'])) {
        fwrite(STDERR, "pages.json entry #{$i} needs at least \"title\" and \"html_file\".\n");
        exit(1);
    }

    $htmlPath = $siteDir . '/' . $page['html_file'];

    if (!is_file($htmlPath)) {
        fwrite(STDERR, "pages.json entry #{$i} (\"{$page['title']}\") references missing file: {$htmlPath}\n");
        exit(1);
    }

    $html = file_get_contents($htmlPath);
    $css = '';

    if (!empty($page['css_file'])) {
        $cssPath = $siteDir . '/' . $page['css_file'];

        if (!is_file($cssPath)) {
            fwrite(STDERR, "pages.json entry #{$i} (\"{$page['title']}\") references missing file: {$cssPath}\n");
            exit(1);
        }

        $css = file_get_contents($cssPath);
    }

    $isHome = !empty($page['is_home']);
    $homeCount += $isHome ? 1 : 0;

    $builtPages[] = [
        'slug'              => $isHome ? '' : ($page['slug'] ?? ''),
        'title'             => $page['title'],
        'is_home'           => $isHome,
        'sort_order'        => $page['sort_order'] ?? $i,
        'meta_title'        => $page['meta_title'] ?? null,
        'meta_description'  => $page['meta_description'] ?? null,
        'canonical_url'     => $page['canonical_url'] ?? null,
        'content'           => [
            'engine' => 'gjs',
            'html'   => $html,
            'css'    => $css,
            // Left null on purpose: the GrapesJS canvas parses html/css
            // into its own component tree the first time someone opens
            // the editor, exactly as it already does for legacy
            // {sections:[...]} content — see WebsiteController::edit().
            'gjs'    => null,
        ],
    ];
}

if ($homeCount !== 1) {
    fwrite(STDERR, "Exactly one page must have \"is_home\": true (found {$homeCount}).\n");
    exit(1);
}

// Home must be pages[0] — website:import / WebsiteImportService treats
// whichever page comes first as home, regardless of an is_home flag on a
// later entry.
usort($builtPages, fn ($a, $b) => $b['is_home'] <=> $a['is_home']);

$payload = [
    'format'      => 'webfactory-export',
    'version'     => 1,
    'exported_at' => date(DATE_ATOM),
    'website'     => [
        'name'             => $site['name'],
        'brand_color'      => $site['brand_color'] ?? '#0d6efd',
        'logo_path'        => $site['logo_path'] ?? null,
        'favicon_path'     => $site['favicon_path'] ?? null,
        'sitemap_enabled'  => $site['sitemap_enabled'] ?? true,
        'robots_directive' => $site['robots_directive'] ?? 'index,follow',
        'website_type'     => $site['website_type'],
        'website_template' => $site['website_template'] ?? null,
    ],
    'pages' => $builtPages,
];

file_put_contents($outputPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

fwrite(STDOUT, "Wrote {$outputPath} (" . count($builtPages) . " page(s)).\n");
