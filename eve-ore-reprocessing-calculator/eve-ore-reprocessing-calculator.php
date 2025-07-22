<?php
/*
Plugin Name: EVE Ore Reprocessing Calculator
Description: Adds a shortcode [reprocessing_calculator] to display an EVE Online Ore Reprocessing Calculator.
Version: 1.1
Author: C4813
*/

function eve_reprocessing_calculator_shortcode() {
    ob_start(); ?>

    <style>
        .reprocessing-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            max-width: 800px;
            margin: 0 auto;
        }
        .reprocessing-column {
            flex: 1 1 300px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
        .reprocessing-column h3 {
            text-align: center;
        }
        .reprocessing-column label {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            font-weight: bold;
        }
        .reprocessing-column input,
        .reprocessing-column select {
            width: 150px;
            text-align: center;
        }
        .reprocessing-column select {
            text-align-last: center;
        }
        #reprocessing-form button {
            margin-top: 20px;
            padding: 10px;
            font-size: 16px;
        }
        .ore-table-wrapper {
            max-width: 800px;
            margin: 40px auto 0 auto; /* 40px top margin for spacing */
            overflow-x: auto;
        }
        .ore-table-wrapper table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .ore-table-wrapper th,
        .ore-table-wrapper td {
          padding: 8px 12px;
          border: 1px solid #ccc;
        }
    </style>

    <form id="reprocessing-form">
        <div class="reprocessing-container">
            <div class="reprocessing-column">
                <h3>Skills (0–5)</h3>
                <label>Reprocessing
                    <input type="number" id="R" name="R" min="0" max="5" value="5">
                </label>
                <label>Reprocessing Efficiency
                    <input type="number" id="Re" name="Re" min="0" max="5" value="5">
                </label>
                <label>Specific Ore Skill
                    <input type="number" id="Op" name="Op" min="0" max="5" value="5">
                </label>
                <label>Implant
                    <select id="imp" name="imp">
                        <option value="none">None</option>
                        <option value="801">RX-801 (+1%)</option>
                        <option value="802">RX-802 (+2%)</option>
                        <option value="804">RX-804 (+4%)</option>
                    </select>
                </label>
            </div>

            <div class="reprocessing-column">
                <h3>Structure Settings</h3>
                <label>Structure
                    <select id="structure" name="structure">
                        <option value="npc">NPC Station / Citadel</option>
                        <option value="athanor" selected>Athanor</option>
                        <option value="tatara">Tatara</option>
                    </select>
                </label>
                <label>Security
                    <select id="sec" name="sec">
                        <option value="hisec">Highsec</option>
                        <option value="lowsec">Lowsec</option>
                        <option value="nullsec">Null/J-space</option>
                    </select>
                </label>
                <label>Rig
                    <select id="rig" name="rig">
                        <option value="none">None</option>
                        <option value="t1">T1</option>
                        <option value="t2">T2</option>
                    </select>
                </label>
            </div>
        </div>
    </form>

    <div id="result" style="margin-top: 20px; text-align: center;"></div>

    <div style="text-align:center; margin-top: 30px;">
        <textarea id="ore-input" rows="6" style="width: 500px; text-align: center; margin-bottom: 15px;" placeholder="Paste your ore quantities here &#10;e.g.&#10;Veldspar 10000&#10;Scordite 5000"></textarea><br />
        <button type="button" onclick="calculateMineralYield()">Calculate Mineral Yield</button>
        <div id="mineral-result" style="margin-top: 15px;"></div>
    </div>

    <script>
    const oreData = {
      "Compressed Arkonor": {
        "Pyerite": 3200,
        "Mexallon": 1200,
        "Megacyte": 120
      },
      "Compressed Crimson Arkonor": {
        "Pyerite": 3360,
        "Mexallon": 1260,
        "Megacyte": 126
      },
      "Compressed Prime Arkonor": {
        "Pyerite": 3520,
        "Mexallon": 1320,
        "Megacyte": 132
      },
      "Compressed Flawless Arkonor": {
        "Pyerite": 3680,
        "Mexallon": 1380,
        "Megacyte": 138
      },
      "Compressed Bezdnacine": {
        "Tritanium": 40000,
        "Isogen": 4800,
        "Megacyte": 128
      },
      "Compressed Abyssal Bezdnacine": {
        "Tritanium": 42000,
        "Isogen": 5040,
        "Megacyte": 135
      },
      "Compressed Hadal Bezdnacine": {
        "Tritanium": 44000,
        "Isogen": 5280,
        "Megacyte": 141
      },
      "Compressed Bistot": {
        "Pyerite": 3200,
        "Mexallon": 1200,
        "Zydrine": 160
      },
      "Compressed Triclinic Bistot": {
        "Pyerite": 3360,
        "Mexallon": 1260,
        "Zydrine": 168
      },
      "Compressed Monoclinic Bistot": {
        "Pyerite": 3520,
        "Mexallon": 1320,
        "Zydrine": 176
      },
      "Compressed Cubic Bistot": {
        "Pyerite": 3680,
        "Mexallon": 1380,
        "Zydrine": 184
      },
      "Compressed Crokite": {
        "Pyerite": 800,
        "Mexallon": 2000,
        "Nocxium": 800
      },
      "Compressed Sharp Crokite": {
        "Pyerite": 840,
        "Mexallon": 2100,
        "Nocxium": 840
      },
      "Compressed Crystalline Crokite": {
        "Pyerite": 880,
        "Mexallon": 2200,
        "Nocxium": 880
      },
      "Compressed Pellucid Crokite": {
        "Pyerite": 920,
        "Mexallon": 2300,
        "Nocxium": 920
      },
      "Compressed Dark Ochre": {
        "Mexallon": 1360,
        "Isogen": 1200,
        "Nocxium": 320
      },
      "Compressed Onyx Ochre": {
        "Mexallon": 1428,
        "Isogen": 1260,
        "Nocxium": 336
      },
      "Compressed Obsidian Ochre": {
        "Mexallon": 1496,
        "Isogen": 1320,
        "Nocxium": 352
      },
      "Compressed Jet Ochre": {
        "Mexallon": 1564,
        "Isogen": 1380,
        "Nocxium": 368
      },
      "Compressed Ducinium": {
        "Megacyte": 170
      },
      "Compressed Noble Ducinium": {
        "Megacyte": 179
      },
      "Compressed Royal Ducinium": {
        "Megacyte": 187
      },
      "Compressed Imperial Ducinium": {
        "Megacyte": 196
      },
      "Compressed Eifyrium": {
        "Zydrine": 266
      },
      "Compressed Doped Eifyrium": {
        "Zydrine": 279
      },
      "Compressed Boosted Eifyrium": {
        "Zydrine": 293
      },
      "Compressed Augmented Eifyrium": {
        "Zydrine": 306
      },
      "Compressed Gneiss": {
        "Pyerite": 2000,
        "Mexallon": 1500,
        "Isogen": 800
      },
      "Compressed Iridescent Gneiss": {
        "Pyerite": 2100,
        "Mexallon": 1575,
        "Isogen": 840
      },
      "Compressed Prismatic Gneiss": {
        "Pyerite": 2200,
        "Mexallon": 1650,
        "Isogen": 880
      },
      "Compressed Brilliant Gneiss": {
        "Pyerite": 2300,
        "Mexallon": 1725,
        "Isogen": 920
      },
      "Compressed Hedbergite": {
        "Pyerite": 450,
        "Nocxium": 120
      },
      "Compressed Vitric Hedbergite": {
        "Pyerite": 473,
        "Nocxium": 126
      },
      "Compressed Glazed Hedbergite": {
        "Pyerite": 495,
        "Nocxium": 132
      },
      "Compressed Lustrous Hedbergite": {
        "Pyerite": 518,
        "Nocxium": 138
      },
      "Compressed Hemorphite": {
        "Isogen": 240,
        "Nocxium": 90
      },
      "Compressed Vivid Hemorphite": {
        "Isogen": 252,
        "Nocxium": 95
      },
      "Compressed Radiant Hemorphite": {
        "Isogen": 264,
        "Nocxium": 99
      },
      "Compressed Scintillating Hemorphite": {
        "Isogen": 276,
        "Nocxium": 104
      },
      "Compressed Jaspet": {
        "Mexallon": 150,
        "Nocxium": 50
      },
      "Compressed Pure Jaspet": {
        "Mexallon": 158,
        "Nocxium": 53
      },
      "Compressed Pristine Jaspet": {
        "Mexallon": 165,
        "Nocxium": 55
      },
      "Compressed Immaculate Jaspet": {
        "Mexallon": 173,
        "Nocxium": 58
      },
      "Compressed Kernite": {
        "Mexallon": 60,
        "Isogen": 120
      },
      "Compressed Luminous Kernite": {
        "Mexallon": 63,
        "Isogen": 126
      },
      "Compressed Fiery Kernite": {
        "Mexallon": 66,
        "Isogen": 132
      },
      "Compressed Resplendant Kernite": {
        "Mexallon": 69,
        "Isogen": 138
      },
      "Compressed Mercoxit": {
        "Morphite": 140
      },
      "Compressed Magma Mercoxit": {
        "Morphite": 147
      },
      "Compressed Vitreous Mercoxit": {
        "Morphite": 154
      },
      "Compressed Mordunium": {
        "Pyerite": 88
      },
      "Compressed Plum Mordunium": {
        "Pyerite": 92
      },
      "Compressed Prize Mordunium": {
        "Pyerite": 97
      },
      "Compressed Plunder Mordunium": {
        "Pyerite": 101
      },
      "Compressed Omber": {
        "Pyerite": 90,
        "Isogen": 75
      },
      "Compressed Silvery Omber": {
        "Pyerite": 95,
        "Isogen": 79
      },
      "Compressed Golden Omber": {
        "Pyerite": 99,
        "Isogen": 83
      },
      "Compressed Platinoid Omber": {
        "Pyerite": 104,
        "Isogen": 87
      },
      "Compressed Plagioclase": {
        "Tritanium": 174,
        "Mexallon": 70
      },
      "Compressed Azure Plagioclase": {
        "Tritanium": 183,
        "Mexallon": 74
      },
      "Compressed Rich Plagioclase": {
        "Tritanium": 192,
        "Mexallon": 77
      },
      "Compressed Sparkling Plagioclase": {
        "Tritanium": 201,
        "Mexallon": 81
      },
      "Compressed Pyroxeres": {
        "Pyerite": 90,
        "Mexallon": 30
      },
      "Compressed Solid Pyroxeres": {
        "Pyerite": 95,
        "Mexallon": 32
      },
      "Compressed Viscous Pyroxeres": {
        "Pyerite": 99,
        "Mexallon": 33
      },
      "Compressed Opulent Pyroxeres": {
        "Pyerite": 104,
        "Mexallon": 35
      },
      "Compressed Rakovene": {
        "Tritanium": 40000,
        "Isogen": 3200,
        "Zydrine": 200
      },
      "Compressed Abyssal Rakovene": {
        "Tritanium": 42000,
        "Isogen": 3360,
        "Zydrine": 210
      },
      "Compressed Hadal Rakovene": {
        "Tritanium": 44000,
        "Isogen": 3520,
        "Zydrine": 220
      },
      "Compressed Scordite": {
        "Tritanium": 150,
        "Pyerite": 99
      },
      "Compressed Condensed Scordite": {
        "Tritanium": 158,
        "Pyerite": 103
      },
      "Compressed Massive Scordite": {
        "Tritanium": 165,
        "Pyerite": 110
      },
      "Compressed Glossy Scordite": {
        "Tritanium": 173,
        "Pyerite": 114
      },
      "Compressed Spodumain": {
        "Tritanium": 48000,
        "Isogen": 1000,
        "Nocxium": 160,
        "Zydrine": 80,
        "Megacyte": 40
      },
      "Compressed Bright Spodumain": {
        "Tritanium": 50400,
        "Isogen": 1050,
        "Nocxium": 168,
        "Zydrine": 84,
        "Megacyte": 42
      },
      "Compressed Gleaming Spodumain": {
        "Tritanium": 52800,
        "Isogen": 1100,
        "Nocxium": 176,
        "Zydrine": 88,
        "Megacyte": 44
      },
      "Compressed Dazzling Spodumain": {
        "Tritanium": 55200,
        "Isogen": 1150,
        "Nocxium": 184,
        "Zydrine": 92,
        "Megacyte": 46
      },
      "Compressed Talassonite": {
        "Tritanium": 40000,
        "Nocxium": 960,
        "Megacyte": 32
      },
      "Compressed Abyssal Talassonite": {
        "Tritanium": 42000,
        "Nocxium": 1008,
        "Megacyte": 34
      },
      "Compressed Hadal Talassonite": {
        "Tritanium": 44000,
        "Nocxium": 1056,
        "Megacyte": 36
      },
      "Compressed Veldspar": {
        "Tritanium": 400
      },
      "Compressed Concentrated Veldspar": {
        "Tritanium": 420
      },
      "Compressed Dense Veldspar": {
        "Tritanium": 440
      },
      "Compressed Stable Veldspar": {
        "Tritanium": 460
      },
      "Compressed Ytirium": {
        "Isogen": 240
      },
      "Compressed Bootleg Ytirium": {
        "Isogen": 252
      },
      "Compressed Firewater Ytirium": {
        "Isogen": 264
      },
      "Compressed Moonshine Ytirium": {
        "Isogen": 276
      },
      "Compressed Bitumens": {
        "Pyerite": 6000,
        "Mexallon": 400
      },
      "Compressed Brimful Bitumens": {
        "Pyerite": 6900,
        "Mexallon": 460
      },
      "Compressed Glistening Bitumens": {
        "Pyerite": 12000,
        "Mexallon": 800
      },
      "Compressed Coesite": {
        "Pyerite": 2000,
        "Mexallon": 400
      },
      "Compressed Brimful Coesite": {
        "Pyerite": 2300,
        "Mexallon": 460
      },
      "Compressed Glistening Coesite": {
        "Pyerite": 4000,
        "Mexallon": 800
      },
      "Compressed Sylvite": {
        "Pyerite": 4000,
        "Mexallon": 400
      },
      "Compressed Brimful Sylvite": {
        "Pyerite": 4600,
        "Mexallon": 460
      },
      "Compressed Glistening Sylvite": {
        "Pyerite": 8000,
        "Mexallon": 800
      },
      "Compressed Zeolites": {
        "Pyerite": 8000,
        "Mexallon": 400
      },
      "Compressed Brimful Zeolites": {
        "Pyerite": 9200,
        "Mexallon": 460
      },
      "Compressed Glistening Zeolites": {
        "Pyerite": 16000,
        "Mexallon": 800
      },
      "Arkonor": {
        "Pyerite": 3200,
        "Mexallon": 1200,
        "Megacyte": 120
      },
      "Crimson Arkonor": {
        "Pyerite": 3360,
        "Mexallon": 1260,
        "Megacyte": 126
      },
      "Prime Arkonor": {
        "Pyerite": 3520,
        "Mexallon": 1320,
        "Megacyte": 132
      },
      "Flawless Arkonor": {
        "Pyerite": 3680,
        "Mexallon": 1380,
        "Megacyte": 138
      },
      "Bezdnacine": {
        "Tritanium": 40000,
        "Isogen": 4800,
        "Megacyte": 128
      },
      "Abyssal Bezdnacine": {
        "Tritanium": 42000,
        "Isogen": 5040,
        "Megacyte": 135
      },
      "Hadal Bezdnacine": {
        "Tritanium": 44000,
        "Isogen": 5280,
        "Megacyte": 141
      },
      "Bistot": {
        "Pyerite": 3200,
        "Mexallon": 1200,
        "Zydrine": 160
      },
      "Triclinic Bistot": {
        "Pyerite": 3360,
        "Mexallon": 1260,
        "Zydrine": 168
      },
      "Monoclinic Bistot": {
        "Pyerite": 3520,
        "Mexallon": 1320,
        "Zydrine": 176
      },
      "Cubic Bistot": {
        "Pyerite": 3680,
        "Mexallon": 1380,
        "Zydrine": 184
      },
      "Crokite": {
        "Pyerite": 800,
        "Mexallon": 2000,
        "Nocxium": 800
      },
      "Sharp Crokite": {
        "Pyerite": 840,
        "Mexallon": 2100,
        "Nocxium": 840
      },
      "Crystalline Crokite": {
        "Pyerite": 880,
        "Mexallon": 2200,
        "Nocxium": 880
      },
      "Pellucid Crokite": {
        "Pyerite": 920,
        "Mexallon": 2300,
        "Nocxium": 920
      },
      "Dark Ochre": {
        "Mexallon": 1360,
        "Isogen": 1200,
        "Nocxium": 320
      },
      "Onyx Ochre": {
        "Mexallon": 1428,
        "Isogen": 1260,
        "Nocxium": 336
      },
      "Obsidian Ochre": {
        "Mexallon": 1496,
        "Isogen": 1320,
        "Nocxium": 352
      },
      "Jet Ochre": {
        "Mexallon": 1564,
        "Isogen": 1380,
        "Nocxium": 368
      },
      "Ducinium": {
        "Megacyte": 170
      },
      "Noble Ducinium": {
        "Megacyte": 179
      },
      "Royal Ducinium": {
        "Megacyte": 187
      },
      "Imperial Ducinium": {
        "Megacyte": 196
      },
      "Eifyrium": {
        "Zydrine": 266
      },
      "Doped Eifyrium": {
        "Zydrine": 279
      },
      "Boosted Eifyrium": {
        "Zydrine": 293
      },
      "Augmented Eifyrium": {
        "Zydrine": 306
      },
      "Gneiss": {
        "Pyerite": 2000,
        "Mexallon": 1500,
        "Isogen": 800
      },
      "Iridescent Gneiss": {
        "Pyerite": 2100,
        "Mexallon": 1575,
        "Isogen": 840
      },
      "Prismatic Gneiss": {
        "Pyerite": 2200,
        "Mexallon": 1650,
        "Isogen": 880
      },
      "Brilliant Gneiss": {
        "Pyerite": 2300,
        "Mexallon": 1725,
        "Isogen": 920
      },
      "Hedbergite": {
        "Pyerite": 450,
        "Nocxium": 120
      },
      "Vitric Hedbergite": {
        "Pyerite": 473,
        "Nocxium": 126
      },
      "Glazed Hedbergite": {
        "Pyerite": 495,
        "Nocxium": 132
      },
      "Lustrous Hedbergite": {
        "Pyerite": 518,
        "Nocxium": 138
      },
      "Hemorphite": {
        "Isogen": 240,
        "Nocxium": 90
      },
      "Vivid Hemorphite": {
        "Isogen": 252,
        "Nocxium": 95
      },
      "Radiant Hemorphite": {
        "Isogen": 264,
        "Nocxium": 99
      },
      "Scintillating Hemorphite": {
        "Isogen": 276,
        "Nocxium": 104
      },
      "Jaspet": {
        "Mexallon": 150,
        "Nocxium": 50
      },
      "Pure Jaspet": {
        "Mexallon": 158,
        "Nocxium": 53
      },
      "Pristine Jaspet": {
        "Mexallon": 165,
        "Nocxium": 55
      },
      "Immaculate Jaspet": {
        "Mexallon": 173,
        "Nocxium": 58
      },
      "Kernite": {
        "Mexallon": 60,
        "Isogen": 120
      },
      "Luminous Kernite": {
        "Mexallon": 63,
        "Isogen": 126
      },
      "Fiery Kernite": {
        "Mexallon": 66,
        "Isogen": 132
      },
      "Resplendant Kernite": {
        "Mexallon": 69,
        "Isogen": 138
      },
      "Mercoxit": {
        "Morphite": 140
      },
      "Magma Mercoxit": {
        "Morphite": 147
      },
      "Vitreous Mercoxit": {
        "Morphite": 154
      },
      "Mordunium": {
        "Pyerite": 88
      },
      "Plum Mordunium": {
        "Pyerite": 92
      },
      "Prize Mordunium": {
        "Pyerite": 97
      },
      "Plunder Mordunium": {
        "Pyerite": 101
      },
      "Omber": {
        "Pyerite": 90,
        "Isogen": 75
      },
      "Silvery Omber": {
        "Pyerite": 95,
        "Isogen": 79
      },
      "Golden Omber": {
        "Pyerite": 99,
        "Isogen": 83
      },
      "Platinoid Omber": {
        "Pyerite": 104,
        "Isogen": 87
      },
      "Plagioclase": {
        "Tritanium": 174,
        "Mexallon": 70
      },
      "Azure Plagioclase": {
        "Tritanium": 183,
        "Mexallon": 74
      },
      "Rich Plagioclase": {
        "Tritanium": 192,
        "Mexallon": 77
      },
      "Sparkling Plagioclase": {
        "Tritanium": 201,
        "Mexallon": 81
      },
      "Pyroxeres": {
        "Pyerite": 90,
        "Mexallon": 30
      },
      "Solid Pyroxeres": {
        "Pyerite": 95,
        "Mexallon": 32
      },
      "Viscous Pyroxeres": {
        "Pyerite": 99,
        "Mexallon": 33
      },
      "Opulent Pyroxeres": {
        "Pyerite": 104,
        "Mexallon": 35
      },
      "Rakovene": {
        "Tritanium": 40000,
        "Isogen": 3200,
        "Zydrine": 200
      },
      "Abyssal Rakovene": {
        "Tritanium": 42000,
        "Isogen": 3360,
        "Zydrine": 210
      },
      "Hadal Rakovene": {
        "Tritanium": 44000,
        "Isogen": 3520,
        "Zydrine": 220
      },
      "Scordite": {
        "Tritanium": 150,
        "Pyerite": 99
      },
      "Condensed Scordite": {
        "Tritanium": 158,
        "Pyerite": 103
      },
      "Massive Scordite": {
        "Tritanium": 165,
        "Pyerite": 110
      },
      "Glossy Scordite": {
        "Tritanium": 173,
        "Pyerite": 114
      },
      "Spodumain": {
        "Tritanium": 48000,
        "Isogen": 1000,
        "Nocxium": 160,
        "Zydrine": 80,
        "Megacyte": 40
      },
      "Bright Spodumain": {
        "Tritanium": 50400,
        "Isogen": 1050,
        "Nocxium": 168,
        "Zydrine": 84,
        "Megacyte": 42
      },
      "Gleaming Spodumain": {
        "Tritanium": 52800,
        "Isogen": 1100,
        "Nocxium": 176,
        "Zydrine": 88,
        "Megacyte": 44
      },
      "Dazzling Spodumain": {
        "Tritanium": 55200,
        "Isogen": 1150,
        "Nocxium": 184,
        "Zydrine": 92,
        "Megacyte": 46
      },
      "Talassonite": {
        "Tritanium": 40000,
        "Nocxium": 960,
        "Megacyte": 32
      },
      "Abyssal Talassonite": {
        "Tritanium": 42000,
        "Nocxium": 1008,
        "Megacyte": 34
      },
      "Hadal Talassonite": {
        "Tritanium": 44000,
        "Nocxium": 1056,
        "Megacyte": 36
      },
      "Veldspar": {
        "Tritanium": 400
      },
      "Concentrated Veldspar": {
        "Tritanium": 420
      },
      "Dense Veldspar": {
        "Tritanium": 440
      },
      "Stable Veldspar": {
        "Tritanium": 460
      },
      "Ytirium": {
        "Isogen": 240
      },
      "Bootleg Ytirium": {
        "Isogen": 252
      },
      "Firewater Ytirium": {
        "Isogen": 264
      },
      "Moonshine Ytirium": {
        "Isogen": 276
      },
      "Bitumens": {
        "Pyerite": 6000,
        "Mexallon": 400
      },
      "Brimful Bitumens": {
        "Pyerite": 6900,
        "Mexallon": 460
      },
      "Glistening Bitumens": {
        "Pyerite": 12000,
        "Mexallon": 800
      },
      "Coesite": {
        "Pyerite": 2000,
        "Mexallon": 400
      },
      "Brimful Coesite": {
        "Pyerite": 2300,
        "Mexallon": 460
      },
      "Glistening Coesite": {
        "Pyerite": 4000,
        "Mexallon": 800
      },
      "Sylvite": {
        "Pyerite": 4000,
        "Mexallon": 400
      },
      "Brimful Sylvite": {
        "Pyerite": 4600,
        "Mexallon": 460
      },
      "Glistening Sylvite": {
        "Pyerite": 8000,
        "Mexallon": 800
      },
      "Zeolites": {
        "Pyerite": 8000,
        "Mexallon": 400
      },
      "Brimful Zeolites": {
        "Pyerite": 9200,
        "Mexallon": 460
      },
      "Glistening Zeolites": {
        "Pyerite": 16000,
        "Mexallon": 800
      }
    };

    function calcYield() {
        const f = new FormData(document.getElementById('reprocessing-form'));
        const R  = parseInt(f.get('R'))  || 0;
        const Re = parseInt(f.get('Re')) || 0;
        const Op = parseInt(f.get('Op')) || 0;
        const structure = f.get('structure');
        const sec = f.get('sec');
        const rig = f.get('rig');
        const imp = f.get('imp');

        const Rm = (rig === 't1') ? 1 : (rig === 't2') ? 3 : 0;
        const Sec = (rig === 'none') ? 0 : (sec === 'lowsec') ? 0.06 : (sec === 'nullsec') ? 0.12 : 0;
        const Sm = (structure === 'athanor') ? 0.02 : (structure === 'tatara') ? 0.055 : 0;
        const Im = { 'none': 0, '801': 0.01, '802': 0.02, '804': 0.04 }[imp] || 0;

        const base = 50 + Rm;
        const yieldValue = base
            * (1 + Sec)
            * (1 + Sm)
            * (1 + R * 0.03)
            * (1 + Re * 0.02)
            * (1 + Op * 0.02)
            * (1 + Im);

        // Internally store full precision (not rounded)
        window.reprocessingYield = (yieldValue / 100); // Calculate to 4 decimals

        // Display only 2 decimals to user
        document.getElementById('result').innerHTML =
        `<strong>Reprocessing Percentage:</strong> ${yieldValue.toFixed(2)}%`;
    }

    function calculateMineralYield() {
        if (typeof window.reprocessingYield === 'undefined') {
            alert("Please calculate your yield percentage first.");
            return;
        }
    
        let lines = document.getElementById('ore-input').value.trim().split('\n');
        let yieldRate = window.reprocessingYield;
        let totalResults = {};
    
        lines.forEach(line => {
            // Match format: "Ore Name<TAB or space>Quantity<TAB>Extra"
            let match = line.trim().match(/^(.+?)\s+([\d,]+)(\s|$)/);
            if (!match) return;
    
            let oreName = match[1].trim();
            let qty = parseInt(match[2].replace(/,/g, '')); // strip commas
            if (!qty || !oreName) return;
    
            // Case-insensitive ore matching
            let matchedOre = Object.keys(oreData).find(
                key => key.toLowerCase() === oreName.toLowerCase()
            );
            if (!matchedOre) return;
    
            let comps = oreData[matchedOre];
            let batches = Math.floor(qty / 100);
            if (batches <= 0) return;
    
            for (let mineral in comps) {
                let amount = Math.floor(comps[mineral] * batches * yieldRate);
                totalResults[mineral] = (totalResults[mineral] || 0) + amount;
            }
        });
    
        // Display results
        let html = '<strong>Minerals Yielded:</strong><br>';
        if (!Object.keys(totalResults).length) {
            html += 'No valid ore input.';
        } else {
            for (let [min, amt] of Object.entries(totalResults)) {
             html += `${min} ${amt.toLocaleString()}<br>`;
            }

        }
    
        document.getElementById('mineral-result').innerHTML = html;
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('#reprocessing-form input, #reprocessing-form select').forEach(el => {
            el.addEventListener('change', calcYield);
        });
        calcYield(); // Initial call to display starting value
    });


    // Trigger on load
    calcYield();

    </script>

    <div class="ore-table-wrapper">
        <table>
            <thead><tr><th><strong>Skill</strong></th><th><strong>Affected Ore</strong></th></tr></thead>
            <tbody>
                <tr><td>Abyssal Ore Processing</td><td>Bezdnacin, Rakovene, Talassonite</td></tr>
                <tr><td>Coherent Ore Processing</td><td>Hedbergite, Hemorphite, Jaspet, Kernite, Omber</td></tr>
                <tr><td>Common Moon Ore Processing</td><td>Cobaltite, Euxenite, Titanite, Scheelite</td></tr>
                <tr><td>Complex Ore Processing</td><td>Arkonor, Bistot, Spodumain</td></tr>
                <tr><td>Exceptional Moon Ore Processing</td><td>Xenotime, Monazite, Loparite, Ytterbite</td></tr>
                <tr><td>Rare Moon Ore Processing</td><td>Carnotite, Zircon, Pollucite, Cinnabar</td></tr>
                <tr><td>Simple Ore Processing</td><td>Plagioclase, Pyroxeres, Scordite, Veldspar</td></tr>
                <tr><td>Ubiquitous Moon Ore Processing</td><td>Zeolites, Sylvite, Bitumens, Coesite</td></tr>
                <tr><td>Uncommon Moon Ore Processing</td><td>Otavite, Sperrylite, Vanadinite, Chromite</td></tr>
                <tr><td>Variegated Ore Processing</td><td>Crokite, Dark Ochre, Gneiss</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ore-table-wrapper">
        <table>
            <thead><tr><th><strong>Ore</strong></th><th><strong>Skill</strong></th></tr></thead>
            <tbody>
                <?php
                $oreSkills = [
                    "Arkonor" => "Complex Ore Processing", "Bezdnacin" => "Abyssal Ore Processing",
                    "Bistot" => "Complex Ore Processing", "Bitumens" => "Ubiquitous Moon Ore Processing",
                    "Carnotite" => "Rare Moon Ore Processing", "Chromite" => "Uncommon Moon Ore Processing",
                    "Cinnabar" => "Rare Moon Ore Processing", "Coesite" => "Ubiquitous Moon Ore Processing",
                    "Cobaltite" => "Common Moon Ore Processing", "Crokite" => "Variegated Ore Processing",
                    "Dark Ochre" => "Variegated Ore Processing", "Euxenite" => "Common Moon Ore Processing",
                    "Gneiss" => "Variegated Ore Processing", "Hedbergite" => "Coherent Ore Processing",
                    "Hemorphite" => "Coherent Ore Processing", "Jaspet" => "Coherent Ore Processing",
                    "Kernite" => "Coherent Ore Processing", "Loparite" => "Exceptional Moon Ore Processing",
                    "Monazite" => "Exceptional Moon Ore Processing", "Omber" => "Coherent Ore Processing",
                    "Otavite" => "Uncommon Moon Ore Processing", "Plagioclase" => "Simple Ore Processing",
                    "Pollucite" => "Rare Moon Ore Processing", "Pyroxeres" => "Simple Ore Processing",
                    "Rakovene" => "Abyssal Ore Processing", "Scordite" => "Simple Ore Processing",
                    "Scheelite" => "Common Moon Ore Processing", "Spodumain" => "Complex Ore Processing",
                    "Sperrylite" => "Uncommon Moon Ore Processing", "Sylvite" => "Ubiquitous Moon Ore Processing",
                    "Talassonite" => "Abyssal Ore Processing", "Titanite" => "Common Moon Ore Processing",
                    "Vanadinite" => "Uncommon Moon Ore Processing", "Veldspar" => "Simple Ore Processing",
                    "Xenotime" => "Exceptional Moon Ore Processing", "Ytterbite" => "Exceptional Moon Ore Processing",
                    "Zircon" => "Rare Moon Ore Processing"
                ];
                ksort($oreSkills);
                foreach ($oreSkills as $ore => $skill) {
                    echo "<tr><td>$ore</td><td>$skill</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
<?php
    return ob_get_clean();
}

add_shortcode('reprocessing_calculator', 'eve_reprocessing_calculator_shortcode');