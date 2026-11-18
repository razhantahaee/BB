<?php
// صفحه‌ی ورود ادمین (و تنظیم رمز برای بار اول)
session_start();
require __DIR__ . '/config.php';
if (isset($_GET['logout'])) { $_SESSION = []; session_destroy(); header('Location: ../index.html'); exit; }
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>ورود ادمین · بزن بریم</title>
<style>
body{font-family:Vazirmatn,Tahoma,sans-serif;background:#FAF6EE;color:#222;display:grid;place-items:center;min-height:100vh;margin:0}
.box{background:#fff;border:1px solid #e6ddcc;border-radius:16px;padding:28px;width:min(92vw,380px);box-shadow:0 8px 24px rgba(0,0,0,.06)}
h1{font-size:19px;margin:0 0 14px;color:#1E6B4E}
label{display:block;font-size:14px;margin-bottom:6px}
input{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #cfc5b1;border-radius:10px;font-size:15px}
button{margin-top:14px;width:100%;padding:11px;border:0;border-radius:10px;background:#1E6B4E;color:#fff;font-size:15px;cursor:pointer}
.msg{margin-top:12px;font-size:14px;min-height:20px;color:#b5332e}
.ok{color:#1E6B4E}
a{color:#1E6B4E;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <h1 id="title">ورود ادمین</h1>
  <form id="f">
    <label for="pw" id="lbl">رمز</label>
    <input id="pw" type="password" autocomplete="current-password" required minlength="10">
    <label for="pw2" id="lbl2" hidden>تکرار رمز</label>
    <input id="pw2" type="password" autocomplete="new-password" hidden minlength="10">
    <button type="submit" id="btn">ورود</button>
  </form>
  <div class="msg" id="msg" role="status"></div>
  <p style="margin:14px 0 0"><a href="../index.html">بازگشت به سایت</a></p>
</div>
<script>
(async function(){
  var api='api.php';
  var st=await fetch(api+'?op=whoami',{credentials:'same-origin'}).then(function(r){return r.json();});
  var first=!st.hasAdmin;
  if(st.admin){ document.getElementById('msg').className='msg ok'; document.getElementById('msg').innerHTML='شما وارد شده‌اید. <a href="setup.php?logout=1">خروج</a>'; }
  if(first){
    document.getElementById('title').textContent='تنظیم رمز ادمین';
    document.getElementById('lbl2').hidden=false; document.getElementById('pw2').hidden=false;
    document.getElementById('btn').textContent='ذخیره رمز';
  }
  document.getElementById('f').addEventListener('submit',async function(e){
    e.preventDefault();
    var msg=document.getElementById('msg'); msg.className='msg'; msg.textContent='';
    var pw=document.getElementById('pw').value;
    if(first && pw!==document.getElementById('pw2').value){ msg.textContent='دو رمز یکسان نیستند.'; return; }
    var op=first?'setup':'login';
    var r=await fetch(api+'?op='+op,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({password:pw})});
    var j=await r.json().catch(function(){return {};});
    if(r.ok){ msg.className='msg ok'; msg.textContent='انجام شد. به سایت می‌روید…'; setTimeout(function(){location.href='../index.html';},600); }
    else { msg.textContent=j.error==='wrong password'?'رمز اشتباه است.':(j.error||'خطا'); }
  });
})();
</script>
</body>
</html>
