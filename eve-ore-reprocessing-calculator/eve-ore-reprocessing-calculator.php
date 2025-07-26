<?php
/*
Plugin Name: EVE Ore Reprocessing Calculator
Description: Adds a shortcode [reprocessing_calculator] to display an EVE Online Ore Reprocessing Calculator.
Version: 2.0.1
Author: C4813
*/

defined('ABSPATH') or die('No script kiddies please!');

function eve_reprocessing_calculator_enqueue_assets() {
    $url = plugin_dir_url(__FILE__);

    wp_enqueue_style('eve-reprocessing-style', $url . 'style.css');
    wp_enqueue_script('ore-data', $url . 'ore-data.js', [], null, true);
    wp_enqueue_script('ice-data', $url . 'ice-data.js', [], null, true);
    wp_enqueue_script('skills-data', $url . 'skills.js', [], null, true);
    wp_enqueue_script('eve-reprocessing-script', $url . 'script.js', ['ore-data', 'ice-data', 'skills-data'], null, true);
}
add_action('wp_enqueue_scripts', 'eve_reprocessing_calculator_enqueue_assets');

function eve_reprocessing_calculator_shortcode() {
    ob_start(); ?>

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
                <h3><strong>Implant</strong></h3>
                <select id="imp" name="imp">
                    <option value="none">None</option>
                    <option value="801">RX-801 (+1%)</option>
                    <option value="802">RX-802 (+2%)</option>
                    <option value="804" selected>RX-804 (+4%)</option>
                </select>
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

        <div class="ore-skills-section">
            <h3 style="text-align: center;">Ore-Specific Skills (0–5)</h3>
            <div id="ore-skills-wrapper" class="two-column-skills">
                <?php
                $skills = [
                    'abyssal' => 'Abyssal Ore',
                    'coherent' => 'Coherent Ore',
                    'common' => 'Common Moon Ore',
                    'complex' => 'Complex Ore',
                    'exceptional' => 'Exceptional Moon Ore',
                    'ice' => 'Ice Ore',
                    'mercoxit' => 'Mercoxit Ore',
                    'rare' => 'Rare Moon Ore',
                    'simple' => 'Simple Ore',
                    'ubiquitous' => 'Ubiquitous Moon Ore',
                    'uncommon' => 'Uncommon Moon Ore',
                    'variegated' => 'Variegated Ore',
                ];
                asort($skills);
                $chunks = array_chunk($skills, ceil(count($skills) / 2), true);
                foreach ($chunks as $col) {
                    echo '<div class="ore-skill-column">';
                    foreach ($col as $id => $label) {
                        echo '<div class="ore-skill-line">';
                        echo '<label>' . $label . '</label>';
                        echo '<div class="ore-skill-entry">';
                        echo '<input type="number" id="' . $id . '" name="' . $id . '" min="0" max="5" value="5">';
                        echo '<span id="yield-' . $id . '" class="yield-value">0.00%</span>';
                        echo '</div></div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </form>
    <p style="text-align:center; margin-top: 20px; font-size:75%; font-weight: bold;">If you change any of the above settings <i>after</i> calculating the yield, you must recalculate.</p>
    <div style="text-align:center; margin-top: 30px;">
        <textarea id="ore-input" rows="6" style="width: 500px; text-align: center; margin-bottom: 15px;" placeholder="Paste your ore quantities here&#10;e.g.&#10;Veldspar 10000&#10;Scordite 5000"></textarea><br />

        <button id="calculate-button">Calculate Yield</button>
        <div id="mineral-result" style="margin-top: 15px;"></div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('reprocessing_calculator', 'eve_reprocessing_calculator_shortcode');
