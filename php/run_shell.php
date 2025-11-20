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

// --- CONFIG ---
$allowedScripts = [
    'check_missing_info.php',
    'test_script.php',
	'update_counts_all.php',
];

$script = $_GET['script'] ?? '';

if (!in_array($script, $allowedScripts)) {
    http_response_code(403);
    echo "Error: Script not allowed.";
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

			#output {
				margin-top: 100px;
				padding: 15px;
				white-space: pre-wrap;
				font-family: monospace;
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

		<div id="output"></div>

		<script>
			$("#runBtn").on("click", function() {
				$("#status").text("Running...");
				$("#output").empty();

				$.ajax({
					url: "run_execute.php",
					method: "POST",
					data: { script: "<?php echo $script; ?>" },
					success: function(data) {
						$("#output").html(data);
						$("#status").text("Completed.");
					},
					error: function(xhr) {
						$("#output").text("Error running script:\n\n" + xhr.responseText);
						$("#status").text("Error!");
					}
				});
			});
		</script>
	</body>
</html>