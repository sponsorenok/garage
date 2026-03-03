// assets/admin/purchase_lines_spreadsheet.js
import jspreadsheet from "jspreadsheet-ce";
import jsuites from "jsuites"; // ok to keep (dropdowns may rely on it)

/**
 * Purchase "requests -> spreadsheet"
 * - One sheet only
 * - Reload table from selected PartRequests via ajax
 * - Sync selected rows (requestItemId + chosen itemId + buyQty) into hidden textarea JSON
 *
 * IMPORTANT:
 * - We DO NOT use "hidden" column type for rid, because it won't exist in DOM td[data-x="0"].
 *   Instead we keep it as readOnly text and hide it via CSS (see note at bottom).
 */

function safeParse(str, fallback) {
    try {
        return JSON.parse(str);
    } catch {
        return fallback;
    }
}

function findRequestsSelect() {
    // EA can render either name="Purchase[requests][]" or name="Purchase[requests]"
    return document.querySelector(
        'select[name="Purchase[requests][]"], select[name="Purchase[requests]"]'
    );
}

function getSelectedRequestIds() {
    const sel = findRequestsSelect();
    if (!sel) return [];
    return Array.from(sel.selectedOptions || [])
        .map((o) => parseInt(o.value, 10))
        .filter(Number.isFinite);
}

function parseIdFromDisplay(s) {
    const m = String(s || "")
        .trim()
        .match(/^(\d+)\s*[-—]/);
    return m ? parseInt(m[1], 10) : null;
}

function toDecimal3(v, def = "0.000") {
    const s = String(v ?? "").trim();
    if (!s) return def;
    const n = parseFloat(s.replace(",", "."));
    if (!Number.isFinite(n)) return def;
    return n.toFixed(3);
}

function getCellText(gridEl, x, y) {
    const td = gridEl.querySelector(`td[data-x="${x}"][data-y="${y}"]`);
    if (!td) return "";
    const dv = td.getAttribute("data-value");
    const text = dv !== null ? dv : td.textContent;
    return String(text || "").trim();
}

