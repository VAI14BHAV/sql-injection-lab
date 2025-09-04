# sql-injection-lab
Created an environment to deliver both an intentionally exploitable login and a WAF-protected login on a single EC2 box
SQL Injection Lab with ModSecurity WAF on AWS EC2
📌 Overview

This lab demonstrates how to:

Set up a vulnerable web application on AWS EC2.

Exploit SQL Injection (SQLi) vulnerabilities.

Mitigate SQLi attacks using ModSecurity WAF (Web Application Firewall).

🚀 Step 1: Launch EC2 Instance

Log in to AWS Management Console
.

Go to EC2 → Launch Instance.

Choose Ubuntu 20.04 LTS AMI.

Select instance type: t2.micro (Free tier).

Configure key pair & security group:

Allow SSH (22) from your IP.

Allow HTTP (80) for web traffic.

Launch and connect:

ssh -i your-key.pem ubuntu@<EC2-Public-IP>

⚙️ Step 2: Install Apache, PHP, MySQL
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql git -y

💾 Step 3: Clone Vulnerable SQLi Application
cd /var/www/html
sudo rm -rf *
sudo git clone https://github.com/VAI14BHAV/sql-injection-lab.git .
sudo chown -R www-data:www-data /var/www/html

🗄️ Step 4: Setup Database
sudo mysql -u root -p


Inside MySQL:

CREATE DATABASE sqli_lab;
CREATE USER 'sqli_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON sqli_lab.* TO 'sqli_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;


Import schema (example):

mysql -u sqli_user -p sqli_lab < /var/www/html/database.sql

🔓 Step 5: Test Vulnerable SQLi

Open browser → http://<EC2-Public-IP>/

Try a login bypass:

' OR 1=1 -- -

🛡️ Step 6: Install & Configure ModSecurity WAF
sudo apt install libapache2-mod-security2 -y
sudo a2enmod security2
sudo systemctl restart apache2


Enable recommended rules:

sudo cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf
sudo nano /etc/modsecurity/modsecurity.conf


Set:

SecRuleEngine On

📜 Step 7: Add Custom SQLi Blocking Rule

Create a local rules file:

sudo nano /etc/modsecurity/local_rules.conf


Add:

SecRule REQUEST_URI|ARGS|ARGS_NAMES|REQUEST_HEADERS|XML:/* "(?i:(\bor\b|\band\b).*(=|like)|union\s+select|sleep\()" \
    "id:1001,phase:2,deny,status:403,msg:'SQL Injection Attempt Blocked'"


Include in config:

sudo nano /etc/apache2/mods-enabled/security2.conf


Add line:

IncludeOptional /etc/modsecurity/local_rules.conf


Restart Apache:

sudo systemctl restart apache2

🧪 Step 8: Verify WAF Protection

Retry payload:

' OR 1=1 -- -


You should now get 403 Forbidden.
