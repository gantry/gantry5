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
							<h2 class="page-title">Typography</h2>
						</div>
					</div>
				</div>
				<div class="grid pure-g-r">
					<div class="block pure-u-1">
						<div class="content">
							<h3>Headings</h3>
							<p>The header tags have been assigned rem units based on golden ratio standards and also make use of core line height based margins to provide consistent spacings between the header and content below so line heights stay equal to the grid at all times.</p>
						</div>
					</div>
				</div>
				<div class="grid pure-g-r">
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>HTML</h6>
<pre>
&lt;h1&gt;This is an H1 heading&lt;/h1&gt;
&lt;h2&gt;This is an H2 heading&lt;/h2&gt;
&lt;h3&gt;This is an H3 heading&lt;/h3&gt;
&lt;h4&gt;This is an H4 heading&lt;/h4&gt;
&lt;h5&gt;This is an H5 heading&lt;/h5&gt;
&lt;h6&gt;This is an H6 heading&lt;/h6&gt;
</pre>
						</div>
					</div>
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>Rendered</h6>
							<h1>This is an H1 heading</h1>
							<h2>This is an H2 heading</h2>
							<h3>This is an H3 heading</h3>
							<h4>This is an H4 heading</h4>
							<h5>This is an H5 heading</h5>
							<h6>This is an H6 heading</h6>
						</div>
					</div>
				</div>
				<hr>
				<div class="grid pure-g-r">
					<div class="block pure-u-1">
						<div class="content">
							<h3>Paragraphs</h3>
							<p>The paragraph element makes use of a bottom margin that is equal to the $core-line-height (24px) to keep spacing consistent between headers and paragraph breaks, as well as stay inline with the text line height grid</p>
						</div>
					</div>
				</div>
				<div class="grid pure-g-r">
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>HTML</h6>
<pre>
&lt;p&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ultricies tristique nulla et mattis. Phasellus id massa eget nisl congue blandit sit amet id ligula.&lt;/p&gt;

&lt;p&gt;Mauris faucibus nibh et nibh cursus in vestibulum sapien egestas. Curabitur ut lectus tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit.&lt;/p&gt;
</pre>
						</div>
					</div>
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>Rendered</h6>
							<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ultricies tristique nulla et mattis. Phasellus id massa eget nisl congue blandit sit amet id ligula.</p>
							<p>Mauris faucibus nibh et nibh cursus in vestibulum sapien egestas. Curabitur ut lectus tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
						</div>
					</div>
				</div>
				<hr>
				<div class="grid pure-g-r">
					<div class="block pure-u-1">
						<div class="content">
							<h3>Lists</h3>
							<p>Default list styles are preserved to provide the best fallback and browser rendering under standard circumstances. Non formatted lists are provided via additional classes.</p>
						</div>
					</div>
				</div>
				<div class="grid pure-g-r">
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>HTML</h6>
<pre>
&lt;ul&gt;
	&lt;li&gt;A much longer list item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
		&lt;ul&gt;
			&lt;li&gt;Nested List Item&lt;/li&gt;
			&lt;li&gt;Nested List Item&lt;/li&gt;
			&lt;li&gt;Nested List Item&lt;/li&gt;
		&lt;/ul&gt;
	&lt;li&gt;List Item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
&lt;/ul&gt;
</pre>
						</div>
					</div>
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>Rendered</h6>
							<ul>
								<li>A much longer list item</li>
								<li>List Item</li>
								<li>List Item</li>
								<ul>
									<li>Nested List Item</li>
									<li>Nested List Item</li>
									<li>Nested List Item</li>
								</ul>
								<li>List Item</li>
								<li>List Item</li>
								<li>List Item</li>
							</ul>
						</div>
					</div>
				</div>
								<div class="grid pure-g-r">
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>HTML</h6>
<pre>
&lt;ol&gt;
	&lt;li&gt;A much longer list item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
		&lt;ul&gt;
			&lt;li&gt;Nested List Item&lt;/li&gt;
			&lt;li&gt;Nested List Item&lt;/li&gt;
			&lt;li&gt;Nested List Item&lt;/li&gt;
		&lt;/ul&gt;
	&lt;li&gt;List Item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
	&lt;li&gt;List Item&lt;/li&gt;
&lt;/ol&gt;
</pre>
						</div>
					</div>
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>Rendered</h6>
							<ol>
								<li>A much longer list item</li>
								<li>List Item</li>
								<li>List Item</li>
								<ul>
									<li>Nested List Item</li>
									<li>Nested List Item</li>
									<li>Nested List Item</li>
								</ul>
								<li>List Item</li>
								<li>List Item</li>
								<li>List Item</li>
							</ol>
						</div>
					</div>
				</div>
				<hr>
				<div class="grid pure-g-r">
					<div class="block pure-u-1">
						<div class="content">
							<h3>Blockquote</h3>
							<p>Standard blockquote styling to provide a break from the context of the article with some very slight formatting.</p>
						</div>
					</div>
				</div>
				<div class="grid pure-g-r">
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>HTML</h6>
<pre>
&lt;blockquote&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ultricies tristique nulla et mattis.&lt;cite&gt;This is a citation&lt;/cite&gt;&lt;/blockquote&gt;
</pre>
						</div>
					</div>
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>Rendered</h6>
							<blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ultricies tristique nulla et mattis.<cite>This is a citation</cite></blockquote>
						</div>
					</div>
				</div>
				<hr>
				<div class="grid pure-g-r">
					<div class="block pure-u-1">
						<div class="content">
							<h3>Tables</h3>
							<p>Basic table styling that emphasizes white space, organization and best responsive practices</p>
						</div>
					</div>
				</div>
				<div class="grid pure-g-r">
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>HTML</h6>
<pre>
&lt;table&gt;
	&lt;thead&gt;
		&lt;tr&gt;
			&lt;th&gt;#&lt;/th&gt;
			&lt;th&gt;Header One&lt;/th&gt;
			&lt;th&gt;Header Two&lt;/th&gt;
			&lt;th&gt;Header Three&lt;/th&gt;
		&lt;/tr&gt;
	&lt;/thead&gt;
	&lt;tbody&gt;
		&lt;tr&gt;
			&lt;td&gt;1&lt;/td&gt;
			&lt;td&gt;Value One&lt;/td&gt;
			&lt;td&gt;Value Two&lt;/td&gt;
			&lt;td&gt;Value Three&lt;/td&gt;
		&lt;/tr&gt;
		&lt;tr&gt;
			&lt;td&gt;2&lt;/td&gt;
			&lt;td&gt;Value One&lt;/td&gt;
			&lt;td&gt;Value Two&lt;/td&gt;
			&lt;td&gt;Value Three&lt;/td&gt;
		&lt;/tr&gt;
		&lt;tr&gt;
			&lt;td&gt;3&lt;/td&gt;
			&lt;td&gt;Value One&lt;/td&gt;
			&lt;td&gt;Value Two&lt;/td&gt;
			&lt;td&gt;Value Three&lt;/td&gt;
		&lt;/tr&gt;
	&lt;/tbody&gt;
&lt;/table&gt;
</pre>
						</div>
					</div>
					<div class="block pure-u-1-2">
						<div class="content">
							<h6>Rendered</h6>
							<table>
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