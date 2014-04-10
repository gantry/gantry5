<!DOCTYPE html>
<html lang="en">
<?php include(__DIR__.'/core/head.php'); ?>
<body>

<div class="container">

	<!-- Header -->
	<?php include(__DIR__.'/sections/header.php'); ?>

	<section id="main">
		<div class="grid pure-g-r">
			<div class="block pure-u-1">
				<div class="content">
					<h2 class="page-title">Typography</h2>
				</div>
			</div>
		</div>
		<div class="grid pure-g-r">
			<div class="block pure-u-1-2">
				<div class="content">
					<h1>This is an H1 heading</h1>
					<h2>This is an H2 heading</h2>
					<h3>This is an H3 heading</h3>
					<h4>This is an H4 heading</h4>
					<h5>This is an H5 heading</h5>
					<h6>This is an H6 heading</h6>
				</div>
			</div>
			<div class="block size-1-4 pure-u-1-4">
				<div class="content">
					<ul>
						<li>Unordered List Item</li>
						<li>Unordered List Item</li>
						<li>Unordered List Item</li>
						<li>Unordered List Item</li>
						<li>Unordered List Item</li>
					</ul>
				</div>
			</div>
			<div class="block size-1-4 pure-u-1-4">
				<div class="content">
					<ol>
						<li>Ordered List Item</li>
						<li>Ordered List Item</li>
						<li>Ordered List Item</li>
						<li>Ordered List Item</li>
						<li>Ordered List Item</li>
					</ol>
				</div>
			</div>
		</div>
		<div class="grid pure-g-r">
			<div class="block pure-u-1-2">
				<div class="content">
					<h2>Pre</h2>
					<pre>&lt;p&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ultricies tristique nulla et mattis.&lt;/p&gt;</pre>
				</div>
			</div>
			<div class="block pure-u-1-2">
				<div class="content">
					<h2>Blockquote</h2>
					<blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ultricies tristique nulla et mattis.</blockquote>
				</div>
			</div>
		</div>
		<div class="grid pure-g-r">
			<div class="block pure-u-1-2">
				<div class="content">
					<h2>Table</h2>
					<table class="table">
						<thead>
							<tr>
							<th>#</th>
							<th>Header One</th>
							<th>Header Two</th>
							<th>Header Three</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>1</td>
								<td>Value One</td>
								<td>Value Two</td>
								<td>Value Three</td>
							</tr>
							<tr>
								<td>2</td>
								<td>Value One</td>
								<td>Value Two</td>
								<td>Value Three</td>
							</tr>
							<tr>
								<td>3</td>
								<td>Value One</td>
								<td>Value Two</td>
								<td>Value Three</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="block pure-u-1-2">
				<div class="content">
					
				</div>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<?php include(__DIR__.'/sections/footer.php'); ?>

</div>
    
</body>
</html>