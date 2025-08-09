// script.js
(function(){
  "use strict";

  // Title-case utility
  const toTitleCase = (str) =>
    str.replace(/\w\S*/g, txt => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());

  // Skill ID -> label (frozen to prevent mutation)
  const skillNames = Object.freeze({
    abyssal: "Abyssal Ore Processing",
    coherent: "Coherent Ore Processing",
    common: "Common Moon Ore Processing",
    complex: "Complex Ore Processing",
    exceptional: "Exceptional Moon Ore Processing",
    ice: "Ice Processing",
    mercoxit: "Mercoxit Ore Processing",
    rare: "Rare Moon Ore Processing",
    simple: "Simple Ore Processing",
    ubiquitous: "Ubiquitous Moon Ore Processing",
    uncommon: "Uncommon Moon Ore Processing",
    variegated: "Variegated Ore Processing"
  });

  const clamp = (n, min, max) => Math.min(Math.max(n, min), max);
  const toInt = (v) => {
    const n = parseInt(v, 10);
    return Number.isFinite(n) ? n : 0;
  };
  const has = (obj, key) => Object.prototype.hasOwnProperty.call(obj, key);

  function calculateUpwellYield(R, Re, Op, ImRaw, structure, sec, rig) {
    const Rm  = rig === "t2" ? 3 : rig === "t1" ? 1 : 0;
    const Sec = sec === "nullsec" ? 0.12 : sec === "lowsec" ? 0.06 : 0.00;
    const Sm  = structure === "tatara" ? 0.055 : structure === "athanor" ? 0.02 : 0;
    const Im  = ({ none:0, "801":0.01, "802":0.02, "804":0.04 })[ImRaw] || 0;

    const base = 50 + Rm;
    return base * (1+Sec) * (1+Sm) * (1 + R*0.03) * (1 + Re*0.02) * (1 + Op*0.02) * (1 + Im);
  }

  function updateOreSkillYields(form) {
    // Clamp core skills to [0..5] and reflect back
    const R  = clamp(toInt(form.querySelector("#R")?.value),  0, 5);
    const Re = clamp(toInt(form.querySelector("#Re")?.value), 0, 5);
    form.querySelector("#R").value  = R;
    form.querySelector("#Re").value = Re;

    const Im  = form.querySelector("#imp")?.value || "none";
    const structure = form.querySelector("#structure")?.value || "npc";
    const sec       = form.querySelector("#sec")?.value || "hisec";
    const rig       = form.querySelector("#rig")?.value || "none";

    // For each ore-specific skill, clamp to [0..5], reflect, and print yield
    for (const id of Object.keys(skillNames)) {
      const OpInput = form.querySelector(`#${id}`);
      if (!OpInput) continue;
      const Op = clamp(toInt(OpInput.value), 0, 5);
      OpInput.value = Op;

      const span = document.getElementById(`yield-${id}`);
      if (!span) continue;

      const yieldVal = calculateUpwellYield(R, Re, Op, Im, structure, sec, rig);
      span.textContent = yieldVal.toFixed(2) + "%";
    }
  }

  function attachListeners() {
    const form = document.getElementById("reprocessing-form");
    if (!form) return;

    form.addEventListener("input", () => updateOreSkillYields(form), { passive: true });
    form.addEventListener("change", () => updateOreSkillYields(form));
    updateOreSkillYields(form);

    const calcBtn   = document.getElementById("calculate-button");
    const textarea  = document.getElementById("ore-input");
    const outputDiv = document.getElementById("mineral-result");

    calcBtn?.addEventListener("click", () => {
      const lines = (textarea?.value || "").trim().split("\n");
      const mineralTotals = {};

      for (const rawLine of lines) {
        const cleanLine = rawLine.trim().replace(/\t+/g, " ").replace(/ {2,}/g, " ");
        if (!cleanLine) continue;

        const parts = cleanLine.split(" ");

        // Remove standalone 'Compressed' token
        const oreNameIndex = parts.findIndex(p => isNaN(parseFloat(p)));
        if (oreNameIndex > -1 && parts[oreNameIndex].toLowerCase() === "compressed") {
          parts.splice(oreNameIndex, 1);
        }

        const numberIndex = parts.findIndex(p => !isNaN(parseFloat(p)));
        if (numberIndex === -1) continue;

        let rawOre = parts.slice(0, numberIndex).join(" ");
        const qty = parseFloat(parts[numberIndex]);
        if (!Number.isFinite(qty) || qty <= 0) continue;

        // Normalize ore name
        rawOre = rawOre.toLowerCase().startsWith("compressed ") ? rawOre.substring(10) : rawOre;
        rawOre = toTitleCase(rawOre);

        // Resolve ore against data maps (prefer exact, then suffix match)
        const findKey = (obj, name) =>
          Object.keys(obj).find(k => k.toLowerCase() === name.toLowerCase()) ||
          Object.keys(obj).find(k => name.toLowerCase().endsWith(k.toLowerCase()));

        let oreMatch = (typeof iceData !== "undefined" && findKey(iceData, rawOre)) ||
                       (typeof oreData !== "undefined" && findKey(oreData, rawOre));
        if (!oreMatch) continue;

        // Determine yield % from the relevant skill span, fallback to Ice yield if needed
        const skillLabel = (typeof oreSkills !== "undefined" && has(oreSkills, oreMatch)) ? oreSkills[oreMatch] : null;
        const skillId = skillLabel ? Object.keys(skillNames).find(k => skillNames[k] === skillLabel) : null;
        const yieldSpan = skillId ? document.getElementById("yield-" + skillId) : null;

        const fallbackIce = document.getElementById("yield-ice");
        const fallbackYield = fallbackIce ? (parseFloat(fallbackIce.textContent) || 0) / 100 : 0;
        const yieldPercent = yieldSpan ? (parseFloat(yieldSpan.textContent) || 0) / 100 : fallbackYield;

        const isIce = (typeof iceData !== "undefined") && Object.prototype.hasOwnProperty.call(iceData, oreMatch);
        const batchSize = isIce ? 1 : 100;
        const reprocessableQty = Math.floor(qty / batchSize) * batchSize;

        const minerals = isIce ? iceData[oreMatch] : oreData[oreMatch];
        if (!minerals) continue;

        for (const [mineral, perBatch] of Object.entries(minerals)) {
          const total = Math.floor((reprocessableQty / batchSize) * perBatch * yieldPercent);
          if (total > 0) {
            mineralTotals[mineral] = (mineralTotals[mineral] || 0) + total;
          }
        }
      }

      // Write results
      const linesOut = Object.entries(mineralTotals).map(([m, t]) => `${m} ${t}`);
      outputDiv.textContent = linesOut.join("\n");
    });
  }

  document.addEventListener("DOMContentLoaded", attachListeners);
})();
