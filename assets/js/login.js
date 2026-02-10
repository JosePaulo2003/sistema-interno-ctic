// Transição do loader para o formulário. Simples, direto, e com um teatrinho básico.
(function(){
  const loader = document.getElementById('loader');
  const main = document.querySelector('main.login-container');
  const card = document.getElementById('loginCard');
  if(!loader || !main || !card) return;

  const hasErrors = document.body.getAttribute('data-has-errors') === '1';
  const delay = hasErrors ? 0 : 900; // Se houver erros, não faço o usuário esperar o show.

  function rand(min, max){ return Math.floor(Math.random()*(max-min+1))+min; }

  function fragmentAndReposition(){
    // Saída dramática
    card.classList.remove('fragment-in');
    card.classList.add('fragment-out');
    card.addEventListener('animationend', function onOut(){
      card.removeEventListener('animationend', onOut);
      // Calcula posição aleatória dentro da viewport com margem de 40px
      const vw = window.innerWidth; const vh = window.innerHeight;
      const rect = card.getBoundingClientRect();
      const maxX = Math.max(40 + rect.width/2, vw - 40 - rect.width/2);
      const maxY = Math.max(40 + rect.height/2, vh - 60 - rect.height/2);
      const minX = 40 + rect.width/2;
      const minY = 60 + rect.height/2;
      const x = rand(minX, maxX);
      const y = rand(minY, maxY);
      document.documentElement.style.setProperty('--card-x', x + 'px');
      document.documentElement.style.setProperty('--card-y', y + 'px');
      // Entrada triunfal
      card.classList.remove('fragment-out');
      card.classList.add('fragment-in');
    });
  }

  function reveal(){
    main.hidden = false;
    requestAnimationFrame(()=>{
      loader.classList.add('hide');
      // Foco no primeiro campo
      const firstInput = main.querySelector('input[name="username"]');
      if(firstInput) firstInput.focus();
      // Se houve erro, faz o show de desfragmentação na carga
      if(hasErrors) {
        setTimeout(fragmentAndReposition, 60);
      }
    });
  }

  window.addEventListener('load', ()=>{
    setTimeout(reveal, delay);
  });
})();

// Toggle mostrar/ocultar senha, porque digitar cegamente é um esporte radical.
(function(){
  const btn = document.getElementById('togglePassword');
  const input = document.getElementById('password');
  if(!btn || !input) return;
  btn.addEventListener('click', (e)=>{
    e.preventDefault();
    const isPwd = input.type === 'password';
    input.type = isPwd ? 'text' : 'password';
    btn.setAttribute('aria-pressed', isPwd ? 'true' : 'false');
    btn.setAttribute('aria-label', isPwd ? 'Ocultar senha' : 'Mostrar senha');
    input.focus();
  });
})();
