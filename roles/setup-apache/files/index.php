<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>Workshop Application</title>
      <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
      <style>
         body {
         font-family: 'Open Sans', sans-serif;
         text-align: center;
         background-color: WhiteSmoke;
         }
         .container {
         position: absolute;
         top: 30%;
         left: 50%;
         -moz-transform: translateX(-50%) translateY(-50%);
         -webkit-transform: translateX(-50%) translateY(-50%);
         transform: translateX(-50%) translateY(-50%);
         font-size: 200%;
         }
         .version {
         position: absolute;
         top: 50%;
         left: 50%;
         -moz-transform: translateX(-50%) translateY(-50%);
         -webkit-transform: translateX(-50%) translateY(-50%);
         transform: translateX(-50%) translateY(-50%);
         font-size: 150%;
         }
         footer {
         width: 100%;
         font-size: 100%;
         }
         img {
         margin: 0 auto;
         }
      </style>
   </head>
   <body>
      <div class="container">
         <img src="https://www.ansible.com/hubfs/Logo-Red_Hat-Ansible_Automation_Platform-A-Standard.svg" width="150%"/>
      </div>
      <div class="version" style="color:red;">

        <?php
        require 'dbvars.php';
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', '1');
        error_reporting(E_ALL | E_STRICT);

        try
        {
            $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password, $options);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            //var_dump($conn->query("SHOW SESSION STATUS WHERE Variable_name IN ('Ssl_version','Ssl_cipher')")->fetchAll());
            $stmt = $conn->query('SELECT application_name, application_version FROM webapp_db.INFO');

            if ($stmt->rowCount() > 0)
            {
                foreach ($stmt as $row)
                {
                    echo "<b>" . $row["application_name"] . "</b> v" . $row["application_version"] . "<br>";
                }
            }

            $stmt = $conn->query("SHOW SESSION STATUS WHERE Variable_name IN ('Ssl_version','Ssl_cipher')");

            if ($stmt->rowCount() > 0)
            {
                foreach ($stmt as $row)
                {
                    echo "<b>" . $row["Variable_name"] . "</b>:" . $row["Value"] . "<br>";
                }
            }

            $conn = null;
        }
        catch(PDOException $e)
        {
            echo $e;
            echo "Connection failed: " . $e->getMessage();
        }
        ?>
      </div>
   </body>
</html>