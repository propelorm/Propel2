<patTemplate:tmpl name="page">
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>{TITLE}</title>
	<style>
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
	</style>
</head>

<body>

<patTemplate:tmpl name="form">
	{START}
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