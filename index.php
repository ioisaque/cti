<!DOCTYPE html>
<html lang="pt-br">

<head>
  <!-- INDEX -->
  <meta name="robots" content="index, follow">
  <meta name="author" content="Isaque Costa">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0,viewport-fit=cover">
  <meta name="theme-color" content="#000">

  <title>Portfólio CTI</title>
  <meta content="Portfólio CTI" property="og:title" />
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
      <div class="row mb-4 align-items-center">
        <div class="col-xl-7 col-12">
          <h4 class="my-4">📚 Portfólio CTI 2026 🚀</h4>
          <p class="bio">
            Uma vitrine dos projetos criados pela turma, reunindo criatividade, tecnologia e muita personalidade.
            Explore os trabalhos, conheça as ideias da galera e veja de perto o que saiu das aulas.</p>

          <p style="font-weight:bold; margin-bottom: 0.2em;">Professor responsável:
            <a href="https://isaque.it" class="pointer" style="display:inline-flex; vertical-align:middle; margin-left: -1rem;">
              <img src="https://cdn.isaque.it/assets/logos/logo-b.png" class="logo-b" alt="Logo do Professor Isaque">
              <img src="https://cdn.isaque.it/assets/logos/logo-w.png" class="logo-w" alt="Logo do Professor Isaque">
            </a>
          </p>
          <p style="margin-bottom: 0.5em;">Confira um pouco do <a href="https://www.tiktok.com/@ioisaque" class="pointer" style="color: #fff; font-weight: 600; text-decoration: none;" aria-label="TikTok do Professor Isaque">MakingOf no TikTok
              <span style="display:inline-flex; vertical-align:middle; margin-left:0.35rem;">
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path fill="currentColor" d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.3 0 .6.04.88.13V9.4a6.36 6.36 0 0 0-.88-.06A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43V8.56a8.16 8.16 0 0 0 4.77 1.53V6.73c-.35 0-.7-.01-1.04-.04Z" />
                </svg></span>
            </a>
          </p>
        </div>

        <div class="col-xl-5 col-12 text-right">
          <img class="header-logo" src="logo.png" alt="Logo CTI">
        </div>
      </div>
      <div class="row content">
        <?php
        $projectsRoot = __DIR__ . DIRECTORY_SEPARATOR . 'projects';
        $projectDirs = [];
        if (is_dir($projectsRoot)) {
          $projectGroups = [
            'pinned' => [],
            'default' => [],
            'low' => [],
          ];
          $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($projectsRoot, FilesystemIterator::SKIP_DOTS)
          );
          foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || strcasecmp($fileInfo->getFilename(), 'manifest.json') !== 0) {
              continue;
            }
            $manifest = json_decode(file_get_contents($fileInfo->getPathname()), true);
            $type = is_array($manifest) && isset($manifest['type']) ? strtolower((string) $manifest['type']) : '';

            if ($type === 'hidden') {
              continue;
            }

            if ($type === 'pinned') {
              $projectGroups['pinned'][] = dirname($fileInfo->getPathname());
            } elseif ($type === 'low') {
              $projectGroups['low'][] = dirname($fileInfo->getPathname());
            } else {
              $projectGroups['default'][] = dirname($fileInfo->getPathname());
            }
          }

          foreach ($projectGroups as $type => $dirs) {
            $projectGroups[$type] = array_values(array_unique($dirs));
            shuffle($projectGroups[$type]);
          }

          $projectDirs = array_merge($projectGroups['pinned'], $projectGroups['default'], $projectGroups['low']);
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