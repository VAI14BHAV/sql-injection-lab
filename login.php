<?php
// Database connection
$conn = mysqli_connect("localhost", "vulnuser", "vulnpass", "sqli_demo");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vulnerable Login</title>
</head>
<body>
    <h2>Login Form (Vulnerable to SQLi)</h2>
    <form method="POST" action="">
        Username: <input type="text" name="username"><br><br>
        Password: <input type="password" name="password"><br><br>
        <input type="submit" value="Login">
    </form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 🚨 Vulnerable query (no sanitization, no prepared statements)
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
	$clean_username = preg_replace("/[^a-zA-Z]/", "", $username);
	$clean_username = ucfirst(strtolower($clean_username));
 	echo "<p>✅ Login successful! Welcome, $clean_username.</p>";
    } else {
        echo "<p>❌ Invalid credentials.</p>";
    }
}
?>
</body>
</html>
