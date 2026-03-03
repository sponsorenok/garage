import jspreadsheet from "jspreadsheet-ce";
import jsuites from "jsuites";

function safeParse(str, fallback) {
    try { return JSON.parse(str); } catch { return fallback; }
}

function findDefaultVehicleSelect() {
    return document.querySelector('select[name$="[defaultVehicle]"]');
}

function getDefaultVehicleId() {
    const sel = findDefaultVehicleSelect();
    const v = sel ? (sel.value || "").trim() : "";
    const id = v ? parseInt(v, 10) : null;
    return Number.isFinite(id) ? id : null;
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".ea-partrequest-sheet").forEach((wrap) => {
        const textareaId = wrap.dataset.target;
        const gridEl = document.getElementById(`${textareaId}__grid`);
        const textarea = document.getElementById(textareaId);

        console.log("items_spreadsheet DOM-export loaded", textareaId, textarea?.getAttribute("name"));

        if (!gridEl || !textarea) return;

        const vehicles = safeParse(wrap.dataset.vehicles || "[]", []);
        const types = safeParse(wrap.dataset.types || "[]", []);
        const rowsObj = safeParse(textarea.value || "[]", []);

        // Vehicles dropdown: "id — label"
        const vehicleDisplayById = new Map();
        const vehicleSource = Array.isArray(vehicles)
            ? vehicles
                .filter(v => v && typeof v === "object" && v.id)
                .map(v => {
                    const id = parseInt(v.id, 10);
                    const label = v.label ?? ("Авто #" + id);
                    const s = `${id} — ${label}`;
                    vehicleDisplayById.set(id, s);
                    return s;
                })
            : [];

        function parseVehicleId(cellValue) {
            const s = String(cellValue || "").trim();
            const m = s.match(/^(\d+)\s*[-—]/);
            return m ? parseInt(m[1], 10) : null;
        }

        // Types dropdown: label shown, value saved
        const typeValueByLabel = new Map(); // label -> value
        const typeLabelByValue = new Map(); // value -> label
        const typeSourceLabels = Array.isArray(types)
            ? types
                .filter(t => t && typeof t === "object" && t.label && t.value)
                .map(t => {
                    const label = String(t.label);
                    const value = String(t.value);
                    typeValueByLabel.set(label, value);
                    typeLabelByValue.set(value, label);
                    return label;
                })
            : [];

        // Columns: [nameRaw, categoryLabel, qty, vehicleDisplay]
        const data = rowsObj.map((r) => {
            const catLabel = r.category ? (typeLabelByValue.get(String(r.category)) ?? "") : "";
            const vehicleDisp = r.vehicleId ? (vehicleDisplayById.get(parseInt(r.vehicleId, 10)) ?? "") : "";
            return [
                r.nameRaw ?? "",
                catLabel,
                r.qty ?? 1,
                vehicleDisp,
            ];
        });

        // Init spreadsheet
        jspreadsheet(gridEl, {
            tabs: false,
            toolbar: false,
            worksheets: [
                {
                    data,
                    minDimensions: [4, 10],
                    columns: [
                        { title: "Найменування", type: "text", width: 520 },
                        { title: "Тип", type: "dropdown", width: 220, source: typeSourceLabels },
                        { title: "Кількість", type: "numeric", width: 120 },
                        { title: "Авто", type: "dropdown", width: 320, source: vehicleSource },
                    ],
                },
            ],
        });

        function getCellText(x, y) {
            const td = gridEl.querySelector(`td[data-x="${x}"][data-y="${y}"]`);
            if (!td) return "";
            // інколи value зберігається в data-value
            const dv = td.getAttribute("data-value");
            const text = (dv !== null ? dv : td.textContent);
            return String(text || "").trim();
        }

        function getMaxRowIndex() {
            let maxY = -1;
            gridEl.querySelectorAll("td[data-y]").forEach(td => {
                const y = parseInt(td.getAttribute("data-y"), 10);
                if (Number.isFinite(y) && y > maxY) maxY = y;
            });
            return maxY;
        }

        function ensureVehicleText(vehicleText) {
            if (String(vehicleText || "").trim()) return vehicleText;

            const defId = getDefaultVehicleId();
            if (!defId) return "";

            const disp = vehicleDisplayById.get(defId);
            return disp ? disp : "";
        }

        function syncFromDom() {
            const maxY = getMaxRowIndex();
            if (maxY < 0) {
                textarea.value = "[]";
                return;
            }

            const out = [];
            for (let y = 0; y <= maxY; y++) {
                const nameRaw = getCellText(0, y);
                if (!nameRaw) continue;

                const catLabel = getCellText(1, y);
                const catValue = catLabel ? (typeValueByLabel.get(catLabel) ?? null) : null;

                const qtyRaw = getCellText(2, y);
                const qty = qtyRaw ? String(row[4]).replace(',', '.')
                    : "1.000";

                const vehicleText = ensureVehicleText(getCellText(3, y));
                const vehicleId = parseVehicleId(vehicleText);

                out.push({
                    nameRaw,
                    category: catValue,   // ✅ VALUE на бекенд
                    qty,
                    vehicleId,
                });
            }

            textarea.value = JSON.stringify(out);
        }

        // ✅ Синки на “вихід з клітинки” та перед submit (capture щоб точно спрацювало)
        const schedule = () => setTimeout(syncFromDom, 0);
        wrap.addEventListener("keyup", schedule, true);
        wrap.addEventListener("focusout", schedule, true);
        wrap.addEventListener("paste", schedule, true);
        wrap.addEventListener("mouseup", schedule, true);

        const defSelect = findDefaultVehicleSelect();
        if (defSelect) defSelect.addEventListener("change", schedule);

        const form = wrap.closest("form");
        if (form) form.addEventListener("submit", () => syncFromDom());

        // initial
        syncFromDom();
    });
});
