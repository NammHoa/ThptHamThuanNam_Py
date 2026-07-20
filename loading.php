<?php
$logo_path = $logo_path ?? 'images/logo-thpt-cent.jpg';
?>
<style>
  #page-loader {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #003366 0%, #004080 50%, #0066cc 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    transition: opacity 0.6s ease;
  }
  #page-loader.hidden {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
  }
  .loader-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
  }
  .loader-logo-wrap {
    position: relative;
    margin-bottom: 20px;
  }
  .loader-logo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: contain;
    border: 3px solid rgba(255,255,255,0.3);
    padding: 5px;
    background: rgba(255,255,255,0.05);
    animation: logoPulse 2s ease-in-out infinite;
  }
  @keyframes logoPulse {
    0%, 100% { transform: scale(1);    filter: drop-shadow(0 0 8px rgba(255,255,255,0.3)); }
    50%       { transform: scale(1.05); filter: drop-shadow(0 0 16px rgba(255,255,255,0.5)); }
  }
  .loader-ring {
    position: absolute;
    top: -8px; left: -8px;
    width: 106px; height: 106px;
    border-radius: 50%;
    border: 2px solid transparent;
    border-top-color: rgba(255,255,255,0.6);
    border-right-color: rgba(255,255,255,0.2);
    animation: spin 1.5s linear infinite;
  }
  @keyframes spin {
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  .loader-school {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 15px;
    font-weight: 400;
    letter-spacing: 5px;
    color: rgba(255,255,255,0.95);
    text-transform: uppercase;
    margin: 0 0 6px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
  }
  .loader-subtitle {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11.5px;
    color: rgba(255,255,255,0.55);
    letter-spacing: 1.5px;
    margin: 0 0 22px;
  }
  .loader-bar-wrap {
    width: 200px;
    height: 3px;
    background: rgba(255,255,255,0.12);
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 12px;
  }
  .loader-bar {
    height: 100%;
    width: 0%;
    border-radius: 99px;
    background: linear-gradient(90deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0.9) 50%, rgba(255,255,255,0.4) 100%);
    transition: width 0.5s cubic-bezier(0.4,0,0.2,1);
    position: relative;
    overflow: hidden;
  }
  .loader-bar::after {
    content: '';
    position: absolute;
    top: 0; left: -60px;
    width: 60px; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
    animation: shimmer 1.4s ease-in-out infinite;
  }
  @keyframes shimmer {
    0%   { left: -60px; }
    100% { left: 100%; }
  }
  .loader-status {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 9.5px;
    color: rgba(255,255,255,0.4);
    letter-spacing: 3.5px;
    text-transform: uppercase;
  }
</style>

<div id="page-loader">
  <div class="loader-content">
    <div class="loader-logo-wrap">
      <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="loader-logo">
      <div class="loader-ring"></div>
    </div>
    <p class="loader-school">THPT HÀM THUẬN NAM</p>
    <p class="loader-subtitle">Hệ thống đăng ký nguyện vọng lớp 10</p>
    <div class="loader-bar-wrap">
      <div class="loader-bar" id="loader-bar"></div>
    </div>
    <p class="loader-status" id="loader-status">ĐANG TẢI...</p>
  </div>
</div>

<script>
(function() {
  var loader = document.getElementById('page-loader');
  var bar    = document.getElementById('loader-bar');
  var status = document.getElementById('loader-status');

  if (bar) {
    setTimeout(function() { bar.style.width = '25%'; }, 100);
    setTimeout(function() { bar.style.width = '55%'; }, 350);
    setTimeout(function() { bar.style.width = '80%'; }, 650);
  }

  window.addEventListener('load', function() {
    if (window._isSubmitting) return;
    if (bar) bar.style.width = '100%';
    setTimeout(function() {
      if (loader && !window._isSubmitting) {
        loader.style.opacity = '0';
        setTimeout(function() {
          loader.classList.add('hidden');
          loader.style.opacity = '';
        }, 600);
      }
    }, 200);
  });

  // ── Fix bfcache ──
  window.addEventListener('pageshow', function(e) {
    window._isSubmitting = false;
    if (!loader) return;
    loader.classList.add('hidden');
    loader.style.cssText = 'display:none!important;opacity:0!important;visibility:hidden!important;pointer-events:none!important;';

    var btn = document.querySelector('button[type="submit"]');
    if (btn) {
      btn.disabled         = false;
      btn.innerHTML        = 'Gửi đăng ký';
      btn.style.background = '';
      btn.style.cursor     = '';
    }
  });

  window.showOverlay = function(msg) {
    window._isSubmitting = true;
    if (!loader) return;
    loader.style.cssText = '';
    loader.classList.remove('hidden');
    loader.style.opacity = '1';
    if (status && msg) status.textContent = msg.toUpperCase();
    if (bar) {
      bar.style.transition = 'none';
      bar.style.width      = '0%';
      setTimeout(function() {
        bar.style.transition = 'width 0.5s cubic-bezier(0.4,0,0.2,1)';
        bar.style.width      = '55%';
      }, 50);
      setTimeout(function() { bar.style.width = '80%'; }, 700);
    }
  };

})();
</script>