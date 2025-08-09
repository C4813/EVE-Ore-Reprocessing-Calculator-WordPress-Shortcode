<?php
// eve-reprocessing-calculator.php
/*
Plugin Name: EVE Ore Reprocessing Calculator
Description: Adds a shortcode [reprocessing_calculator] to display an EVE Online Ore Reprocessing Calculator. Includes admin settings for default selections.
Version: 2.3.0
Author: C4813
*/

defined('ABSPATH') || exit;

if (!defined('EVE_REPROC_VERSION')) {
    define('EVE_REPROC_VERSION', '2.2.0');
}

/**
 * Canonical ore-skill groups (IDs used in inputs) and labels.
 */
function eve_reproc_get_ore_skill_groups(): array {
    return [
        'abyssal'     => 'Abyssal Ore',
        'coherent'    => 'Coherent Ore',
        'common'      => 'Common Moon Ore',
        'complex'     => 'Complex Ore',
        'exceptional' => 'Exceptional Moon Ore',
        'ice'         => 'Ice Ore',
        'mercoxit'    => 'Mercoxit Ore',
        'rare'        => 'Rare Moon Ore',
        'simple'      => 'Simple Ore',
        'ubiquitous'  => 'Ubiquitous Moon Ore',
        'uncommon'    => 'Uncommon Moon Ore',
        'variegated'  => 'Variegated Ore',
    ];
}

/**
 * Default option values.
 */
function eve_reproc_default_settings(): array {
    $ore_groups_default = array_fill_keys(array_keys(eve_reproc_get_ore_skill_groups()), 5);
    return [
        'skill_R'   => 5,
        'skill_Re'  => 5,
        'structure' => 'athanor',   // npc|athanor|tatara
        'security'  => 'hisec',     // hisec|lowsec|nullsec
        'rig'       => 'none',      // none|t1|t2
        'implant'   => '804',       // none|801|802|804
        'ore_skills'=> $ore_groups_default, // each 0..5
    ];
}

/**
 * Fetch settings merged with defaults and clamped.
 */
function eve_reproc_get_settings(): array {
    $saved    = get_option('eve_reproc_settings', []);
    $defaults = eve_reproc_default_settings();
    $merged   = array_merge($defaults, is_array($saved) ? $saved : []);

    // Ensure ore_skills contains all known keys, clamp values 0..5
    $groups = array_keys(eve_reproc_get_ore_skill_groups());
    $merged['ore_skills'] = array_merge(
        array_fill_keys($groups, 5),
        is_array($merged['ore_skills'] ?? null) ? $merged['ore_skills'] : []
    );
    foreach ($merged['ore_skills'] as $k => $v) {
        $v = (int)$v;
        if ($v < 0 || $v > 5) $v = 5;
        $merged['ore_skills'][$k] = $v;
    }
    return $merged;
}

/**
 * Sanitize settings: allow only current, valid choices; whitelist ore keys.
 */
function eve_reproc_sanitize_settings($input) {
    $out = eve_reproc_default_settings();
    $skill_clamp = static function($n){ $n=(int)$n; return max(0, min(5, $n)); };

    // Core skills
    if (isset($input['skill_R']))  { $out['skill_R']  = $skill_clamp($input['skill_R']); }
    if (isset($input['skill_Re'])) { $out['skill_Re'] = $skill_clamp($input['skill_Re']); }

    // Selects (strict lists)
    $structure_allowed = ['npc','athanor','tatara'];
    $security_allowed  = ['hisec','lowsec','nullsec'];
    $rig_allowed       = ['none','t1','t2'];
    $implant_allowed   = ['none','801','802','804'];

    if (isset($input['structure']) && in_array($input['structure'], $structure_allowed, true)) {
        $out['structure'] = $input['structure'];
    }
    if (isset($input['security']) && in_array($input['security'], $security_allowed, true)) {
        $out['security'] = $input['security'];
    }
    if (isset($input['rig']) && in_array($input['rig'], $rig_allowed, true)) {
        $out['rig'] = $input['rig'];
    }
    if (isset($input['implant']) && in_array($input['implant'], $implant_allowed, true)) {
        $out['implant'] = $input['implant'];
    }

    // Ore-specific skills: whitelist keys, clamp values
    $allowed_keys = array_fill_keys(array_keys(eve_reproc_get_ore_skill_groups()), true);
    $out['ore_skills'] = [];
    if (!empty($input['ore_skills']) && is_array($input['ore_skills'])) {
        foreach ($input['ore_skills'] as $k => $v) {
            if (isset($allowed_keys[$k])) {
                $out['ore_skills'][$k] = $skill_clamp($v);
            }
        }
    }
    // Ensure all keys exist
    foreach ($allowed_keys as $k => $_) {
        if (!isset($out['ore_skills'][$k])) $out['ore_skills'][$k] = 5;
    }

    return $out;
}

