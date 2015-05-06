<?php defined('_JEXEC') or die; ?>
<?php
$mapid = "map";
$svg_map_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iNjEycHgiIGhlaWdodD0iNjEycHgiIHZpZXdCb3g9IjAgMCA2MTIgNjEyIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA2MTIgNjEyOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGc+PHBhdGggZD0iTTUxNi4zMTYsMzM3LjUybDk0LjIzMywxOTMuNTgxYzMuODMyLDcuODczLTAuMTk2LDE0LjMxNC04Ljk1MiwxNC4zMTRIMTAuNDAyYy04Ljc1NiwwLTEyLjc4NS02LjQ0MS04Ljk1Mi0xNC4zMTRMOTUuNjg0LDMzNy41MmMxLjQ5OS0zLjA3OSw1LjUyOC01LjU5OSw4Ljk1Mi01LjU5OWg4MC44MDFjMi40OSwwLDUuODUzLDEuNTU5LDcuNDgzLDMuNDQyYzUuNDgyLDYuMzM1LDExLjA2NiwxMi41MjQsMTYuNjM0LDE4LjY1YzUuMjg4LDUuODE1LDEwLjYwNCwxMS43MDYsMTUuODc4LDE3LjczNWgtOTUuODkxYy0zLjQyNSwwLTcuNDU0LDIuNTE5LTguOTUyLDUuNTk5TDU4LjE2Myw1MDUuNTg5aDQ5NS42N2wtNjIuNDIxLTEyOC4yNDJjLTEuNDk4LTMuMDgtNS41MjctNS41OTktOC45NTMtNS41OTloLTk2LjEwOGM1LjI3My02LjAyOSwxMC41OTEtMTEuOTIsMTUuODc5LTE3LjczNWM1LjU4NS02LjE0NCwxMS4yLTEyLjMyMSwxNi42OTUtMTguNjU4YzEuNjI4LTEuODc4LDQuOTg0LTMuNDM0LDcuNDctMy40MzRoODAuOTcxQzUxMC43ODksMzMxLjkyMSw1MTQuODE3LDMzNC40MzksNTE2LjMxNiwzMzcuNTJ6IE00NDQuNTQxLDIwNS4yMjhjMCwxMDUuNzc2LTg4LjA1OCwxMjUuNjE0LTEyOS40NzIsMjI3LjI2NWMtMy4zNjUsOC4yNi0xNC45OTQsOC4yMTgtMTguMzYtMC4wNGMtMzcuMzU5LTkxLjY1MS0xMTIuNjM4LTExNi43ODQtMTI3LjA0MS0xOTguNDMyYy0xNC4xODEtODAuMzc5LDQxLjQ3MS0xNTkuMTE1LDEyMi43MjktMTY2Ljc5NkMzNzUuMDM3LDU5LjQxMyw0NDQuNTQxLDEyNC4yMDQsNDQ0LjU0MSwyMDUuMjI4eiBNMzc5LjExNCwyMDUuMjI4YzAtNDAuNDM2LTMyLjc3OS03My4yMTYtNzMuMjE2LTczLjIxNnMtNzMuMjE2LDMyLjc4LTczLjIxNiw3My4yMTZjMCw0MC40MzcsMzIuNzc5LDczLjIxNiw3My4yMTYsNzMuMjE2UzM3OS4xMTQsMjQ1LjY2NSwzNzkuMTE0LDIwNS4yMjh6Ii8+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjwvc3ZnPg==';
?>
<script>
  ymaps.ready(
    function() {
      jQuery(function($){
        init(
          <?php echo json_encode($this->items); ?> ,
          <?php echo $mapid; ?>
        );
      }
    );
  })
</script>
<div id="<?php echo $mapid; ?>" style="width: 100%; height: 400px" class="places-map places-map-component"></div>

<?php foreach($this->places_by_towns as $town_title => $town_places) : ?>
  <div class="row">
    <div class="span12">
      <h2><?php echo $town_title;?></h2>
    </div>
    <?php foreach($town_places as $place) : ?>
      <div class="span4 places-one-place">
        <div class="place-info-block">
          <a href="#<?php echo $mapid; ?>" data-toggle="tooltip" title="Посмотреть на карте" data-place-id="<?php echo $place->id; ?>" class="place-look-wrap">
            <img src="<?php echo $svg_map_icon; ?>" width="26px" height="26px" alt="Посмотреть на карте">
          </a>
          <?php if($place->address): ?>
          <div class="place-info-text place-address">
            <span class="place-info-label">Адрес: </span>
            <span class="place-info-value"><?php echo $place->address; ?></span>
          </div>
          <?php endif ?>
          <?php if($place->phone): ?>
          <div class="place-info-text place-phone">
            <span class="place-info-label">Телефон: </span>
            <span class="place-info-value">
              <a href="tel:<?php echo $place->phone; ?>">
                <?php echo $place->phone; ?>
              </a>
            </span>
          </div>
          <?php endif ?>
          <?php if($place->fax): ?>
          <div class="place-info-text place-fax">
            <span class="place-info-label">Факс: </span>
            <span class="place-info-value"><?php echo $place->fax; ?></span>
          </div>
          <?php endif ?>
          <?php if($place->email): ?>
          <div class="place-info-text place-email">
            <span class="place-info-label">Email: </span>
            <span class="place-info-value">
              <a href="mailto:<?php echo $place->email; ?>">
                <?php echo $place->email; ?>
              </a>
            </span>
          </div>
          <?php endif ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>