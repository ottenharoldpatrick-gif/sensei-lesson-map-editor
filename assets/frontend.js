/**
 * SLME Kolommen (front) — GEEN submappen.
 * - Max 5 kolommen op breed scherm; minder op smallere schermen.
 * - Kolommen worden NIET breder dan --tile-w (CSS).
 * - Elke rij = 1 module; minder lessen => minder kolommen; overflow breekt naar volgende regel.
 */
(function(){
  function screenMaxCols(w){
    if (w <= 520)  return 1;
    if (w <= 768)  return 2;
    if (w <= 992)  return 3;
    if (w <= 1200) return 4;
    return 5;
  }

  function applyCols(){
    var wrap = document.querySelector('.slme-columns-wrap');
    if(!wrap) return;

    var maxCols = screenMaxCols(window.innerWidth);

    document.querySelectorAll('.slme-module-row').forEach(function(row){
      var lessons = parseInt(row.getAttribute('data-lessons') || '0', 10);
      var cols = Math.max(1, Math.min(maxCols, lessons || maxCols));
      var grid = row.querySelector('.slme-grid');
      if(grid){
        grid.style.setProperty('--cols', cols);
      }
    });
  }

  window.addEventListener('resize', applyCols);
  document.addEventListener('DOMContentLoaded', applyCols);
  applyCols();
})();