/**
 * Register settings/fields.
 */
add_action('admin_init', function () {
    register_setting(
        'eve_reproc_settings_group',
        'eve_reproc_settings',
        [
            'type'              => 'array',
            'sanitize_callback' => 'eve_reproc_sanitize_settings',
            'default'           => eve_reproc_default_settings(),
            'show_in_rest'      => false,
        ]
    );

    add_settings_section(
        'eve_reproc_main_section',
        'EVE Reprocessing Defaults',
        function () {
            echo '<p>Choose the default values shown in the Ore Reprocessing Calculator. Users can still change them on the page.</p>';
        },
        'eve_reproc_settings_page'
    );

    // Core skills
    add_settings_field(
        'eve_reproc_skill_R',
        'Reprocessing (0–5)',
        function () {
            $opt = eve_reproc_get_settings();
            echo '<select name="eve_reproc_settings[skill_R]">';
            for ($i = 0; $i <= 5; $i++) {
                printf('<option value="%1$d" %2$s>%1$d</option>', $i, selected($opt['skill_R'], $i, false));
            }
            echo '</select>';
        },
        'eve_reproc_settings_page',
        'eve_reproc_main_section'
    );

    add_settings_field(
        'eve_reproc_skill_Re',
        'Reprocessing Efficiency (0–5)',
        function () {
            $opt = eve_reproc_get_settings();
            echo '<select name="eve_reproc_settings[skill_Re]">';
            for ($i = 0; $i <= 5; $i++) {
                printf('<option value="%1$d" %2$s>%1$d</option>', $i, selected($opt['skill_Re'], $i, false));
            }
            echo '</select>';
        },
        'eve_reproc_settings_page',
        'eve_reproc_main_section'
    );

    // Structure/security/rig/implant
    add_settings_field(
        'eve_reproc_structure',
        'Structure',
        function () {
            $opt = eve_reproc_get_settings();
            $choices = ['npc' => 'NPC Station / Citadel', 'athanor' => 'Athanor', 'tatara' => 'Tatara'];
            echo '<select name="eve_reproc_settings[structure]">';
            foreach ($choices as $val => $label) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['structure'], $val, false), esc_html($label));
            }
            echo '</select>';
        },
        'eve_reproc_settings_page',
        'eve_reproc_main_section'
    );

    add_settings_field(
        'eve_reproc_security',
        'Security',
        function () {
            $opt = eve_reproc_get_settings();
            $choices = ['hisec' => 'Highsec', 'lowsec' => 'Lowsec', 'nullsec' => 'Null/J-space'];
            echo '<select name="eve_reproc_settings[security]">';
            foreach ($choices as $val => $label) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['security'], $val, false), esc_html($label));
            }
            echo '</select>';
        },
        'eve_reproc_settings_page',
        'eve_reproc_main_section'
    );

    add_settings_field(
        'eve_reproc_rig',
        'Rig',
        function () {
            $opt = eve_reproc_get_settings();
            $choices = ['none' => 'None', 't1' => 'T1', 't2' => 'T2'];
            echo '<select name="eve_reproc_settings[rig]">';
            foreach ($choices as $val => $label) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['rig'], $val, false), esc_html($label));
            }
            echo '</select>';
        },
        'eve_reproc_settings_page',
        'eve_reproc_main_section'
    );

    add_settings_field(
        'eve_reproc_implant',
        'Implant',
        function () {
            $opt = eve_reproc_get_settings();
            $choices = ['none' => 'None', '801' => 'RX-801 (+1%)', '802' => 'RX-802 (+2%)', '804' => 'RX-804 (+4%)'];
            echo '<select name="eve_reproc_settings[implant]">';
            foreach ($choices as $val => $label) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['implant'], $val, false), esc_html($label));
            }
            echo '</select>';
        },
        'eve_reproc_settings_page',
        'eve_reproc_main_section'
    );

    // Ore-specific skills (0..5)
    add_settings_field(
        'eve_reproc_ore_skills',
        'Ore-Specific Skills (0–5)',
        function () {
            $opt = eve_reproc_get_settings();
            $groups = eve_reproc_get_ore_skill_groups();
            echo '<div style="display:flex;gap:40px;flex-wrap:wrap;">';
            $keys = array_keys($groups);
            $mid = (int)ceil(count($keys) / 2);
            $cols = [array_slice($keys, 0, $mid), array_slice($keys, $mid)];
            foreach ($cols as $col) {
                echo '<div>';
                foreach ($col as $gid) {
                    $label = $groups[$gid];
                    echo '<label style="display:block;margin-bottom:6px;">' . esc_html($label) . ' ';
                    echo '<select name="eve_reproc_settings[ore_skills][' . esc_attr($gid) . ']">';
                    for ($i = 0; $i <= 5; $i++) {
                        printf('<option value="%1$d" %2$s>%1$d</option>', $i, selected($opt['ore_skills'][$gid] ?? 5, $i, false));
                    }
                    echo '</select></label>';
                }
                echo '</div>';
            }
            echo '</div>';
        },
        'eve_reproc_settings_page',
        'eve_reproc_main_section'
    );
});

