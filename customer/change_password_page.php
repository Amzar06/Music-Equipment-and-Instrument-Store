<!DOCTYPE html>
<html>
<head>
<title>Change Password</title>
<style>
body { font-family: Arial; background:#f2f2f2; margin:0; }
.header { background:#4a9b6f; color:white; padding:15px 30px; }
.container { display:flex; justify-content:center; align-items:center; height:90vh; }
.box { background:white; padding:30px; width:350px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
input { width:100%; padding:10px; margin:8px 0; border-radius:6px; border:1px solid #ddd; }
.btn { width:100%; background:#4a9b6f; color:white; padding:10px; border:none; border-radius:6px; }
.btn:hover { background:#3d8460; }
</style>
</head>

<body>
<div class="header">ADMIN PORTAL</div>
<div class="container">
<div class="box">
<h2>Change Password</h2>
<input type="password" placeholder="Current Password">
<input type="password" placeholder="New Password">
<input type="password" placeholder="Confirm New Password">
<button class="btn">Update Password</button>
</div>
</div>
</body>
</html>
