<?php
session_start();

$configFile = __DIR__ . "/data/config.json";
$usersFile = __DIR__ . "/data/users.json";

// Ensure data directory exists
if (!is_dir(__DIR__ . "/data")) {
    mkdir(__DIR__ . "/data", 0777, true);
}

// Initialize config.json if it doesn't exist
if (!file_exists($configFile)) {
    $initialConfig = [
        "title" => "TAKBIR SMS BOMBER",
        "version" => "1.0.0",
        "developer" => "Takbir Ahmed",
        "notice" => "Welcome to Takbir SMS Bomber. Use responsibly.",
        "update_link" => "https://example.com/update",
        "apis" => [
            ["id" => "s1", "name" => "BTCL MyBTCL", "url" => base64_encode("https://mybtcl.btcl.gov.bd/api/ecare/anonym/sendOTP.json"), "status" => "active"],
            ["id" => "s2", "name" => "BTCL PhoneBill", "url" => base64_encode("https://phonebill.btcl.com.bd/api/bcare/anonym/sendOTP.json"), "status" => "active"],
            ["id" => "s3", "name" => "Bioscope Plus", "url" => base64_encode("https://api-dynamic.bioscopelive.com/v2/auth/login?country=BD&platform=web&language=en"), "status" => "active"]
        ]
    ];
    file_put_contents($configFile, json_encode($initialConfig, JSON_PRETTY_PRINT));
}

// --- API Endpoint Logic ---
if (isset($_GET["action"]) && $_GET["action"] == "track") {
    header("Content-Type: application/json");
    $ip = $_SERVER["REMOTE_ADDR"];
    $device = $_GET["device"] ?? "Unknown";
    $time = date("Y-m-d H:i:s");
    
    $users = [];
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
    }
    
    $users[$ip] = [
        "last_seen" => $time,
        "device" => $device
    ];
    
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    
    $config = json_decode(file_get_contents($configFile), true);
    echo json_encode(["status" => "success", "config" => $config]);
    exit;
}

// --- Admin Panel Logic ---

// Simple Auth (You can change this)
$admin_user = "admin";
$admin_pass = "admin123";

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: admin_api.php");
    exit;
}

if (!isset($_SESSION["logged_in"])) {
    if (isset($_POST["login"])) {
        if ($_POST["user"] == $admin_user && $_POST["pass"] == $admin_pass) {
            $_SESSION["logged_in"] = true;
        } else {
            $error = "Invalid Credentials!";
        }
    }
}

if (!isset($_SESSION["logged_in"])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Admin Login</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head>
    <body class="bg-dark text-white">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card bg-secondary text-white">
                        <div class="card-header text-center"><h3>Admin Login</h3></div>
                        <div class="card-body">
                            <?php if(isset($error)) echo "<div class=\'alert alert-danger\'>$error</div>"; ?>
                            <form method="POST">
                                <input type="text" name="user" class="form-control mb-3" placeholder="Username" required>
                                <input type="password" name="pass" class="form-control mb-3" placeholder="Password" required>
                                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load Data
$config = json_decode(file_get_contents($configFile), true);
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

// Handle Updates
if (isset($_POST["update_config"])) {
    $config["title"] = $_POST["title"];
    $config["developer"] = $_POST["developer"];
    $config["notice"] = $_POST["notice"];
    $config["update_link"] = $_POST["update_link"];
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    $msg = "Config Updated!";
}

if (isset($_POST["update_api"])) {
    $api_id = $_POST["api_id"];
    foreach ($config["apis"] as &$api) {
        if ($api["id"] == $api_id) {
            $api["status"] = $_POST["status"];
            $api["url"] = base64_encode($_POST["url"]);
        }
    }
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    $msg = "API Updated!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SMS Bomber Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">SMS Bomber Admin Panel</a>
            <a href="?logout=1" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($msg)) echo "<div class=\'alert alert-success\'>$msg</div>"; ?>
        
        <div class="row">
            <!-- User Stats -->
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">Total Users</div>
                    <div class="card-body text-center">
                        <h2><?php echo count($users); ?></h2>
                        <p>Active Users (IP Based)</p>
                    </div>
                </div>
            </div>

            <!-- General Config -->
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white">General Settings</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label>Tool Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo $config["title"]; ?>">
                            </div>
                            <div class="mb-3">
                                <label>Developer Name</label>
                                <input type="text" name="developer" class="form-control" value="<?php echo $config["developer"]; ?>">
                            </div>
                            <div class="mb-3">
                                <label>Notice/Info</label>
                                <textarea name="notice" class="form-control"><?php echo $config["notice"]; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Update Link</label>
                                <input type="text" name="update_link" class="form-control" value="<?php echo $config["update_link"]; ?>">
                            </div>
                            <button type="submit" name="update_config" class="btn btn-success">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Management -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">API Management</div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>API Name</th>
                            <th>Status</th>
                            <th>URL (Decoded)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($config["apis"] as $api): ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="api_id" value="<?php echo $api["id"]; ?>">
                                <td><?php echo $api["name"]; ?></td>
                                <td>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="active" <?php if($api["status"]=="active") echo "selected"; ?>>Active</option>
                                        <option value="dead" <?php if($api["status"]=="dead") echo "selected"; ?>>Dead</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="url" class="form-control form-control-sm" value="<?php echo base64_decode($api["url"]); ?>">
                                </td>
                                <td>
                                    <button type="submit" name="update_api" class="btn btn-warning btn-sm">Update</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User List -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Recent Users</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Device</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $ip => $data): ?>
                        <tr>
                            <td><?php echo $ip; ?></td>
                            <td><?php echo $data["device"]; ?></td>
                            <td><?php echo $data["last_seen"]; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
