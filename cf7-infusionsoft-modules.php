<?php function wpcf7_tg_pane_infusionsoft() { ?>

<div id="wpcf7-tg-pane-infusionsoft" class="hidden">
	<form action="">
		<table>
			<tbody>
				<tr>
					<td style="display:none;">
						<input type="checkbox" name="required" checked disabled>&nbsp;Required field (for InfusionSoft)
					</td>
				</tr>
				<tr>
					<td>
						Type<br>
						<select id="infusionsoft-tag" name="name">
							<option value="infusionsoft-first-name">First Name</option>
							<option value="infusionsoft-last-name">Last Name</option>
							<option value="infusionsoft-email">Email Address</option>
							<option value="infusionsoft-phone">Phone Number</option>
						</select>
					</td>
					<td></td>
				</tr>
			</tbody>
		</table>
		<table>
		<tbody>
			<tr>
				<td>
					<code>id</code> (optional)<br>
					<input type="text" name="id" class="idvalue oneline option">
				</td>
				<td>
					<code>class</code> (optional)<br>
					<input type="text" name="class" class="classvalue oneline option">
				</td>
			</tr>
			<tr>
				<td>
					<code>size</code> (optional)<br>
					<input type="number" name="size" class="numeric oneline option" min="1">
				</td>
				<td>
					<code>maxlength</code> (optional)<br>
					<input type="number" name="maxlength" class="numeric oneline option" min="1">
				</td>
			</tr>
			<tr>
				<td>
					Placeholder text (optional)<br><input type="text" name="values" class="oneline">
				</td>
				<td>
					<br><input type="checkbox" name="placeholder" class="option">&nbsp;Use placeholder text?</td>
			</tr>
		</tbody></table>

		<div class="tg-tag">
			Copy this code and paste it into the form to the left.<br>
			<input id="infusionsoft-tag-type" type="text" name="text" class="tag" readonly="readonly" onfocus="this.select()">
		</div>
		<div class="tg-mail-tag">
			And, put this code into the Mail fields below.<br>
			<input type="text" class="mail-tag" readonly="readonly" onfocus="this.select()">
		</div>

		<table>
			<tr>
				<td>
					Note:<br>
					<span style="font-size:1em; color: #666; font-style: italic; display: block;">
						To ensure that the form can successfully send data to InfusionSoft, you must add fields for <code>Email Address</code> and at least one of the following: <code>First Name</code> or <code>Last Name</code>.
						InfusionSoft requires an email address and first OR last name (at a minimum) in order to add a contact to the database.
					</span>
					<ul>
						<li>
							This plugin also supports email and phone HTML5 input types. If you wish to use HTML5, these can be used immediately, by copying and pasting any of the following into the form to the left:
						</li>
						<li>
							Email: <code>[email* infusionsoft-email]</code>
						</li>
						<li>
							Phone: <code>[tel infusionsoft-phone]</code>
						</li>
					</ul>
				</td>
			</tr>
		</table>
	</form>
</div>

<?php } ?>