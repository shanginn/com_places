<?php defined('_JEXEC') or die; ?>
<?php
$mapid = "map";
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
<div id="<?php echo $mapid; ?>" class="places-map places-map-component"></div>

<?php foreach($this->places_by_towns as $town_title => $town_places) : ?>
  <div class="row">
    <div class="span12">
      <h2><?php echo $town_title;?></h2>
    </div>
    <?php foreach($town_places as $place) : ?>
      <div class="span4 places-one-place">
        <div class="place-info-block">
          <a href="#<?php echo $mapid; ?>" data-toggle="tooltip" title="Посмотреть на карте" data-place-id="<?php echo $place->id; ?>" class="place-look-wrap">
            <img src="/images/map.svg" width="26px" height="26px" alt="Посмотреть на карте">
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