/**
 * Settings page.
 */
add_action('admin_menu', function () {
    add_options_page(
        'EVE Reprocessing',
        'EVE Reprocessing',
        'manage_options',
        'eve_reproc_settings_page',
        'eve_reproc_render_settings_page'
    );
});

function eve_reproc_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'eve-reproc'));
    } ?>
    <div class="wrap">
        <h1>EVE Reprocessing Defaults</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('eve_reproc_settings_group');
            do_settings_sections('eve_reproc_settings_page');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// Shortcode (scoped enqueue for assets)
// -----------------------------------------------------------------------------

/**
 * Enqueue assets only when the shortcode is used.
 */
function eve_reproc_enqueue_assets_scoped() {
    $url  = plugin_dir_url(__FILE__);
    $path = plugin_dir_path(__FILE__);

    $ver_css   = file_exists($path.'style.css')   ? filemtime($path.'style.css')   : EVE_REPROC_VERSION;
    $ver_ore   = file_exists($path.'ore-data.js') ? filemtime($path.'ore-data.js') : EVE_REPROC_VERSION;
    $ver_ice   = file_exists($path.'ice-data.js') ? filemtime($path.'ice-data.js') : EVE_REPROC_VERSION;
    $ver_skill = file_exists($path.'skills.js')   ? filemtime($path.'skills.js')   : EVE_REPROC_VERSION;
    $ver_main  = file_exists($path.'script.js')   ? filemtime($path.'script.js')   : EVE_REPROC_VERSION;

    wp_enqueue_style('eve-reprocessing-style', $url . 'style.css', [], $ver_css);
    wp_enqueue_script('ore-data',    $url . 'ore-data.js',   [], $ver_ore,   true);
    wp_enqueue_script('ice-data',    $url . 'ice-data.js',   [], $ver_ice,   true);
    wp_enqueue_script('skills-data', $url . 'skills.js',     [], $ver_skill, true);
    wp_enqueue_script('eve-reprocessing-script', $url . 'script.js', ['ore-data','ice-data','skills-data'], $ver_main, true);
}

