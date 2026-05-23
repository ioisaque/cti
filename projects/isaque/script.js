(function () {
  const STORAGE_KEY = "isaquecosta-seven-wonders-visited";
  /** Minimum layout height per row-* step (px); natural height wins if larger */
  const ROW_LAYOUT_UNIT = 168;

  function loadState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === "object" ? parsed : {};
    } catch {
      return {};
    }
  }

  function saveState(state) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) {
      console.warn("Could not save checklist:", e);
    }
  }

  function updateProgress(root, state) {
    const el = document.getElementById("progress");
    if (!el) return;
    const total = root.querySelectorAll('input[type="checkbox"][name="visited"]').length;
    const count = Object.values(state).filter(Boolean).length;
    el.textContent = count === 0
      ? `0 of ${total} visited`
      : `${count} of ${total} visited`;
  }

  function columnCountFromViewport() {
    const w = window.innerWidth;
    if (w >= 1300) return 4;
    if (w >= 900) return 3;
    if (w >= 560) return 2;
    return 1;
  }

  function colSpan(el, maxCols) {
    let s = 1;
    if (el.classList.contains("col-4")) s = 4;
    else if (el.classList.contains("col-3")) s = 3;
    else if (el.classList.contains("col-2")) s = 2;
    else if (el.classList.contains("col-1")) s = 1;
    return Math.min(s, maxCols);
  }

  function rowFactor(el) {
    if (el.classList.contains("row-4")) return 4;
    if (el.classList.contains("row-3")) return 3;
    if (el.classList.contains("row-2")) return 2;
    return 1;
  }

  function layoutWondersMasonry(root) {
    if (!root.classList.contains("wonders--masonry")) return;
    const items = Array.prototype.slice.call(root.querySelectorAll(":scope > .wonder"));
    if (items.length === 0) return;

    const n = columnCountFromViewport();
    const layoutHeights = [];

    items.forEach(function (li) {
      const span = colSpan(li, n);
      li.style.boxSizing = "border-box";
      li.style.position = "absolute";
      li.style.left = "0";
      li.style.top = "0";
      li.style.width = (100 * span) / n + "%";
      const natural = li.offsetHeight;
      layoutHeights.push(Math.max(natural, rowFactor(li) * ROW_LAYOUT_UNIT));
    });

    const colHeights = new Array(n).fill(0);

    items.forEach(function (li, idx) {
      const span = colSpan(li, n);
      const layoutH = layoutHeights[idx];
      let bestI = 0;
      let bestTop = Infinity;
      for (let i = 0; i <= n - span; i++) {
        let maxH = 0;
        for (let k = 0; k < span; k++) {
          if (colHeights[i + k] > maxH) maxH = colHeights[i + k];
        }
        if (maxH < bestTop || (maxH === bestTop && i < bestI)) {
          bestTop = maxH;
          bestI = i;
        }
      }

      const leftPct = (100 * bestI) / n;
      const widthPct = (100 * span) / n;
      li.style.left = leftPct + "%";
      li.style.top = bestTop + "px";
      li.style.width = widthPct + "%";

      const newBottom = bestTop + layoutH;
      for (let k = 0; k < span; k++) {
        colHeights[bestI + k] = newBottom;
      }
    });

    root.style.height = Math.max.apply(null, colHeights) + "px";
  }

  function initMasonry(root) {
    root.classList.add("wonders--masonry");

    let scheduled = 0;
    function scheduleLayout() {
      if (scheduled) return;
      scheduled = requestAnimationFrame(function () {
        scheduled = 0;
        layoutWondersMasonry(root);
      });
    }

    layoutWondersMasonry(root);

    window.addEventListener("resize", scheduleLayout);
    if (typeof ResizeObserver !== "undefined") {
      new ResizeObserver(scheduleLayout).observe(root);
    }

    root.querySelectorAll("img").forEach(function (img) {
      if (!img.complete) {
        img.addEventListener("load", scheduleLayout, { passive: true });
      }
    });
  }

  function init() {
    const root = document.getElementById("wonders");
    if (!root) return;

    initMasonry(root);

    let state = loadState();
    const boxes = root.querySelectorAll('input[type="checkbox"][name="visited"]');

    boxes.forEach(function (input) {
      const id = input.value;
      input.checked = Boolean(state[id]);
      input.addEventListener("change", function () {
        state[id] = input.checked;
        saveState(state);
        updateProgress(root, state);
      });
    });

    root.querySelectorAll(".card-media").forEach(function (media) {
      media.addEventListener("click", function (e) {
        if (e.target.closest("a.maps-btn")) return;
        if (e.target.closest("label.check-row")) return;
        const input = media.querySelector('input[type="checkbox"][name="visited"]');
        if (!input) return;
        input.checked = !input.checked;
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
    });

    updateProgress(root, state);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
