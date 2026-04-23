/* ============================================================
 * q-data-table.js
 * Enhancer client-side para tablas del sistema.
 * Busca todos los <table class="q-data-table"> y les agrega:
 *   · toolbar con título, contador, buscador y filtros
 *   · ordenamiento por columna (click en th)
 *   · paginación con items-por-página (10/25/50/100/Todos)
 *   · estado "sin resultados"
 *
 * Se auto-inicializa en DOMContentLoaded y observa nuevas tablas
 * agregadas al DOM (por Alpine, htmx, etc.).
 *
 * Configuración por atributos del <table> o de un wrapper .q-dt-wrap:
 *   data-title         Título de la toolbar (default: primer H1/H2 previo o "Datos")
 *   data-icon          Ícono remix (default: "ri-table-line")
 *   data-per-page      Items por página inicial (default: 10 — 0 = todos)
 *   data-search        "true"/"false" (default: true)
 *   data-sort          "true"/"false" global (default: true)
 *   data-unit          Nombre de la unidad en el contador ("reglas", "facturas"...)
 *
 * Por columna (en <th>):
 *   data-filter="Etiqueta"   Activa dropdown de filtro único usando valores distintos
 *   data-sortable="false"    Desactiva ordenamiento de esa columna
 *   data-type="number|date"  Fuerza tipo de comparación para ordenar
 * ============================================================ */