/**
 * Shortcode markup; uses saved defaults to prefill values.
 */
function eve_reprocessing_calculator_shortcode() {
    // Enqueue assets here (scoped)
    eve_reproc_enqueue_assets_scoped();

    $opt        = eve_reproc_get_settings();
    $ore_groups = eve_reproc_get_ore_skill_groups();

    ob_start(); ?>

    <form id="reprocessing-form">
        <div class="reprocessing-container">
            <div class="reprocessing-column">
                <h3>Skills (0–5)</h3>
                <label>Reprocessing
                    <input type="number" id="R" name="R" min="0" max="5" step="1" inputmode="numeric" value="<?php echo (int)$opt['skill_R']; ?>">
                </label>
                <label>Reprocessing Efficiency
                    <input type="number" id="Re" name="Re" min="0" max="5" step="1" inputmode="numeric" value="<?php echo (int)$opt['skill_Re']; ?>">
                </label>
                <h3><strong>Implant</strong></h3>
                <select id="imp" name="imp">
                    <?php
                    $implants = ['none' => 'None', '801' => 'RX-801 (+1%)', '802' => 'RX-802 (+2%)', '804' => 'RX-804 (+4%)'];
                    foreach ($implants as $val => $label) {
                        printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['implant'], $val, false), esc_html($label));
                    }
                    ?>
                </select>
            </div>

            <div class="reprocessing-column">
                <h3>Structure Settings</h3>
                <label>Structure
                    <select id="structure" name="structure">
                        <?php
                        $structures = ['npc' => 'NPC Station / Citadel', 'athanor' => 'Athanor', 'tatara' => 'Tatara'];
                        foreach ($structures as $val => $label) {
                            printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['structure'], $val, false), esc_html($label));
                        }
                        ?>
                    </select>
                </label>
                <label>Security
                    <select id="sec" name="sec">
                        <?php
                        $secs = ['hisec' => 'Highsec', 'lowsec' => 'Lowsec', 'nullsec' => 'Null/J-space'];
                        foreach ($secs as $val => $label) {
                            printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['security'], $val, false), esc_html($label));
                        }
                        ?>
                    </select>
                </label>
                <label>Rig
                    <select id="rig" name="rig">
                        <?php
                        $rigs = ['none' => 'None', 't1' => 'T1', 't2' => 'T2'];
                        foreach ($rigs as $val => $label) {
                            printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($opt['rig'], $val, false), esc_html($label));
                        }
                        ?>
                    </select>
                </label>
            </div>
        </div>

        <div class="ore-skills-section">
            <h3 class="text-center">Ore-Specific Skills (0–5)</h3>
            <div id="ore-skills-wrapper" class="two-column-skills">
                <?php
                asort($ore_groups);
                $chunks = array_chunk($ore_groups, ceil(count($ore_groups) / 2), true);
                foreach ($chunks as $col) {
                    echo '<div class="ore-skill-column">';
                    foreach ($col as $id => $label) {
                        $val = isset($opt['ore_skills'][$id]) ? (int)$opt['ore_skills'][$id] : 5;
                        echo '<div class="ore-skill-line">';
                        echo '<label>' . esc_html($label) . '</label>';
                        echo '<div class="ore-skill-entry">';
                        echo '<input type="number" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" min="0" max="5" step="1" inputmode="numeric" value="' . $val . '">';
                        echo '<span id="yield-' . esc_attr($id) . '" class="yield-value">0.00%</span>';
                        echo '</div></div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </form>

    <p class="text-center note">
        If you change any of the above settings <i>after</i> calculating the yield, you must recalculate.
    </p>

    <div class="result-wrap">
        <textarea id="ore-input" rows="6" class="textarea-wide" placeholder="Paste your ore quantities here&#10;e.g.&#10;Veldspar 10000&#10;Scordite 5000"></textarea><br />
        <button id="calculate-button" type="button">Calculate Yield</button>
        <div id="mineral-result" class="mt-15"></div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('reprocessing_calculator', 'eve_reprocessing_calculator_shortcode');
