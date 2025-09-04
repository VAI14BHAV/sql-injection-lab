# SQL Injection Lab with ModSecurity WAF on AWS EC2
This repository contains a step-by-step guide to set up a vulnerable web application on AWS EC2, demonstrate SQL Injection exploitation, and then mitigate the attack using ModSecurity Web Application Firewall (WAF).

---

## Step 1: Launch EC2 Instance
1. Log in to AWS Management Console.
2. Navigate to EC2 and launch a new instance.
3. Select Ubuntu 20.04 LTS AMI.
4. Choose instance type: `t2.micro`.
5. Configure security group:
   - Allow SSH (22) from your IP.
   - Allow HTTP (80) from all.
6. Launch the instance and connect:
   ```bash
   ssh -i your-key.pem ubuntu@<EC2-Public-IP>

