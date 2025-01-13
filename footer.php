<?php

global $page, $version, $devmode, $time;

if ($page !== 'admin') { ?>
  <div class="push"></div>
<?php } ?>

<?php if ($page !== 'admin') { ?>
  <footer class="footer py-5 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-12 col-md-6">
            <?php if (!getSetting('disable_tos', 'value2')) { ?>
              <a
                href="tos.php"
                class="tos-link"
              ><?= lang('tos') ?></a>
            <?php } ?>

            <?php if (getSetting('imprint_enable', 'value2')) { ?>
              - <a
                href="imprint.php"
                class="tos-link"
              ><?= lang('imprint') ?></a>
            <?php } ?>

            <?php if (getSetting('privacy_enable', 'value2')) { ?>
              <br>
              <a
                href="privacy.php"
                class="tos-link"
              ><?= lang('privacy', 'Privacy Policy') ?></a>
            <?php } ?>
        </div>

        <div
          class="col-12 col-md-6"
          style="text-align: right;"
        >
          <!-- Check if copyright is set to show -->
            <?php if (getSetting('site_copyright', 'value2')) { ?>
              <span style="color: #c10000"><a href="https://www.gmodstore.com/market/view/565" target="_blank">Prometheus</a></span>
                <?= lang('by') ?>
              Marcuz & Newjorciks<br>
            <?php } ?>
          <i class="fab fa-steam"></i> <?= lang('powered_by', 'Powered By'); ?> <a href="https://steampowered.com">Steam</a><br>

          <!-- Print version number -->
          <span class="version">v.<?= $version ?></span>
          <!--
              Revision
              518360635
          -->
        </div>
      </div>

      <div class="row">
        <div
          class="col text-right"
          style="margin-top: 5px;"
        >
            <?php if (!getSetting('disable_language_selector', 'value2')) { ?>
              <select
                name="language"
                class="selectpicker client_language_picker"
                data-style="btn-prom"
                data-live-search="true"
              >
                  <?php
                  echo options::languages();
                  ?>
              </select>
            <?php } ?>

            <?php if (!getSetting('disable_theme_selector', 'value2')) { ?>
              <select
                name="theme"
                class="selectpicker client_theme_picker"
                data-style="btn-prom"
                data-live-search="true"
              >
                <?php
                  echo theme::options();
                ?>
              </select>
            <?php } ?>
        </div>
      </div>
    </div>
  </footer>
<?php } ?>

  <script src="compiled/js/tinymce/tinymce.min.js"></script>
  <script src="compiled/js/site.js"></script>

  <script>
    <?php if ($page !== 'admin' && getSetting('halloween_things', 'value2')) { ?>
      $.fn.halloweenBats({});
    <?php } ?>
  </script>

  <script
    type="template"
    id="message-danger"
  >
  <p class='bs-callout bs-callout-danger'>
    <button
      type='button'
      class='close'
      data-dismiss='alert'
    >&times;
    </button>
  </p>
  </script>

  <script
    type="template"
    id="message-success"
  >
  <p class='bs-callout bs-callout-success'>
    <button
      type='button'
      class='close'
      data-dismiss='alert'
    >&times;
    </button>
  </p>
  </script>
  </body>
  </html>

<?php
if ($devmode) { ?>
  <div
    class="position-fixed top-right p-3 m-3 bs-callout bs-callout-danger alert"
    style="bottom: 0; right: 0;"
  >
    <i class="fas fa-stopwatch fa-fw"></i>
    <span>
            <?= "Page loaded in: " . (microtime(true) - $time) . "s" ?>
        </span>
  </div>
<?php } ?>

<?php
//var_dump($db->queries);
