// Global ore skill map (must match skill input IDs)

function toTitleCase(str) {
  return str.replace(
    /\w\S*/g,
    txt => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
  );
}

const skillNames = {
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
};

document.addEventListener("DOMContentLoaded", () => {
  function calculateUpwellYield(R, Re, Op, ImRaw, structure, sec, rig) {
    const Rm = rig === "t2" ? 3 : rig === "t1" ? 1 : 0;
    const Sec = sec === "nullsec" ? 0.12 : sec === "lowsec" ? 0.06 : 0.00;
    const Sm = structure === "tatara" ? 0.055 : structure === "athanor" ? 0.02 : 0;
    const Im = {
      none: 0,
      "801": 0.01,
      "802": 0.02,
      "804": 0.04
    }[ImRaw] || 0;

    const base = 50 + Rm;
    const yieldPercent =
      base *
      (1 + Sec) *
      (1 + Sm) *
      (1 + R * 0.03) *
      (1 + Re * 0.02) *
      (1 + Op * 0.02) *
      (1 + Im);

    return yieldPercent;
  }

  function updateOreSkillYields() {
    const R = parseInt(document.getElementById("R")?.value) || 0;
    const Re = parseInt(document.getElementById("Re")?.value) || 0;
    const Im = document.getElementById("imp")?.value || "none";
    const structure = document.getElementById("structure")?.value;
    const sec = document.getElementById("sec")?.value;
    const rig = document.getElementById("rig")?.value;

    for (const [id] of Object.entries(skillNames)) {
      const Op = parseInt(document.getElementById(id)?.value) || 0;
      const yieldVal = calculateUpwellYield(R, Re, Op, Im, structure, sec, rig);
      const span = document.getElementById(`yield-${id}`);
      if (span) {
        span.textContent = yieldVal.toFixed(2) + "%";
      }
    }
  }

  function attachListeners() {
    const inputs = document.querySelectorAll(
      '#reprocessing-form input[type="number"], #reprocessing-form select'
    );
    inputs.forEach(input => input.addEventListener("input", updateOreSkillYields));
  }

  attachListeners();
  updateOreSkillYields();

  // Adjust skill layout to two columns
  const wrapper = document.getElementById("ore-skills-wrapper");
  if (wrapper) {
    wrapper.style.display = "grid";
    wrapper.style.gridTemplateColumns = "1fr 1fr";
    wrapper.style.columnGap = "20px";
  }
  const heading = document.querySelector(".ore-skills-section h3");
  if (heading) {
    heading.style.textAlign = "center";
    heading.style.gridColumn = "1 / -1";
  }

  document.getElementById("calculate-button")?.addEventListener("click", (e) => {
    e.preventDefault();
    const lines = document.getElementById("ore-input")?.value.trim().split("\n") || [];
    const mineralTotals = {};

    lines.forEach(line => {
      const cleanLine = line.trim().replace(/\t+/g, " ").replace(/ {2,}/g, " ");
      const parts = cleanLine.split(" ");
      const oreNameIndex = parts.findIndex(p => isNaN(parseFloat(p)));
      if (oreNameIndex > -1 && parts[oreNameIndex].toLowerCase() === "compressed") {
        parts.splice(oreNameIndex, 1);
      }
      const numberIndex = parts.findIndex(p => !isNaN(parseFloat(p)));
      if (numberIndex === -1) return;

      let rawOre = parts.slice(0, numberIndex).join(" ");
      const qty = parseFloat(parts[numberIndex]);

      rawOre = rawOre.toLowerCase().startsWith("compressed ") ? rawOre.substring(10) : rawOre;
      rawOre = toTitleCase(rawOre);

      let oreMatch = Object.keys(iceData).find(k => k.toLowerCase() === rawOre.toLowerCase()) ||
                     Object.keys(iceData).find(k => rawOre.toLowerCase().endsWith(k.toLowerCase())) ||
                     Object.keys(oreData).find(k => k.toLowerCase() === rawOre.toLowerCase()) ||
                     Object.keys(oreData).find(k => rawOre.toLowerCase().endsWith(k.toLowerCase()));

      if (!oreMatch || isNaN(qty) || qty <= 0) return;

      const skillLabel = oreSkills[oreMatch];
      const skillId = Object.keys(skillNames).find(k => skillNames[k] === skillLabel);
      const yieldSpan = skillId ? document.getElementById("yield-" + skillId) : null;
      const fallbackYield = parseFloat(document.getElementById("yield-ice")?.textContent || "0") / 100;
      const yieldPercent = yieldSpan ? parseFloat(yieldSpan.textContent) / 100 : fallbackYield;

      const isIce = !!iceData[oreMatch];
      const batchSize = isIce ? 1 : 100;
      const reprocessableQty = Math.floor(qty / batchSize) * batchSize;
      const minerals = isIce ? iceData[oreMatch] : oreData[oreMatch];

      for (const [mineral, perBatchAmount] of Object.entries(minerals)) {
        const total = Math.floor((reprocessableQty / batchSize) * perBatchAmount * yieldPercent);
        if (total > 0) {
          mineralTotals[mineral] = (mineralTotals[mineral] || 0) + total;
        }
      }
    });

    let output = "";
    for (const [mineral, total] of Object.entries(mineralTotals)) {
      output += `${mineral} ${total}\n`;
    }

    document.getElementById("mineral-result").innerText = output.trim();
  });

  const textarea = document.getElementById("ore-input");
  const oreContainer = document.getElementById("ore-container");

  if (textarea && oreContainer) {
    textarea.addEventListener("input", () => {
      oreContainer.innerHTML = "";

      const lines = textarea.value.trim().split("\n");
      lines.forEach(line => {
        const cleanLine = line.trim().replace(/\t+/g, " ").replace(/ {2,}/g, " ");
        const parts = cleanLine.split(" ");
        const oreNameIndex = parts.findIndex(p => isNaN(parseFloat(p)));
        if (oreNameIndex > -1 && parts[oreNameIndex].toLowerCase() === "compressed") {
          parts.splice(oreNameIndex, 1);
        }
        const numberIndex = parts.findIndex(p => !isNaN(parseFloat(p)));
        if (numberIndex === -1) return;

        let oreName = parts.slice(0, numberIndex).join(" ");
        if (oreName.toLowerCase().startsWith("compressed ")) {
          oreName = oreName.substring(10);
        }
        oreName = toTitleCase(oreName);

        const quantity = parts[numberIndex];

        const row = document.createElement("div");
        row.className = "ore-row";

        const nameInput = document.createElement("input");
        nameInput.className = "ore-name";
        nameInput.type = "text";
        nameInput.value = oreName;
        nameInput.style.width = "150px";

        const qtyInput = document.createElement("input");
        qtyInput.className = "ore-quantity";
        qtyInput.type = "number";
        qtyInput.value = quantity;
        qtyInput.style.width = "150px";

        const output = document.createElement("span");
        output.className = "ore-yield";
        output.style.marginLeft = "10px";

        row.appendChild(nameInput);
        row.appendChild(qtyInput);
        row.appendChild(output);

        oreContainer.appendChild(row);
      });
    });
  }
});
