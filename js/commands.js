
/**
 * DeepSID / Commands
 */

(function(global, $) {
  'use strict';

  // --- helpers ---
  const stripDiacritics = s =>
    s.normalize('NFD').replace(/\p{Diacritic}/gu, '');
  const norm = s => stripDiacritics(String(s).toLowerCase().trim());

  function makeInitials(parts) {
    if (!parts.length) return [];
    const joined = parts.map(p => p[0]).join('');
    const swapped = parts.length === 2 ? parts[1][0] + parts[0][0] : null;
    return swapped ? [joined, swapped] : [joined];
  }

  function extractHvscMusiciansRoots(allPaths) {
    const roots = new Set();
    for (const full of allPaths) {
      if (!full.startsWith('_High Voltage SID Collection/')) continue;
      const i = full.indexOf('/MUSICIANS/');
      if (i === -1) continue;
      const tail = full.slice(i + '/MUSICIANS/'.length);
      const segs = tail.split('/').filter(Boolean);
      if (segs.length < 2) continue;
      const letter = segs[0];
      const composerSeg = segs[1];
      const root = full.slice(0, i + '/MUSICIANS/'.length) + `${letter}/${composerSeg}`;
      roots.add(root);
    }
    return [...roots];
  }

  function buildMusiciansIndexFromHvsc(allPaths) {
    const composerRoots = extractHvscMusiciansRoots(allPaths);
    const entries = [];
    const tokenMap = new Map();

    for (const folder of composerRoots) {
      const composerSegment = folder.substring(folder.lastIndexOf('/') + 1);
      const parts = composerSegment.split('_').filter(Boolean).map(norm);
      const tokens = new Set([...parts, norm(parts.join(''))]);
      tokens.add(norm(composerSegment.replace(/_/g, '')));
      const initials = makeInitials(parts).map(norm);

      for (const t of tokens) {
        if (!tokenMap.has(t)) tokenMap.set(t, new Set());
        tokenMap.get(t).add(folder);
      }
      for (const ini of initials) {
        if (!tokenMap.has(ini)) tokenMap.set(ini, new Set());
        tokenMap.get(ini).add(folder);
      }

      entries.push({ folder, tokens: [...tokens], initials });
    }
    return { entries, tokenMap };
  }

  // --- state + loader ---
  let musiciansIndex = null;

  function loadMusiciansIndex() {
    if (musiciansIndex) return $.Deferred().resolve(musiciansIndex).promise();
    const cacheKey = 'ds_hvsc_paths_v2';
    const cached = localStorage.getItem(cacheKey);
    if (cached) {
      try {
        const arr = JSON.parse(cached);
        musiciansIndex = buildMusiciansIndexFromHvsc(arr);
        return $.Deferred().resolve(musiciansIndex).promise();
      } catch {}
    }
    return $.getJSON('php/csdb_folders.php').then(arr => {
      localStorage.setItem(cacheKey, JSON.stringify(arr));
      musiciansIndex = buildMusiciansIndexFromHvsc(arr);
      return musiciansIndex;
    });
  }

  // --- resolver ---
  function resolveMusicianFolder(cmd, idx) {
    const k = norm(cmd);
    const exact = idx.entries.filter(e => e.tokens.includes(k));
    if (exact.length) return exact.map(e => e.folder).sort()[0];
    const prefix = idx.entries.filter(e => e.tokens.some(t => t.startsWith(k)));
    if (prefix.length) return prefix.map(e => e.folder).sort()[0];
    const ini = idx.entries.filter(e => e.initials.some(i => i.startsWith(k)));
    if (ini.length) return ini.map(e => e.folder).sort()[0];
    return null;
  }

  function handlePlusCommand(raw) {
    const v = String(raw).trim();
    if (!v.startsWith('+')) return Promise.resolve(false);

    const key = v.slice(1);

    // Always return a Promise for the letter shortcut too
    if (/^[a-z]$/i.test(key)) {
      this.gotoFolder(`MUSICIANS/${key.toUpperCase()}`);
      return Promise.resolve(true);
    }

    // loadMusiciansIndex() already returns a thenable (jqXHR or a resolved deferred)
    return loadMusiciansIndex().then(idx => {
      const folder = resolveMusicianFolder(key, idx);
      if (folder) {
        this.gotoFolder(folder);
        return true;
      }
      return false;
    });
  }

  global.cmds = {
    handlePlusCommand,
    loadMusiciansIndex,
    resolveMusicianFolder
  };

})(window, jQuery);