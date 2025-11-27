<?php
/**
 * DeepSID
 *
 * Run the shell PHP that runs an utility PHP script.
 * 
 * For administrators only.
 * 
 * @used-by		admin_scripts.php
 */

require_once("class.account.php"); // Includes setup

if (!$account->CheckLogin() || $account->UserName() != 'JCH' || $account->UserID() != JCH)
	die("This is for administrators only.");

try {
	$allowedScripts = $account->GetDB()
	    ->query('SELECT script FROM admin_scripts WHERE script <> "" AND script IS NOT NULL')
    	->fetchAll(PDO::FETCH_COLUMN);	

} catch(PDOException $e) {
	$account->LogActivityError('run_shell.php', $e->getMessage());
	exit;
}		

$script = $_GET['script'] ?? '';

if (!in_array($script, $allowedScripts)) {
    http_response_code(403);
    echo 'Error: Script not allowed.';
    exit;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Run Script: <?php echo htmlspecialchars($script); ?></title>

		<style>
			body {
				margin: 0;
				font-family: Arial, sans-serif;
			}

			#panel {
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				background: #eee;
				border-bottom: 1px solid #bbb;
				padding: 15px;
				z-index: 1000;
			}

			#panel h1 {
				margin: 0 0 10px 0;
				font-size: 20px;
			}

			#outputTransport {
				box-sizing: border-box;

				position: absolute;
				top: 100px;
				left: 0;
				right: 0;
				bottom: 0;

				width: 100%;
				height: calc(100vh - 100px);

				border: none;
			}

			#runBtn {
				padding: 8px 15px;
				font-size: 14px;
				cursor: pointer;
			}
		</style>

		<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	</head>
	<body>
		<div id="panel">
			<h1>Script: <?php echo htmlspecialchars($script); ?></h1>
			<button id="runBtn">RUN SCRIPT</button>
			<span id="status" style="margin-left:10px; color:#666;"></span>
		</div>

		<iframe id="outputTransport"></iframe>
		<div id="output" style="display:none;"></div>

		<script>
			$("#runBtn").on("click", function() {
				$("#status").text("Running...");
				$("#output").empty();

				// Load and stream output into hidden iframe
				document.getElementById("outputTransport").src =
					"run_execute.php?script=<?php echo $script; ?>";
			});
		</script>
	</body>
</html>