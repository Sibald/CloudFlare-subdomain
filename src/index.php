<?php
include_once('class_cloudflare.php');

// CloudFlare username and API token
$cf = new cloudflare_api("EMAIL", "TOKEN");
// CloudFlare domain you'd like to create the subdomain on
$domain = "example.com";
// Base directory of this app (include beginning and trailing slashes)
$basedir = "/";
// Init for later in script
$continue = true;

if (isset($_POST['serverip'])) {
    // TODO validate user input
    $serverip = $_POST['serverip'];
    $port = $_POST['port'];
    $prefix = $_POST['prefix'];

    if ($port == 25565) {
        $type = "A";
    } else {
        $type = "SRV";
    }

    // Make sure no subdomain already exists with the same name
    $result = $cf->rec_load_all($domain);
    $array = json_decode(json_encode($result), True);
    foreach ($array['response']["recs"]["objs"] as $value) {
        if ($value["name"] == "$prefix.$domain") {
            echo("Error: The record already exists.");
            $continue = false;
        } else if ($value["name"] == "_minecraft._tcp.$prefix.$domain") {
            echo("Error: The record already exists.");
            $continue = false;
        }

        if ($type == "SRV" && $value["name"] == "srv-$prefix.$domain") {
            $continue = false;
        }
    }

	// rec_edit($domain, $type, $id, $name, $content, $ttl = 1, $mode = 1, $prio = 1, $service = 1, $srvname = 1, $protocol = 1, $weight = 1, $port = 1, $target = 1)
    if ($type == "A" && $continue) {
        if ( (!$type) || (!$serverip) || (!$prefix) ) {
            echo("Please complete all fields.");
        } else {
            $result = $cf->rec_new($domain, $type, $prefix, $serverip, $ttl = 1, $mode = 0);

            $array = json_decode(json_encode($result), True);
            if ($array["result"] == "success") {
                echo("Subdomain created! <strong>$prefix.$domain</strong>");
            } else {
	            echo("Error: " . $array["msg"]);
            }
        }
    } else if ($type == "SRV" && $continue) {
        if ( (!$type) || (!$serverip) || (!$port) || (!$prefix) ) {
            echo("Please complete all fields.");
        } else {
            // Create the A record so that the SRV record has a host to point at
            $result = $cf->rec_new($domain, "A", "srv-" . $prefix, $serverip, $ttl = 1, $mode = 0);
            // Create SRV record pointing at the host created above
            $result2 = $cf->rec_new($domain, $type, $prefix, $serverip, $ttl = 1, $mode = 0, $prio = 1, "_minecraft", $prefix, "_tcp", $weight = 1, $port, "srv-$prefix.$domain");

            $array = json_decode(json_encode($result), True);
            $array2 = json_decode(json_encode($result2), True);
            if ($array["result"] == "success" && $array2["result"] == "success") {
                echo("Subdomain created! <strong>$prefix.$domain</strong>");
            } else {
                echo("Error1: " . $array['msg']);
                echo("Error2: " . $array2['msg']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
    <head>
        <title>CloudFlare subdomain creator</title>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta name="author" content="Dylan Hansch">
    </head>
    <body>
        <h2>CloudFlare subdomain creator</h2>
        <form action="<?php echo($basedir); ?>" method="post" role="form">
            <strong>Server IP: </strong>
            <input type="text" name="serverip" required><br>

            <div id="portdiv">
                <strong>Port: </strong>
                <input type="text" id="port" name="port" required><br>
            </div>

            <strong>Subdomain prefix: </strong>
            <input type="text" name="prefix" required><br><br>

            <button type="submit" name="create">Create</button>
        </form>
    </body>
</html>