function getMaxRowIndex(gridEl) {
    let maxY = -1;
    gridEl.querySelectorAll("td[data-y]").forEach((td) => {
        const y = parseInt(td.getAttribute("data-y"), 10);
        if (Number.isFinite(y) && y > maxY) maxY = y;
    });
    return maxY;
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".ea-purchase-sheet").forEach((wrap) => {
        const textareaId = wrap.dataset.target;
        const gridEl = document.getElementById(`${textareaId}__grid`);
        const textarea = document.getElementById(textareaId);

        if (!gridEl || !textarea) return;

        const ajaxUrl = wrap.dataset.ajaxUrl || "";
        const items = safeParse(wrap.dataset.items || "[]", []);
        const types = safeParse(wrap.dataset.types || "[]", []);

        // VALUE -> label (for read-only column display)
        const typeLabelByValue = new Map();
        (Array.isArray(types) ? types : []).forEach((t) => {
            if (t?.value && t?.label) typeLabelByValue.set(String(t.value), String(t.label));
        });

        // Items dropdown source: ["id — label", ...]
        const itemSource = (Array.isArray(items) ? items : [])
            .filter((i) => i && typeof i === "object" && i.id)
            .map((i) => {
                const id = parseInt(i.id, 10);
                const label = i.label ?? `Item #${id}`;
                return `${id} — ${label}`;
            });

        // Keep current instance
        let jss = null;

        function initSpreadsheet(dataRows) {
            // Destroy previous (best-effort)
            try {
                if (jss && typeof jss.destroy === "function") jss.destroy();
            } catch (_) {
                // ignore
            }
            gridEl.innerHTML = "";

            const data =
                Array.isArray(dataRows) && dataRows.length
                    ? dataRows
                    : Array.from({ length: 10 }, () => ["", "", "", "", "", ""]);

            jss = jspreadsheet(gridEl, {
                worksheets: [
                    {
                        data,
                        minDimensions: [6, 10],
                        columns: [
                            // rid: NOT "hidden" (so DOM td exists). We'll hide via CSS.
                            { title: "rid", type: "text", width: 1, readOnly: true },
                            { title: "Заявка: назва", type: "text", width: 520, readOnly: true },
                            { title: "Тип", type: "text", width: 220, readOnly: true },
                            { title: "Потрібно", type: "text", width: 120, readOnly: true },
                            { title: "Item (реальна)", type: "dropdown", width: 420, source: itemSource },
                            { title: "Закупити", type: "numeric", width: 120 },
                        ],
                    },
                ],
                tabs: false,
                toolbar: false,
            });
        }

        async function fetchRequestItems(ids) {
            if (!ajaxUrl) return { rows: [] };

            const url = new URL(ajaxUrl, window.location.origin);
            ids.forEach((id) => url.searchParams.append("ids[]", String(id)));

            const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
            if (!res.ok) throw new Error(`AJAX ${res.status}`);
            return await res.json(); // { rows: [...] }
        }

        function rowsToTable(rows) {
            return rows.map((r) => {
                const typeLabel = r.category ? typeLabelByValue.get(String(r.category)) ?? "" : "";
                const need = String(r.openQty ?? "0.000");

                const reqLabel = `#${r.requestId} — ${r.nameRaw}${
                    r.vehicleLabel ? ` (${r.vehicleLabel})` : ""
                }`;

                return [
                    String(r.requestItemId || ""), // rid visible-in-dom but CSS-hidden
                    reqLabel,
                    typeLabel,
                    need,
                    "", // user chooses item
                    need, // default buy = need
                ];
            });
        }

        function syncToTextareaFromDom() {
            // DOM-based sync (stable)
            const maxY = getMaxRowIndex(gridEl);
            const out = [];

            if (maxY < 0) {
                textarea.value = "[]";
                return;
            }

            for (let y = 0; y <= maxY; y++) {
                const ridRaw = getCellText(gridEl, 0, y);
                const rid = ridRaw ? parseInt(ridRaw, 10) : null;

                // if row doesn't represent request item — ignore
                if (!rid) continue;

                const itemDisp = getCellText(gridEl, 4, y);
                const itemId = parseIdFromDisplay(itemDisp);

                const buyQtyRaw = getCellText(gridEl, 5, y);
                const buyQty = toDecimal3(buyQtyRaw, "0.000");

                // only rows that user actually wants to buy
                if (!itemId) continue;
                if (parseFloat(buyQty) <= 0) continue;

                out.push({
                    requestItemId: rid,
                    itemId,
                    buyQty,
                });
            }

            textarea.value = JSON.stringify(out);
        }

        async function reloadFromRequests() {
            const ids = getSelectedRequestIds();

            if (!ids.length) {
                initSpreadsheet([]);
                syncToTextareaFromDom();
                return;
            }

            const data = await fetchRequestItems(ids);
            const rows = Array.isArray(data.rows) ? data.rows : [];
            const table = rowsToTable(rows);

            initSpreadsheet(table);

            // after render — sync
            setTimeout(syncToTextareaFromDom, 0);
        }

        // ---------- listeners (ONLY ONCE) ----------
        const schedule = () => setTimeout(syncToTextareaFromDom, 0);

        wrap.addEventListener("keyup", schedule, true);
        wrap.addEventListener("focusout", schedule, true);
        wrap.addEventListener("paste", schedule, true);
        wrap.addEventListener("mouseup", schedule, true);

        const form = wrap.closest("form");
        if (form) form.addEventListener("submit", () => syncToTextareaFromDom());

        const reqSelect = findRequestsSelect();
        if (reqSelect) reqSelect.addEventListener("change", () => reloadFromRequests().catch(console.error));

        // initial
        reloadFromRequests().catch(console.error);
    });
});

/**
 * CSS you must add (e.g. assets/admin.css):
 *
 *  .jss_container td[data-x="0"],
 *  .jss_container th[data-x="0"],
 *  .jexcel_container td[data-x="0"],
 *  .jexcel_container th[data-x="0"] {
 *    display: none !important;
 *  }
 *
 * This hides rid column but keeps it in DOM so sync works.
 */
