<!DOCTYPE html>
<html lang="en">
<head>
<title>Error</title>
<style type="text/css">

.container:before, .container:after {
	display: table;
	line-height: 0;
	content: "";
}

body {
	margin: 0;
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-size: 14px;
	line-height: 20px;
	color: #333333;
	background-color: #ffffff;
	padding-top: 60px;
}

h1 {
	font-size: 38.5px;
	line-height: 40px;
	margin: 10px 0;
	font-family: inherit;
	font-weight: bold;
	color: inherit;
	text-rendering: optimizelegibility;
}

.container {
	width: 1170px;
	margin-right: auto;
	margin-left: auto;
}

p {
margin: 0 0 10px;
}

</style>
</head>
<body>
    <div class="container">
      <h1><?=$e['header']?></h1>
      <p><?=$e['message']?></p>
      <!-- mysql error code -->
      <p><?=$e['error_code']?></p>
    </div>
</body>
</html>