(function () {
  'use strict';

  var PER_PAGE_OPTIONS = [10, 25, 50, 100, 0];

  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function txt(el) { return (el.textContent || '').trim(); }
  function norm(s) {
    return (s || '').toString().toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '');
  }

  function ensureWrap(table) {
    // Si ya tiene .q-dt-wrap padre, usarlo. Si no, envolver.
    var wrap = table.closest('.q-dt-wrap');
    if (wrap) return wrap;
    wrap = document.createElement('div');
    wrap.className = 'q-dt-wrap';
    // Copiar dataset relevante del table al wrap para config
    ['title','icon','perPage','search','sort','unit'].forEach(function (k) {
      var dk = 'data-' + k.replace(/[A-Z]/g, function (m) { return '-' + m.toLowerCase(); });
      if (table.hasAttribute(dk)) wrap.setAttribute(dk, table.getAttribute(dk));
    });
    table.parentNode.insertBefore(wrap, table);
    wrap.appendChild(table);
    return wrap;
  }

  function getOpt(wrap, table, key, def) {
    var v = wrap.getAttribute('data-' + key);
    if (v == null) v = table.getAttribute('data-' + key);
    return v == null ? def : v;
  }

  function parseBool(v, def) {
    if (v == null) return def;
    v = ('' + v).toLowerCase();
    if (v === 'false' || v === '0' || v === 'no') return false;
    if (v === 'true'  || v === '1' || v === 'yes') return true;
    return def;
  }

  function compareValues(a, b, type) {
    if (type === 'number') {
      var na = parseFloat((a || '').replace(/[^\d.\-]/g, ''));
      var nb = parseFloat((b || '').replace(/[^\d.\-]/g, ''));
      if (isNaN(na) && isNaN(nb)) return 0;
      if (isNaN(na)) return 1;
      if (isNaN(nb)) return -1;
      return na - nb;
    }
    if (type === 'date') {
      var da = Date.parse(a), db = Date.parse(b);
      if (isNaN(da) && isNaN(db)) return 0;
      if (isNaN(da)) return 1;
      if (isNaN(db)) return -1;
      return da - db;
    }
    return norm(a).localeCompare(norm(b), 'es', { numeric: true, sensitivity: 'base' });
  }

  function init(table) {
    if (table.__qdt) return; // ya inicializada
    table.__qdt = true;

    var wrap = ensureWrap(table);
    var tbody = table.tBodies[0];
    if (!tbody) return;

    // Ignorar tablas muy chicas si no tienen marcador explícito
    var rows = $$('tr', tbody).filter(function (r) { return !r.classList.contains('q-dt-empty-row'); });
    var total = rows.length;

    var cfg = {
      title:    getOpt(wrap, table, 'title', null),
      icon:     getOpt(wrap, table, 'icon',  'ri-table-line'),
      unit:     getOpt(wrap, table, 'unit',  'registros'),
      search:   parseBool(getOpt(wrap, table, 'search', 'true'), true),
      sort:     parseBool(getOpt(wrap, table, 'sort',   'true'), true),
      paginate: parseBool(getOpt(wrap, table, 'paginate', 'true'), true),
      perPage:  parseInt(getOpt(wrap, table, 'per-page', '10'), 10),
    };
    if (isNaN(cfg.perPage)) cfg.perPage = 10;
    // Si paginate=false, no hay paginador ni per-page — mostrar todas
    if (!cfg.paginate) cfg.perPage = 0;

    // Título inferido si no se especifica
    if (!cfg.title) {
      var prev = wrap.previousElementSibling;
      while (prev) {
        var h = prev.tagName && prev.tagName.match(/^H[1-6]$/) ? prev : prev.querySelector('h1,h2,h3');
        if (h && txt(h)) { cfg.title = txt(h); break; }
        prev = prev.previousElementSibling;
      }
      if (!cfg.title) cfg.title = 'Datos';
    }

    // Detectar columnas de filtro
    var headers = $$('thead th', table);
    var filterCols = [];
    headers.forEach(function (th, idx) {
      var lbl = th.getAttribute('data-filter');
      if (lbl != null) {
        filterCols.push({ idx: idx, label: lbl || txt(th) || 'Filtro' });
      }
      // Marcar sortables
      if (cfg.sort && th.getAttribute('data-sortable') !== 'false') {
        th.setAttribute('data-sortable', 'true');
        if (!$('.q-dt-sort', th)) {
          var s = document.createElement('span');
          s.className = 'q-dt-sort';
          th.appendChild(document.createTextNode(' '));
          th.appendChild(s);
        }
      }
    });

    // Envolver la tabla en .q-dt-box si aún no está
    var box = table.parentNode.classList.contains('q-dt-box') ? table.parentNode : null;
    if (!box) {
      box = document.createElement('div');
      box.className = 'q-dt-box';
      table.parentNode.insertBefore(box, table);
      box.appendChild(table);
    }

    // ---- Toolbar ----
    var toolbar = document.createElement('div');
    toolbar.className = 'q-dt-toolbar';
    toolbar.innerHTML =
      '<span class="q-dt-title"><i class="' + cfg.icon + '"></i> ' + escHtml(cfg.title) + '</span>' +
      '<span class="q-dt-count" data-q-count>' + total + ' ' + escHtml(cfg.unit) + '</span>';
    wrap.insertBefore(toolbar, box);

    // Filtros (insertados antes del contador)
    var filterInputs = [];
    filterCols.forEach(function (fc) {
      var vals = {};
      rows.forEach(function (r) {
        var cell = r.cells[fc.idx];
        if (!cell) return;
        var v = txt(cell);
        if (v) vals[v] = true;
      });
      var sortedVals = Object.keys(vals).sort(function (a, b) {
        return norm(a).localeCompare(norm(b), 'es', { numeric: true, sensitivity: 'base' });
      });
      if (!sortedVals.length) return;

      var wrapFilter = document.createElement('span');
      wrapFilter.className = 'q-dt-filter';
      var sel = document.createElement('select');
      sel.innerHTML = '<option value="">' + escHtml(fc.label) + ': Todos</option>' +
        sortedVals.map(function (v) {
          return '<option value="' + escHtml(v) + '">' + escHtml(v) + '</option>';
        }).join('');
      wrapFilter.appendChild(sel);
      toolbar.insertBefore(wrapFilter, toolbar.querySelector('[data-q-count]'));
      filterInputs.push({ col: fc.idx, el: sel, wrap: wrapFilter });
      sel.addEventListener('change', function () {
        wrapFilter.classList.toggle('has-value', !!sel.value);
        state.page = 1; apply();
      });
    });

    // Search (insertado antes de filtros/contador)
    var search = null;
    if (cfg.search) {
      var sbox = document.createElement('span');
      sbox.className = 'q-dt-search';
      sbox.innerHTML =
        '<i class="ri-search-line"></i>' +
        '<input type="search" placeholder="Buscar..." autocomplete="off">' +
        '<button type="button" class="q-dt-clear" title="Limpiar">✕</button>';
      toolbar.insertBefore(sbox, toolbar.querySelector('.q-dt-filter, [data-q-count]'));
      search = sbox.querySelector('input');
      var clear = sbox.querySelector('.q-dt-clear');
      search.addEventListener('input', function () {
        sbox.classList.toggle('has-value', !!search.value);
        state.page = 1; apply();
      });
      clear.addEventListener('click', function () {
        search.value = ''; sbox.classList.remove('has-value');
        state.page = 1; apply(); search.focus();
      });
    }

    // ---- Footer (paginación + per-page) ----
    var footer = document.createElement('div');
    footer.className = 'q-dt-footer';
    if (cfg.paginate) {
      footer.innerHTML =
        '<span class="q-dt-info"></span>' +
        '<span class="q-dt-perpage"><span>Mostrar</span>' +
        '<select>' + PER_PAGE_OPTIONS.map(function (n) {
          return '<option value="' + n + '"' + (n === cfg.perPage ? ' selected' : '') + '>' +
                 (n === 0 ? 'Todos' : n) + '</option>';
        }).join('') + '</select></span>' +
        '<span class="q-dt-pager"></span>';
    } else {
      footer.innerHTML = '<span class="q-dt-info"></span>';
    }
    wrap.appendChild(footer);

    var perSel = footer.querySelector('.q-dt-perpage select');
    if (perSel) {
      perSel.addEventListener('change', function () {
        state.perPage = parseInt(perSel.value, 10) || 0;
        state.page = 1; apply();
      });
    }

    // Empty state row (se inserta cuando no hay matches)
    var emptyRow = document.createElement('tr');
    emptyRow.className = 'q-dt-empty-row';
    emptyRow.style.display = 'none';
    var emptyCell = document.createElement('td');
    emptyCell.colSpan = Math.max(1, headers.length);
    emptyCell.innerHTML =
      '<div class="q-dt-empty">' +
        '<i class="ri-inbox-line"></i>' +
        '<div class="q-dt-empty-title">Sin resultados</div>' +
        '<div>Probá con otro término de búsqueda o limpiá los filtros.</div>' +
      '</div>';
    emptyRow.appendChild(emptyCell);
    tbody.appendChild(emptyRow);

    // ---- Sort handlers ----
    headers.forEach(function (th, idx) {
      if (th.getAttribute('data-sortable') !== 'true') return;
      th.addEventListener('click', function () {
        var curAsc  = th.classList.contains('q-dt-sorted-asc');
        var curDesc = th.classList.contains('q-dt-sorted-desc');
        headers.forEach(function (x) { x.classList.remove('q-dt-sorted-asc', 'q-dt-sorted-desc'); });
        var dir = curAsc ? 'desc' : (curDesc ? null : 'asc');
        state.sortCol = dir == null ? null : idx;
        state.sortDir = dir;
        if (dir === 'asc')  th.classList.add('q-dt-sorted-asc');
        if (dir === 'desc') th.classList.add('q-dt-sorted-desc');
        apply();
      });
    });

    // ---- Estado ----
    var state = {
      page: 1,
      perPage: cfg.perPage,
      sortCol: null,
      sortDir: null,
    };

    function apply() {
      var q = search ? norm(search.value) : '';
      var filters = filterInputs.map(function (f) {
        return { col: f.col, val: f.el.value };
      }).filter(function (f) { return f.val !== ''; });

      // Filtrar
      var visible = rows.filter(function (r) {
        // Search global
        if (q) {
          var hay = norm(r.__qdtText || (r.__qdtText = txt(r)));
          if (hay.indexOf(q) === -1) return false;
        }
        // Filtros por columna
        for (var i = 0; i < filters.length; i++) {
          var f = filters[i];
          var c = r.cells[f.col];
          if (!c || txt(c) !== f.val) return false;
        }
        return true;
      });

      // Ordenar
      if (state.sortCol != null && state.sortDir) {
        var colIdx = state.sortCol;
        var type = (headers[colIdx] && headers[colIdx].getAttribute('data-type')) || null;
        visible.sort(function (a, b) {
          var av = txt(a.cells[colIdx] || { textContent: '' });
          var bv = txt(b.cells[colIdx] || { textContent: '' });
          var cmp = compareValues(av, bv, type);
          return state.sortDir === 'asc' ? cmp : -cmp;
        });
      }

      // Paginar
      var perPage = state.perPage || visible.length || 1;
      var pages = Math.max(1, Math.ceil(visible.length / perPage));
      if (state.page > pages) state.page = pages;
      var start = (state.page - 1) * perPage;
      var end   = start + perPage;

      // Ocultar todas y mostrar la ventana
      rows.forEach(function (r) { r.classList.add('q-dt-hidden'); });
      // Reacomodar en el DOM según orden (solo si cambió el orden)
      var frag = document.createDocumentFragment();
      visible.forEach(function (r, i) {
        if (i >= start && i < end) {
          r.classList.remove('q-dt-hidden');
          frag.appendChild(r);
        }
      });
      // Mantener el orden visual acorde al sort: mover los visibles al inicio del tbody
      tbody.insertBefore(frag, emptyRow);

      // Empty row
      emptyRow.style.display = visible.length === 0 ? '' : 'none';

      // Info + contador + pager
      $('[data-q-count]', toolbar).textContent = total + ' ' + cfg.unit;
      var info = $('.q-dt-info', footer);
      if (visible.length === 0) {
        info.innerHTML = 'Sin resultados' +
          (rows.length ? ' <span style="color:#94a3b8">(de ' + rows.length + ')</span>' : '');
      } else if (state.perPage === 0) {
        info.innerHTML = 'Mostrando <b>' + visible.length + '</b> ' + cfg.unit;
      } else {
        info.innerHTML = 'Mostrando <b>' + (start + 1) + '</b>–<b>' + Math.min(end, visible.length) +
                        '</b> de <b>' + visible.length + '</b>' +
                        (visible.length !== rows.length ? ' <span style="color:#94a3b8">(filtrados de ' + rows.length + ')</span>' : '');
      }
      renderPager(pages);
    }

    function renderPager(pages) {
      var pager = $('.q-dt-pager', footer);
      if (!pager) return;
      pager.innerHTML = '';
      if (pages <= 1) return;

      var p = state.page;
      function btn(label, targetPage, opts) {
        opts = opts || {};
        var b = document.createElement('button');
        b.type = 'button';
        b.innerHTML = label;
        if (opts.active)   b.classList.add('is-active');
        if (opts.disabled) b.disabled = true;
        b.addEventListener('click', function () {
          if (b.disabled || opts.active) return;
          state.page = targetPage; apply();
        });
        pager.appendChild(b);
      }

      btn('‹', p - 1, { disabled: p === 1 });
      // Mostrar ventana de páginas alrededor del actual
      var maxBtns = 5;
      var start = Math.max(1, p - Math.floor(maxBtns / 2));
      var end = Math.min(pages, start + maxBtns - 1);
      start = Math.max(1, end - maxBtns + 1);
      if (start > 1) {
        btn('1', 1);
        if (start > 2) btn('…', -1, { disabled: true });
      }
      for (var i = start; i <= end; i++) {
        btn(String(i), i, { active: i === p });
      }
      if (end < pages) {
        if (end < pages - 1) btn('…', -1, { disabled: true });
        btn(String(pages), pages);
      }
      btn('›', p + 1, { disabled: p === pages });
    }

    apply();
  }

  function initAll(root) {
    $$('table.q-data-table', root || document).forEach(init);
  }

  function escHtml(s) {
    return ('' + s).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  // Auto-init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(); });
  } else {
    initAll();
  }

  // Observar nuevas tablas (SPA / render tardío)
  if (typeof MutationObserver !== 'undefined') {
    var mo = new MutationObserver(function (muts) {
      muts.forEach(function (m) {
        m.addedNodes && m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if (n.matches && n.matches('table.q-data-table')) init(n);
          if (n.querySelectorAll) initAll(n);
        });
      });
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
  }

  // API pública por si alguna vista quiere inicializar manualmente
  window.QDataTable = { init: init, initAll: initAll };
})();
