document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', event => {
    if (!confirm(el.dataset.confirm || '¿Deseas continuar?')) event.preventDefault();
  });
});
document.querySelectorAll('[data-alphanumeric]').forEach(input => {
  input.addEventListener('input', () => {
    input.value = input.value.replace(/[^a-zA-Z0-9]/g, '');
  });
});


(() => {
  const key = 'rescatAlimentosTheme';
  const apply = theme => {
    document.documentElement.dataset.theme = theme;
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      button.innerHTML = theme === 'dark' ? '☀ <span>Claro</span>' : '◐ <span>Oscuro</span>';
      button.setAttribute('aria-label', theme === 'dark' ? 'Activar modo claro' : 'Activar modo oscuro');
    });
  };
  let saved = 'light';
  try { saved = localStorage.getItem(key) || 'light'; } catch (e) {}
  apply(saved);
  document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    apply(next);
    try { localStorage.setItem(key, next); } catch (e) {}
  }));
})();


// Navegación móvil accesible
(() => {
  const toggle = document.querySelector('[data-menu-toggle]');
  const nav = document.getElementById('main-navigation');
  if (!toggle || !nav) return;
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    nav.classList.toggle('is-open', !open);
  });
  nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    toggle.setAttribute('aria-expanded', 'false');
    nav.classList.remove('is-open');
  }));
})();


// Geolocalización: solicita permiso solo tras una acción explícita.
(() => {
  const shareButton = document.getElementById('share-location');
  if (shareButton) shareButton.addEventListener('click', () => {
    const status = document.getElementById('location-status');
    if (!navigator.geolocation) { status.textContent = 'Este navegador no permite geolocalización.'; return; }
    status.textContent = 'Obteniendo ubicación...';
    navigator.geolocation.getCurrentPosition(pos => {
      document.getElementById('latitude').value = pos.coords.latitude.toFixed(7);
      document.getElementById('longitude').value = pos.coords.longitude.toFixed(7);
      status.textContent = 'Ubicación agregada. Se enviará al publicar la donación.';
    }, () => { status.textContent = 'No se pudo obtener la ubicación. Revisa los permisos del navegador.'; },
    {enableHighAccuracy:true, timeout:12000, maximumAge:60000});
  });
  const nearby = document.getElementById('find-nearby-centers');
  if (nearby) nearby.addEventListener('click', () => {
    const status = document.getElementById('nearby-status');
    if (!navigator.geolocation) { status.textContent = 'Tu navegador no permite geolocalización.'; return; }
    status.textContent = 'Obteniendo ubicación...';
    navigator.geolocation.getCurrentPosition(pos => {
      const url = `https://www.google.com/maps/search/centros+de+donacion/@${pos.coords.latitude},${pos.coords.longitude},14z`;
      status.innerHTML = '';
      const link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener noreferrer';
      link.textContent = 'Ver centros cercanos en Google Maps'; status.appendChild(link);
    }, () => { status.textContent = 'No se pudo obtener tu ubicación. Revisa los permisos.'; });
  });
})();

// Vista previa de imágenes y documentos seleccionados.
(() => {
  const input = document.getElementById('attachments');
  const preview = document.getElementById('attachment-preview');
  if (!input || !preview) return;
  input.addEventListener('change', () => {
    preview.replaceChildren();
    Array.from(input.files).forEach(file => {
      const item = document.createElement('span');
      item.className = 'attachment-chip';
      item.textContent = `${file.type === 'application/pdf' ? '📄' : '📷'} ${file.name}`;
      preview.appendChild(item);
    });
  });
})();

// Chatbot: la clave de OpenAI permanece exclusivamente en el servidor.
(() => {
  const panel = document.getElementById('chat-panel');
  const open = document.getElementById('chat-open');
  const close = document.getElementById('chat-close');
  const form = document.getElementById('chat-form');
  const input = document.getElementById('chat-input');
  const messages = document.getElementById('chat-messages');
  if (!panel || !open || !form) return;
  const toggle = visible => { panel.hidden = !visible; open.setAttribute('aria-expanded', String(visible)); if (visible) input.focus(); };
  open.addEventListener('click', () => toggle(panel.hidden));
  close.addEventListener('click', () => toggle(false));
  const addMessage = (text, type) => {
    const node = document.createElement('p'); node.className = type === 'user' ? 'chat-user' : 'chat-bot';
    node.textContent = text; messages.appendChild(node); messages.scrollTop = messages.scrollHeight;
  };
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const message = input.value.trim(); if (!message) return;
    addMessage(message, 'user'); input.value = ''; input.disabled = true;
    const submit = form.querySelector('button'); submit.disabled = true; submit.textContent = '...';
    try {
      const response = await fetch('api/chat.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({message})});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'No se pudo obtener respuesta.');
      addMessage(data.answer, 'bot');
    } catch (error) { addMessage(error.message || 'No se pudo conectar con el asistente.', 'bot'); }
    finally { input.disabled = false; submit.disabled = false; submit.textContent = 'Enviar'; input.focus(); }
  });
})();

// Revelado al hacer scroll y contadores animados
(() => {
  if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  document.documentElement.classList.add('js-reveal');
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { const t = e.target; t.classList.add('in'); io.unobserve(t); setTimeout(() => { t.classList.remove('reveal', 'in'); t.style.transitionDelay = ''; }, 1000); } }), {threshold: .1});
  document.querySelectorAll('.about-section, .card, .stat, .cta-panel, .forum-post, .places-intro, .forum-heading').forEach((el, i) => { el.classList.add('reveal'); el.style.transitionDelay = (i % 3) * 80 + 'ms'; io.observe(el); });
  document.querySelectorAll('.impact-strip strong, .hero-badge b').forEach(el => {
    const end = parseInt(el.textContent, 10); if (!end) return; const t0 = performance.now(); el.textContent = '0';
    const step = t => { const p = Math.min(1, (t - t0) / 1200); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); }; requestAnimationFrame(step);
  });
})();
