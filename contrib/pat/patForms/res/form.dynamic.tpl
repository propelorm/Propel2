<patTemplate:tmpl name="page">
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>{TITLE}</title>
	<style>
		body {
			font: normal 13px arial;
		}
		form div.row {
			clear: left;
			float: left;
			margin-bottom: 5px;
		}
		form label {
			float: left !important;
			display: block;
			width: 100px;
		}
		form div div {
			float: left !important;
			margin: 0px 10px 0px 0px;
		}
		form div.descr {
			font-style: italic;
		}
		form div.buttons {
			clear: left;
			padding: 5px 0px 0px 100px;
		}
		form div.errors {
			border: solid 1px #666666;
			margin-bottom: 25px;
			padding-bottom: 10px;
			background: #f6f6f6;
		}
		form div.errors h3 {
			background: #666666;
			color: #ffffff;
			font-size: 14px;
			padding: 2px 5px 2px 10px;
			margin: 0px;
		}
		form div.errors p {
			padding: 10px 10px 10px 10px;
			margin: 0px;
		}
		form div.errors ul {
			margin: 0px;
			padding-left: 24px;
		}
		input, select {
			font: normal 12px arial;
		}
	</style>
</head>

<body>

<patTemplate:tmpl name="form">
	{START}
	<pattemplate:tmpl name="errors" visibility="hidden">
	<div class="errors">
		<h3>Validation failed</h3>
		<p>Sorry, your input could not be saved for the following reasons:</p>
		<ul>
		<pattemplate:tmpl name="error">
		<li><b>{FIELD}:</b> {MESSAGE}</li>
		</pattemplate:tmpl>
		</ul>
	</div>
	</pattemplate:tmpl>
	<patTemplate:tmpl name="elements" type="condition" conditionvar="display">
		<patTemplate:sub condition="no">
			{ELEMENT}
		</patTemplate:sub>
		<patTemplate:sub condition="__default">
			<div class="row">
				<label for="{ID}" title="{TITLE}">{LABEL}:</label>
				<div>{ELEMENT}</div>
				<div class="descr">{DESCRIPTION}</div>
			</div>
		</patTemplate:sub>
	</patTemplate:tmpl>
	<div class="buttons">
		<input type="submit" name="save" value="Save form"/>
	</div>
	{END}
</patTemplate:tmpl>

</body>
</html>
</patTemplate:tmpl>