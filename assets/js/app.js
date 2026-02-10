// Eu podia usar uma lib, mas preferi escrever minha própria Matrix para manter o ego bem nutrido.
(function(){
  const canvas = document.getElementById('matrix');
  const ctx = canvas.getContext('2d');
  let width, height, columns, drops, fontSize;

  function resize(){
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
    fontSize = Math.max(14, Math.floor(width/90));
    columns = Math.floor(width / fontSize);
    drops = Array(columns).fill(0);
  }

  function draw(){
    // Fundo esmaecido para trailing
    ctx.fillStyle = 'rgba(10, 15, 15, 0.08)';
    ctx.fillRect(0,0,width,height);

    ctx.fillStyle = '#00ff88';
    ctx.font = fontSize + "px JetBrains Mono, monospace";

    for(let i=0;i<columns;i++){
      const text = String.fromCharCode(0x30A0 + Math.random() * 96);
      const x = i * fontSize;
      const y = drops[i] * fontSize;
      ctx.fillText(text, x, y);
      if(y > height && Math.random() > 0.975){
        drops[i] = 0; // Reinício preguiçoso
      }
      drops[i]++;
    }
    requestAnimationFrame(draw);
  }

  window.addEventListener('resize', resize);
  resize();
  requestAnimationFrame(draw);
})();

// Terminal fake com typing, porque a gente gosta de um show antes do conteúdo.
(function(){
  const el = document.getElementById('terminal');
  if(!el) return;

  const lines = [
    '[INFO] Bootstrapping CTIC console... OK',
    '[SEC] Verificando políticas de acesso... OK',
    '[NET] Estabelecendo túneis seguros... OK',
    '[DB] Conectando aos serviços de dados... OK',
    '[READY] Sistemas disponíveis carregados.\n'
  ];

  let i = 0;
  function printLine(text){
    const line = document.createElement('div');
    const ts = new Date().toISOString().replace('T',' ').split('.')[0];
    line.innerHTML = `<span class="muted">${ts}</span> <span class="ok">${escapeHtml(text)}</span>`;
    el.appendChild(line);
    el.scrollTop = el.scrollHeight;
  }

  function typeAll(){
    if(i >= lines.length) return;
    setTimeout(()=>{
      printLine(lines[i++]);
      typeAll();
    }, 500 + Math.random()*500);
  }

  function escapeHtml(str){
    return str.replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  }

  // Engatilhar a sequência após um respiro, para parecer que foi caro de fazer.
  setTimeout(typeAll, 600);
})();

// Navegação suave, para não parecer 2010.
(function(){
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', (e)=>{
      const id = a.getAttribute('href').slice(1);
      const target = document.getElementById(id);
      if(target){
        e.preventDefault();
        window.scrollTo({top: target.offsetTop - 70, behavior: 'smooth'});
      }
    });
  });
})();
