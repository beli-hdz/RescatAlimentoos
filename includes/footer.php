
</main>

<section class="emergency-contact" aria-label="Contacto de emergencia">
  <div class="emergency-copy">
    <strong>¿Necesitas ayuda inmediata?</strong>
    <p>Si existe una emergencia, comunícate con los servicios de emergencia.</p>
  </div>

  <a class="emergency-call" href="tel:8001234575">
    <span class="ec-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z"/></svg></span><span class="ec-text"><small>Llamar ahora</small><b>800 123 4575</b></span>
  </a>

  <?php require __DIR__ . '/../config/contact.php'; ?>
  <?php if (!empty($whatsappContact) && preg_match('/^[0-9]{10,15}$/', $whatsappContact)): ?>
    <a class="emergency-whatsapp"
       href="<?= e('https://wa.me/' . $whatsappContact) ?>"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Contactar a RescatAlimentos por WhatsApp">
      <span class="ec-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 01-12.4 7.4L3 20l1.2-5.4A8.4 8.4 0 1121 11.5z"/></svg></span><span class="ec-text"><small>Escríbenos por</small><b>WhatsApp</b></span>
    </a>
  <?php else: ?>
    <span class="emergency-whatsapp emergency-whatsapp-disabled" aria-disabled="true">
      <span class="ec-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 01-12.4 7.4L3 20l1.2-5.4A8.4 8.4 0 1121 11.5z"/></svg></span><span class="ec-text"><small>Próximamente</small><b>WhatsApp</b></span>
    </span>
  <?php endif; ?>
</section>

<div class="chat-widget" id="rescat-chat">
  <section class="chat-panel" id="chat-panel" hidden aria-label="Asistente de RescatAlimentos">

    <div class="chat-heading">
      <div>
        <strong>Asistente RescatAlimentos</strong>
        <small>Preguntas sobre donaciones y entregas</small>
      </div>
      <button type="button" id="chat-close" aria-label="Cerrar chat">×</button>
    </div>

    <div class="chat-messages" id="chat-messages" aria-live="polite">
      <p class="chat-bot">
        ¡Hola! Puedo orientarte sobre registros, donaciones, entregas y centros de recolección.
      </p>
    </div>

    <form id="chat-form" class="chat-form">
      <label class="sr-only" for="chat-input">Escribe tu pregunta</label>
      <input id="chat-input" maxlength="1200" placeholder="Escribe tu pregunta..." required>
      <button type="submit">Enviar</button>
    </form>

    <small class="chat-disclaimer">
      No compartas contraseñas ni información sensible.
    </small>

  </section>

  <button type="button" class="chat-launcher" id="chat-open" aria-expanded="false">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 01-12.4 7.4L3 20l1.2-5.4A8.4 8.4 0 1121 11.5z"/></svg>Ayuda con IA
  </button>
</div>

<footer class="site-footer">

  <div class="footer-inner footer-inner-redes">

    <div class="footer-brand-block">
      <a class="brand footer-brand" href="index.php">
        <span class="brand-mark"><img src="assets/img/icono-rescatalimentos-v3.svg" alt="" width="40" height="40"></span> Rescat<span>Alimentos</span>
      </a>
      <p>Conectamos alimentos, oportunidades y personas. Juntos reducimos el desperdicio y fortalecemos nuestra comunidad.</p>
    </div>

    <div class="footer-social">
      <strong>Síguenos</strong>
      <div class="social-links" aria-label="Redes sociales"><a class="soc fb" href="https://facebook.com/rescatalimentos.mx" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><i class="soc-ico"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></i></a><a class="soc ig" href="https://instagram.com/rescatalimentos.mx" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><i class="soc-ico"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/></svg></i></a><a class="soc tt" href="https://tiktok.com/@rescatalimentos.mx" target="_blank" rel="noopener noreferrer" aria-label="TikTok" title="TikTok"><i class="soc-ico"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></i></a></div>
    </div>

  </div>

  <div class="footer-bottom">
    <span>© <?= date('Y') ?> RescatAlimentos. Todos los derechos reservados. <em>· Perfiles de redes de ejemplo</em></span>
    <span>Una comunidad que comparte, una comunidad que crece. 🌱</span>
  </div>

</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
