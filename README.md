# SQL Injection Lab with ModSecurity WAF on AWS EC2
This repository contains a step-by-step guide to set up a vulnerable web application on AWS EC2, demonstrate SQL Injection exploitation, and then mitigate the attack using ModSecurity Web Application Firewall (WAF).

---

## Step 1: Launch EC2 Instance
1. Log in to AWS Management Console.
2. Navigate to EC2 and launch a new instance.
3. Select Ubuntu 24.04-amd64.
4. Choose instance type: `t3.micro`.
5. Configure security group:
   - Allow SSH (22) from your IP.
   - Allow HTTP (80) from all.
<img width="1653" height="512" alt="image" src="https://github.com/user-attachments/assets/d555df3c-8be1-4dbd-909d-3b4855986aaa" />

This is how the security group should look like.

6. Download a Key pair .pem file.
7. For environment setup we have used Putty but it don't understand the .pem file so need to convert to .ppk file using Puttygen.
8. Open PuTTY. In Host Name (or IP address), enter: ubuntu@54.88.52.121
9. Go to Connection → SSH → Auth → Credentials. Browse and select your mykey.ppk.
10. Click Open → it should log you into the EC2 instance.

## Step 2: Install Apache, PHP, MySQL, ModSecurity and some other dependencies
```bash
sudo apt install apache2 php libapache2-mod-php mariadb-server php-mysql libapache2-mod-security2 -y
sudo a2enmod security2
sudo systemctl enable --now apache2
```
## Step 3: Create a SQli vulnerable app
1. Create a directory of any name,
2. Give the permission and ownership like below
```bash
sudo mkdir -p /var/www/html/sqli
sudo chown -R www-data:www-data /var/www/html/sqli
sudo chmod -R 644 /var/www/html/sqli
```
3. Now lets create the database using mysql.
```bash
sudo mysql -u root

CREATE DATABASE sqli_demo;
USE vulnsite;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL
);

INSERT INTO users (username, password) VALUES
('test1', 'pass1'),
('test2', 'pass2'),
('admin', 'admin123');
```

5. Under the sqli directory, will create two file login.php and login2.php with same code.
```bash
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
```
## Step 4: Create a custom ModSecurity SQLi rule
Since global rules are already active, what we want is:
   - Disable ModSecurity for /login.php only.
   - Keep ModSecurity enabled (default) for /login2.php
1. The file /etc/apache2/sites-enabled/000-default.conf is the default virtual host configuration file for Apache on Ubuntu/Debian systems. This setup enables you to protect some pages while allowing others to run without ModSecurity blocking them. Below are the rules that can be added.
```bash
<VirtualHost *:80>
#    DocumentRoot /var/www/html
<Directory /var/www/html>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

# Disable ModSecurity only for vulnerable page
<Location "/login.php">
    SecRuleEngine Off
</Location>

# Leave ModSecurity ON for login2.php (default behavior)
<Location "/login2.php">
    SecRuleEngine On
</Location>

</VirtualHost>
```
2. Run Test config command to check if there is any sort of error
```bash
sudo apache2ctl configtest
```
3. Modsecurity do have a by default rule, but in some cases it is not enough to protect against the attacks like SQli, etc. So we will be configuring our own rule,
```bash
sudo nano /etc/modsecurity/local_rules.conf

# Disable ModSecurity ONLY for login.php
<LocationMatch "/login.php">
    SecRuleEngine Off
</LocationMatch>

# Keep ModSecurity enabled for login2.php
<LocationMatch "/login2.php">
    SecRuleEngine On

    # First rule checks for .php files AND carries the disruptive action
    SecRule REQUEST_FILENAME "\.php$" \
        "id:100010,\
         phase:2,\
         t:none,\
         block,\
         status:403,\
         log,\
         msg:'SQL injection attempt on login2.php',\
         chain"

        # Second rule just checks the arguments, no disruptive action here
        SecRule ARGS "(select|union|from|where|having|order by|group by)" \
            "t:none,\
             t:urlDecodeUni,\
             t:htmlEntityDecode,\
             t:compressWhiteSpace"

             # Block typical SQL injection patterns in login2.php
             SecRule REQUEST_URI|ARGS "(?i)(\bor\b\s+1=1|\bor\b\s+'.*'=.*|--|#|/\*)" \
                "id:100011,\
                phase:2,\
                t:none,t:urlDecodeUni,t:htmlEntityDecode,t:compressWhiteSpace,\
                deny,\
                status:403,\
                log,\
                msg:'SQLi attempt blocked on login2.php'"
</LocationMatch>
```
4. The above customize rule we can directly include to the global configuration of Modsecurity(modsecurity.conf).
<img width="961" height="544" alt="image" src="https://github.com/user-attachments/assets/d1b2aaef-51fa-4a18-b62e-8d44a0cd92ab" />
5. Start the apache server
   
```bash
sudo apache2ctl configtest
sudo systemctl restart apache2
```

## Step 5: Testing of the application

- /login.php (vulnerable without WAF)
<img width="635" height="402" alt="image" src="https://github.com/user-attachments/assets/c8b2ca3e-bc22-49b9-93d8-672b4240251a" />

- /login2.php (You Can Try)
<img width="618" height="340" alt="image" src="https://github.com/user-attachments/assets/a182bab6-d57e-4228-a2ee-200acf72e8f2" />


