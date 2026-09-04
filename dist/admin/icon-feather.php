<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr"
  data-pc-theme="light">
<!-- [Head] start -->

<head>
  <title>Feather Icon Pack Template</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="description" content="." />
  <meta name="keywords" content="." />
  <meta name="author" content="Sniper 2025" />
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
  <link rel="stylesheet" href="../assets/fonts/feather.css" />
  <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
  <link rel="stylesheet" href="../assets/fonts/material.css" />
  <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body>
  <!-- [ Pre-loader ] start -->
  <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
    <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
      <div
        class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]">
      </div>
    </div>
  </div>
  <!-- [ Pre-loader ] End -->
  
  <!-- [ Sidebar Menu ] start -->
  <?php include '../includes/sidebar.php'; ?>
  <!-- [ Sidebar Menu ] end -->
  <!-- [ Header Topbar ] start -->
  <?php include '../includes/header.php'; ?>
  <!-- [ Header ] end -->

  <!-- [ Main Content ] start -->
  <div class="pc-container">
    <div class="pc-content">
      <!-- [ breadcrumb ] start -->
      <div class="page-header">
        <div class="page-block">
          <div class="page-header-title">
            <h5 class="mb-0 font-medium">Feather Icon</h5>
          </div>
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
            <li class="breadcrumb-item" aria-current="page">Feather Icon</li>
          </ul>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->


      <!-- [ Main Content ] start -->
      <div class="grid grid-cols-12 gap-6">
        <!-- [ sample-page ] start -->
        <div class="col-span-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-2">Feather Icon</h5>
              <p>
                Use svg icon with
                <code class="text-danger-400 text-sm">&lt;i data-feather="&lt;&lt; Copied code &gt;&gt;"&gt;</code>
                in you html code
              </p>
            </div>
            <div class="card-body text-center">
              <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 sm:col-span-6 sm:col-start-4">
                  <input type="text" id="icon-search" class="form-control mb-4" placeholder="search . . " />
                </div>
              </div>
              <div
                class="i-main text-center *:relative *:cursor-pointer *:inline-flex *:items-center *:justify-center *:w-[70px] *:h-[70px] *:m-[5px] *:rounded-lg *:border *:border-theme-border dark:*:border-themedark-border"
                id="icon-wrapper"></div>
            </div>
          </div>
        </div>
        <!-- [ sample-page ] end -->
      </div>
      <!-- [ Main Content ] end -->
    </div>
  </div>
  <!-- [ Main Content ] end -->
    <?php include '../includes/footer.php'; ?>
  <!-- Required Js -->
  <script src="../assets/js/plugins/simplebar.min.js"></script>
  <script src="../assets/js/plugins/popper.min.js"></script>
  <script src="../assets/js/icon/custom-icon.js"></script>
  <script src="../assets/js/plugins/feather.min.js"></script>
  <script src="../assets/js/component.js"></script>
  <script src="../assets/js/theme.js"></script>
  <script src="../assets/js/script.js"></script>

  <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
  </div>


  <script>
    layout_change('false');
      layout_theme_sidebar_change('dark');
      change_box_container('false');
      layout_caption_change('true');
      layout_rtl_change('false');
      preset_change('preset-1');
      main_layout_change('vertical');
  </script>

  <!-- [Page Specific JS] start -->
  <script src="../assets/js/plugins/clipboard.min.js"></script>
  <script>
    var icon_list = [
      'alert-octagon',
      'alert-circle',
      'activity',
      'alert-triangle',
      'align-center',
      'airplay',
      'align-justify',
      'align-left',
      'align-right',
      'arrow-down-left',
      'arrow-down-right',
      'anchor',
      'aperture',
      'arrow-left',
      'arrow-right',
      'arrow-down',
      'arrow-up-left',
      'arrow-up-right',
      'arrow-up',
      'award',
      'bar-chart',
      'at-sign',
      'bar-chart-2',
      'battery-charging',
      'bell-off',
      'battery',
      'bluetooth',
      'bell',
      'book',
      'briefcase',
      'camera-off',
      'calendar',
      'bookmark',
      'box',
      'camera',
      'check-circle',
      'check',
      'check-square',
      'cast',
      'chevron-down',
      'chevron-left',
      'chevron-right',
      'chevron-up',
      'chevrons-down',
      'chevrons-right',
      'chevrons-up',
      'chevrons-left',
      'circle',
      'clipboard',
      'chrome',
      'clock',
      'cloud-lightning',
      'cloud-drizzle',
      'cloud-rain',
      'cloud-off',
      'codepen',
      'cloud-snow',
      'compass',
      'copy',
      'corner-down-right',
      'corner-down-left',
      'corner-left-down',
      'corner-left-up',
      'corner-up-left',
      'corner-up-right',
      'corner-right-down',
      'corner-right-up',
      'cpu',
      'credit-card',
      'crosshair',
      'disc',
      'delete',
      'download-cloud',
      'download',
      'droplet',
      'edit-2',
      'edit',
      'external-link',
      'eye',
      'feather',
      'facebook',
      'file-minus',
      'eye-off',
      'fast-forward',
      'file-text',
      'film',
      'file',
      'file-plus',
      'folder',
      'filter',
      'flag',
      'globe',
      'grid',
      'heart',
      'home',
      'github',
      'image',
      'inbox',
      'layers',
      'info',
      'instagram',
      'layout',
      'link-2',
      'life-buoy',
      'link',
      'log-in',
      'list',
      'lock',
      'log-out',
      'loader',
      'mail',
      'maximize-2',
      'map',
      'map-pin',
      'menu',
      'message-circle',
      'message-square',
      'minimize-2',
      'mic-off',
      'minus-circle',
      'mic',
      'minus-square',
      'minus',
      'moon',
      'monitor',
      'more-vertical',
      'more-horizontal',
      'move',
      'music',
      'navigation-2',
      'navigation',
      'octagon',
      'package',
      'pause-circle',
      'pause',
      'percent',
      'phone-call',
      'phone-forwarded',
      'phone-missed',
      'phone-off',
      'phone-incoming',
      'phone',
      'phone-outgoing',
      'pie-chart',
      'play-circle',
      'play',
      'plus-square',
      'plus-circle',
      'plus',
      'pocket',
      'printer',
      'power',
      'radio',
      'repeat',
      'refresh-ccw',
      'rewind',
      'rotate-ccw',
      'refresh-cw',
      'rotate-cw',
      'save',
      'search',
      'server',
      'scissors',
      'share-2',
      'share',
      'shield',
      'settings',
      'skip-back',
      'shuffle',
      'sidebar',
      'skip-forward',
      'slack',
      'slash',
      'smartphone',
      'square',
      'speaker',
      'star',
      'stop-circle',
      'sun',
      'sunrise',
      'tablet',
      'tag',
      'sunset',
      'target',
      'thermometer',
      'thumbs-up',
      'thumbs-down',
      'toggle-left',
      'toggle-right',
      'trash-2',
      'trash',
      'trending-up',
      'trending-down',
      'triangle',
      'type',
      'twitter',
      'upload',
      'umbrella',
      'upload-cloud',
      'unlock',
      'user-check',
      'user-minus',
      'user-plus',
      'user-x',
      'user',
      'users',
      'video-off',
      'video',
      'voicemail',
      'volume-x',
      'volume-2',
      'volume-1',
      'volume',
      'watch',
      'wifi',
      'x-square',
      'wind',
      'x',
      'x-circle',
      'zap',
      'zoom-in',
      'zoom-out',
      'command',
      'cloud',
      'hash',
      'headphones',
      'underline',
      'italic',
      'bold',
      'crop',
      'help-circle',
      'paperclip',
      'shopping-cart',
      'tv',
      'wifi-off',
      'minimize',
      'maximize',
      'gitlab',
      'sliders'
    ];
    for (var i = 0, l = icon_list.length; i < l; i++) {
      let icon_block = document.createElement('div');
      icon_block.setAttribute('class', 'i-block');
      icon_block.setAttribute('data-clipboard-text', icon_list[i]);
      icon_block.setAttribute('data-filter', icon_list[i]);

      let icon_main = document.createElement('i');
      icon_main.setAttribute('data-feather', icon_list[i]);
      icon_main.setAttribute('class', 'w-5 h-5');
      icon_block.appendChild(icon_main);
      document.querySelector('#icon-wrapper').append(icon_block);
    }
    feather.replace();
    window.addEventListener('load', (event) => {
      var i_copy = new ClipboardJS('.i-block');
      i_copy.on('success', function(e) {
        var targetElement = e.trigger;
        let icon_badge = document.createElement('span');
        icon_badge.setAttribute('class', 'ic-badge badge bg-success-500 text-white text-sm absolute bottom-1 left-2/4 -translate-x-2/4');
        icon_badge.innerHTML = 'copied';
        targetElement.append(icon_badge);
        setTimeout(function() {
          targetElement.children[1].remove();
        }, 3000);
      });

      i_copy.on('error', function(e) {
        var targetElement = e.trigger;
        let icon_badge = document.createElement('span');
        icon_badge.setAttribute('class', 'ic-badge badge bg-danger-500 text-white text-sm absolute bottom-1 left-2/4 -translate-x-2/4');
        icon_badge.innerHTML = 'Error';
        targetElement.append(icon_badge);
        setTimeout(function() {
          targetElement.children[1].remove();
        }, 3000);
      });
      document.querySelector('#icon-search').addEventListener('keyup', function() {
        var g = document.querySelector('#icon-search').value.toLowerCase();
        var tc = document.querySelectorAll('.i-main .i-block');
        for (var i = 0; i < tc.length; i++) {
          var c = tc[i];
          var t = c.getAttribute('data-filter');
          if (t) {
            var s = t.toLowerCase();
          }
          if (s) {
            var n = s.indexOf(g);
            if (n !== -1) {
              c.style.display = 'inline-flex';
            } else {
              c.style.display = 'none';
            }
          }
        }
      });
    });
  </script>
  <!-- [Page Specific JS] end -->
</body>
<!-- [Body] end -->
</html>