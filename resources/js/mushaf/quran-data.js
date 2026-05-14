/**
 * Quran data scaffold for the interactive Mushaf viewer.
 *
 * Production build target: a pre-baked JSON file generated from the
 * `quran` npm package + KFGQPC QPC v4 page metadata, with:
 *   - surahs: [{ number, name_ar, name_latin, start_page, ayah_count, juz }, ...]
 *   - verseToPage: { [surah]: { [ayah]: page } }
 *   - pageGlyphs: page → { lines: [{ surah, ayah, text, line_index }] }
 *
 * This stub exposes the same API surface so window.mushaf renders a
 * graceful "page N" placeholder until the data is bundled. The
 * KFGQPC fonts + glyph payloads ship in a follow-up deploy artifact
 * (see CLAUDE.md note on font-only PR landing first).
 */
(function () {
    'use strict';

    const surahs = []; // populated by the build step

    /** Look up the page containing (surah, ayah). Returns 1 if unknown. */
    function findPage(surah, ayah) {
        const s = window.QuranData && window.QuranData._verseToPage && window.QuranData._verseToPage[surah];
        if (!s) return 1;
        return s[ayah] || 1;
    }

    /** Per-page glyph strings. Returns null until the data file ships,
     *  which makes mushaf.js fall back to its placeholder rendering. */
    function pageGlyphs(_page) {
        return null;
    }

    window.QuranData = {
        surahs,
        _verseToPage: {},
        findPage,
        pageGlyphs,
    };
})();
