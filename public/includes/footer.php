<?php
/**
 * SICA-E · Pie de Página Institucional Común
 * Institución Educativa Brighton Pamplona
 */
?>
<footer class="site-footer">
  <div class="container">
    <p style="font-weight:600; color:var(--navy);">
      <?= APP_NAME ?> · <?= APP_FULL_NAME ?>
    </p>
    <p>
      <?= INSTITUTION_NAME ?> — Pamplona, Norte de Santander, Colombia
    </p>
    <p style="font-size:0.76rem; color:var(--text-light); margin-top:0.4rem;">
      Proyecto Desarrollado por: <strong><?= AUTHOR_NAME ?></strong> · Versión <?= APP_VERSION ?>
    </p>
  </div>
</footer>

<!-- Scripts Globales -->
<script src="<?= BASE_URL ?>/assets/js/api.js"></script>

</body>
</html>
