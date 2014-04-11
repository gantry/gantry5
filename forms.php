<!DOCTYPE html>
<html lang="en">
<?php include(__DIR__.'/core/head.php'); ?>
<body>

<div class="container">

	<!-- Header -->
	<?php include(__DIR__.'/sections/header.php'); ?>

	<div class="grid pure-g-r">
		<div class="block pure-u-4-5">
			<section id="main">
				<div class="grid pure-g-r">
					<div class="block pure-u-1">
						<div class="content">
							<h2 class="page-title">Forms</h2>
						</div>
					</div>
				</div>
				<div class="grid pure-g-r">
					<div class="block pure-u-1">
						<div class="content">
							<fieldset>
							    <form>
							      <h2>Form Element</h2>

							      <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam dignissim convallis est. Quisque aliquam. Donec faucibus. Nunc iaculis suscipit dui.</p>

							      <p><label for="text_field">Text Field:</label>
							        <input type="text" id="text_field"></p>

							      <p><label for="text_area">Text Area:</label>
							        <textarea id="text_area"></textarea></p>

							      <p><label for="select_element">Select Element:</label>
							        <select name="select_element">
							          <optgroup label="Option Group 1">
							            <option value="1">Option 1</option>
							            <option value="2">Option 2</option>
							            <option value="3">Option 3</option>
							          </optgroup>
							          <optgroup label="Option Group 2">
							            <option value="1">Option 1</option>
							            <option value="2">Option 2</option>
							            <option value="3">Option 3</option>
							          </optgroup>
							      </select></p>

							      <p><label for="radio_buttons">Radio Buttons:</label>
							        <label>
							          <input type="radio" class="radio" name="radio_button" value="radio_1"> Radio 1
							        </label>
							        <label>
							          <input type="radio" class="radio" name="radio_button" value="radio_2"> Radio 2
							        </label>
							        <label>
							          <input type="radio" class="radio" name="radio_button" value="radio_3"> Radio 3
							        </label>
							      </p>

							      <p><label for="checkboxes">Checkboxes:</label>
							        <label>
							          <input type="checkbox" class="checkbox" name="checkboxes" value="check_1"> Radio 1
							        </label>
							        <label>
							          <input type="checkbox" class="checkbox" name="checkboxes" value="check_2"> Radio 2
							        </label>
							        <label>
							          <input type="checkbox" class="checkbox" name="checkboxes" value="check_3"> Radio 3
							        </label>
							      </p>

							      <p><label for="password">Password:</label>
							        <input type="password" class="password" name="password">
							      </p>

							      <p><label for="file">File Input:</label>
							        <input type="file" class="file" name="file">
							      </p>


							      <p><input class="button" type="submit" value="Submit"></p>
							    </form>
							  </fieldset>
						</div>
					</div>
				</div>
			</section>
		</div>
		<div class="block size-1-5 pure-u-1-5">
			<?php include(__DIR__.'/sections/sidebar.php'); ?>
		</div>
	</div>

	<!-- Footer -->
	<?php include(__DIR__.'/sections/footer.php'); ?>

</div>
    
</body>
</html>