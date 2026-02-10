// Eu disse que ia dar show: logs em sequência, barra que respira e botão de pular para quem está com pressa.
(function(){
  const overlay = document.getElementById('welcome');
  if(!overlay) return; // Se não tem cortina, não tem teatro.

  const logBox = document.getElementById('welcomeLog');
  const bar = document.getElementById('welcomeBar');
  const skip = document.getElementById('welcomeSkip');
  const total = parseInt(overlay.getAttribute('data-welcome-duration')||'6500',10);

  const steps = [
    '[INFO] Inicializando ambiente seguro...',
    '[SEC] Verificando credenciais e políticas...',
    '[NET] Estabelecendo sessão e túneis...',
    '[DB] Conectando aos serviços de dados...',
    '[SYNC] Sincronizando preferências do usuário...',
    '[READY] Console carregado. Bem-vindo.'
  ];

  let start = null, canceled = false;
  function print(line){
    if(!logBox) return;
    const div = document.createElement('div');
    const ts = new Date().toISOString().replace('T',' ').split('.')[0];
    div.innerHTML = `<span class="muted">${ts}</span> <span class="ok">${escapeHtml(line)}</span>`;
    logBox.appendChild(div);
    logBox.scrollTop = logBox.scrollHeight;
  }
  function escapeHtml(str){return str.replace(/[&<>\"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}

  function animate(ts){
    if(!start) start = ts;
    const elapsed = Math.min(ts - start, total);
    const pct = Math.round((elapsed/total)*100);
    if(bar) bar.style.width = pct + '%';

    const idx = Math.min(steps.length-1, Math.floor((elapsed/total)*steps.length));
    // imprime apenas quando muda de índice
    if(!logBox.dataset.idx || parseInt(logBox.dataset.idx,10) !== idx){
      logBox.dataset.idx = String(idx);
      print(steps[idx]);
    }

    if(elapsed >= total || canceled){
      overlay.classList.add('hide');
      return; // fim do show
    }
    requestAnimationFrame(animate);
  }

  if(skip){
    skip.addEventListener('click', ()=>{
      canceled = true;
      if(bar) bar.style.width = '100%';
      overlay.classList.add('hide');
    });
  }

  window.addEventListener('load', ()=> requestAnimationFrame(animate));
})();
