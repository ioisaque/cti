<!DOCTYPE html>
<html lang="pt-br">

<head>
  <!-- INDEX -->
  <meta name="robots" content="index, follow">
  <meta name="author" content="Isaque Costa">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0,viewport-fit=cover">
  <meta name="theme-color" content="#000">

  <title>LocalHost - Portfólio</title>
  <meta content="LocalHost - Portfólio" property="og:title" />
  <meta name="description" content="isaque.it - Acelerando Ideias!" />
  <meta content="isaque.it - Acelerando Ideias!" property="og:description" />
  <meta name="keywords" content="ioisaque, isaque costa, isaque.it, responsivo, pagina de error, vector, minimalista, design, simples, bonito, ideyou, ios, android" />

  <!-- FAVICONS -->
  <link rel="apple-touch-icon" href="https://cdn.isaque.it/assets/icons/apple-icon-180.png">
  <link rel="icon" type="image/png" sizes="64x64" href="https://cdn.isaque.it/assets/icons/favicon-64.png">
  <link rel="icon" type="image/png" sizes="196x196" href="https://cdn.isaque.it/assets/icons/favicon-196.png">
  <meta name="msapplication-square70x70logo" content="https://cdn.isaque.it/assets/icons/mstile-icon-128.png">
  <meta name="msapplication-square150x150logo" content="https://cdn.isaque.it/assets/icons/mstile-icon-270.png">
  <meta name="msapplication-square310x310logo" content="https://cdn.isaque.it/assets/icons/mstile-icon-558.png">
  <meta name="msapplication-wide310x150logo" content="https://cdn.isaque.it/assets/icons/mstile-icon-558-270.png">

  <!-- Font -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700%7CPoppins:400,500" rel="stylesheet" />

  <!-- CSS -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.ideyou.com.br/assets/css/main-styles.css" rel="stylesheet" />
  <link href="https://cdn.ideyou.com.br/assets/css/mobile-styles.css" rel="stylesheet" />
  <link href="styles.css" rel="stylesheet" />
</head>

<body>
  <div class="wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-3 profile">
          <img class="photo-w" src="logo.png" alt="Profile Picture">
          <img class="photo-b" src="logo.png" alt="Profile Picture">
          <p>Professor Isaque Costa</p>

          <a class="social" href="mailto:costa@isaque.it" target="_blank">
            <img src="https://cdn.isaque.it/assets/socials/icons8-email.gif" alt="Email">
            <span>costa@isaque.it</span>
          </a>
          <a class="social" href="https://github.com/ioisaque" target="_blank">
            <img src="https://cdn.isaque.it/assets/socials/icons8-github.png" alt="GitHub">
            <span>/ioisaque</span>
          </a>
          <a class="social" href="https://instagram.com/ioisaque" target="_blank">
            <img src="https://cdn.isaque.it/assets/socials/icons8-instagram.gif" alt="Instagram">
            <span>/ioisaque</span>
          </a>
          <a class="social" href="https://linkedin.com/in/ioisaque/" target="_blank">
            <img src="https://cdn.isaque.it/assets/socials/icons8-linkedin.gif" alt="LinkedIn">
            <span>/in/ioisaque</span>
          </a>
        </div>
        <div class="col-md-9 content">
          <div class="row my-3">
            <div class="col-xl-6 col-12 pl-0">
              <a href="https://isaque.it" class="pointer">
                <img src="https://cdn.isaque.it/assets/logos/logo-b.png" class="logo-b" alt="Logo">
                <img src="https://cdn.isaque.it/assets/logos/logo-w.png" class="logo-w" alt="Logo">
              </a>
            </div>
            <div class="col-xl-6 col-12 text-right">
            </div>
          </div>
          <div class="row mb-4">
            <div class="col-xl-7 col-12">
              <h4 class="my-4">Bem Vindo ao Portfólio da nossa turma!</h4>
              <pre class="bio">
            Abaixo você poderá conferir os trabalhos desenvolvidos por nós durante o curso de informática de 2026.</pre>
            </div>
            <div class="col-xl-5 col-12 text-right">
              <span class="idea-img"></span>
            </div>
          </div>

          <div class="row content">
            <?php
            $projectsRoot = __DIR__ . DIRECTORY_SEPARATOR . 'projects';
            $projectDirs = [];
            if (is_dir($projectsRoot)) {
              $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($projectsRoot, FilesystemIterator::SKIP_DOTS)
              );
              foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || strcasecmp($fileInfo->getFilename(), 'manifest.json') !== 0) {
                  continue;
                }
                $projectDirs[] = dirname($fileInfo->getPathname());
              }
              $projectDirs = array_values(array_unique($projectDirs));
              sort($projectDirs, SORT_NATURAL | SORT_FLAG_CASE);
            }
            ?>
            <?php foreach ($projectDirs as $projectHome) : ?>
              <?php
              $relativeFromProjects = substr($projectHome, strlen($projectsRoot));
              $relativeFromProjects = trim(str_replace(['\\', '/'], '/', $relativeFromProjects), '/');
              $link = 'projects' . ($relativeFromProjects !== '' ? '/' . $relativeFromProjects : '');
              ?>

              <div class="repo">
                <a href="./<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>">
                  <img src="https://cdn.isaque.it/assets/gifs/load-bars.gif" alt="Repo 1 Image">
                  <div class="repo-id">
                    <h2><?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <pre></pre>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const repos = document.querySelectorAll('.repo');

      repos.forEach(repo => {
        const baseHref = repo.querySelector('a').getAttribute('href');
        const manifestUrl = baseHref + '/manifest.json';

        fetch(manifestUrl)
          .then(response => response.json())
          .then(data => {
            // Cores
            repo.className = repo.className.replace(/\bbg-\S+/g, '');
            repo.style.color = data.theme_color;
            if (data.background_color) {
              repo.style.backgroundColor = data.background_color;
            } else if (data.background && typeof data.background === 'object') {
              Object.keys(data.background).forEach(key => {
                let styleKey = 'background-' + key;
                let value = data.background[key];
                
                console.log(`${styleKey} => `, value);
                
                repo.style[styleKey] = value;
              });
            }

            // Nome e descrição
            repo.querySelector('h2').textContent = data.app_name ?? data.name;
            repo.querySelector('pre').textContent = data.description ?? '';

            // Ícone — prioriza purpose = "portfolio"
            let iconSrc = null;
            if (Array.isArray(data.icons) && data.icons.length > 0) {
              const portfolioIcon = data.icons.find(i => i.purpose === 'portfolio');
              const defaultIcon = data.icons[0];
              iconSrc = (portfolioIcon && portfolioIcon.src) || (defaultIcon && defaultIcon.src);
            }

            // Fallback
            if (!iconSrc) {
              iconSrc = 'https://cdn.isaque.it/assets/gifs/load-bars.gif';
            } else if (!/^https?:\/\//.test(iconSrc)) {
              iconSrc = baseHref + '/' + iconSrc.replace(/^\/+/, '');
            }

            // Aplicar ícone
            repo.querySelector('img').src = iconSrc;
          })
          .catch(error => console.error('Error fetching manifest:', error));
      });
    });
  </script>
</body>

</html>