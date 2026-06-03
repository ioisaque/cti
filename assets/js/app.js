(function () {
  'use strict';

  var WOHAX_LEGEND = {
    user_admin: 'Administrators',
    user_supermod: 'Super Moderators',
    user_mod: 'Moderators',
    user_coder: 'Coders',
    user_vip: 'VIP',
    user_honor: 'Honor'
  };

  var WOHAX_GHOSTS = [
    { name: 'sh0z', titleClass: 'user_admin', ghost: true },
    { name: 'Neo', titleClass: 'user_admin', ghost: true },
    { name: 'Quinzel', titleClass: 'user_supermod', ghost: true },
    { name: 'Synthetic', titleClass: 'user_mod', ghost: true },
    { name: 'Articulator', titleClass: 'user_mod', ghost: true },
    { name: 'Jhonny', titleClass: 'user_mod', ghost: true },
    { name: 'block', titleClass: 'user_coder', ghost: true }
  ];

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function sanitizeClass(value) {
    if (typeof value !== 'string' || value.trim() === '') {
      return '';
    }
    return value.trim().replace(/[^a-zA-Z0-9_\- ]/g, '').replace(/\s+/g, ' ');
  }

  function buildStyleFromManifest(manifest, colorKey) {
    var style = '';
    var textColor = '';

    if (colorKey === 'color') {
      textColor = typeof manifest.color === 'string' && manifest.color !== ''
        ? manifest.color
        : (typeof manifest.theme_color === 'string' ? manifest.theme_color : '');
    } else {
      textColor = typeof manifest.theme_color === 'string' ? manifest.theme_color : '';
    }

    if (textColor !== '') {
      style += 'color:' + textColor + ';';
    }

    var background = manifest.background;
    if (background && typeof background === 'object' && Object.keys(background).length > 0) {
      Object.keys(background).forEach(function (key) {
        var cssValue = background[key];
        if (typeof key !== 'string' || typeof cssValue !== 'string' || cssValue.trim() === '') {
          return;
        }
        key = key.toLowerCase().trim();
        if (!/^[a-z-]+$/.test(key)) {
          return;
        }
        style += 'background-' + key + ':' + cssValue + ';';
      });
    } else if (typeof manifest.background_color === 'string' && manifest.background_color !== '') {
      style += 'background-color:' + manifest.background_color + ';';
    }

    return style;
  }

  function groupAndShuffleProjects(projects) {
    var groups = { pinned: [], default: [], low: [] };

    projects.forEach(function (entry) {
      var manifest = entry.manifest && typeof entry.manifest === 'object' ? entry.manifest : {};
      var type = String(manifest.type || '').toLowerCase();
      if (type === 'pinned') {
        groups.pinned.push(entry);
      } else if (type === 'low') {
        groups.low.push(entry);
      } else {
        groups.default.push(entry);
      }
    });

    function shuffle(list) {
      for (var i = list.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var tmp = list[i];
        list[i] = list[j];
        list[j] = tmp;
      }
      return list;
    }

    return shuffle(groups.pinned).concat(shuffle(groups.default), shuffle(groups.low));
  }

  function renderProjectCard(entry) {
    var url = typeof entry.url === 'string' ? entry.url : '';
    if (url === '') {
      return '';
    }

    var manifest = entry.manifest && typeof entry.manifest === 'object' ? entry.manifest : {};
    var name = typeof entry.name === 'string' && entry.name !== ''
      ? entry.name
      : (typeof entry.display_host === 'string' ? entry.display_host : '');
    var description = typeof manifest.description === 'string' ? manifest.description : '';
    var repoStyle = buildStyleFromManifest(manifest, 'theme_color');
    var styleAttr = repoStyle !== '' ? ' style="' + escapeHtml(repoStyle) + '"' : '';

    var iconSrc = 'https://cdn.isaque.it/assets/gifs/load-bars.gif';
    if (Array.isArray(manifest.icons) && manifest.icons.length > 0) {
      var portfolioIcon = null;
      manifest.icons.forEach(function (icon) {
        if (icon && icon.purpose === 'portfolio') {
          portfolioIcon = icon;
        }
      });
      var iconEntry = portfolioIcon || manifest.icons[0];
      if (iconEntry && typeof iconEntry.src === 'string' && iconEntry.src !== '') {
        iconSrc = iconEntry.src;
        if (!/^https?:\/\//i.test(iconSrc)) {
          iconSrc = String(entry.asset_url || url).replace(/\/$/, '') + '/' + iconSrc.replace(/^\//, '');
        }
      }
    }

    var titleClass = sanitizeClass(manifest.titleClass);
    var titleClassAttr = titleClass !== '' ? ' class="' + escapeHtml(titleClass) + '"' : '';

    return (
      '<div class="repo repo-project"' + styleAttr + '>' +
        '<a href="' + escapeHtml(url) + '"' + styleAttr + '>' +
          '<img src="' + escapeHtml(iconSrc) + '" alt="' + escapeHtml(name) + '">' +
          '<div class="repo-id">' +
            '<h2' + titleClassAttr + '>' + escapeHtml(name) + '</h2>' +
            '<pre>' + escapeHtml(description) + '</pre>' +
          '</div>' +
        '</a>' +
      '</div>'
    );
  }

  function renderStationCard(entry) {
    var url = typeof entry.url === 'string' ? entry.url : '';
    if (url === '') {
      return '';
    }

    var manifest = entry.manifest && typeof entry.manifest === 'object' ? entry.manifest : {};
    var port = Number(entry.port) || 80;
    var hostIp = typeof entry.ip === 'string' ? entry.ip : '';
    var displayHost = typeof entry.display_host === 'string' && entry.display_host !== ''
      ? entry.display_host
      : hostIp;
    var subtitle = displayHost;
    if (port !== 80 && subtitle !== '') {
      subtitle += ':' + port;
    }

    var name = typeof entry.name === 'string' && entry.name !== ''
      ? entry.name
      : displayHost;

    var stationStyle = buildStyleFromManifest(manifest, 'color');
    var styleAttr = stationStyle !== '' ? ' style="' + escapeHtml(stationStyle) + '"' : '';

    var iconFile = '';
    if (Array.isArray(manifest.icons) && manifest.icons.length > 0) {
      var iconEntry = manifest.icons[0];
      if (iconEntry && typeof iconEntry.src === 'string' && iconEntry.src !== '') {
        iconFile = iconEntry.src.split('/').pop();
      }
    }

    var iconSrc = 'assets/img/avatar-placeholder.svg';
    if (iconFile !== '') {
      if (hostIp === '127.0.0.1') {
        iconSrc = 'logo.png';
      } else if (hostIp !== '') {
        iconSrc = 'assets/php/image.php?ip=' + encodeURIComponent(hostIp)
          + '&port=' + port
          + '&src=' + encodeURIComponent(iconFile);
      }
    }

    var titleClass = sanitizeClass(manifest.titleClass);
    var textClass = sanitizeClass(manifest.textClass);
    var subtitleClass = 'station-host' + (textClass !== '' ? ' ' + escapeHtml(textClass) : '');
    var nameClass = 'station-name' + (titleClass !== '' ? ' ' + escapeHtml(titleClass) : '');

    return (
      '<div class="station"' + styleAttr + '>' +
        '<a href="' + escapeHtml(url) + '" class="station-link"' + styleAttr + '>' +
          '<img class="station-avatar" src="' + escapeHtml(iconSrc) + '" alt="' + escapeHtml(name) + '">' +
          '<div class="station-body">' +
            '<h2 class="' + nameClass + '">' + escapeHtml(name) + '</h2>' +
            '<pre class="' + subtitleClass + '">' + escapeHtml(subtitle) + '</pre>' +
          '</div>' +
        '</a>' +
      '</div>'
    );
  }

  function buildWohaxUsers(stations) {
    var users = [];

    stations.forEach(function (entry) {
      var url = typeof entry.url === 'string' ? entry.url : '';
      if (url === '') {
        return;
      }

      var manifest = entry.manifest && typeof entry.manifest === 'object' ? entry.manifest : {};
      var displayHost = typeof entry.display_host === 'string' && entry.display_host !== ''
        ? entry.display_host
        : (typeof entry.ip === 'string' ? entry.ip : '');
      var name = typeof entry.name === 'string' && entry.name !== ''
        ? entry.name
        : displayHost;

      if (name === '') {
        return;
      }

      var titleClass = 'user_registered';
      var sanitized = sanitizeClass(manifest.titleClass);
      if (sanitized !== '') {
        titleClass = sanitized;
      }

      users.push({ name: name, url: url, titleClass: titleClass, ghost: false });
    });

    users = users.concat(WOHAX_GHOSTS);

    for (var i = users.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = users[i];
      users[i] = users[j];
      users[j] = tmp;
    }

    return users;
  }

  function renderWohaxPanel(stations) {
    var users = buildWohaxUsers(stations);
    var usersHtml = users.map(function (user, index) {
      var prefix = index > 0 ? '<span class="wohax-sep">, </span>' : '';
      if (user.ghost) {
        return prefix + '<span class="wohax-user ' + escapeHtml(user.titleClass) + '">' + escapeHtml(user.name) + '</span>';
      }
      return prefix + '<a href="' + escapeHtml(user.url) + '" class="wohax-user ' + escapeHtml(user.titleClass) + '">' + escapeHtml(user.name) + '</a>';
    }).join('');

    var legendHtml = Object.keys(WOHAX_LEGEND).map(function (cssClass) {
      return '<span class="wohax-legend-item"><span class="' + escapeHtml(cssClass) + '">' + escapeHtml(WOHAX_LEGEND[cssClass]) + '</span></span>';
    }).join('');

    return (
      '<div class="wohax-panel">' +
        '<div class="wohax-head">Currently Active Users</div>' +
        '<div class="wohax-body">' +
          '<p class="wohax-users mb-0">' + usersHtml + '</p>' +
          '<div class="wohax-legend">' + legendHtml + '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function scanTooltip(updatedAt, stationCount) {
    if (updatedAt) {
      return 'Última varredura: ' + updatedAt + ' · ' + stationCount + ' estação(ões)';
    }
    if (stationCount > 0) {
      return stationCount + ' estação(ões) na rede';
    }
    return 'Nenhuma varredura ainda';
  }

  function wireImageFallbacks(container, selector, fallbackSrc) {
    if (!container) {
      return;
    }
    container.querySelectorAll(selector).forEach(function (img) {
      img.addEventListener('error', function () {
        if (img.dataset.fallbackApplied === '1') {
          return;
        }
        img.dataset.fallbackApplied = '1';
        img.src = fallbackSrc;
      });
    });
  }

  function initTabs(listingTabs) {
    if (!listingTabs || typeof bootstrap === 'undefined') {
      return;
    }

    listingTabs.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tabEl) {
      tabEl.addEventListener('shown.bs.tab', function (event) {
        var target = event.target.getAttribute('data-bs-target');
        if (target) {
          localStorage.setItem('cti-listing-tab', target);
        }
      });
    });

    var savedTab = localStorage.getItem('cti-listing-tab');
    if (!savedTab) {
      return;
    }

    var tabTrigger = listingTabs.querySelector('[data-bs-target="' + savedTab + '"]');
    if (tabTrigger) {
      bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
    }
  }

  function wireDiscoverButton(button, onComplete) {
    var defaultLabel = button.textContent;

    button.addEventListener('click', function () {
      button.disabled = true;
      button.textContent = 'Varrendo…';

      fetch('assets/php/discover.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (!data.ok) {
            throw new Error(data.error || 'Falha na descoberta.');
          }
          if (data.preserved && data.warning) {
            button.disabled = false;
            button.textContent = defaultLabel;
            button.setAttribute('title', data.warning);
            return;
          }
          button.textContent = 'Recarregando…';
          onComplete(data);
        })
        .catch(function (error) {
          button.disabled = false;
          button.textContent = defaultLabel;
          button.setAttribute('title', error.message);
        });
    });
  }

  function renderProjects(container, projects) {
    if (!container) {
      return;
    }
    if (!projects.length) {
      container.innerHTML = '<p class="stations-empty px-1 mb-0">Nenhum projeto encontrado.</p>';
      return;
    }
    container.innerHTML = groupAndShuffleProjects(projects).map(renderProjectCard).join('');
    wireImageFallbacks(container, '.repo img', 'https://cdn.isaque.it/assets/gifs/load-bars.gif');
  }

  function renderStations(container, stations, isDiscoverAdmin) {
    if (!container) {
      return;
    }
    if (!stations.length) {
      var suffix = isDiscoverAdmin ? ' Use Atualizar ou aguarde o cron.' : '';
      container.innerHTML = '<p class="stations-empty px-1 mb-0">Nenhuma estação encontrada.' + suffix + '</p>';
      return;
    }
    container.innerHTML = stations.map(renderStationCard).join('');
    wireImageFallbacks(container, '.station-avatar', 'assets/img/avatar-placeholder.svg');
  }

  function loadHubAssets() {
    if (document.getElementById('wohax-css')) {
      return;
    }

    var wohaxCss = document.createElement('link');
    wohaxCss.id = 'wohax-css';
    wohaxCss.rel = 'stylesheet';
    wohaxCss.href = 'assets/css/wohax.css';
    document.head.appendChild(wohaxCss);
  }

  function isLocalHubHost(hostname, hubConfig) {
    hostname = String(hostname || '').toLowerCase();

    if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '[::1]') {
      return true;
    }

    if (hostname.endsWith('.local')) {
      return true;
    }

    if (/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)\d+\.\d+$/.test(hostname)) {
      return true;
    }

    var localHubHosts = Array.isArray(hubConfig.localHubHosts) ? hubConfig.localHubHosts : [];
    return localHubHosts.some(function (host) {
      return String(host).toLowerCase() === hostname;
    });
  }

  function fetchJson(url) {
    return fetch(url).then(function (response) {
      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }
      return response.json();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    fetchJson('assets/config/hub.json')
      .then(function (hubConfig) {
        var hostname = window.location.hostname.toLowerCase();
        var isLocalHub = isLocalHubHost(hostname, hubConfig);

        var hubListing = document.getElementById('hub-listing');
        var hubProjectsGrid = document.getElementById('projects-grid');
        var publicProjectsGrid = document.getElementById('public-projects-grid');

        if (isLocalHub) {
          loadHubAssets();
          if (hubListing) {
            hubListing.hidden = false;
          }
          if (publicProjectsGrid) {
            publicProjectsGrid.hidden = true;
          }
          initTabs(hubListing);
        } else if (publicProjectsGrid) {
          publicProjectsGrid.hidden = false;
        }

        var projectsContainer = isLocalHub ? hubProjectsGrid : publicProjectsGrid;

        return fetchJson('assets/php/projects.php')
          .then(function (data) {
            renderProjects(projectsContainer, Array.isArray(data.projects) ? data.projects : []);
            if (!isLocalHub) {
              return null;
            }
            return fetchJson('assets/php/stations.php');
          })
          .then(function (stationsData) {
            if (!stationsData) {
              return;
            }

            var stations = Array.isArray(stationsData.stations) ? stationsData.stations : [];
            var stationsGrid = document.getElementById('stations-grid');
            var wohaxContainer = document.getElementById('wohax-container');
            var discoverButton = document.getElementById('btn-discover-stations');
            var refreshWrap = document.getElementById('listing-refresh-wrap');

            renderStations(stationsGrid, stations, !!stationsData.isDiscoverAdmin);

            if (wohaxContainer) {
              wohaxContainer.innerHTML = renderWohaxPanel(stations);
            }

            if (stationsData.isDiscoverAdmin && refreshWrap && discoverButton) {
              refreshWrap.hidden = false;
              var tooltip = scanTooltip(stationsData.updatedAt, stations.length);
              discoverButton.setAttribute('title', tooltip);
              discoverButton.setAttribute('aria-label', 'Atualizar estações. ' + tooltip);
              wireDiscoverButton(discoverButton, function () {
                fetchJson('assets/php/stations.php')
                  .then(function (freshData) {
                    var freshStations = Array.isArray(freshData.stations) ? freshData.stations : [];
                    renderStations(stationsGrid, freshStations, !!freshData.isDiscoverAdmin);
                    if (wohaxContainer) {
                      wohaxContainer.innerHTML = renderWohaxPanel(freshStations);
                    }
                    var newTooltip = scanTooltip(freshData.updatedAt, freshStations.length);
                    discoverButton.disabled = false;
                    discoverButton.textContent = 'Atualizar';
                    discoverButton.setAttribute('title', newTooltip);
                    discoverButton.setAttribute('aria-label', 'Atualizar estações. ' + newTooltip);
                  })
                  .catch(function () {
                    window.location.reload();
                  });
              });
            }
          });
      })
      .catch(function () {
        var fallbackContainer = document.getElementById('public-projects-grid') || document.getElementById('projects-grid');
        if (fallbackContainer) {
          fallbackContainer.innerHTML = '<p class="stations-empty px-1 mb-0">Erro ao carregar projetos.</p>';
        }
      });
  });
})();
