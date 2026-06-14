<style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&display=swap');
#lp-overlay{
  position:fixed;inset:0;z-index:9999;
  background:linear-gradient(135deg,#003366,#004080,#0066cc);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  font-family:'Montserrat',sans-serif;
}
#lp-overlay .lp-ring{
  width:90px;height:90px;border-radius:50%;
  border:1px solid rgba(255,255,255,0.2);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:24px;position:relative;
  animation:fadeUp 0.7s ease forwards;opacity:0;
}
#lp-overlay .lp-ring::before{
  content:'';position:absolute;inset:-8px;border-radius:50%;
  border:1px solid rgba(255,255,255,0.08);
  animation:pulse-ring 2.5s ease-in-out infinite;
}
#lp-overlay .lp-ring::after{
  content:'';position:absolute;inset:-16px;border-radius:50%;
  border:1px solid rgba(255,255,255,0.04);
  animation:pulse-ring 2.5s 0.5s ease-in-out infinite;
}
#lp-overlay .lp-logo{
  width:72px;height:72px;border-radius:50%;
  background:rgba(255,255,255,0.12);
  border:1px solid rgba(255,255,255,0.3);
  display:flex;align-items:center;justify-content:center;
  overflow:hidden;
  animation:logo-breathe 2.5s ease-in-out infinite;
}
#lp-overlay .lp-logo img{width:100%;height:100%;object-fit:cover;}
#lp-overlay .lp-name{
  color:#fff;font-size:14px;font-weight:500;
  letter-spacing:4px;text-transform:uppercase;
  margin-bottom:6px;
  animation:fadeUp 0.7s 0.2s ease forwards;opacity:0;
}
#lp-overlay .lp-sub{
  color:rgba(255,255,255,0.5);font-size:11px;
  letter-spacing:1.5px;font-weight:300;
  margin-bottom:36px;
  animation:fadeUp 0.7s 0.35s ease forwards;opacity:0;
}
#lp-overlay .lp-bar-wrap{
  width:200px;height:2px;
  background:rgba(255,255,255,0.12);
  border-radius:2px;position:relative;
  margin-bottom:16px;
  animation:fadeUp 0.7s 0.5s ease forwards;opacity:0;
}
#lp-overlay .lp-bar-fill{
  position:absolute;top:0;left:0;height:100%;
  width:0%;background:rgba(255,255,255,0.7);
  border-radius:2px;transition:width 0.3s ease;
}
#lp-overlay .lp-bar-shimmer{
  position:absolute;top:-2px;left:-60px;
  width:60px;height:6px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.95),transparent);
  border-radius:3px;
  animation:shimmer 1.6s ease-in-out infinite;
  pointer-events:none;
}
#lp-overlay .lp-txt{
  color:rgba(255,255,255,0.4);font-size:10px;
  letter-spacing:3px;text-transform:uppercase;
  animation:fadeUp 0.7s 0.65s ease forwards;opacity:0;
}
#lp-overlay .lp-particle{
  position:absolute;border-radius:50%;
  background:rgba(255,255,255,0.06);
  animation:float linear infinite;
}
@keyframes logo-breathe{
  0%,100%{transform:scale(1);box-shadow:0 0 0px rgba(255,255,255,0);}
  50%{transform:scale(1.1);box-shadow:0 0 20px rgba(255,255,255,0.2);}
}
@keyframes pulse-ring{
  0%,100%{transform:scale(1);opacity:1;}
  50%{transform:scale(1.1);opacity:0.3;}
}
@keyframes fadeUp{
  from{opacity:0;transform:translateY(12px);}
  to{opacity:1;transform:translateY(0);}
}
@keyframes float{
  from{transform:translateY(0);opacity:0.06;}
  50%{opacity:0.12;}
  to{transform:translateY(-120px);opacity:0;}
}
@keyframes shimmer{
  0%{left:-60px;opacity:0;}
  20%{opacity:1;}
  80%{opacity:1;}
  100%{left:200px;opacity:0;}
}
</style>

<div id="lp-overlay">
  <div class="lp-particle" style="width:80px;height:80px;left:8%;top:60%;animation-duration:8s;"></div>
  <div class="lp-particle" style="width:40px;height:40px;left:80%;top:70%;animation-duration:6s;animation-delay:1s;"></div>
  <div class="lp-particle" style="width:60px;height:60px;left:15%;top:20%;animation-duration:10s;animation-delay:2s;"></div>
  <div class="lp-particle" style="width:30px;height:30px;left:70%;top:15%;animation-duration:7s;animation-delay:0.5s;"></div>
  <div class="lp-particle" style="width:50px;height:50px;left:50%;top:80%;animation-duration:9s;animation-delay:1.5s;"></div>

  <div class="lp-ring">
    <div class="lp-logo">
      <img src="/nguyenvong/images/logo-thpt-cent.jpg" alt="Logo">
    </div>
  </div>
  <div class="lp-name">THPT Hàm Thuận Nam</div>
  <div class="lp-sub">Hệ thống đăng ký nguyện vọng lớp 10</div>
  <div class="lp-bar-wrap">
    <div class="lp-bar-fill" id="lp-bar"></div>
    <div class="lp-bar-shimmer"></div>
  </div>
  <div class="lp-txt" id="lp-txt">Đang tải</div>
</div>

<script>
(function(){
  var overlay = document.getElementById('lp-overlay');
  var bar     = document.getElementById('lp-bar');
  var txt     = document.getElementById('lp-txt');
  var p = 0;

  var iv = setInterval(function(){
    if(p < 85){ p += Math.random()*10; if(p>85) p=85;
      bar.style.width = p+'%';
    }
  }, 200);

  var startTime = Date.now();
  window.addEventListener('load', function(){
    clearInterval(iv);
    bar.style.width = '100%';
    txt.textContent = 'Hoàn tất';
    var delay = Math.max(0, 500-(Date.now()-startTime));
    setTimeout(function(){
      overlay.style.transition = 'opacity 0.5s ease';
      overlay.style.opacity = '0';
      setTimeout(function(){ overlay.style.display='none'; }, 500);
    }, delay);
  });

})();
</script>