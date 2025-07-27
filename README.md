# EVE Ore Reprocessing Calculator WordPress- Shortcode
Adds a shortcode [reprocessing_calculator] to display an EVE Online Ore Reprocessing Calculator.

Download the latest release, extract the folder, upload the `eve-ore-reprocessing-calculator` folder to `wp-content/plugins`


**To change what structures and skills are selected by default:**

Open `eve-ore-reprocessing-calculator.php`

**Reprocessing Skill & Reprocessing Efficiency Skill**

Reprocessing Skill = Line 30; Reprocessing Skill = Line 33

Change `value="5"` to `value=x` where `x` = `0-5`
  ```
<label>Reprocessing
    <input type="number" id="R" name="R" min="0" max="5" value="5">
</label>
<label>Reprocessing Efficiency
    <input type="number" id="Re" name="Re" min="0" max="5" value="5">
</label>
 ```
**Structure, Security, Rig & Implant**

Structure = Lines 48-50; Security = Lines 55-57; Rig = Lines 62-64; Implant = Lines 37-40

remove ` selected` from the current default, add to the one you want as default.

Example:

`RX-804` is default, but you want `none` as default.

Change
```
<select id="imp" name="imp">
  <option value="none">None</option>
  <option value="801">RX-801 (+1%)</option>
  <option value="802">RX-802 (+2%)</option>
  <option value="804" selected>RX-804 (+4%)</option>
</select>
```
to
```
<select id="imp" name="imp">
     <option value="none" selected>None</option>
    <option value="801">RX-801 (+1%)</option>
    <option value="802">RX-802 (+2%)</option>
    <option value="804">RX-804 (+4%)</option>
</select>
```
**Ore-specific skills**

Find (line 96)

`echo '<input type="number" id="' . $id . '" name="' . $id . '" min="0" max="5" value="5">';`

Change `value="5"` to `value=x` where `x` = `0-5`

Why change the defaults? If you have a regular setup, you can change the defaults so all you need to do is visit the page, paste in the results, and hit calculate. It saves clicks preventing you from having to change the sttings each time you visit the page.
